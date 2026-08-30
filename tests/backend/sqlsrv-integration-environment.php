<?php
declare(strict_types=1);

/** @param array<string,string|false> $environment
 *  @return array{server:string,database:string,user:string,password:string}
 */
function yuva_sqlsrv_test_configuration(array $environment): array
{
    $required = [
        'YUVA_TEST_DB_SERVER', 'YUVA_TEST_DB_DATABASE',
        'YUVA_TEST_DB_USER', 'YUVA_TEST_DB_PASSWORD',
    ];
    foreach ($required as $name) {
        if (!isset($environment[$name]) || trim((string)$environment[$name]) === '') {
            throw new RuntimeException('Missing protected SQL integration setting: ' . $name);
        }
    }

    $server = strtolower(trim((string)$environment['YUVA_TEST_DB_SERVER']));
    $database = trim((string)$environment['YUVA_TEST_DB_DATABASE']);
    $user = trim((string)$environment['YUVA_TEST_DB_USER']);
    if (!in_array($server, ['127.0.0.1', 'localhost'], true)) {
        throw new RuntimeException('SQL integration tests require the local disposable CI server.');
    }
    if (strcasecmp($database, 'yuva_club') === 0
        || preg_match('/^yuva_club_ci_[0-9]+_[0-9]+$/', $database) !== 1) {
        throw new RuntimeException('Unsafe SQL integration database name.');
    }
    if (preg_match('/^yuva_ci_runner_[0-9]+_[0-9]+$/', $user) !== 1
        || in_array(strtolower($user), ['sa', 'yuvaadmin', 'admin'], true)) {
        throw new RuntimeException('Unsafe SQL integration database user.');
    }
    if (($environment['YUVA_TEST_DB_EPHEMERAL'] ?? '') !== 'YES') {
        throw new RuntimeException('Disposable SQL integration confirmation is required.');
    }

    return [
        'server' => $server,
        'database' => $database,
        'user' => $user,
        'password' => (string)$environment['YUVA_TEST_DB_PASSWORD'],
    ];
}

function yuva_configure_sqlsrv_integration_environment(): array
{
    $names = [
        'YUVA_TEST_DB_SERVER', 'YUVA_TEST_DB_DATABASE', 'YUVA_TEST_DB_USER',
        'YUVA_TEST_DB_PASSWORD', 'YUVA_TEST_DB_EPHEMERAL',
    ];
    $environment = [];
    foreach ($names as $name) {
        $environment[$name] = getenv($name);
    }
    $config = yuva_sqlsrv_test_configuration($environment);
    putenv('APP_ENV=test');
    putenv('DB_DRIVER=sqlsrv');
    putenv('DB_HOST=' . $config['server']);
    putenv('DB_PORT=1433');
    putenv('DB_DATABASE=' . $config['database']);
    putenv('DB_USERNAME=' . $config['user']);
    putenv('DB_PASSWORD=' . $config['password']);
    return $config;
}

function yuva_assert_sqlsrv_integration_identity(PDO $pdo, array $config): void
{
    if ((string)$pdo->query('SELECT DB_NAME()')->fetchColumn() !== $config['database']) {
        throw new RuntimeException('Connected SQL integration database identity is unsafe.');
    }
    if ((string)$pdo->query('SELECT USER_NAME()')->fetchColumn() !== $config['user']) {
        throw new RuntimeException('Connected SQL integration user identity is unsafe.');
    }
}
