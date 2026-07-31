<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class AiReviewRepository
{
    /** @var callable(): array<string, array<string, mixed>> */
    private $load;

    /** @var callable(array<string, array<string, mixed>>): void */
    private $save;

    public function __construct(callable $load, callable $save)
    {
        $this->load = $load;
        $this->save = $save;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $records = ($this->load)();
        return is_array($records) ? $records : [];
    }

    /** @return array<string, mixed> */
    public function find(string $studentId): array
    {
        $record = $this->all()[$studentId] ?? [];
        return is_array($record) ? $record : [];
    }

    /** @param array<string, mixed> $record */
    public function save(string $studentId, array $record): void
    {
        $records = $this->all();
        $records[$studentId] = $record;
        ($this->save)($records);
    }
}
