<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\LoginThrottle;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;
use YuvaClub\Authentication\StudentLoginWorkflow;

$expectedRoot = realpath(__DIR__ . '/../..');
$isolatedRoot = realpath((string) getenv('YUVA_TEST_ISOLATED_ROOT'));
if ($expectedRoot === false || $isolatedRoot === false || $expectedRoot !== $isolatedRoot) {
    throw new RuntimeException(
        'Filesystem approval regression must run through the isolated validation runner.'
    );
}

require_once $expectedRoot . '/portal-lib.php';

function filesystem_approval_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @param array<string, mixed> $record */
function filesystem_approval_write_record(string $studentId, array $record): void
{
    write_json_file(portal_records_file(), [$studentId => $record]);
}

/** @return array<string, mixed> */
function filesystem_approval_sql_row(array $overrides = []): array
{
    return array_merge([
        'student_id' => 301,
        'student_user_id' => 201,
        'yuva_id' => 'YC2026001',
        'student_first_name' => 'Synthetic',
        'student_last_name' => 'Approval',
        'preferred_name' => 'Synthetic',
        'date_of_birth' => '2010-01-02',
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'student_email' => 'synthetic.approval@example.test',
        'password_hash' => 'student-hash',
        'user_role' => 'student',
        'user_status' => 'active',
        'registration_status' => 'approved',
        'program_name' => 'School Yuva',
    ], $overrides);
}

/**
 * @param array<string, mixed>|null $sqlRow
 */
function filesystem_approval_service(
    string $mode,
    ?array &$sqlRow
): AuthenticationService {
    $repository = new PortalRepository(
        static fn(string $sql, array $parameters): ?array => $sqlRow,
        static fn(string $sql, array $parameters): array => []
    );
    $adapter = new PortalCompatibilityAdapter();
    $students = new StudentAuthentication(
        $repository,
        $adapter,
        static fn(string $yuvaId): ?array =>
            find_authenticatable_filesystem_student($yuvaId),
        static fn(array $record, string $credential): bool =>
            hash_equals((string) ($record['Date of Birth'] ?? ''), $credential),
        static fn(string $password, string $hash): bool =>
            $password === 'correct-password' && $hash === 'student-hash',
        static fn(string $hash): bool => false
    );
    $parents = new ParentAuthentication(
        $repository,
        static fn(string $yuvaId): ?array => find_student($yuvaId),
        static fn(string $email): array => [],
        static fn(array $record, string $credential): bool => false
    );
    return new AuthenticationService($mode, $students, $parents);
}

$studentId = 'YC2026001';
$headers = registration_headers();
$student = array_fill_keys($headers, '');
$student['Yuva Club ID'] = $studentId;
$student['Student First Name'] = 'Synthetic';
$student['Student Last Name'] = 'Approval';
$student['Date of Birth'] = '2010-01-02';
$student['Student Email'] = 'synthetic.approval@example.test';
$student['Parent Email'] = 'synthetic.parent@example.test';

$handle = fopen(registration_csv_path(), 'wb');
if ($handle === false) {
    throw new RuntimeException('Unable to create isolated registration fixture.');
}
fputcsv($handle, $headers);
fputcsv($handle, array_map(
    static fn(string $header): string => (string) ($student[$header] ?? ''),
    $headers
));
fclose($handle);

write_json_file(portal_records_file(), []);
filesystem_approval_assert(
    find_authenticatable_filesystem_student($studentId) === null,
    'A newly registered student without a student record must be rejected.'
);

$rejectedApprovalRecords = [
    'missing approval field' => ['attendance' => '0'],
    'blank approval value' => ['approved' => ''],
    'malformed approval value' => ['approved' => ['Approved']],
    'unknown approval value' => ['approved' => 'Unknown'],
    'Pending' => ['approved' => 'Pending'],
    'Waitlist' => ['approved' => 'Waitlist'],
    'Inactive' => ['approved' => 'Inactive'],
];
foreach ($rejectedApprovalRecords as $label => $record) {
    filesystem_approval_write_record($studentId, $record);
    filesystem_approval_assert(
        find_authenticatable_filesystem_student($studentId) === null,
        $label . ' must fail closed.'
    );
}

filesystem_approval_write_record($studentId, ['approved' => 'Approved']);
$approvedStudent = find_authenticatable_filesystem_student($studentId);
filesystem_approval_assert(
    is_array($approvedStudent)
    && ($approvedStudent['Yuva Club ID'] ?? null) === $studentId,
    'Exact Approved state must preserve the filesystem student record.'
);

