<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class AiMentorService
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly AiPromptCatalog $prompts,
        private readonly AiReviewValidator $validator,
        private readonly AiReviewStore $reviews
    ) {
    }

    /**
     * @param array<string, mixed> $student
     * @param array<string, mixed> $selection
     * @param array<string, mixed> $research
     * @return array{ok: bool, review?: array<string, mixed>, error?: string}
     */
    public function reviewResearch(
        array $student,
        array $selection,
        array $research
    ): array {
        $result = $this->provider->generateStructuredReview(
            $this->prompts->researchReview($student, $selection, $research)
        );
        if (!($result['ok'] ?? false) || !is_array($result['output'] ?? null)) {
            return [
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'AI review failed.'),
            ];
        }
        return $this->validator->validate($result['output']);
    }

    /**
     * @param array<string, mixed> $student
     * @param array<string, mixed> $selection
     * @param array<string, mixed> $research
     * @return array<string, mixed>
     */
    public function createDraft(
        string $studentId,
        array $student,
        array $selection,
        array $research
    ): array {
        $sourceHash = self::sourceRevisionHash($selection, $research);
        $provider = method_exists($this->provider, 'providerName')
            ? $this->provider->providerName()
            : 'test';
        $model = method_exists($this->provider, 'modelName')
            ? $this->provider->modelName()
            : 'test';
        $this->reviews->save($studentId, [
            'ok' => false,
            'review' => [],
            'source_revision_hash' => $sourceHash,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => AiPromptCatalog::RESEARCH_REVIEW_VERSION,
            'status' => 'Processing',
        ]);
        $processing = $this->reviews->find($studentId);

        try {
            $result = $this->reviewResearch($student, $selection, $research);
        } catch (\Throwable) {
            $result = [
                'ok' => false,
                'error' => 'AI review could not be generated.',
            ];
        }
        $record = [
            'id' => $processing['id'] ?? 0,
            'ok' => $result['ok'],
            'review' => $result['review'] ?? [],
            'error' => $result['error'] ?? '',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'prompt_version' => AiPromptCatalog::RESEARCH_REVIEW_VERSION,
            'source_revision_hash' => $sourceHash,
            'provider' => $provider,
            'model' => $model,
            'topic_title' => $selection['topic_title'] ?? '',
            'topic_category' => $selection['topic_category'] ?? '',
            'status' => ($result['ok'] ?? false)
                ? 'Draft'
                : 'Failed',
            'error_code' => ($result['ok'] ?? false) ? null : 'provider_or_validation_failure',
            'error_category' => ($result['ok'] ?? false) ? null : 'AI review could not be generated.',
        ];
        $this->reviews->save($studentId, $record);
        return $record;
    }

    public static function sourceRevisionHash(array $selection, array $research): string
    {
        $source = [
            'topic_category' => (string) ($selection['topic_category'] ?? ''),
            'topic_title' => (string) ($selection['topic_title'] ?? ''),
            'research_notes' => (string) ($research['research_notes'] ?? ''),
            'sources_used' => (string) ($research['sources_used'] ?? ''),
            'presentation_outline' => (string) ($research['presentation_outline'] ?? ''),
            'prepared_questions' => (string) ($research['prepared_questions'] ?? ''),
        ];
        return hash('sha256', json_encode($source, JSON_UNESCAPED_SLASHES) ?: '');
    }
}
