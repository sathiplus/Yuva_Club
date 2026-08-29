<?php
declare(strict_types=1);

require_once __DIR__ . '/sqlsrv-integration-environment.php';

function safety_assert_throws(array $environment, string $message): void
{
    try {
        yuva_sqlsrv_test_configuration($environment);
    } catch (RuntimeException) {
        return;
    }
    throw new RuntimeException($message);
}

$valid = [
    'YUVA_TEST_DB_SERVER' => '127.0.0.1',
    'YUVA_TEST_DB_DATABASE' => 'yuva_club_ci_123_1',
    'YUVA_TEST_DB_USER' => 'yuva_ci_runner_123_1',
    'YUVA_TEST_DB_PASSWORD' => 'ephemeral-value',
    'YUVA_TEST_DB_EPHEMERAL' => 'YES',
];
if (yuva_sqlsrv_test_configuration($valid)['database'] !== 'yuva_club_ci_123_1') {
    throw new RuntimeException('Valid disposable SQL configuration was rejected.');
}
safety_assert_throws(array_replace($valid, ['YUVA_TEST_DB_DATABASE' => 'yuva_club']), 'Production database was accepted.');
safety_assert_throws(array_replace($valid, ['YUVA_TEST_DB_DATABASE' => 'development']), 'Invalid database name was accepted.');
safety_assert_throws(array_replace($valid, ['YUVA_TEST_DB_SERVER' => 'yuvaclub-sql-central.database.windows.net']), 'Remote SQL server was accepted.');
safety_assert_throws(array_replace($valid, ['YUVA_TEST_DB_USER' => 'yuvaadmin']), 'Production user was accepted.');
safety_assert_throws(array_replace($valid, ['YUVA_TEST_DB_EPHEMERAL' => 'NO']), 'Non-disposable environment was accepted.');
$missing = $valid;
unset($missing['YUVA_TEST_DB_PASSWORD']);
safety_assert_throws($missing, 'Missing protected configuration was accepted.');
fwrite(STDOUT, "PASS production database and remote server refusal\n");
fwrite(STDOUT, "PASS test naming, contained-user, and ephemeral guards\n");
