<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class AiReviewValidator
{
    /** @var array<string, int> */
    private const SCORES = [
        'research_quality' => 20,
        'presentation_structure' => 20,
        'topic_understanding' => 20,
        'discussion_questions' => 15,
        'leadership_lesson' => 15,
        'effort_and_readiness' => 10,
        'total_points' => 100,
        'suggested_tokens' => 4,
    ];

    /** @var array<int, string> */
    private const TEXT_FIELDS = [
        'summary',
        'communication_skills',
        'leadership_milestones',
        'admin_notes',
    ];

    /**
     * @param array<string, mixed> $review
     * @return array{ok: bool, review?: array<string, mixed>, error?: string}
     */
    public function validate(array $review): array
    {
        $normalized = $review;
        foreach (self::SCORES as $field => $maximum) {
            if (!array_key_exists($field, $review) || !is_numeric($review[$field])) {
                return $this->failure('AI response was missing a required score.');
            }
            $score = (int) $review[$field];
            if ($score < 0 || $score > $maximum) {
                return $this->failure('AI response contained an invalid score.');
            }
            $normalized[$field] = $score;
        }

        foreach (self::TEXT_FIELDS as $field) {
            if (!isset($review[$field]) || !is_string($review[$field])) {
                return $this->failure('AI response was missing required feedback.');
            }
            $normalized[$field] = trim($review[$field]);
        }

        foreach (['strengths', 'improvements'] as $field) {
            if (!isset($review[$field]) || !is_array($review[$field])) {
                return $this->failure('AI response was missing required feedback items.');
            }
            $items = [];
            foreach ($review[$field] as $item) {
                if (!is_string($item) || trim($item) === '') {
                    return $this->failure('AI response contained an invalid feedback item.');
                }
                $items[] = trim($item);
            }
            if ($items === []) {
                return $this->failure('AI response contained no feedback items.');
            }
            $normalized[$field] = array_slice($items, 0, 10);
        }

        return ['ok' => true, 'review' => $normalized];
    }

    /** @return array{ok: false, error: string} */
    private function failure(string $message): array
    {
        return ['ok' => false, 'error' => $message];
    }
}
