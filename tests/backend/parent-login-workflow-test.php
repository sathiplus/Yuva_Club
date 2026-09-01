<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\LoginThrottle;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\ParentLoginWorkflow;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;

require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/ParentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/AuthenticationService.php';
require_once __DIR__ . '/../../backend/authentication/LoginThrottle.php';
require_once __DIR__ . '/../../backend/authentication/ParentLoginWorkflow.php';

function parent_login_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function parent_login_parent(array $overrides = []): array
{
    return array_merge([
        'parent_id' => 301,
        'parent_user_id' => 401,
        'first_name' => 'Synthetic',
        'last_name' => 'Parent',
        'email' => 'parent@example.test',
        'password_hash' => 'parent-hash',
        'user_role' => 'parent',
        'user_status' => 'active',
        'email_verified_at' => '2026-01-01 00:00:00',
        'activated_at' => '2026-01-01 00:00:00',
        'credentials_version' => 1,
    ], $overrides);
}

/** @return array<string, mixed> */
function parent_login_student(string $yuvaId, int $studentId, int $userId): array
{
    return [
        'student_id' => $studentId,
        'student_user_id' => $userId,
        'yuva_id' => $yuvaId,
        'student_first_name' => 'Synthetic',
        'student_last_name' => (string) $studentId,
        'date_of_birth' => '2010-01-02',
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'password_hash' => 'student-hash',
        'user_role' => 'student',
        'user_status' => 'active',
        'registration_status' => 'approved',
        'program_name' => 'School Yuva',
        'parent_email' => 'parent@example.test',
    ];
}

/** @return array<string, mixed> */
function parent_login_link(
    string $yuvaId,
    int $studentId,
    int $studentUserId,
    array $overrides = []
): array {
    return array_merge([
        'parent_user_id' => 401,
        'parent_user_role' => 'parent',
        'parent_user_status' => 'active',
        'parent_email_verified_at' => '2026-01-01 00:00:00',
        'parent_password_hash' => 'parent-hash',
        'parent_activated_at' => '2026-01-01 00:00:00',
        'parent_credentials_version' => 1,
        'parent_id' => 301,
        'student_id' => $studentId,
        'student_user_id' => $studentUserId,
        'consent_status' => 'granted',
        'yuva_id' => $yuvaId,
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'student_user_role' => 'student',
        'student_user_status' => 'active',
        'student_first_name' => 'Synthetic',
        'student_last_name' => (string) $studentId,
    ], $overrides);
}

/** @return array<string, string> */
function parent_login_filesystem_student(string $yuvaId = 'YC2026001'): array
{
    return [
        'Yuva Club ID' => $yuvaId,
        'Student First Name' => 'Synthetic',
        'Student Last Name' => 'Student',
        'Date of Birth' => '2010-01-02',
        'Student Email' => 'student@example.test',
        'Parent/Guardian Name' => 'Synthetic Parent',
        'Parent Email' => 'parent@example.test',
    ];
}

/**
 * @param array<string, mixed>|null $parent
 * @param array<string, array<string, mixed>> $students
 * @param array<string, array<string, mixed>> $links
 * @param array<int, array<string, mixed>> $children
 * @param array<int, array<string, string>> $filesystemStudents
 */
