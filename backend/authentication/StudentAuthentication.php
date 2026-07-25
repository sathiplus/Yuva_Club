<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use InvalidArgumentException;

final class StudentAuthentication
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private PortalRepository $repository;
    private PortalCompatibilityAdapter $adapter;

    /** @var callable(string): ?array<string, mixed> */
    private $filesystemFinder;

    /** @var callable(array<string, mixed>, string): bool */
    private $filesystemVerifier;

    /** @var callable(string, string): bool */
    private $passwordVerifier;

    /** @var callable(string): bool */
    private $passwordNeedsRehash;

    public function __construct(
        PortalRepository $repository,
        PortalCompatibilityAdapter $adapter,
        callable $filesystemFinder,
        callable $filesystemVerifier,
        ?callable $passwordVerifier = null,
        ?callable $passwordNeedsRehash = null
    ) {
        $this->repository = $repository;
        $this->adapter = $adapter;
        $this->filesystemFinder = $filesystemFinder;
        $this->filesystemVerifier = $filesystemVerifier;
        $this->passwordVerifier = $passwordVerifier
            ?? static fn(string $password, string $hash): bool => password_verify($password, $hash);
        $this->passwordNeedsRehash = $passwordNeedsRehash
            ?? static fn(string $hash): bool => password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * @return array{
     *   authenticated: bool,
     *   source: string|null,
     *   student_id: string|null,
     *   user_id: int|null,
     *   record: array<string, mixed>|null,
     *   password_rehash_required: bool,
     *   failure_category: string|null
     * }
     */
    public function authenticate(string $mode, string $yuvaId, string $credential): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['filesystem', 'sql', 'hybrid'], true)) {
            throw new InvalidArgumentException('Unsupported portal authentication mode.');
        }

        $yuvaId = $this->normalizeYuvaId($yuvaId);
        if ($yuvaId === '' || $credential === '') {
            return $this->failure('invalid_credentials');
        }

        if ($mode === 'filesystem') {
            return $this->authenticateFilesystem($yuvaId, $credential);
        }

        $sqlStudent = $this->repository->findStudentByYuvaId($yuvaId);
        if ($mode === 'sql') {
            return $this->authenticateSql($sqlStudent, $credential);
        }

        return $this->authenticateHybrid($yuvaId, $credential, $sqlStudent);
    }

    /** @param array<string, mixed>|null $sqlStudent */
    private function authenticateHybrid(
        string $yuvaId,
        string $credential,
        ?array $sqlStudent
    ): array {
        $filesystemStudent = ($this->filesystemFinder)($yuvaId);

        if ($sqlStudent !== null && $filesystemStudent !== null) {
            $sqlRecord = $this->adapter->studentToLegacyRecord($sqlStudent);
            if (!$this->recordsAreCompatible($filesystemStudent, $sqlRecord)) {
                return $this->failure('hybrid_conflict');
            }
        }

        if ($sqlStudent !== null && $this->isActivatedSqlStudent($sqlStudent)) {
            return $this->authenticateSql($sqlStudent, $credential);
        }

        if ($sqlStudent !== null && !$this->mayUseLegacyWhileUnactivated($sqlStudent)) {
            return $this->rejectSqlCredential($credential, 'account_unavailable');
        }

        if ($filesystemStudent === null) {
            return $this->rejectSqlCredential($credential, 'invalid_credentials');
        }

        return $this->authenticateFilesystemRecord(
            $yuvaId,
            $credential,
            $filesystemStudent
        );
    }

    private function authenticateFilesystem(string $yuvaId, string $credential): array
    {
        $record = ($this->filesystemFinder)($yuvaId);
        if ($record === null) {
            return $this->failure('invalid_credentials');
        }
        return $this->authenticateFilesystemRecord($yuvaId, $credential, $record);
    }

    /** @param array<string, mixed> $record */
    private function authenticateFilesystemRecord(
        string $yuvaId,
        string $credential,
        array $record
    ): array {
        if (!(($this->filesystemVerifier)($record, $credential))) {
            return $this->failure('invalid_credentials');
        }

        return [
            'authenticated' => true,
            'source' => 'filesystem',
            'student_id' => $yuvaId,
            'user_id' => null,
            'record' => $record,
            'password_rehash_required' => false,
            'failure_category' => null,
        ];
    }

    /** @param array<string, mixed>|null $student */
    private function authenticateSql(?array $student, string $password): array
    {
        if ($student === null || !$this->isActivatedSqlStudent($student)) {
            return $this->rejectSqlCredential($password, 'account_unavailable');
        }

        $hash = (string) ($student['password_hash'] ?? '');
        if (!(($this->passwordVerifier)($password, $hash))) {
            return $this->failure('invalid_credentials');
        }

        return [
            'authenticated' => true,
            'source' => 'sql',
            'student_id' => $this->normalizeYuvaId((string) ($student['yuva_id'] ?? '')),
            'user_id' => (int) ($student['student_user_id'] ?? 0),
            'record' => $this->adapter->studentToLegacyRecord($student),
            'password_rehash_required' => ($this->passwordNeedsRehash)($hash),
            'failure_category' => null,
        ];
    }

    /** @param array<string, mixed> $student */
    private function isActivatedSqlStudent(array $student): bool
    {
        $registrationStatus = strtolower((string) ($student['registration_status'] ?? ''));
        return strtolower((string) ($student['user_role'] ?? '')) === 'student'
            && strtolower((string) ($student['user_status'] ?? '')) === 'active'
            && strtolower((string) ($student['student_approval_status'] ?? '')) === 'approved'
            && ($student['approved_at'] ?? null) !== null
            && (string) ($student['password_hash'] ?? '') !== ''
            && ($registrationStatus === '' || $registrationStatus === 'approved');
    }

    /** @param array<string, mixed> $student */
    private function mayUseLegacyWhileUnactivated(array $student): bool
    {
        $registrationStatus = strtolower((string) ($student['registration_status'] ?? ''));
        return strtolower((string) ($student['user_role'] ?? '')) === 'student'
            && strtolower((string) ($student['user_status'] ?? '')) === 'active'
            && strtolower((string) ($student['student_approval_status'] ?? '')) === 'approved'
            && ($student['approved_at'] ?? null) !== null
            && (string) ($student['password_hash'] ?? '') === ''
            && ($registrationStatus === '' || $registrationStatus === 'approved');
    }

    /**
     * @param array<string, mixed> $filesystem
     * @param array<string, mixed> $sql
     */
    private function recordsAreCompatible(array $filesystem, array $sql): bool
    {
        if (
            $this->normalizeYuvaId((string) ($filesystem['Yuva Club ID'] ?? ''))
            !== $this->normalizeYuvaId((string) ($sql['Yuva Club ID'] ?? ''))
        ) {
            return false;
        }

        foreach (['Date of Birth', 'Student Email', 'Parent Email'] as $field) {
            $left = strtolower(trim((string) ($filesystem[$field] ?? '')));
            $right = strtolower(trim((string) ($sql[$field] ?? '')));
            if ($left !== '' && $right !== '' && !hash_equals($left, $right)) {
                return false;
            }
        }

        return true;
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
    private function rejectSqlCredential(string $password, string $category): array
    {
        ($this->passwordVerifier)($password, self::DUMMY_PASSWORD_HASH);
        return $this->failure($category);
    }

    /** @return array<string, mixed> */
    private function failure(string $category): array
    {
        return [
            'authenticated' => false,
            'source' => null,
            'student_id' => null,
            'user_id' => null,
            'record' => null,
            'password_rehash_required' => false,
            'failure_category' => $category,
        ];
    }
}
