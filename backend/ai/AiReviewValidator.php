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
        'recommended_next_step',
        'admin_notes',
    ];

    private const MAX_TEXT_LENGTH = 2000;

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
            if ($normalized[$field] === '' || mb_strlen($normalized[$field]) > self::MAX_TEXT_LENGTH) {
                return $this->failure('AI response contained invalid feedback length.');
            }
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
            if ($items === [] || count($items) > 6) {
                return $this->failure('AI response contained no feedback items.');
            }
            foreach ($items as $item) {
                if (mb_strlen($item) > 600) {
                    return $this->failure('AI response contained an oversized feedback item.');
                }
            }
            $normalized[$field] = $items;
        }

        $expected = $normalized['research_quality']
            + $normalized['presentation_structure']
            + $normalized['topic_understanding']
            + $normalized['discussion_questions']
            + $normalized['leadership_lesson']
            + $normalized['effort_and_readiness'];
        if ($normalized['total_points'] !== $expected) {
            return $this->failure('AI response total did not match its category scores.');
        }

        $allowed = array_merge(array_keys(self::SCORES), self::TEXT_FIELDS, ['strengths', 'improvements']);
        if (array_diff(array_keys($review), $allowed) !== []) {
            return $this->failure('AI response contained unexpected fields.');
        }

        return ['ok' => true, 'review' => $normalized];
    }

    /** @return array{ok: false, error: string} */
    private function failure(string $message): array
    {
        return ['ok' => false, 'error' => $message];
    }
}
