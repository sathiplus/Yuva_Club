<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/repositories.php';
require_once __DIR__ . '/../../backend/auth.php';

function backend_test_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function backend_test_expect_exception(callable $callback, string $message): void {
    try {
        $callback();
    } catch (Throwable $error) {
        backend_test_assert(
            str_contains($error->getMessage(), $message),
            'Unexpected exception: ' . $error->getMessage()
        );
        return;
    }
    throw new RuntimeException('Expected exception was not thrown.');
}

function backend_test_function_source(string $name): string {
    $reflection = new ReflectionFunction($name);
    $lines = file($reflection->getFileName());
    if ($lines === false) {
        throw new RuntimeException('Unable to read function source: ' . $name);
    }
    return implode('', array_slice(
        $lines,
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1
    ));
}

backend_test_assert(
    backend_validate_reserved_yuva_id('YC2026001') === 'YC2026001',
    'Canonical reserved YUVA IDs must be preserved exactly.'
);
backend_test_expect_exception(
    static fn() => backend_validate_reserved_yuva_id('yc-2026-001'),
    'canonical stored format'
);
backend_test_expect_exception(
    static fn() => backend_validate_reserved_yuva_id('YC1999001'),
    'invalid year or numeric suffix'
);
backend_test_assert(
    backend_internal_student_email('YC2026001') === 'yc2026001@students.invalid',
    'Internal student identity must be deterministic.'
);
backend_test_assert(
    backend_usable_email(' Student@Example.COM ') === 'student@example.com',
    'Usable submitted emails must be normalized.'
);
backend_test_assert(
    backend_usable_email('student@students.invalid') === null,
    'Submitted .invalid addresses must not be treated as deliverable.'
);

$studentEmail = 'student.identity@example.test';
$parentEmail = 'parent.identity@example.test';
$identityResources = backend_identity_lock_resources([
    $studentEmail,
    $parentEmail,
]);
backend_test_assert(
    count($identityResources) === 2,
    'Distinct identity emails must produce two lock resources.'
);
foreach ($identityResources as $resource) {
    backend_test_assert(
        str_starts_with($resource, 'yuva-identity-email:')
        && preg_match('/^yuva-identity-email:[a-f0-9]{64}$/', $resource) === 1,
        'Identity lock resources must use SHA-256 names.'
    );
    backend_test_assert(
        !str_contains($resource, $studentEmail)
        && !str_contains($resource, $parentEmail)
        && !str_contains($resource, '@'),
        'Identity lock resources must not contain raw email data.'
    );
}
backend_test_assert(
    $identityResources === backend_identity_lock_resources([
        $parentEmail,
        $studentEmail,
    ]),
    'Opposite student/parent ordering must produce the same lock order.'
);
backend_test_assert(
    count(backend_identity_lock_resources([
        'Duplicate@Example.test',
        ' duplicate@example.TEST ',
    ])) === 1,
    'Duplicate normalized identities must produce one lock resource.'
);
$sortedResources = $identityResources;
sort($sortedResources, SORT_STRING);
backend_test_assert(
    $identityResources === $sortedResources,
    'Identity lock resources must be sorted lexically.'
);

$lockAttempts = [];
backend_test_expect_exception(
    static function () use ($identityResources, &$lockAttempts): void {
        backend_acquire_lock_resources(
            $identityResources,
            static function (string $resource) use (&$lockAttempts): void {
                $lockAttempts[] = $resource;
                throw new RuntimeException(
                    'The requested database operation is already in progress.'
                );
            }
        );
    },
    'already in progress'
);
backend_test_assert(
    $lockAttempts === [$identityResources[0]],
    'Identity resolution must stop immediately when a lock cannot be acquired.'
);

$databaseSource = file_get_contents(__DIR__ . '/../../backend/database.php');
$repositorySource = file_get_contents(__DIR__ . '/../../backend/repositories.php');
$authSource = file_get_contents(__DIR__ . '/../../backend/auth.php');
backend_test_assert($databaseSource !== false, 'database.php is unreadable.');
backend_test_assert($repositorySource !== false, 'repositories.php is unreadable.');
backend_test_assert($authSource !== false, 'auth.php is unreadable.');

