<?php
declare(strict_types=1);

require_once __DIR__ . '/run-azure-sql-migrations.php';

$database = trim((string)getenv('YUVA_TEST_DB_DATABASE'));
$server = strtolower(trim((string)getenv('YUVA_TEST_DB_SERVER')));
if (!in_array($server, ['127.0.0.1', 'localhost'], true)
    || preg_match('/^yuva_club_ci_[0-9]+_[0-9]+$/', $database) !== 1
    || strcasecmp($database, 'yuva_club') === 0
    || getenv('YUVA_TEST_DB_EPHEMERAL') !== 'YES') {
    throw new RuntimeException('Unsafe SQL integration bootstrap target.');
}
$adminUser = (string)getenv('YUVA_TEST_DB_ADMIN_USER');
$adminPassword = (string)getenv('YUVA_TEST_DB_ADMIN_PASSWORD');
if ($adminUser === '' || $adminPassword === '') {
    throw new RuntimeException('Missing disposable SQL bootstrap credentials.');
}
putenv('APP_ENV=test');
putenv('DB_DRIVER=sqlsrv');
putenv('DB_HOST=' . $server);
putenv('DB_PORT=1433');
putenv('DB_DATABASE=' . $database);
putenv('DB_USER=' . $adminUser);
putenv('DB_PASSWORD=' . $adminPassword);

$migrations = array_values(array_filter(
    migration_discover(__DIR__ . '/../database'),
    static fn(array $migration): bool => !in_array($migration['version'], ['04', '05'], true)
));
$result = migration_run(Database::connection(), $migrations);
fwrite(STDOUT, json_encode([
    'database' => $database,
    'applied' => count($result['applied']),
    'skipped_historical_versions' => ['04', '05'],
], JSON_THROW_ON_ERROR) . PHP_EOL);
