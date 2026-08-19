<?php
declare(strict_types=1);

namespace YuvaClub\AI;

interface AiReviewStore
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed> */
    public function find(string $studentId): array;

    /** @param array<string, mixed> $record */
    public function save(string $studentId, array $record): void;
}
