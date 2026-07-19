<?php
declare(strict_types=1);

require_once __DIR__ . '/../../tools/run-azure-sql-migrations.php';

function integration_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function integration_connection(): PDO {
    $config = app_config()['database'];
    $dsn = sprintf(
        'sqlsrv:Server=tcp:%s,%s;Database=%s;Encrypt=yes;TrustServerCertificate=no;ConnectionPooling=0',
        $config['host'],
        $config['port'] ?: '1433',
        $config['name']
    );
    return new PDO(
        $dsn,
        $config['user'],
        $config['password'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

if (env_value('YUVA_RUN_SQL_INTEGRATION') !== 'YES') {
    fwrite(STDERR, "SKIP Set YUVA_RUN_SQL_INTEGRATION=YES for the isolated SQL test.\n");
    exit(2);
}
if (app_environment() !== 'test') {
    throw new RuntimeException('Integration migrations require APP_ENV=test.');
}

$databaseName = app_config()['database']['name'] ?? '';
if (!preg_match('/(test|ci|scratch|temp)/i', $databaseName)) {
    throw new RuntimeException('The isolated database name must identify it as test data.');
}

$directory = realpath(__DIR__ . '/../../database');
if ($directory === false) {
    throw new RuntimeException('Migration directory is unavailable.');
}

$pdo = db();
migration_assert_sqlsrv($pdo);
$requiredTables = ['programs', 'users', 'students', 'registrations', 'schema_migrations'];
$presence = migration_table_presence($pdo, $requiredTables);
if (count(array_filter($presence)) !== 0) {
    throw new RuntimeException('Blank-database validation requires a new empty test database.');
}

$migrations = migration_discover($directory);
$firstRun = migration_run($pdo, $migrations);
integration_assert(
    in_array('01-schema.azure-sql.sql', $firstRun['applied'], true),
    'The blank database did not receive the baseline migration.'
);
integration_assert(
    in_array('02-schema-migrations.azure-sql.sql', $firstRun['applied'], true),
    'The blank database did not record the migration ledger migration.'
);

$secondRun = migration_run($pdo, $migrations);
integration_assert(
    count($secondRun['skipped']) === count($migrations),
    'The second run was not idempotent.'
);

$lockConnection = integration_connection();
migration_acquire_lock($pdo);
try {
    $lockWasRejected = false;
    try {
        migration_acquire_lock($lockConnection);
    } catch (RuntimeException $error) {
        $lockWasRejected = str_contains($error->getMessage(), 'application lock');
    }
    integration_assert($lockWasRejected, 'A concurrent migration lock was not rejected.');
} finally {
    migration_release_lock($pdo);
}

$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR
    . 'yuva-migration-integration-' . bin2hex(random_bytes(8));
if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
    throw new RuntimeException('Temporary migration directory could not be created.');
}

try {
    foreach ($migrations as $migration) {
        $target = $temporaryDirectory . DIRECTORY_SEPARATOR . $migration['filename'];
        copy($migration['path'], $target);
    }
    file_put_contents(
        $temporaryDirectory . DIRECTORY_SEPARATOR . '02-schema-migrations.azure-sql.sql',
        "\n-- intentional checksum change for validation\n",
        FILE_APPEND
    );
    $changedMigrations = migration_discover($temporaryDirectory);

    $checksumWasRejected = false;
    try {
        migration_run($pdo, $changedMigrations);
    } catch (RuntimeException $error) {
        $checksumWasRejected = str_contains($error->getMessage(), 'Applied migration has changed');
    }
    integration_assert($checksumWasRejected, 'A changed applied checksum was not rejected.');
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

fwrite(STDOUT, "PASS blank-database migration\n");
fwrite(STDOUT, "PASS rerun idempotency\n");
fwrite(STDOUT, "PASS checksum mismatch refusal\n");
fwrite(STDOUT, "PASS SQL Server application lock exclusion\n");
