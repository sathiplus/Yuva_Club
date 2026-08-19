<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$version = preg_replace('/[^0-9a-f]/i', '', (string) ($_POST['version'] ?? '')) ?? '';
try {
    $repository = ai_review_repository();
    if (!$repository instanceof \YuvaClub\AI\SqlAiReviewRepository || $studentId === '' || strlen($version) !== 16) {
        throw new RuntimeException('SQL AI draft is unavailable.');
    }
    $current = $repository->find($studentId);
    $generated = $current['generated_review'] ?? [];
    if (($current['status'] ?? '') !== 'Draft' || !is_array($generated)) {
        redirect_to('admin.php?status=ai-missing#ai-mentor-reviews');
    }
    $edited = array_merge($generated, [
        'summary' => trim((string) ($_POST['summary'] ?? '')),
        'strengths' => text_lines((string) ($_POST['strengths'] ?? '')),
        'improvements' => text_lines((string) ($_POST['improvements'] ?? '')),
        'communication_skills' => trim((string) ($_POST['communication_skills'] ?? '')),
        'leadership_milestones' => trim((string) ($_POST['leadership_milestones'] ?? '')),
        'recommended_next_step' => trim((string) ($_POST['recommended_next_step'] ?? '')),
        'suggested_tokens' => filter_var($_POST['suggested_tokens'] ?? null, FILTER_VALIDATE_INT),
    ]);
    $validation = (new \YuvaClub\AI\AiReviewValidator())->validate($edited);
    if (!($validation['ok'] ?? false)) {
        redirect_to('admin.php?status=ai-edit-invalid#ai-mentor-reviews');
    }
    $adminUserId = find_sql_admin_user_id((string) $admin['email']);
    if ($adminUserId === null) {
        throw new RuntimeException('SQL admin identity is unavailable.');
    }
    if (!$repository->saveAdminEdit($studentId, $validation['review'], $version, $adminUserId)) {
        redirect_to('admin.php?status=ai-edit-conflict#ai-mentor-reviews');
    }
    if ($adminUserId !== null) {
        log_activity($adminUserId, 'ai_mentor.draft_edited', 'ai_mentor_review', (int) $current['id']);
    }
    redirect_to('admin.php?status=ai-draft-saved#ai-mentor-reviews');
} catch (Throwable $error) {
    error_log('YUVA AI draft save failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    redirect_to('admin.php?status=ai-error#ai-mentor-reviews');
}