function parent_login_service(
    string $mode,
    ?array &$parent,
    array &$students,
    array &$links,
    array &$children,
    array $filesystemStudents,
    int &$sqlFetches
): AuthenticationService {
    $repository = new PortalRepository(
        static function (string $sql, array $parameters) use (
            &$parent,
            &$students,
            &$links,
            &$sqlFetches
        ): ?array {
            $sqlFetches++;
            if (array_key_exists('email', $parameters)) {
                return $parent;
            }
            if (
                array_key_exists('parent_user_id', $parameters)
                && array_key_exists('yuva_id', $parameters)
            ) {
                return $links[(string) $parameters['yuva_id']] ?? null;
            }
            if (array_key_exists('parent_user_id', $parameters)) {
                return $parent;
            }
            return $students[(string) ($parameters['yuva_id'] ?? '')] ?? null;
        },
        static function (string $sql, array $parameters) use (
            &$children,
            &$sqlFetches
        ): array {
            $sqlFetches++;
            return $children;
        }
    );
    $filesystemFinder = static function (string $yuvaId) use (
        $filesystemStudents
    ): ?array {
        foreach ($filesystemStudents as $student) {
            if (($student['Yuva Club ID'] ?? '') === $yuvaId) {
                return $student;
            }
        }
        return null;
    };
    $filesystemParents = static function (string $email) use (
        $filesystemStudents
    ): array {
        return array_values(array_filter(
            $filesystemStudents,
            static fn(array $student): bool =>
                strtolower((string) ($student['Parent Email'] ?? ''))
                === strtolower($email)
        ));
    };

    $adapter = new PortalCompatibilityAdapter();
    $studentAuthentication = new StudentAuthentication(
        $repository,
        $adapter,
        $filesystemFinder,
        static fn(array $record, string $credential): bool => false,
        static fn(string $password, string $hash): bool =>
            $password === 'student-password' && $hash === 'student-hash',
        static fn(string $hash): bool => false
    );
    $parentAuthentication = new ParentAuthentication(
        $repository,
        $filesystemFinder,
        $filesystemParents,
        static fn(array $record, string $credential): bool =>
            strtolower((string) ($record['Parent Email'] ?? ''))
            === strtolower($credential),
        static fn(string $password, string $hash): bool =>
            $password === 'parent-password' && $hash === 'parent-hash',
        static fn(string $hash): bool => false
    );
    return new AuthenticationService(
        $mode,
        $studentAuthentication,
        $parentAuthentication
    );
}

function parent_login_temp_path(string $name): string
{
    return sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'yuva-parent-login-'
        . $name
        . '-'
        . bin2hex(random_bytes(6))
        . '.json';
}

function parent_login_cleanup(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}

$parent = null;
$students = [];
$links = [];
$children = [];
$filesystem = [parent_login_filesystem_student()];
$fetches = 0;
$filesystemService = parent_login_service(
    'filesystem',
    $parent,
    $students,
    $links,
    $children,
    $filesystem,
    $fetches
);
$filesystemPath = parent_login_temp_path('filesystem');
$regenerations = 0;
$filesystemWorkflow = new ParentLoginWorkflow(
    $filesystemService,
    new LoginThrottle($filesystemPath),
    static fn(?string $token): bool => $token === 'valid-csrf',
    static function () use (&$regenerations): void {
        $regenerations++;
    }
);
$filesystemSession = [];
$filesystemLoginStartedAt = time();
$filesystemResult = $filesystemWorkflow->attempt(
    $filesystemSession,
    'parent@example.test',
    'parent@example.test',
    'YC2026001',
    'valid-csrf',
    '192.0.2.0/24'
);
parent_login_assert(
    $filesystemResult['authenticated'] === false
    && $filesystemSession === []
    && $regenerations === 0
    && $fetches > 0,
    'Filesystem mode must not permit legacy Child YUVA ID plus Parent Email authentication.'
);
$mismatchSession = [];
parent_login_assert(
    $filesystemWorkflow->attempt(
        $mismatchSession,
        'wrong@example.test',
        'wrong@example.test',
        'YC2026001',
        'valid-csrf',
        '198.51.100.0/24'
    )['authenticated'] === false
    && $mismatchSession === [],
    'Filesystem parent mismatch must fail.'
);
parent_login_cleanup($filesystemPath);