foreach ([
    'SET XACT_ABORT ON',
    'SET TRANSACTION ISOLATION LEVEL',
    'sys.sp_getapplock',
    '@LockOwner = :lock_owner',
    'db_safe_rollback',
] as $contract) {
    backend_test_assert(
        str_contains($databaseSource, $contract),
        'Database helper contract is missing: ' . $contract
    );
}

$approvalSource = backend_test_function_source('approve_registration_sqlsrv');
foreach ([
    'SERIALIZABLE',
    'yuva-registration-approval:',
    'backend_registration_identity_lock_resources',
    'backend_acquire_lock_resources',
    'Transaction',
    'UPDLOCK, HOLDLOCK',
    "['new', 'reviewing', 'waitlisted']",
    'reserved_yuva_id',
    'next_yuva_id',
    'sqlsrv_resolve_user',
    'sqlsrv_resolve_parent',
    'sqlsrv_link_student_parent',
    'registration.approved',
] as $contract) {
    backend_test_assert(
        str_contains($approvalSource, $contract),
        'SQL approval contract is missing: ' . $contract
    );
}
foreach (['LIMIT ', 'FOR UPDATE', 'UTC_TIMESTAMP', 'lastInsertId'] as $mysqlSql) {
    backend_test_assert(
        !str_contains($approvalSource, $mysqlSql),
        'SQL Server approval contains MySQL-only behavior: ' . $mysqlSql
    );
}

foreach ([
    'UPDATE dbo.yuva_id_counters WITH (UPDLOCK, HOLDLOCK)',
    'OUTPUT INSERTED.last_number',
    'YUVA ID counter is not initialized',
    'OUTPUT INSERTED.id',
    '@students.invalid',
    'incompatible account role',
    'SELECT TOP (1) student_id',
] as $contract) {
    backend_test_assert(
        str_contains($repositorySource, $contract),
        'Repository contract is missing: ' . $contract
    );
}

foreach ([
    'SELECT TOP (1) * FROM users',
    'OUTPUT INSERTED.id',
    'DATEADD(HOUR, 48, SYSUTCDATETIME())',
    'db_now_sql()',
] as $contract) {
    backend_test_assert(
        str_contains($authSource, $contract),
        'Azure SQL auth contract is missing: ' . $contract
    );
}

backend_test_assert(
    str_contains(
        backend_test_function_source('approve_registration'),
        "db_driver() === 'sqlsrv'"
    ),
    'Public approval must route SQL Server without changing its signature.'
);
backend_test_assert(
    str_contains(
        backend_test_function_source('approve_registration_mysql'),
        'FOR UPDATE'
    ),
    'The existing MySQL approval path must remain isolated and available.'
);

$registrationLockPosition = strpos(
    $approvalSource,
    "'yuva-registration-approval:'"
);
$identityLockPosition = strpos(
    $approvalSource,
    'backend_acquire_lock_resources'
);
$registrationRowLockPosition = strpos(
    $approvalSource,
    'FROM dbo.registrations WITH (UPDLOCK, HOLDLOCK)'
);
backend_test_assert(
    $registrationLockPosition !== false
    && $identityLockPosition !== false
    && $registrationRowLockPosition !== false
    && $registrationLockPosition < $identityLockPosition
    && $identityLockPosition < $registrationRowLockPosition,
    'Approval lock order must be registration, identities, then registration row.'
);

fwrite(STDOUT, "PASS Azure SQL database helper contract\n");
fwrite(STDOUT, "PASS deterministic hashed identity lock ordering\n");
fwrite(STDOUT, "PASS duplicate identity lock elimination\n");
fwrite(STDOUT, "PASS identity lock failure stops approval\n");
fwrite(STDOUT, "PASS SQL Server approval query compatibility\n");
fwrite(STDOUT, "PASS reserved YUVA ID preservation contract\n");
fwrite(STDOUT, "PASS deterministic internal student identity\n");
fwrite(STDOUT, "PASS role-conflict protection contract\n");
fwrite(STDOUT, "PASS idempotency and relationship guards\n");
fwrite(STDOUT, "PASS auth.php Azure SQL compatibility\n");
fwrite(STDOUT, "PASS legacy MySQL path isolation\n");
