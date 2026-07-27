<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\LoginThrottle;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;
use YuvaClub\Authentication\StudentLoginWorkflow;

require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/ParentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/AuthenticationService.php';
require_once __DIR__ . '/../../backend/authentication/LoginThrottle.php';
require_once __DIR__ . '/../../backend/authentication/StudentLoginWorkflow.php';

function student_login_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function student_login_sql_row(array $overrides = []): array
{
    return array_merge([
        'student_id' => 301,
        'student_user_id' => 201,
        'yuva_id' => 'YC2026001',
        'student_first_name' => 'Synthetic',
        'student_last_name' => 'Student',
        'preferred_name' => 'Synthetic',
        'date_of_birth' => '2010-01-02',
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'student_email' => 'synthetic.student@example.test',
        'password_hash' => 'student-hash',
        'user_role' => 'student',
        'user_status' => 'active',
        'registration_status' => 'approved',
        'program_name' => 'School Yuva',
    ], $overrides);
}

/** @return array<string, string> */
function student_login_filesystem_row(): array
{
    return [
        'Yuva Club ID' => 'YC2026001',
        'Date of Birth' => '2010-01-02',
        'Student Email' => 'synthetic.student@example.test',
        'Parent Email' => 'parent@example.test',
    ];
}

/**
 * @param array<string, mixed>|null $sqlRow
 * @param array<string, string>|null $filesystemRow
 */
function student_login_service(
    string $mode,
    ?array &$sqlRow,
    ?array $filesystemRow,
    int &$sqlFetches
): AuthenticationService {
    $repository = new PortalRepository(
        static function (string $sql, array $parameters) use (
            &$sqlRow,
            &$sqlFetches
        ): ?array {
            $sqlFetches++;
            return $sqlRow;
        },
        static fn(string $sql, array $parameters): array => []
    );
    $adapter = new PortalCompatibilityAdapter();
    $students = new StudentAuthentication(
        $repository,
        $adapter,
        static fn(string $yuvaId): ?array =>
            $filesystemRow !== null
            && ($filesystemRow['Yuva Club ID'] ?? '') === $yuvaId
                ? $filesystemRow
                : null,
        static fn(array $record, string $credential): bool =>
            hash_equals((string) ($record['Date of Birth'] ?? ''), $credential),
        static fn(string $password, string $hash): bool =>
            $password === 'correct-password' && $hash === 'student-hash',
        static fn(string $hash): bool => false
    );
    $parents = new ParentAuthentication(
        $repository,
        static fn(string $yuvaId): ?array => null,
        static fn(string $email): array => [],
        static fn(array $record, string $credential): bool => false
    );
    return new AuthenticationService($mode, $students, $parents);
}

function student_login_throttle_path(string $suffix): string
{
    return sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'yuva-student-login-'
        . $suffix
        . '-'
        . bin2hex(random_bytes(6))
        . '.json';
}

function student_login_remove_throttle(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}

$sqlRow = null;
$sqlFetches = 0;
$filesystemService = student_login_service(
    'filesystem',
    $sqlRow,
    student_login_filesystem_row(),
    $sqlFetches
);
$filesystemPath = student_login_throttle_path('filesystem');
$regenerations = 0;
$filesystemWorkflow = new StudentLoginWorkflow(
    $filesystemService,
    new LoginThrottle($filesystemPath),
    static fn(?string $token): bool => $token === 'valid-csrf',
    static function () use (&$regenerations): void {
        $regenerations++;
    }
);
$filesystemSession = [];
$filesystemSuccess = $filesystemWorkflow->attempt(
    $filesystemSession,
    'YC-2026-1',
    '2010-01-02',
    'valid-csrf',
    '192.0.2.0/24'
);
student_login_assert(
    $filesystemSuccess['authenticated'] === true
    && $filesystemSession === ['student_id' => 'YC2026001']
    && $regenerations === 1
    && $sqlFetches === 0,
    'Filesystem login must preserve the legacy session without SQL access.'
);
$filesystemFailure = $filesystemWorkflow->attempt(
    $filesystemSession,
    'YC2026001',
    'wrong-dob',
    'valid-csrf',
    '192.0.2.0/24'
);
student_login_assert(
    $filesystemFailure['authenticated'] === false,
    'Filesystem credential failure must be generic.'
);
student_login_remove_throttle($filesystemPath);

