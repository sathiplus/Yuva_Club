<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/ParentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/AuthenticationService.php';

function authentication_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param array<string, array<string, mixed>> $students
 * @param array<string, array<string, mixed>> $parents
 * @param array<int, array<int, array<string, mixed>>> $children
 * @param array<string, array<string, mixed>> $links
 */
function authentication_test_repository(
    array $students = [],
    array $parents = [],
    array $children = [],
    array $links = [],
    ?array &$queries = null
): PortalRepository {
    $queries = [];

    $fetchOne = static function (string $sql, array $parameters) use (
        $students,
        $parents,
        $links,
        &$queries
    ): ?array {
        $queries[] = $sql;
        if (array_key_exists('email', $parameters)) {
            return $parents[(string) $parameters['email']] ?? null;
        }
        if (array_key_exists('parent_user_id', $parameters)) {
            $key = $parameters['parent_user_id'] . ':' . ($parameters['yuva_id'] ?? '');
            return $links[$key] ?? null;
        }
        $studentEmail = strtolower((string) ($parameters['student_email'] ?? ''));
        if ($studentEmail !== '') {
            foreach ($students as $student) {
                if (strtolower((string) ($student['student_email'] ?? '')) === $studentEmail) {
                    return $student;
                }
            }
            return null;
        }
        return $students[(string) ($parameters['yuva_id'] ?? '')] ?? null;
    };

    $fetchAll = static function (string $sql, array $parameters) use (
        $children,
        &$queries
    ): array {
        $queries[] = $sql;
        return $children[(int) ($parameters['parent_user_id'] ?? 0)] ?? [];
    };

    return new PortalRepository($fetchOne, $fetchAll);
}

/** @return array<string, mixed> */
function authentication_test_sql_student(array $overrides = []): array
{
    return array_merge([
        'student_id' => 101,
        'student_user_id' => 201,
        'yuva_id' => 'YC2026001',
        'student_first_name' => 'Test',
        'student_last_name' => 'Student',
        'preferred_name' => 'Tester',
        'date_of_birth' => '2010-01-02',
        'age' => 16,
        'program_name' => 'School Yuva',
        'student_email' => 'yc2026001@students.invalid',
        'parent_email' => 'parent@example.test',
        'password_hash' => 'student-hash',
        'user_role' => 'student',
        'user_status' => 'active',
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'registration_status' => 'approved',
    ], $overrides);
}