$parent = parent_login_parent();
$students = [
    'YC2026001' => parent_login_student('YC2026001', 501, 601),
    'YC2026002' => parent_login_student('YC2026002', 502, 602),
];
$links = [
    'YC2026001' => parent_login_link('YC2026001', 501, 601),
    'YC2026002' => parent_login_link('YC2026002', 502, 602),
];
$children = [
    parent_login_link('YC2026001', 501, 601),
    parent_login_link('YC2026002', 502, 602),
];
$fetches = 0;
$sqlService = parent_login_service(
    'sql',
    $parent,
    $students,
    $links,
    $children,
    [],
    $fetches
);
$sqlPath = parent_login_temp_path('sql');
$sqlRegenerations = 0;
$sqlWorkflow = new ParentLoginWorkflow(
    $sqlService,
    new LoginThrottle($sqlPath),
    static fn(?string $token): bool => $token === 'valid-csrf',
    static function () use (&$sqlRegenerations): void {
        $sqlRegenerations++;
    }
);
$sqlSession = [
    'student_id' => 'YC2026999',
    'student_auth_source' => 'sql',
    'student_user_id' => 999,
    'portal_role' => 'student',
];
$sqlResult = $sqlWorkflow->attempt(
    $sqlSession,
    'PARENT@EXAMPLE.TEST',
    'parent-password',
    null,
    'valid-csrf',
    '20010db800000000/64'
);
parent_login_assert(
    $sqlResult === [
        'authenticated' => true,
        'requires_child_selection' => true,
    ]
    && ($sqlSession['parent_auth_source'] ?? null) === 'sql'
    && ($sqlSession['parent_user_id'] ?? null) === 401
    && ($sqlSession['parent_id'] ?? null) === 301
    && ($sqlSession['parent_credentials_version'] ?? null) === 1
    && is_int($sqlSession['parent_authenticated_at'] ?? null)
    && ($sqlSession['portal_role'] ?? null) === 'parent'
    && !isset($sqlSession['parent_student_id'])
    && $sqlRegenerations === 1,
    'SQL parent identity must create exact session state without child context.'
);
parent_login_assert(
    count($sqlWorkflow->authorizedChildren($sqlSession) ?? []) === 2,
    'Reused SQL parent must expose both authorized linked children.'
);
parent_login_assert(
    $sqlWorkflow->selectChild(
        $sqlSession,
        'YC2026002',
        'valid-csrf',
        '20010db800000000/64'
    )
    && ($sqlSession['parent_student_id'] ?? null) === 'YC2026002'
    && $sqlRegenerations === 2,
    'Authorized child selection must regenerate and set child context.'
);
parent_login_assert(
    $sqlWorkflow->revalidateSqlChildAccess($sqlSession) !== null,
    'Authorized SQL parent-child protected access must revalidate.'
);

$preLoginCsrf = str_repeat('a', 64);
$postLoginCsrf = str_repeat('b', 64);
$activeCsrf = $preLoginCsrf;
$boundaryRegenerations = 0;
$boundaryPath = parent_login_temp_path('csrf-boundary');
$boundaryWorkflow = new ParentLoginWorkflow(
    $sqlService,
    new LoginThrottle($boundaryPath),
    static function (?string $token) use (&$activeCsrf): bool {
        return is_string($token) && hash_equals($activeCsrf, $token);
    },
    static function () use (&$boundaryRegenerations): void {
        $boundaryRegenerations++;
    },
    null,
    static function (array &$session) use (&$activeCsrf, $postLoginCsrf): void {
        $activeCsrf = $postLoginCsrf;
        $session['csrf_token'] = $postLoginCsrf;
    }
);
$boundarySession = ['csrf_token' => $preLoginCsrf];
$boundaryResult = $boundaryWorkflow->attempt(
    $boundarySession,
    'parent@example.test',
    'parent-password',
    null,
    $preLoginCsrf,
    '20010db800000000/64'
);
parent_login_assert(
    $boundaryResult['authenticated'] === true
    && ($boundarySession['csrf_token'] ?? null) === $postLoginCsrf
    && $postLoginCsrf !== $preLoginCsrf
    && $boundaryRegenerations === 1,
    'Successful Parent login must regenerate the session and rotate the pre-login CSRF token.'
);