$sqlRow = student_login_sql_row();
$sqlFetches = 0;
$sqlService = student_login_service('sql', $sqlRow, null, $sqlFetches);
$sqlPath = student_login_throttle_path('sql');
$sqlRegenerations = 0;
$sqlWorkflow = new StudentLoginWorkflow(
    $sqlService,
    new LoginThrottle($sqlPath),
    static fn(?string $token): bool => $token === 'valid-csrf',
    static function () use (&$sqlRegenerations): void {
        $sqlRegenerations++;
    }
);
$sqlSession = [
    'student_id' => 'OLD',
    'student_auth_source' => 'filesystem',
    'student_user_id' => 999,
    'portal_role' => 'parent',
];
$sqlSuccess = $sqlWorkflow->attempt(
    $sqlSession,
    'YC2026001',
    'correct-password',
    'valid-csrf',
    '20010db800000000/64'
);
student_login_assert(
    $sqlSuccess['authenticated'] === true
    && $sqlSession === [
        'student_id' => 'YC2026001',
        'student_auth_source' => 'sql',
        'student_user_id' => 201,
        'portal_role' => 'student',
    ]
    && $sqlRegenerations === 1,
    'SQL login must regenerate and create the exact approved session mapping.'
);
foreach (['password', 'password_hash', 'date_of_birth', 'record'] as $forbiddenKey) {
    student_login_assert(
        !array_key_exists($forbiddenKey, $sqlSession),
        'SQL session contains forbidden credential or row data.'
    );
}

$wrongPasswordSession = [];
student_login_assert(
    $sqlWorkflow->attempt(
        $wrongPasswordSession,
        'YC2026001',
        'wrong-password',
        'valid-csrf',
        '198.51.100.0/24'
    )['authenticated'] === false
    && $wrongPasswordSession === [],
    'Wrong SQL password must not create session state.'
);

foreach ([
    ['password_hash' => null],
    ['user_status' => 'inactive'],
    ['user_status' => 'suspended'],
    ['student_approval_status' => 'pending'],
    ['user_role' => 'parent'],
    ['registration_status' => 'pending'],
] as $override) {
    $blockedRow = student_login_sql_row($override);
    $blockedFetches = 0;
    $blockedService = student_login_service('sql', $blockedRow, null, $blockedFetches);
    student_login_assert(
        $blockedService->authenticateStudent(
            'YC2026001',
            'correct-password'
        )['authenticated'] === false,
        'Ineligible SQL student must fail closed.'
    );
}

$csrfRow = student_login_sql_row();
$csrfFetches = 0;
$csrfService = student_login_service('sql', $csrfRow, null, $csrfFetches);
$csrfPath = student_login_throttle_path('csrf');
$csrfWorkflow = new StudentLoginWorkflow(
    $csrfService,
    new LoginThrottle($csrfPath),
    static fn(?string $token): bool => false,
    static function (): void {
        throw new RuntimeException('Session regeneration must not run.');
    }
);
$csrfSession = [];
student_login_assert(
    $csrfWorkflow->attempt(
        $csrfSession,
        'YC2026001',
        'correct-password',
        'invalid-csrf',
        '203.0.113.0/24'
    )['authenticated'] === false
    && $csrfFetches === 0,
    'CSRF rejection must happen before authentication or SQL lookup.'
);
student_login_remove_throttle($csrfPath);

$hybridRow = student_login_sql_row();
$hybridFetches = 0;
$hybridService = student_login_service(
    'hybrid',
    $hybridRow,
    student_login_filesystem_row(),
    $hybridFetches
);
student_login_assert(
    $hybridService->authenticateStudent(
        'YC2026001',
        'correct-password'
    )['source'] === 'sql',
    'Activated SQL identity must take precedence in hybrid mode.'
);
student_login_assert(
    $hybridService->authenticateStudent(
        'YC2026001',
        '2010-01-02'
    )['authenticated'] === false,
    'Hybrid mode must not downgrade after an activated SQL password failure.'
);

$unactivatedRow = student_login_sql_row(['password_hash' => null]);
$unactivatedFetches = 0;
$unactivatedService = student_login_service(
    'hybrid',
    $unactivatedRow,
    student_login_filesystem_row(),
    $unactivatedFetches
);
student_login_assert(
    $unactivatedService->authenticateStudent(
        'YC2026001',
        '2010-01-02'
    )['source'] === 'filesystem',
    'Compatible unactivated SQL student may use temporary legacy login.'
);

$conflictingFilesystem = student_login_filesystem_row();
$conflictingFilesystem['Student Email'] = 'different@example.test';
$conflictRow = student_login_sql_row();
$conflictFetches = 0;
$conflictService = student_login_service(
    'hybrid',
    $conflictRow,
    $conflictingFilesystem,
    $conflictFetches
);
student_login_assert(
    $conflictService->authenticateStudent(
        'YC2026001',
        'correct-password'
    )['authenticated'] === false,
    'Hybrid duplicate conflict must fail closed.'
);