$sqlRow = null;
$filesystemService = filesystem_approval_service('filesystem', $sqlRow);
$throttlePath = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'yuva-filesystem-approval-'
    . bin2hex(random_bytes(6))
    . '.json';
$regenerations = 0;
$workflow = new StudentLoginWorkflow(
    $filesystemService,
    new LoginThrottle($throttlePath),
    static fn(?string $token): bool => $token === 'valid-csrf',
    static function () use (&$regenerations): void {
        $regenerations++;
    }
);
$session = [];
$success = $workflow->attempt(
    $session,
    $studentId,
    '2010-01-02',
    'valid-csrf',
    '192.0.2.0/24'
);
filesystem_approval_assert(
    $success === ['authenticated' => true]
    && $session === ['student_id' => $studentId]
    && $regenerations === 1,
    'Approved filesystem login must preserve the legacy session and regenerate it.'
);

$wrongDobSession = [];
filesystem_approval_assert(
    $workflow->attempt(
        $wrongDobSession,
        $studentId,
        'wrong-dob',
        'valid-csrf',
        '198.51.100.0/24'
    ) === ['authenticated' => false]
    && $wrongDobSession === [],
    'Wrong DOB must retain the generic rejection without session state.'
);

filesystem_approval_write_record($studentId, ['approved' => 'Pending']);
filesystem_approval_assert(
    $filesystemService->authenticateStudent(
        $studentId,
        '2010-01-02'
    )['authenticated'] === false,
    'Pending filesystem students must not establish a new session.'
);
filesystem_approval_assert(
    find_authenticatable_filesystem_student($studentId) === null,
    'A previously approved session must fail revalidation after becoming Pending.'
);

filesystem_approval_write_record($studentId, ['approved' => 'Inactive']);
filesystem_approval_assert(
    find_authenticatable_filesystem_student($studentId) === null,
    'A previously approved session must fail revalidation after becoming Inactive.'
);

$_SESSION = [
    'student_id' => $studentId,
    'student_auth_source' => 'sql',
    'student_user_id' => 201,
    'portal_role' => 'student',
    'unrelated_key' => 'preserved',
];
clear_student_authentication_session();
filesystem_approval_assert(
    $_SESSION === ['unrelated_key' => 'preserved'],
    'Existing student authentication keys must be cleared without unrelated session loss.'
);

$unactivatedSqlRow = filesystem_approval_sql_row(['password_hash' => null]);
$hybridService = filesystem_approval_service('hybrid', $unactivatedSqlRow);
filesystem_approval_assert(
    $hybridService->authenticateStudent(
        $studentId,
        '2010-01-02'
    )['authenticated'] === false,
    'Hybrid legacy fallback must reject a non-approved filesystem student.'
);

filesystem_approval_write_record($studentId, ['approved' => 'Approved']);
filesystem_approval_assert(
    $hybridService->authenticateStudent(
        $studentId,
        '2010-01-02'
    )['source'] === 'filesystem',
    'Hybrid legacy fallback may authenticate an approved filesystem student.'
);

$activatedSqlRow = filesystem_approval_sql_row();
$activatedHybridService = filesystem_approval_service('hybrid', $activatedSqlRow);
filesystem_approval_assert(
    $activatedHybridService->authenticateStudent(
        $studentId,
        '2010-01-02'
    )['authenticated'] === false,
    'Activated SQL password failure must not downgrade to filesystem DOB.'
);

$portalLibSource = file_get_contents($expectedRoot . '/portal-lib.php');
filesystem_approval_assert(
    is_string($portalLibSource)
    && str_contains(
        $portalLibSource,
        'find_authenticatable_filesystem_student($yuvaId)'
    )
    && str_contains(
        $portalLibSource,
        '$student = find_authenticatable_filesystem_student($studentId);'
    )
    && str_contains($portalLibSource, 'clear_student_authentication_session();')
    && str_contains($portalLibSource, "redirect_to('portal-login.php?status=error');"),
    'Login wiring and protected-page revalidation must use the approval helper and generic failure path.'
);

if (is_file($throttlePath)) {
    unlink($throttlePath);
}

fwrite(STDOUT, "PASS filesystem approval states fail closed\n");
fwrite(STDOUT, "PASS approved filesystem login and generic DOB rejection\n");
fwrite(STDOUT, "PASS filesystem protected-session approval revalidation contract\n");
fwrite(STDOUT, "PASS hybrid approved fallback and SQL no-downgrade contract\n");
