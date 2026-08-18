<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class AiReviewState
{
    public const NO_RESEARCH = 'no-research';
    public const NOT_CREATED = 'not-created';
    public const AWAITING_APPROVAL = 'awaiting-approval';
    public const APPROVED = 'approved';
    public const UNAVAILABLE = 'unavailable';

    /** @param array<string, mixed> $reviewRecord */
    public static function fromRuntime(
        bool $hasResearch,
        array $reviewRecord
    ): string {
        if (!$hasResearch) {
            return self::NO_RESEARCH;
        }
        if ($reviewRecord === []) {
            return self::NOT_CREATED;
        }
        if (in_array(($reviewRecord['status'] ?? ''), ['Applied', 'Applied by Admin'], true)) {
            return self::APPROVED;
        }
        if (
            in_array(($reviewRecord['status'] ?? ''), ['Failed', 'Needs Setup', 'Stale'], true)
            || trim((string) ($reviewRecord['error'] ?? '')) !== ''
        ) {
            return self::UNAVAILABLE;
        }
        return self::AWAITING_APPROVAL;
    }
}