foreach ([null, 'malformed', $preLoginCsrf, str_repeat('c', 64)] as $rejectedToken) {
    $rejectedSession = $boundarySession;
    parent_login_assert(
        !$boundaryWorkflow->selectChild(
            $rejectedSession,
            'YC2026001',
            $rejectedToken,
            '20010db800000000/64'
        )
        && !isset($rejectedSession['parent_user_id']),
        'Missing, malformed, stale, or another-session CSRF token must reject child selection.'
    );
}

$validBoundarySession = $boundarySession;
parent_login_assert(
    $boundaryWorkflow->selectChild(
        $validBoundarySession,
        'YC2026001',
        $postLoginCsrf,
        '20010db800000000/64'
    )
    && ($validBoundarySession['parent_student_id'] ?? null) === 'YC2026001'
    && $boundaryWorkflow->selectChild(
        $validBoundarySession,
        'YC2026002',
        $postLoginCsrf,
        '20010db800000000/64'
    )
    && ($validBoundarySession['parent_student_id'] ?? null) === 'YC2026002'
    && $boundaryRegenerations === 3,
    'Fresh post-login CSRF token must permit authorized child selection and switching.'
);

$unrelatedSession = $boundarySession;
parent_login_assert(
    !$boundaryWorkflow->selectChild(
        $unrelatedSession,
        'YC2026999',
        $postLoginCsrf,
        '20010db800000000/64'
    ),
    'A valid CSRF token must not authorize an unrelated child.'
);
parent_login_cleanup($boundaryPath);

$wrongPasswordSession = [];
parent_login_assert(
    $sqlWorkflow->attempt(
        $wrongPasswordSession,
        'parent@example.test',
        'wrong-password',
        null,
        'valid-csrf',
        '203.0.113.0/24'
    )['authenticated'] === false,
    'Wrong SQL parent password must fail.'
);

foreach ([
    ['email_verified_at' => null],
    ['activated_at' => null],
    ['user_status' => 'disabled'],
    ['user_status' => 'suspended'],
    ['user_role' => 'student'],
    ['password_hash' => null],
] as $override) {
    $blockedParent = parent_login_parent($override);
    $blockedFetches = 0;
    $blockedService = parent_login_service(
        'sql',
        $blockedParent,
        $students,
        $links,
        $children,
        [],
        $blockedFetches
    );
    parent_login_assert(
        $blockedService->authenticateParent(
            'parent@example.test',
            'parent-password'
        )['authenticated'] === false,
        'Ineligible SQL parent identity must fail closed.'
    );
}

foreach ([
    null,
    parent_login_link('YC2026001', 501, 601, ['consent_status' => 'revoked']),
    parent_login_link('YC2026001', 501, 601, ['student_approval_status' => 'pending']),
    parent_login_link('YC2026001', 501, 601, ['student_user_status' => 'disabled']),
] as $blockedLink) {
    $selectionParent = parent_login_parent();
    $selectionLinks = $links;
    if ($blockedLink === null) {
        unset($selectionLinks['YC2026001']);
    } else {
        $selectionLinks['YC2026001'] = $blockedLink;
    }
    $selectionFetches = 0;
    $selectionService = parent_login_service(
        'sql',
        $selectionParent,
        $students,
        $selectionLinks,
        $children,
        [],
        $selectionFetches
    );
    $selectionPath = parent_login_temp_path('selection');
    $selectionWorkflow = new ParentLoginWorkflow(
        $selectionService,
        new LoginThrottle($selectionPath),
        static fn(?string $token): bool => true,
        static function (): void {
        }
    );
    $selectionSession = [
        'parent_auth_source' => 'sql',
        'parent_user_id' => 401,
        'parent_id' => 301,
        'parent_credentials_version' => 1,
        'parent_authenticated_at' => time(),
        'portal_role' => 'parent',
    ];
    parent_login_assert(
        !$selectionWorkflow->selectChild(
            $selectionSession,
            'YC2026001',
            'valid-csrf',
            '192.0.2.0/24'
        )
        && $selectionSession === [],
        'Forged, revoked, unapproved, or inactive child must fail closed.'
    );
    parent_login_cleanup($selectionPath);
}

