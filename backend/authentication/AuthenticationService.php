<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use InvalidArgumentException;

final class AuthenticationService
{
    public const MODE_FILESYSTEM = 'filesystem';
    public const MODE_SQL = 'sql';
    public const MODE_HYBRID = 'hybrid';

    private string $mode;
    private StudentAuthentication $students;
    private ParentAuthentication $parents;

    public function __construct(
        string $mode,
        StudentAuthentication $students,
        ParentAuthentication $parents
    ) {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, self::supportedModes(), true)) {
            throw new InvalidArgumentException('Unsupported PORTAL_AUTH_MODE value.');
        }
        $this->mode = $mode;
        $this->students = $students;
        $this->parents = $parents;
    }

    public static function modeFromEnvironment(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return self::MODE_FILESYSTEM;
        }

        $mode = strtolower(trim($value));
        if (!in_array($mode, self::supportedModes(), true)) {
            throw new InvalidArgumentException('Unsupported PORTAL_AUTH_MODE value.');
        }
        return $mode;
    }

    /** @return array<int, string> */
    public static function supportedModes(): array
    {
        return [self::MODE_FILESYSTEM, self::MODE_SQL, self::MODE_HYBRID];
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /** @return array<string, mixed> */
    public function authenticateStudent(string $yuvaId, string $credential): array
    {
        return $this->students->authenticate($this->mode, $yuvaId, $credential);
    }

    /** @return array<string, mixed>|null */
    public function revalidateSqlStudentSession(
        string $yuvaId,
        int $userId
    ): ?array {
        if (!in_array($this->mode, [self::MODE_SQL, self::MODE_HYBRID], true)) {
            return null;
        }

        return $this->students->revalidateSqlSession($yuvaId, $userId);
    }

    /** @return array<string, mixed> */
    public function authenticateParent(
        string $email,
        string $credential,
        ?string $legacyChildYuvaId = null
    ): array {
        return $this->parents->authenticate(
            $this->mode,
            $email,
            $credential,
            $legacyChildYuvaId
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function authorizedParentChildren(int $parentUserId): array
    {
        return $this->parents->authorizedChildren($parentUserId);
    }

    public function parentCanAccessChild(int $parentUserId, string $yuvaId): bool
    {
        return $this->parents->canAccessChild($parentUserId, $yuvaId);
    }

    public function revalidateSqlParentSession(
        int $parentUserId,
        int $parentId
    ): bool {
        if (!in_array($this->mode, [self::MODE_SQL, self::MODE_HYBRID], true)) {
            return false;
        }

        return $this->parents->revalidateSqlSession($parentUserId, $parentId);
    }

    /** @return array<string, mixed>|null */
    public function authorizedSqlParentChildRecord(
        int $parentUserId,
        string $yuvaId
    ): ?array {
        if (!in_array($this->mode, [self::MODE_SQL, self::MODE_HYBRID], true)) {
            return null;
        }

        $link = $this->parents->authorizedChild($parentUserId, $yuvaId);
        if ($link === null) {
            return null;
        }

        return $this->students->revalidateSqlSession(
            $yuvaId,
            (int) ($link['student_user_id'] ?? 0)
        );
    }
}
