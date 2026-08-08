<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use Throwable;

final class StudentApprovalResolver
{
    public const APPROVED = 'Approved';
    public const PENDING = 'Pending';
    public const REJECTED = 'Rejected';
    public const UNAVAILABLE = 'Unavailable';

    private bool $databaseConfigured;

    /** @var callable(string, string): ?array<string, mixed> */
    private $sqlLookup;

    /** @var callable(string): mixed */
    private $filesystemLookup;

    public function __construct(
        bool $databaseConfigured,
        callable $sqlLookup,
        callable $filesystemLookup
    ) {
        $this->databaseConfigured = $databaseConfigured;
        $this->sqlLookup = $sqlLookup;
        $this->filesystemLookup = $filesystemLookup;
    }

    public function resolve(string $yuvaId, string $studentEmail = ''): string
    {
        if (!$this->databaseConfigured) {
            return $this->normalizeFilesystemStatus(
                ($this->filesystemLookup)($yuvaId)
            );
        }

        try {
            $row = ($this->sqlLookup)(
                $yuvaId,
                strtolower(trim($studentEmail))
            );
        } catch (Throwable) {
            return self::UNAVAILABLE;
        }

        if (!is_array($row)) {
            return self::PENDING;
        }

        return $this->normalizeSqlStatus($row['registration_status'] ?? null);
    }

    public function isApproved(string $yuvaId, string $studentEmail = ''): bool
    {
        return $this->resolve($yuvaId, $studentEmail) === self::APPROVED;
    }

    private function normalizeSqlStatus(mixed $status): string
    {
        if (!is_string($status)) {
            return self::PENDING;
        }

        return match (strtolower(trim($status))) {
            'approved' => self::APPROVED,
            'rejected' => self::REJECTED,
            default => self::PENDING,
        };
    }

    private function normalizeFilesystemStatus(mixed $status): string
    {
        if (!is_string($status)) {
            return self::PENDING;
        }

        return match (trim($status)) {
            self::APPROVED => self::APPROVED,
            self::REJECTED => self::REJECTED,
            default => self::PENDING,
        };
    }
}