$clock = 1000;
$throttlePath = student_login_throttle_path('policy');
$throttle = new LoginThrottle(
    $throttlePath,
    2,
    60,
    120,
    static function () use (&$clock): int {
        return $clock;
    }
);
student_login_assert(
    $throttle->isBlocked('student-login', 'YC2026001', '192.0.2.0/24') === false,
    'Fresh throttle bucket must not be blocked.'
);
$throttle->recordFailure('student-login', 'YC2026001', '192.0.2.0/24');
$throttle->recordFailure('student-login', 'YC2026001', '192.0.2.0/24');
student_login_assert(
    $throttle->isBlocked('student-login', 'YC2026001', '192.0.2.0/24') === true,
    'Throttle must lock at the configured limit.'
);
$storedThrottle = (string) file_get_contents($throttlePath);
$decodedThrottle = json_decode($storedThrottle, true);
$storedKeys = is_array($decodedThrottle) ? array_keys($decodedThrottle) : [];
student_login_assert(
    !str_contains($storedThrottle, 'YC2026001')
    && !str_contains($storedThrottle, '192.0.2.0/24')
    && count($storedKeys) === 1
    && preg_match('/^[a-f0-9]{64}$/', (string) $storedKeys[0]) === 1,
    'Throttle storage must contain hashed buckets and numeric state only.'
);
$clock += 121;
student_login_assert(
    $throttle->isBlocked('student-login', 'YC2026001', '192.0.2.0/24') === false,
    'Throttle lock must expire.'
);
$throttle->clear('student-login', 'YC2026001', '192.0.2.0/24');
student_login_assert(
    $throttle->isBlocked('student-login', 'YC2026001', '192.0.2.0/24') === false,
    'Throttle success reset must clear the bucket.'
);
student_login_remove_throttle($throttlePath);

$revalidationRow = student_login_sql_row();
$revalidationFetches = 0;
$revalidationService = student_login_service(
    'sql',
    $revalidationRow,
    null,
    $revalidationFetches
);
$revalidationPath = student_login_throttle_path('revalidation');
$revalidationWorkflow = new StudentLoginWorkflow(
    $revalidationService,
    new LoginThrottle($revalidationPath),
    static fn(?string $token): bool => true,
    static function (): void {
    }
);
$revalidationSession = [
    'student_id' => 'YC2026001',
    'student_auth_source' => 'sql',
    'student_user_id' => 201,
    'portal_role' => 'student',
];
student_login_assert(
    $revalidationWorkflow->revalidateSqlSession($revalidationSession) !== null,
    'Active SQL student session must revalidate.'
);
$revalidationRow['user_status'] = 'suspended';
student_login_assert(
    $revalidationWorkflow->revalidateSqlSession($revalidationSession) === null
    && $revalidationSession === [],
    'Protected access must fail and clear student keys after status changes.'
);
$revalidationRow['user_status'] = 'active';
$wrongIdentitySession = [
    'student_id' => 'YC2026001',
    'student_auth_source' => 'sql',
    'student_user_id' => 999,
    'portal_role' => 'student',
];
student_login_assert(
    $revalidationWorkflow->revalidateSqlSession($wrongIdentitySession) === null
    && $wrongIdentitySession === [],
    'Protected access must require the exact SQL user ID.'
);
student_login_remove_throttle($revalidationPath);

$loginSource = file_get_contents(__DIR__ . '/../../portal-login.php');
$portalSource = file_get_contents(__DIR__ . '/../../portal.php');
student_login_assert(
    is_string($loginSource)
    && str_contains($loginSource, 'csrf_field()')
    && str_contains($loginSource, "portal_student_login_workflow()->attempt")
    && str_contains($loginSource, "\$authMode === 'filesystem'")
    && str_contains($loginSource, "\$authMode === 'sql'"),
    'Student login handler must use the mode-aware workflow and CSRF.'
);
student_login_assert(
    is_string($portalSource)
    && str_contains($portalSource, '$student = require_student();')
    && !str_contains($portalSource, 'AuthenticationService')
    && !str_contains($portalSource, 'student_auth_source'),
    'portal.php and Student UI V1 must remain isolated from authentication wiring.'
);

student_login_remove_throttle($sqlPath);
fwrite(STDOUT, "PASS filesystem student login regression\n");
fwrite(STDOUT, "PASS SQL student login and exact session mapping\n");
fwrite(STDOUT, "PASS student CSRF and throttling controls\n");
fwrite(STDOUT, "PASS hybrid precedence and no-downgrade behavior\n");
fwrite(STDOUT, "PASS protected student session revalidation\n");