/** @return array<string, mixed> */
function authentication_test_sql_parent(array $overrides = []): array
{
    return array_merge([
        'parent_id' => 301,
        'parent_user_id' => 401,
        'first_name' => 'Test',
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
function authentication_test_child(array $overrides = []): array
{
    return array_merge([
        'parent_id' => 301,
        'student_id' => 101,
        'yuva_id' => 'YC2026001',
        'parent_user_role' => 'parent',
        'parent_user_status' => 'active',
        'parent_email_verified_at' => '2026-01-01 00:00:00',
        'parent_password_hash' => 'parent-hash',
        'parent_activated_at' => '2026-01-01 00:00:00',
        'parent_credentials_version' => 1,
        'consent_status' => 'granted',
        'student_approval_status' => 'approved',
        'approved_at' => '2026-01-01 00:00:00',
        'student_user_role' => 'student',
        'student_user_status' => 'active',
    ], $overrides);
}

$adapter = new PortalCompatibilityAdapter();
$filesystemRecord = array_fill_keys($adapter->legacyHeaders(), '');
$filesystemRecord['Yuva Club ID'] = 'YC2026001';
$filesystemRecord['Date of Birth'] = '2010-01-02';
$filesystemRecord['Parent Email'] = 'parent@example.test';

authentication_test_assert(
    AuthenticationService::modeFromEnvironment(null) === 'filesystem'
    && AuthenticationService::modeFromEnvironment('') === 'filesystem',
    'PORTAL_AUTH_MODE must default to filesystem.'
);
foreach (['filesystem', 'sql', 'hybrid'] as $mode) {
    authentication_test_assert(
        AuthenticationService::modeFromEnvironment(strtoupper($mode)) === $mode,
        'Supported authentication mode was not normalized: ' . $mode
    );
}
try {
    AuthenticationService::modeFromEnvironment('automatic');
    throw new RuntimeException('Invalid PORTAL_AUTH_MODE did not fail closed.');
} catch (InvalidArgumentException) {
}

$previousMode = getenv('PORTAL_AUTH_MODE');
$previousServerMode = $_SERVER['PORTAL_AUTH_MODE'] ?? null;
try {
    putenv('PORTAL_AUTH_MODE');
    unset($_SERVER['PORTAL_AUTH_MODE']);
    authentication_test_assert(
        portal_auth_mode() === 'filesystem',
        'Runtime configuration must default to filesystem.'
    );
    putenv('PORTAL_AUTH_MODE=hybrid');
    authentication_test_assert(
        portal_auth_mode() === 'hybrid',
        'Runtime configuration must expose an explicit supported mode.'
    );
} finally {
    if ($previousMode === false) {
        putenv('PORTAL_AUTH_MODE');
    } else {
        putenv('PORTAL_AUTH_MODE=' . $previousMode);
    }
    if ($previousServerMode === null) {
        unset($_SERVER['PORTAL_AUTH_MODE']);
    } else {
        $_SERVER['PORTAL_AUTH_MODE'] = $previousServerMode;
    }
}

$adapted = $adapter->studentToLegacyRecord(authentication_test_sql_student());
authentication_test_assert(
    array_keys($adapted) === $adapter->legacyHeaders(),
    'SQL adapter must preserve the complete legacy header contract and order.'
);
authentication_test_assert(
    $adapted['Yuva Club ID'] === 'YC2026001'
    && $adapted['Student First Name'] === 'Test'
    && $adapted['Program Group'] === 'School Yuva',
    'SQL adapter must map core student fields.'
);
authentication_test_assert(
    $adapted['Student Email'] === '',
    'Internal .invalid student identities must not enter the portal display contract.'
);

$studentRows = ['YC2026001' => authentication_test_sql_student()];
$repository = authentication_test_repository($studentRows, queries: $queries);
$filesystemCalls = 0;
$studentAuthentication = new StudentAuthentication(
    $repository,
    $adapter,
    static function (string $yuvaId) use (&$filesystemCalls, $filesystemRecord): ?array {
        $filesystemCalls++;
        return $yuvaId === 'YC2026001' ? $filesystemRecord : null;
    },
    static fn(array $record, string $credential): bool => $credential === '2010-01-02',
    static fn(string $password, string $hash): bool =>
        $password === 'student-password' && $hash === 'student-hash',
    static fn(string $hash): bool => $hash === 'student-hash'
);

$sqlStudentResult = $studentAuthentication->authenticate(
    'sql',
    'yc-2026-001',
    'student-password'
);
authentication_test_assert(
    $sqlStudentResult['authenticated'] === true
    && $sqlStudentResult['source'] === 'sql'
    && $sqlStudentResult['student_id'] === 'YC2026001'
    && $sqlStudentResult['password_rehash_required'] === true,
    'Activated SQL student must authenticate and report required rehashing.'
);
$sqlEmailRow = authentication_test_sql_student([
    'student_email' => 'synthetic.student@example.test',
]);
$sqlEmailQueries = [];
$sqlEmailAuthentication = new StudentAuthentication(
    authentication_test_repository(
        ['YC2026001' => $sqlEmailRow],
        queries: $sqlEmailQueries
    ),
    $adapter,
    static fn(string $id): ?array => null,
    static fn(array $record, string $credential): bool => false,
    static fn(string $password, string $hash): bool =>
        $password === 'student-password' && $hash === 'student-hash',
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $sqlEmailAuthentication->authenticate(
        'sql',
        'Synthetic.Student@Example.Test',
        'student-password'
    )['student_id'] === 'YC2026001'
    && str_contains((string) ($sqlEmailQueries[0] ?? ''), 'student_user.email'),
    'SQL student authentication must accept the normalized student email identifier.'
);
authentication_test_assert(
    $studentAuthentication->authenticate('sql', 'YC2026001', 'wrong')['authenticated'] === false,
    'Wrong SQL student password must fail.'
);

$studentDummyVerificationCount = 0;
$missingStudentAuthentication = new StudentAuthentication(
    authentication_test_repository(),
    $adapter,
    static fn(string $id): ?array => null,
    static fn(array $record, string $credential): bool => false,
    static function (string $password, string $hash) use (
        &$studentDummyVerificationCount
    ): bool {
        if ($password === 'submitted-student-password' && $hash !== '') {
            $studentDummyVerificationCount++;
        }
        return false;
    },
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $missingStudentAuthentication->authenticate(
        'sql',
        'YC2026999',
        'submitted-student-password'
    )['authenticated'] === false
    && $studentDummyVerificationCount === 1,
    'Missing SQL student must execute the fixed dummy verification path.'
);

$ineligibleStudentDummyCount = 0;
$ineligibleStudentAuthentication = new StudentAuthentication(
    authentication_test_repository([
        'YC2026001' => authentication_test_sql_student(['user_status' => 'suspended']),
    ]),
    $adapter,
    static fn(string $id): ?array => null,
    static fn(array $record, string $credential): bool => false,
    static function (string $password, string $hash) use (
        &$ineligibleStudentDummyCount
    ): bool {
        if ($password === 'submitted-student-password' && $hash !== 'student-hash') {
            $ineligibleStudentDummyCount++;
        }
        return false;
    },
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $ineligibleStudentAuthentication->authenticate(
        'sql',
        'YC2026001',
        'submitted-student-password'
    )['authenticated'] === false
    && $ineligibleStudentDummyCount === 1,
    'Ineligible SQL student must execute the fixed dummy verification path.'
);

foreach ([
    ['user_role' => 'parent'],
    ['user_status' => 'pending'],
    ['user_status' => 'suspended'],
    ['student_approval_status' => 'pending'],
    ['approved_at' => null],
    ['registration_status' => 'rejected'],
    ['password_hash' => null],
] as $override) {
    $blockedRepository = authentication_test_repository([
        'YC2026001' => authentication_test_sql_student($override),
    ]);
    $blocked = new StudentAuthentication(
        $blockedRepository,
        $adapter,
        static fn(string $id): ?array => null,
        static fn(array $record, string $credential): bool => false,
        static fn(string $password, string $hash): bool => true,
        static fn(string $hash): bool => false
    );
    authentication_test_assert(
        $blocked->authenticate('sql', 'YC2026001', 'password')['authenticated'] === false,
        'Invalid SQL student state or role must fail.'
    );
}

$filesystemStudentResult = $studentAuthentication->authenticate(
    'filesystem',
    'YC2026001',
    '2010-01-02'
);
authentication_test_assert(
    $filesystemStudentResult['authenticated'] === true
    && $filesystemStudentResult['source'] === 'filesystem',
    'Filesystem authentication must preserve the injected legacy verifier.'
);

$filesystemCalls = 0;
$hybridSqlResult = $studentAuthentication->authenticate(
    'hybrid',
    'YC2026001',
    'wrong'
);
authentication_test_assert(
    $hybridSqlResult['authenticated'] === false
    && $filesystemCalls === 1,
    'Activated SQL identity must not downgrade after SQL password failure.'
);

$unactivatedRepository = authentication_test_repository([
    'YC2026001' => authentication_test_sql_student(['password_hash' => null]),
]);
$unactivatedHybrid = new StudentAuthentication(
    $unactivatedRepository,
    $adapter,
    static fn(string $id): ?array => $filesystemRecord,
    static fn(array $record, string $credential): bool => $credential === '2010-01-02',
    static fn(string $password, string $hash): bool => false,
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $unactivatedHybrid->authenticate(
        'hybrid',
        'YC2026001',
        '2010-01-02'
    )['source'] === 'filesystem',
    'Compatible unactivated SQL identity may retain temporary legacy authentication.'
);

$conflictingRecord = $filesystemRecord;
$conflictingRecord['Date of Birth'] = '2011-02-03';
$conflict = new StudentAuthentication(
    $repository,
    $adapter,
    static fn(string $id): ?array => $conflictingRecord,
    static fn(array $record, string $credential): bool => true,
    static fn(string $password, string $hash): bool => true,
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $conflict->authenticate('hybrid', 'YC2026001', 'anything')['failure_category']
        === 'hybrid_conflict',
    'Incompatible hybrid student duplicates must fail closed.'
);

$children = [
    authentication_test_child(),
    authentication_test_child([
        'student_id' => 102,
        'yuva_id' => 'YC2026002',
    ]),
    authentication_test_child([
        'student_id' => 103,
        'yuva_id' => 'YC2026003',
        'consent_status' => 'revoked',
    ]),
];
$links = [
    '401:YC2026001' => authentication_test_child(['password_hash' => null]),
    '401:YC2026002' => authentication_test_child([
        'student_id' => 102,
        'yuva_id' => 'YC2026002',
        'password_hash' => 'student-hash',
    ]),
    '401:YC2026003' => authentication_test_child(['consent_status' => 'revoked']),
];
$parentRepository = authentication_test_repository(
    parents: ['parent@example.test' => authentication_test_sql_parent()],
    children: [401 => $children],
    links: $links
);
$parentAuthentication = new ParentAuthentication(
    $parentRepository,
    static fn(string $id): ?array => $id === 'YC2026001' ? $filesystemRecord : null,
    static fn(string $email): array => $email === 'parent@example.test'
        ? [$filesystemRecord]
        : [],
    static fn(array $record, string $credential): bool => $credential === 'parent@example.test',
    static fn(string $password, string $hash): bool =>
        $password === 'parent-password' && $hash === 'parent-hash',
    static fn(string $hash): bool => false
);

$parentResult = $parentAuthentication->authenticate(
    'sql',
    ' Parent@Example.Test ',
    'parent-password'
);
authentication_test_assert(
    $parentResult['authenticated'] === true
    && $parentResult['parent_user_id'] === 401
    && $parentResult['parent_student_id'] === null
    && count($parentResult['children']) === 2
    && !array_key_exists('parent_password_hash', $parentResult['children'][0]),
    'SQL parent identity must authenticate separately and expose only authorized children.'
);
authentication_test_assert(
    $parentAuthentication->authenticate(
        'sql',
        'parent@example.test',
        'wrong'
    )['authenticated'] === false,
    'Wrong parent password must fail.'
);
authentication_test_assert(
    $parentAuthentication->authenticate(
        'hybrid',
        'parent@example.test',
        'parent@example.test',
        'YC2026001'
    )['authenticated'] === false,
    'Activated SQL parent must not downgrade to legacy email-only authentication.'
);
authentication_test_assert(
    $parentAuthentication->canAccessChild(401, 'YC2026001') === true
    && $parentAuthentication->canAccessChild(401, 'YC2026003') === false
    && $parentAuthentication->canAccessChild(401, 'YC2026999') === false,
    'Parent-child authorization must reject revoked and unlinked children.'
);
authentication_test_assert(
    ($parentAuthentication->authorizedChildRecord(401, 'YC2026001')['Yuva Club ID'] ?? null)
        === 'YC2026001'
    && ($parentAuthentication->authorizedChildRecord(401, 'YC2026002')['Yuva Club ID'] ?? null)
        === 'YC2026002'
    && $parentAuthentication->authorizedChildRecord(401, 'YC2026003') === null,
    'Parent-linked child authorization must be independent of student password state and retain relationship authorization.'
);

foreach ([
    ['user_role' => 'student'],
    ['user_status' => 'pending'],
    ['user_status' => 'suspended'],
    ['email_verified_at' => null],
    ['activated_at' => null],
    ['password_hash' => null],
] as $override) {
    $blockedParentRepository = authentication_test_repository(
        parents: ['parent@example.test' => authentication_test_sql_parent($override)]
    );
    $blockedParent = new ParentAuthentication(
        $blockedParentRepository,
        static fn(string $id): ?array => null,
        static fn(string $email): array => [],
        static fn(array $record, string $credential): bool => false,
        static fn(string $password, string $hash): bool => true,
        static fn(string $hash): bool => false
    );
    authentication_test_assert(
        $blockedParent->authenticate(
            'sql',
            'parent@example.test',
            'password'
        )['authenticated'] === false,
        'Invalid SQL parent status, role, verification, or activation must fail.'
    );
}

$legacyParent = $parentAuthentication->authenticate(
    'filesystem',
    'parent@example.test',
    'parent@example.test',
    'YC2026001'
);
authentication_test_assert(
    $legacyParent['authenticated'] === false,
    'Legacy Child YUVA ID plus Parent Email must not authenticate a parent.'
);

$conflictingParentRecord = $filesystemRecord;
$conflictingParentRecord['Parent Email'] = 'different-parent@example.test';
$conflictingParent = new ParentAuthentication(
    $parentRepository,
    static fn(string $id): ?array => $conflictingParentRecord,
    static fn(string $email): array => [$conflictingParentRecord],
    static fn(array $record, string $credential): bool => true,
    static fn(string $password, string $hash): bool => true,
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $conflictingParent->authenticate(
        'hybrid',
        'parent@example.test',
        'anything',
        'YC2026001'
    )['authenticated'] === true,
    'Parent authentication must use the authoritative SQL password and ignore legacy identity stores.'
);

$parentDummyVerificationCount = 0;
$missingParentAuthentication = new ParentAuthentication(
    authentication_test_repository(),
    static fn(string $id): ?array => null,
    static fn(string $email): array => [],
    static fn(array $record, string $credential): bool => false,
    static function (string $password, string $hash) use (
        &$parentDummyVerificationCount
    ): bool {
        if ($password === 'submitted-parent-password' && $hash !== '') {
            $parentDummyVerificationCount++;
        }
        return false;
    },
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $missingParentAuthentication->authenticate(
        'sql',
        'missing-parent@example.test',
        'submitted-parent-password'
    )['authenticated'] === false
    && $parentDummyVerificationCount === 1,
    'Missing SQL parent must execute the fixed dummy verification path.'
);

$ineligibleParentDummyCount = 0;
$ineligibleParentAuthentication = new ParentAuthentication(
    authentication_test_repository(
        parents: [
            'parent@example.test' => authentication_test_sql_parent([
                'user_status' => 'suspended',
            ]),
        ]
    ),
    static fn(string $id): ?array => null,
    static fn(string $email): array => [],
    static fn(array $record, string $credential): bool => false,
    static function (string $password, string $hash) use (
        &$ineligibleParentDummyCount
    ): bool {
        if ($password === 'submitted-parent-password' && $hash !== 'parent-hash') {
            $ineligibleParentDummyCount++;
        }
        return false;
    },
    static fn(string $hash): bool => false
);
authentication_test_assert(
    $ineligibleParentAuthentication->authenticate(
        'sql',
        'parent@example.test',
        'submitted-parent-password'
    )['authenticated'] === false
    && $ineligibleParentDummyCount === 1,
    'Ineligible SQL parent must execute the fixed dummy verification path.'
);

foreach ([
    ['parent_user_status' => 'suspended'],
    ['parent_user_status' => 'disabled'],
    ['parent_user_role' => 'student'],
    ['parent_email_verified_at' => null],
    ['parent_password_hash' => null],
] as $revokedParentState) {
    $authorizationState = authentication_test_child();
    $statefulRepository = new PortalRepository(
        static function (string $sql, array $parameters) use (
            &$authorizationState
        ): ?array {
            if (array_key_exists('email', $parameters)) {
                return authentication_test_sql_parent();
            }
            if (array_key_exists('parent_user_id', $parameters)) {
                return $authorizationState;
            }
            return null;
        },
        static function (string $sql, array $parameters) use (
            &$authorizationState
        ): array {
            return [$authorizationState];
        }
    );
    $statefulParent = new ParentAuthentication(
        $statefulRepository,
        static fn(string $id): ?array => null,
        static fn(string $email): array => [],
        static fn(array $record, string $credential): bool => false,
        static fn(string $password, string $hash): bool =>
            $password === 'parent-password' && $hash === 'parent-hash',
        static fn(string $hash): bool => false
    );

    authentication_test_assert(
        $statefulParent->authenticate(
            'sql',
            'parent@example.test',
            'parent-password'
        )['authenticated'] === true,
        'Parent must authenticate before the post-login state change.'
    );

    $authorizationState = array_merge(
        $authorizationState,
        $revokedParentState
    );
    authentication_test_assert(
        $statefulParent->listAuthorizedChildren(401) === []
        && $statefulParent->canAccessChild(401, 'YC2026001') === false,
        'Post-login parent authorization changes must fail closed.'
    );
}

$service = new AuthenticationService(
    'sql',
    $studentAuthentication,
    $parentAuthentication
);
authentication_test_assert(
    $service->mode() === 'sql'
    && $service->authenticateStudent(
        'YC2026001',
        'student-password'
    )['authenticated'] === true
    && count($service->authorizedParentChildren(401)) === 2,
    'Authentication service must orchestrate the selected mode and role services.'
);

foreach ($queries as $query) {
    authentication_test_assert(
        preg_match('/^\s*SELECT\b/i', $query) === 1,
        'Portal repository must issue read-only SELECT statements.'
    );
    authentication_test_assert(
        stripos($query, 'vw_portal_students') === false,
        'Authentication must not use the display view as an authorization source.'
    );
}

$configSource = file_get_contents(__DIR__ . '/../../backend/config.php');
authentication_test_assert(
    is_string($configSource)
    && str_contains($configSource, "'PORTAL_AUTH_MODE'")
    && !str_contains($configSource, "'PORTAL_STORAGE_MODE'"),
    'Authentication configuration must not change PORTAL_STORAGE_MODE semantics.'
);

fwrite(STDOUT, "PASS authentication mode parsing and filesystem default\n");
fwrite(STDOUT, "PASS SQL-to-legacy compatibility adapter\n");
fwrite(STDOUT, "PASS SQL student status, approval, role, and password gates\n");
fwrite(STDOUT, "PASS filesystem and hybrid student behavior\n");
fwrite(STDOUT, "PASS SQL parent authentication and reuse across children\n");
fwrite(STDOUT, "PASS parent-child authorization\n");
fwrite(STDOUT, "PASS read-only base-table repository boundary\n");
