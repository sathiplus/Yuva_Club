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
