<?php
declare(strict_types=1);

require_once __DIR__ . '/../../tools/run-azure-sql-migrations.php';

function test_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function test_expect_exception(callable $callback, string $expectedMessage): void {
    try {
        $callback();
    } catch (Throwable $error) {
        test_assert(
            str_contains($error->getMessage(), $expectedMessage),
            'Unexpected exception: ' . $error->getMessage()
        );
        return;
    }
    throw new RuntimeException('Expected exception was not thrown: ' . $expectedMessage);
}

$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR
    . 'yuva-migration-test-' . bin2hex(random_bytes(8));
if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
    throw new RuntimeException('Test directory could not be created.');
}

try {
    $repositoryMigrations = migration_discover(__DIR__ . '/../../database');
    test_assert(
        array_column($repositoryMigrations, 'filename') === [
            '01-schema.azure-sql.sql',
            '02-schema-migrations.azure-sql.sql',
            '03-phase-a-identity-approval.azure-sql.sql',
            '04-phase-a-portal-student-view.azure-sql.sql',
        ],
        'Repository migrations are not discovered in deterministic order.'
    );

    $identitySql = file_get_contents(
        __DIR__ . '/../../database/03-phase-a-identity-approval.azure-sql.sql'
    );
    $portalViewSql = file_get_contents(
        __DIR__ . '/../../database/04-phase-a-portal-student-view.azure-sql.sql'
    );
    test_assert($identitySql !== false, 'Phase A identity migration is missing.');
    test_assert($portalViewSql !== false, 'Phase A portal view migration is missing.');

    foreach (
        [
            "COL_LENGTH(N'dbo.registrations', N'reserved_yuva_id') IS NULL",
            "COL_LENGTH(N'dbo.registrations', N'approval_error_code') IS NULL",
            "COL_LENGTH(N'dbo.registrations', N'approval_attempted_at') IS NULL",
            "OBJECT_ID(N'dbo.yuva_id_counters', N'U') IS NULL",
            'WHERE reserved_yuva_id IS NOT NULL',
        ] as $requiredIdentitySql
    ) {
        test_assert(
            str_contains($identitySql, $requiredIdentitySql),
            'Phase A identity migration is missing: ' . $requiredIdentitySql
        );
    }

    $expectedIdentityIndexes = [
        'uq_registrations_reserved_yuva_id',
        'idx_registrations_status_submitted',
        'idx_registrations_parent_approval_lookup',
        'idx_registrations_student_portal_lookup',
        'idx_students_yuva_id_lookup',
        'idx_student_parents_primary_lookup',
    ];
    foreach ($expectedIdentityIndexes as $indexName) {
        test_assert(
            substr_count($identitySql, "name = N'{$indexName}'") === 1,
            'Phase A index does not have exactly one idempotent guard: ' . $indexName
        );
        test_assert(
            substr_count(
                $identitySql,
                'CREATE '
                    . ($indexName === 'uq_registrations_reserved_yuva_id' ? 'UNIQUE ' : '')
                    . "INDEX {$indexName}"
            ) === 1,
            'Phase A index does not have exactly one definition: ' . $indexName
        );
    }
    test_assert(
        substr_count($identitySql, 'FROM sys.indexes') === count($expectedIdentityIndexes),
        'Every Phase A index must have its own sys.indexes guard.'
    );
    foreach (
        [
            'student_id,',
            'reviewed_at DESC,',
            'submitted_at DESC,',
            'id DESC',
            'INCLUDE (status)',
        ] as $studentPortalIndexSql
    ) {
        test_assert(
            str_contains($identitySql, $studentPortalIndexSql),
            'Student portal registration index is missing: ' . $studentPortalIndexSql
        );
    }

    foreach (
        [
            'CREATE OR ALTER VIEW dbo.vw_portal_students',
            'student.yuva_id AS yuva_id',
            'FROM dbo.students AS student',
            'SELECT TOP (1)',
            'student_parent.is_primary DESC',
            'student_parent.created_at',
            'student_parent.parent_id',
        ] as $requiredViewSql
    ) {
        test_assert(
            str_contains($portalViewSql, $requiredViewSql),
            'Phase A portal view migration is missing: ' . $requiredViewSql
        );
    }

    $phaseASql = $identitySql . "\n" . $portalViewSql;
    foreach (
        [
            '/\bLIMIT\b/i',
            '/\bUTC_TIMESTAMP\s*\(/i',
            '/\bAUTO_INCREMENT\b/i',
            '/`[A-Za-z_][A-Za-z0-9_]*`/',
            '/\bSELECT\b[\s\S]*\bFOR\s+UPDATE\b/i',
            '/\b(?:DROP|TRUNCATE)\s+(?:TABLE|VIEW)\b/i',
        ] as $forbiddenSqlPattern
    ) {
        test_assert(
            preg_match($forbiddenSqlPattern, $phaseASql) !== 1,
            'Phase A migrations contain forbidden SQL: ' . $forbiddenSqlPattern
        );
    }

    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . '10-third.azure-sql.sql',
        "SELECT 3;\r\n"
    );
    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . '02-second.azure-sql.sql',
        "SELECT 2;\n"
    );
    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . '01-first.azure-sql.sql',
        "SELECT 1;\n"
    );
    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . 'notes.sql',
        "SELECT 'ignored';\n"
    );

    $migrations = migration_discover($temporaryDirectory);
    test_assert(
        array_column($migrations, 'filename') === [
            '01-first.azure-sql.sql',
            '02-second.azure-sql.sql',
            '10-third.azure-sql.sql',
        ],
        'Migration discovery order is not deterministic.'
    );

    $expectedChecksum = hash('sha256', "SELECT 3;\n");
    test_assert(
        $migrations[2]['checksum'] === $expectedChecksum,
        'Checksums must use canonical LF line endings.'
    );

    $batches = migration_sql_batches("SELECT 1;\nGO\nSELECT 2;\ngo;\nSELECT 3;");
    test_assert(
        $batches === ['SELECT 1;', 'SELECT 2;', 'SELECT 3;'],
        'GO batch parsing failed.'
    );

    migration_assert_checksum(
        $migrations[0],
        [
            '01' => [
                'filename' => $migrations[0]['filename'],
                'name' => $migrations[0]['name'],
                'checksum' => $migrations[0]['checksum'],
                'applied_at' => '2026-01-01 00:00:00',
            ],
        ]
    );

    test_expect_exception(
        static function () use ($migrations): void {
            migration_assert_checksum(
                $migrations[0],
                [
                    '01' => [
                        'filename' => $migrations[0]['filename'],
                        'name' => $migrations[0]['name'],
                        'checksum' => str_repeat('0', 64),
                        'applied_at' => '2026-01-01 00:00:00',
                    ],
                ]
            );
        },
        'Applied migration has changed'
    );

    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . '01-duplicate.azure-sql.sql',
        "SELECT 99;\n"
    );
    test_expect_exception(
        static fn() => migration_discover($temporaryDirectory),
        'Duplicate migration version'
    );
    unlink($temporaryDirectory . DIRECTORY_SEPARATOR . '01-duplicate.azure-sql.sql');

    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid_name.azure-sql.sql',
        "SELECT 100;\n"
    );
    test_expect_exception(
        static fn() => migration_discover($temporaryDirectory),
        'Invalid migration filename'
    );
    unlink($temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid_name.azure-sql.sql');

    $ledgerSql = file_get_contents(
        __DIR__ . '/../../database/02-schema-migrations.azure-sql.sql'
    );
    test_assert($ledgerSql !== false, 'The migration ledger SQL is missing.');
    foreach (
        [
            "OBJECT_ID(N'dbo.schema_migrations', N'U') IS NULL",
            'version NVARCHAR(64)',
            'filename NVARCHAR(260)',
            'migration_name NVARCHAR(260)',
            'checksum_sha256 CHAR(64)',
            'applied_at DATETIME2(7)',
        ] as $requiredSql
    ) {
        test_assert(
            str_contains($ledgerSql, $requiredSql),
            'The migration ledger is missing: ' . $requiredSql
        );
    }

    putenv('DB_HOST=private-database-host');
    putenv('DB_DATABASE=private-database-name');
    putenv('DB_USERNAME=private-database-user');
    putenv('DB_PASSWORD=private-database-password');
    $safeDatabaseError = migration_safe_error_message(
        new PDOException(
            'sqlsrv:Server=private-database-host;Database=private-database-name;'
            . 'UID=private-database-user;PWD=private-database-password'
        )
    );
    foreach (
        [
            'private-database-host',
            'private-database-name',
            'private-database-user',
            'private-database-password',
            'sqlsrv:Server',
        ] as $forbiddenOutput
    ) {
        test_assert(
            !str_contains($safeDatabaseError, $forbiddenOutput),
            'Database exception output exposed connection information.'
        );
    }

    fwrite(STDOUT, "PASS migration discovery\n");
    fwrite(STDOUT, "PASS Phase A repository migration order\n");
    fwrite(STDOUT, "PASS Phase A idempotency guards\n");
    fwrite(STDOUT, "PASS Phase A Azure SQL compatibility\n");
    fwrite(STDOUT, "PASS Phase A non-destructive SQL policy\n");
    fwrite(STDOUT, "PASS deterministic ordering\n");
    fwrite(STDOUT, "PASS canonical SHA-256 checksum\n");
    fwrite(STDOUT, "PASS SQL Server GO batch parsing\n");
    fwrite(STDOUT, "PASS checksum mismatch refusal\n");
    fwrite(STDOUT, "PASS duplicate version refusal\n");
    fwrite(STDOUT, "PASS invalid filename refusal\n");
    fwrite(STDOUT, "PASS idempotent ledger definition\n");
    fwrite(STDOUT, "PASS database exception redaction\n");
} finally {
    $files = glob($temporaryDirectory . DIRECTORY_SEPARATOR . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    rmdir($temporaryDirectory);
}