$csrfParent = parent_login_parent();
$csrfFetches = 0;
$csrfService = parent_login_service(
    'sql',
    $csrfParent,
    $students,
    $links,
    $children,
    [],
    $csrfFetches
);
$csrfPath = parent_login_temp_path('csrf');
$csrfWorkflow = new ParentLoginWorkflow(
    $csrfService,
    new LoginThrottle($csrfPath),
    static fn(?string $token): bool => false,
    static function (): void {
        throw new RuntimeException('CSRF failure must not regenerate.');
    }
);
$csrfSession = [];
parent_login_assert(
    !$csrfWorkflow->attempt(
        $csrfSession,
        'parent@example.test',
        'parent-password',
        null,
        'bad-csrf',
        '192.0.2.0/24'
    )['authenticated']
    && $csrfFetches === 0,
    'Parent CSRF failure must happen before SQL authentication.'
);
$csrfSelectionSession = [
    'parent_auth_source' => 'sql',
    'parent_user_id' => 401,
    'parent_id' => 301,
    'parent_credentials_version' => 1,
    'parent_authenticated_at' => time(),
    'portal_role' => 'parent',
];
parent_login_assert(
    !$csrfWorkflow->selectChild(
        $csrfSelectionSession,
        'YC2026001',
        'bad-csrf',
        '192.0.2.0/24'
    )
    && $csrfSelectionSession === [],
    'Child selection CSRF failure must clear parent authorization state.'
);
parent_login_cleanup($csrfPath);

$hybridParent = parent_login_parent();
$hybridFetches = 0;
$hybridService = parent_login_service(
    'hybrid',
    $hybridParent,
    $students,
    $links,
    $children,
    $filesystem,
    $hybridFetches
);
parent_login_assert(
    $hybridService->authenticateParent(
        'parent@example.test',
        'parent-password',
        'YC2026001'
    )['source'] === 'sql',
    'Activated SQL parent must take precedence in hybrid mode.'
);
parent_login_assert(
    !$hybridService->authenticateParent(
        'parent@example.test',
        'parent@example.test',
        'YC2026001'
    )['authenticated'],
    'Hybrid parent must not downgrade after SQL password failure.'
);

$unactivatedParent = parent_login_parent([
    'password_hash' => null,
    'email_verified_at' => null,
]);
$unactivatedFetches = 0;
$unactivatedService = parent_login_service(
    'hybrid',
    $unactivatedParent,
    $students,
    $links,
    $children,
    $filesystem,
    $unactivatedFetches
);
parent_login_assert(
    !$unactivatedService->authenticateParent(
        'parent@example.test',
        'parent@example.test',
        'YC2026001'
    )['authenticated'],
    'Unactivated Parent must not retain legacy access.'
);

$conflictingFilesystem = parent_login_filesystem_student();
$conflictingFilesystem['Parent/Guardian Name'] = 'Different Parent';
$conflictParent = parent_login_parent();
$conflictFetches = 0;
$conflictService = parent_login_service(
    'hybrid',
    $conflictParent,
    $students,
    $links,
    $children,
    [$conflictingFilesystem],
    $conflictFetches
);
parent_login_assert(
    $conflictService->authenticateParent(
        'parent@example.test',
        'parent-password',
        'YC2026001'
    )['authenticated'],
    'Legacy filesystem identity data must not influence SQL Parent authentication.'
);

