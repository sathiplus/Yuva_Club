<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class AiMentorService
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly AiPromptCatalog $prompts,
        private readonly AiReviewValidator $validator,
        private readonly AiReviewRepository $reviews
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
        $result = $this->reviewResearch($student, $selection, $research);
        $record = [
            'ok' => $result['ok'],
            'review' => $result['review'] ?? [],
            'error' => $result['error'] ?? '',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'prompt_version' => AiPromptCatalog::RESEARCH_REVIEW_VERSION,
            'topic_title' => $selection['topic_title'] ?? '',
            'topic_category' => $selection['topic_category'] ?? '',
            'status' => ($result['ok'] ?? false)
                ? 'Draft - Pending Admin Approval'
                : 'Needs Setup',
        ];
        $this->reviews->save($studentId, $record);
        return $record;
    }
}
