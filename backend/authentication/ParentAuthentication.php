<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use InvalidArgumentException;

final class ParentAuthentication
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private PortalRepository $repository;

    /** @var callable(string): ?array<string, mixed> */
    private $filesystemStudentFinder;

    /** @var callable(string): array<int, array<string, mixed>> */
    private $filesystemParentIdentityFinder;

    /** @var callable(array<string, mixed>, string): bool */
    private $filesystemVerifier;

    /** @var callable(string, string): bool */
    private $passwordVerifier;

    /** @var callable(string): bool */
    private $passwordNeedsRehash;

    public function __construct(
        PortalRepository $repository,
        callable $filesystemStudentFinder,
        callable $filesystemParentIdentityFinder,
        callable $filesystemVerifier,
        ?callable $passwordVerifier = null,
        ?callable $passwordNeedsRehash = null
    ) {
        $this->repository = $repository;
        $this->filesystemStudentFinder = $filesystemStudentFinder;
        $this->filesystemParentIdentityFinder = $filesystemParentIdentityFinder;
        $this->filesystemVerifier = $filesystemVerifier;
        $this->passwordVerifier = $passwordVerifier
            ?? static fn(string $password, string $hash): bool => password_verify($password, $hash);
        $this->passwordNeedsRehash = $passwordNeedsRehash
            ?? static fn(string $hash): bool => password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * The legacy child ID is used only by filesystem compatibility mode.
     *
     * @return array<string, mixed>
     */
    public function authenticate(
        string $mode,
        string $email,
        string $credential,
        ?string $legacyChildYuvaId = null
    ): array {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['filesystem', 'sql', 'hybrid'], true)) {
            throw new InvalidArgumentException('Unsupported portal authentication mode.');
        }

        $email = strtolower(trim($email));
        if ($email === '' || $credential === '') {
            return $this->failure('invalid_credentials');
        }

        if ($mode === 'filesystem') {
            return $this->authenticateFilesystem($email, $credential, $legacyChildYuvaId);
        }

        $parent = $this->repository->findParentByEmail($email);
        if ($mode === 'sql') {
            return $this->authenticateSql($parent, $credential);
        }

        $filesystemIdentities = ($this->filesystemParentIdentityFinder)($email);
        if (!$this->hybridIdentitiesAreCompatible($parent, $filesystemIdentities, $email)) {
            return $this->rejectSqlCredential($credential, 'hybrid_conflict');
        }

        $legacyYuvaId = $this->normalizeYuvaId((string) $legacyChildYuvaId);

        if ($parent !== null && $this->isActivatedParent($parent)) {
            return $this->authenticateSql($parent, $credential);
        }

        if ($parent !== null && !$this->mayUseLegacyWhileUnactivated($parent)) {
            return $this->rejectSqlCredential($credential, 'account_unavailable');
        }

        $result = $this->authenticateFilesystem(
            $email,
            $credential,
            $legacyYuvaId
        );
        if ($parent === null && $result['authenticated'] !== true) {
            ($this->passwordVerifier)($credential, self::DUMMY_PASSWORD_HASH);
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function listAuthorizedChildren(int $parentUserId): array
    {
        $authorized = [];
        foreach ($this->repository->findAuthorizedChildren($parentUserId) as $row) {
            if ($this->isAuthorizedChildRow($row)) {
                $authorized[] = $this->withoutParentAuthorizationFields($row);
            }
        }
        return $authorized;
    }

    /** @return array<int, array<string, mixed>> */
    public function authorizedChildren(int $parentUserId): array
    {
        return $this->listAuthorizedChildren($parentUserId);
    }

    public function canAccessChild(int $parentUserId, string $yuvaId): bool
    {
        $row = $this->repository->findParentChildLink(
            $parentUserId,
            $this->normalizeYuvaId($yuvaId)
        );
        return $row !== null && $this->isAuthorizedChildRow($row);
    }

    /** @param array<string, mixed>|null $parent */
    private function authenticateSql(?array $parent, string $password): array
    {
        if ($parent === null || !$this->isActivatedParent($parent)) {
            return $this->rejectSqlCredential($password, 'account_unavailable');
        }

        $hash = (string) ($parent['password_hash'] ?? '');
        if (!(($this->passwordVerifier)($password, $hash))) {
            return $this->failure('invalid_credentials');
        }

        $parentUserId = (int) ($parent['parent_user_id'] ?? 0);
        return [
            'authenticated' => true,
            'source' => 'sql',
            'parent_user_id' => $parentUserId,
            'parent_id' => (int) ($parent['parent_id'] ?? 0),
            'parent_student_id' => null,
            'children' => $this->listAuthorizedChildren($parentUserId),
            'password_rehash_required' => ($this->passwordNeedsRehash)($hash),
            'failure_category' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function authenticateFilesystem(
        string $email,
        string $credential,
        ?string $legacyChildYuvaId
    ): array {
        $yuvaId = $this->normalizeYuvaId((string) $legacyChildYuvaId);
        if ($yuvaId === '') {
            return $this->failure('invalid_credentials');
        }

        $record = ($this->filesystemStudentFinder)($yuvaId);
        if (
            $record === null
            || strtolower(trim((string) ($record['Parent Email'] ?? ''))) !== $email
            || !(($this->filesystemVerifier)($record, $credential))
        ) {
            return $this->failure('invalid_credentials');
        }

        return [
            'authenticated' => true,
            'source' => 'filesystem',
            'parent_user_id' => null,
            'parent_id' => null,
            'parent_student_id' => $yuvaId,
            'children' => [$record],
            'password_rehash_required' => false,
            'failure_category' => null,
        ];
    }

    /** @param array<string, mixed> $parent */
    private function isActivatedParent(array $parent): bool
    {
        return strtolower((string) ($parent['user_role'] ?? '')) === 'parent'
            && strtolower((string) ($parent['user_status'] ?? '')) === 'active'
            && ($parent['email_verified_at'] ?? null) !== null
            && (string) ($parent['password_hash'] ?? '') !== '';
    }

    /** @param array<string, mixed> $parent */
    private function mayUseLegacyWhileUnactivated(array $parent): bool
    {
        return strtolower((string) ($parent['user_role'] ?? '')) === 'parent'
            && strtolower((string) ($parent['user_status'] ?? '')) === 'active'
            && (string) ($parent['password_hash'] ?? '') === '';
    }

    /** @param array<string, mixed> $row */
    private function isAuthorizedChildRow(array $row): bool
    {
        return strtolower((string) ($row['parent_user_role'] ?? '')) === 'parent'
            && strtolower((string) ($row['parent_user_status'] ?? '')) === 'active'
            && ($row['parent_email_verified_at'] ?? null) !== null
            && (string) ($row['parent_password_hash'] ?? '') !== ''
            && strtolower((string) ($row['consent_status'] ?? '')) === 'granted'
            && strtolower((string) ($row['student_approval_status'] ?? '')) === 'approved'
            && ($row['approved_at'] ?? null) !== null
            && strtolower((string) ($row['student_user_role'] ?? '')) === 'student'
            && strtolower((string) ($row['student_user_status'] ?? '')) === 'active';
    }

    /**
     * @param array<string, mixed>|null $sqlParent
     * @param array<int, array<string, mixed>> $filesystemIdentities
     */
    private function hybridIdentitiesAreCompatible(
        ?array $sqlParent,
        array $filesystemIdentities,
        string $normalizedEmail
    ): bool {
        foreach ($filesystemIdentities as $identity) {
            $filesystemEmail = strtolower(trim(
                (string) ($identity['Parent Email'] ?? '')
            ));
            if ($filesystemEmail === '' || !hash_equals($normalizedEmail, $filesystemEmail)) {
                return false;
            }
        }

        if ($sqlParent === null || $filesystemIdentities === []) {
            return true;
        }

        $sqlEmail = strtolower(trim((string) ($sqlParent['email'] ?? '')));
        if ($sqlEmail === '' || !hash_equals($normalizedEmail, $sqlEmail)) {
            return false;
        }

        $sqlName = $this->normalizeIdentityName(
            (string) ($sqlParent['first_name'] ?? '')
            . ' '
            . (string) ($sqlParent['last_name'] ?? '')
        );
        foreach ($filesystemIdentities as $identity) {
            $filesystemName = $this->normalizeIdentityName(
                (string) ($identity['Parent/Guardian Name'] ?? '')
            );
            if (
                $sqlName !== ''
                && $filesystemName !== ''
                && !hash_equals($sqlName, $filesystemName)
            ) {
                return false;
            }
        }

        return true;
    }

    private function normalizeIdentityName(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($value)));
        return is_string($normalized) ? $normalized : '';
    }

    /** @param array<string, mixed> $row */
    private function withoutParentAuthorizationFields(array $row): array
    {
        unset(
            $row['parent_user_role'],
            $row['parent_user_status'],
            $row['parent_email_verified_at'],
            $row['parent_password_hash']
        );
        return $row;
    }

    /** @return array<string, mixed> */
    private function rejectSqlCredential(string $password, string $category): array
    {
        ($this->passwordVerifier)($password, self::DUMMY_PASSWORD_HASH);
        return $this->failure($category);
    }

    private function normalizeYuvaId(string $value): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^YC-?(\d{4})-?(\d+)$/', $value, $matches) === 1) {
            return sprintf('YC%s%03d', $matches[1], (int) $matches[2]);
        }
        return str_replace('-', '', $value);
    }

    /** @return array<string, mixed> */
    private function failure(string $category): array
    {
        return [
            'authenticated' => false,
            'source' => null,
            'parent_user_id' => null,
            'parent_id' => null,
            'parent_student_id' => null,
            'children' => [],
            'password_rehash_required' => false,
            'failure_category' => $category,
        ];
    }
}