$clock = 1000;
$throttlePath = parent_login_temp_path('throttle');
$throttle = new LoginThrottle(
    $throttlePath,
    2,
    60,
    120,
    static function () use (&$clock): int {
        return $clock;
    }
);
$throttle->recordFailure('parent-login', 'parent@example.test', '192.0.2.0/24');
$throttle->recordFailure('parent-login', 'parent@example.test', '192.0.2.0/24');
parent_login_assert(
    $throttle->isBlocked(
        'parent-login',
        'parent@example.test',
        '192.0.2.0/24'
    ),
    'Parent throttle must lock at the configured limit.'
);
$storedThrottle = (string) file_get_contents($throttlePath);
parent_login_assert(
    !str_contains($storedThrottle, 'parent@example.test')
    && !str_contains($storedThrottle, '192.0.2.0/24'),
    'Parent throttle must not persist raw identity or network data.'
);
$clock += 121;
parent_login_assert(
    !$throttle->isBlocked(
        'parent-login',
        'parent@example.test',
        '192.0.2.0/24'
    ),
    'Parent throttle must expire.'
);
$throttle->clear('parent-login', 'parent@example.test', '192.0.2.0/24');
parent_login_cleanup($throttlePath);

foreach ([
    ['parent' => ['user_status' => 'suspended']],
    ['parent' => ['user_role' => 'student']],
    ['parent' => ['email_verified_at' => null]],
    ['parent' => ['password_hash' => null]],
    ['link' => ['consent_status' => 'revoked']],
    ['link' => ['student_user_status' => 'disabled']],
] as $stateChange) {
    $stateParent = parent_login_parent();
    $stateLinks = $links;
    $stateStudents = $students;
    $stateChildren = $children;
    $stateFetches = 0;
    $stateService = parent_login_service(
        'sql',
        $stateParent,
        $stateStudents,
        $stateLinks,
        $stateChildren,
        [],
        $stateFetches
    );
    $statePath = parent_login_temp_path('state');
    $stateWorkflow = new ParentLoginWorkflow(
        $stateService,
        new LoginThrottle($statePath),
        static fn(?string $token): bool => true,
        static function (): void {
        }
    );
    $stateSession = [
        'parent_auth_source' => 'sql',
        'parent_user_id' => 401,
        'parent_id' => 301,
        'parent_credentials_version' => 1,
        'parent_authenticated_at' => time(),
        'portal_role' => 'parent',
        'parent_student_id' => 'YC2026001',
    ];
    parent_login_assert(
        $stateWorkflow->revalidateSqlChildAccess($stateSession) !== null,
        'Baseline parent protected access must succeed independently of student login activation.'
    );
    if (isset($stateChange['parent'])) {
        $stateParent = array_merge($stateParent, $stateChange['parent']);
    }
    if (isset($stateChange['link'])) {
        $stateLinks['YC2026001'] = array_merge(
            $stateLinks['YC2026001'],
            $stateChange['link']
        );
    }
    parent_login_assert(
        $stateWorkflow->revalidateSqlChildAccess($stateSession) === null
        && $stateSession === [],
        'Changed parent or child authorization state must clear protected access.'
    );
    parent_login_cleanup($statePath);
}

$loginSource = file_get_contents(__DIR__ . '/../../parent-login.php');
$parentSource = file_get_contents(__DIR__ . '/../../parent.php');
parent_login_assert(
    is_string($loginSource)
    && str_contains($loginSource, 'portal_parent_login_workflow()->attempt')
    && str_contains($loginSource, 'csrf_field()')
    && str_contains($loginSource, "value=\"select_child\""),
    'Parent login page must use the workflow, CSRF, and child selection.'
);
parent_login_assert(
    is_string($parentSource)
    && str_contains($parentSource, '$parentContext = require_authenticated_parent();')
    && !str_contains($parentSource, 'AuthenticationService'),
    'Parent UI must delegate only its authorization guard.'
);

parent_login_cleanup($sqlPath);
fwrite(STDOUT, "PASS legacy filesystem Parent authentication rejection\n");
fwrite(STDOUT, "PASS SQL parent identity and child selection sessions\n");
fwrite(STDOUT, "PASS SQL-only Parent precedence and no-downgrade behavior\n");
fwrite(STDOUT, "PASS parent CSRF and throttling controls\n");
fwrite(STDOUT, "PASS parent protected-page authorization revalidation\n");
