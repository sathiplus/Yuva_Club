<?php
declare(strict_types=1);

namespace YuvaClub\AI;

use PDO;
use RuntimeException;

final class SqlAiReviewRepository implements AiReviewStore
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo->query(
            "SELECT r.* FROM dbo.ai_mentor_reviews r
             INNER JOIN (SELECT yuva_id, MAX(id) id FROM dbo.ai_mentor_reviews GROUP BY yuva_id) latest ON latest.id = r.id"
        )->fetchAll(PDO::FETCH_ASSOC);
        $records = [];
        foreach ($rows as $row) {
            $records[(string) $row['yuva_id']] = $this->hydrate($row);
        }
        return $records;
    }

    public function find(string $studentId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT TOP 1 * FROM dbo.ai_mentor_reviews WHERE yuva_id = :yuva_id ORDER BY id DESC'
        );
        $statement->execute(['yuva_id' => $studentId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : [];
    }

    public function save(string $studentId, array $record): void
    {
        $status = (string) ($record['status'] ?? 'Failed');
        $existingId = (int) ($record['id'] ?? 0);
        $review = $record['review'] ?? [];
        if ($existingId > 0) {
            $statement = $this->pdo->prepare(
                'UPDATE dbo.ai_mentor_reviews SET status = :status, generated_result = :result,
                 recommended_next_step = :next_step, error_code = :error_code, error_category = :error_category,
                 generated_at = CASE WHEN :is_draft = 1 THEN SYSUTCDATETIME() ELSE generated_at END,
                 updated_at = SYSUTCDATETIME() WHERE id = :id'
            );
            $statement->execute($this->writeValues($existingId, $status, $review, $record));
            return;
        }

        $student = $this->pdo->prepare('SELECT id FROM dbo.students WHERE yuva_id = :yuva_id');
        $student->execute(['yuva_id' => $studentId]);
        $studentKey = $student->fetchColumn();
        if ($studentKey === false) {
            throw new RuntimeException('Student identity is not available for AI review persistence.');
        }
        $statement = $this->pdo->prepare(
            "INSERT INTO dbo.ai_mentor_reviews
             (student_id, yuva_id, source_submission_reference, source_revision_hash, provider, model, prompt_version, status, generated_result,
              recommended_next_step, error_code, error_category, generated_at)
             OUTPUT INSERTED.id
             VALUES (:student_id, :yuva_id, :source_reference, :source_hash, :provider, :model, :prompt_version, :status, :result,
              :next_step, :error_code, :error_category, CASE WHEN :is_draft = 1 THEN SYSUTCDATETIME() ELSE NULL END)"
        );
        $statement->execute([
            'student_id' => (int) $studentKey,
            'yuva_id' => $studentId,
            'source_hash' => (string) ($record['source_revision_hash'] ?? str_repeat('0', 64)),
            'source_reference' => $studentId . ':' . (string) ($record['source_revision_hash'] ?? str_repeat('0', 64)),
            'provider' => (string) ($record['provider'] ?? 'openai'),
            'model' => (string) ($record['model'] ?? 'configured'),
            'prompt_version' => (string) ($record['prompt_version'] ?? ''),
            'status' => $status,
            'is_draft' => $status === 'Draft' ? 1 : 0,
            'result' => $review === [] ? null : json_encode($review, JSON_UNESCAPED_SLASHES),
            'next_step' => $review['recommended_next_step'] ?? null,
            'error_code' => $record['error_code'] ?? null,
            'error_category' => $record['error_category'] ?? null,
        ]);
        $record['id'] = (int) $statement->fetchColumn();
    }

    public function markLatestStale(string $studentId, string $reason): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE dbo.ai_mentor_reviews SET status = N'Stale', error_code = N'source_changed',
             error_category = :reason, updated_at = SYSUTCDATETIME()
             WHERE id = (SELECT TOP 1 id FROM dbo.ai_mentor_reviews WHERE yuva_id = :yuva_id ORDER BY id DESC)
             AND status IN (N'Processing', N'Draft')"
        );
        $statement->execute(['yuva_id' => $studentId, 'reason' => substr($reason, 0, 80)]);
        return $statement->rowCount() === 1;
    }

    /** @param array<string, mixed> $review */
    public function saveAdminEdit(
        string $studentId,
        array $review,
        string $version,
        ?int $adminUserId
    ): bool {
        $statement = $this->pdo->prepare(
            "UPDATE dbo.ai_mentor_reviews
             SET admin_edited_result = :result, recommended_next_step = :next_step,
                 reviewed_by = :reviewed_by, reviewed_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME()
             WHERE id = (SELECT TOP 1 id FROM dbo.ai_mentor_reviews WHERE yuva_id = :yuva_id ORDER BY id DESC)
               AND status = N'Draft' AND row_version = CONVERT(BINARY(8), :version, 2)"
        );
        $statement->execute([
            'result' => json_encode($review, JSON_UNESCAPED_SLASHES),
            'next_step' => $review['recommended_next_step'],
            'reviewed_by' => $adminUserId,
            'yuva_id' => $studentId,
            'version' => $version,
        ]);
        return $statement->rowCount() === 1;
    }

    public function apply(
        string $studentId,
        string $currentSourceHash,
        ?int $adminUserId
    ): string {
        return \Database::transaction(function (PDO $pdo) use ($studentId, $currentSourceHash, $adminUserId): string {
            \db_acquire_application_lock($pdo, 'ai-mentor-apply:' . $studentId, 0);
            $query = $pdo->prepare(
                "SELECT TOP 1 * FROM dbo.ai_mentor_reviews WITH (UPDLOCK, HOLDLOCK)
                 WHERE yuva_id = :yuva_id ORDER BY id DESC"
            );
            $query->execute(['yuva_id' => $studentId]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return 'missing';
            }
            $reviewId = (int) $row['id'];
            $this->logActivity($pdo, $adminUserId, 'ai_mentor.apply_attempted', $reviewId);
            if ((string) $row['status'] === 'Applied') {
                return 'already-applied';
            }
            if ((string) $row['status'] !== 'Draft') {
                return (string) $row['status'] === 'Stale' ? 'stale' : 'missing';
            }
            if (!hash_equals((string) $row['source_revision_hash'], $currentSourceHash)) {
                $stale = $pdo->prepare(
                    "UPDATE dbo.ai_mentor_reviews SET status = N'Stale', error_code = N'source_changed',
                     error_category = N'Research changed after generation.', updated_at = SYSUTCDATETIME() WHERE id = :id"
                );
                $stale->execute(['id' => $reviewId]);
                $this->logActivity($pdo, $adminUserId, 'ai_mentor.stale_rejected', $reviewId);
                return 'stale';
            }

            $review = json_decode((string) ($row['admin_edited_result'] ?: $row['generated_result']), true);
            if (!is_array($review)) {
                return 'missing';
            }
            $tokens = max(0, min(4, (int) ($review['suggested_tokens'] ?? 0)));
            $ledger = $pdo->prepare(
                "INSERT INTO dbo.student_points (student_id, points, tokens, reason, source_type, source_id, awarded_by)
                 SELECT :student_id, 0, :tokens, N'Applied AI Mentor review', N'ai_mentor_review', :source_id, :awarded_by
                 WHERE NOT EXISTS (SELECT 1 FROM dbo.student_points WITH (UPDLOCK, HOLDLOCK)
                   WHERE student_id = :check_student_id AND source_type = N'ai_mentor_review' AND source_id = :check_source_id)"
            );
            $ledger->execute([
                'student_id' => (int) $row['student_id'],
                'tokens' => $tokens,
                'source_id' => $reviewId,
                'check_student_id' => (int) $row['student_id'],
                'check_source_id' => $reviewId,
                'awarded_by' => $adminUserId,
            ]);
            $apply = $pdo->prepare(
                "UPDATE dbo.ai_mentor_reviews SET status = N'Applied', reviewed_by = COALESCE(reviewed_by, :reviewed_by),
                 reviewed_at = COALESCE(reviewed_at, SYSUTCDATETIME()), applied_at = SYSUTCDATETIME(),
                 apply_reference = COALESCE(apply_reference, NEWID()), updated_at = SYSUTCDATETIME()
                 WHERE id = :id AND status = N'Draft'"
            );
            $apply->execute(['reviewed_by' => $adminUserId, 'id' => $reviewId]);
            if ($apply->rowCount() !== 1) {
                throw new RuntimeException('AI review application lost its concurrency boundary.');
            }
            $this->logActivity($pdo, $adminUserId, 'ai_mentor.apply_succeeded', $reviewId, ['tokens' => $tokens]);
            return 'applied';
        }, 'SERIALIZABLE', true);
    }

    private function logActivity(PDO $pdo, ?int $actorUserId, string $action, int $reviewId, array $metadata = []): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO dbo.activity_logs (actor_user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
             VALUES (:actor, :action, N\'ai_mentor_review\', :entity_id, :metadata, :ip, :agent)'
        );
        $statement->execute([
            'actor' => $actorUserId,
            'action' => $action,
            'entity_id' => $reviewId,
            'metadata' => $metadata === [] ? null : json_encode($metadata),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
        ]);
    }

    /** @return array<string, mixed> */
    private function hydrate(array $row): array
    {
        $generated = json_decode((string) ($row['generated_result'] ?? ''), true);
        $edited = json_decode((string) ($row['admin_edited_result'] ?? ''), true);
        $effective = is_array($edited) ? $edited : (is_array($generated) ? $generated : []);
        return [
            'id' => (int) $row['id'],
            'review_id' => (string) $row['review_key'],
            'student_id' => (string) $row['yuva_id'],
            'source_revision_hash' => (string) $row['source_revision_hash'],
            'provider' => (string) $row['provider'],
            'model' => (string) $row['model'],
            'prompt_version' => (string) $row['prompt_version'],
            'status' => (string) $row['status'],
            'review' => $effective,
            'generated_review' => is_array($generated) ? $generated : [],
            'admin_edited_review' => is_array($edited) ? $edited : [],
            'error' => (string) ($row['error_category'] ?? ''),
            'reviewed_at' => (string) ($row['generated_at'] ?? $row['created_at']),
            'applied_at' => (string) ($row['applied_at'] ?? ''),
            'version' => bin2hex((string) $row['row_version']),
        ];
    }

    /** @return array<string, mixed> */
    private function writeValues(int $id, string $status, array $review, array $record): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'is_draft' => $status === 'Draft' ? 1 : 0,
            'result' => $review === [] ? null : json_encode($review, JSON_UNESCAPED_SLASHES),
            'next_step' => $review['recommended_next_step'] ?? null,
            'error_code' => $record['error_code'] ?? null,
            'error_category' => $record['error_category'] ?? null,
        ];
    }
}
