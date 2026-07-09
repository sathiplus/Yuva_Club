<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function database_settings_present(): bool {
    $config = app_config()['database'];
    return ($config['host'] ?? '') !== ''
        && ($config['name'] ?? '') !== ''
        && ($config['user'] ?? '') !== '';
}

final class Database {
    private static ?PDO $pdo = null;

    public static function connection(): PDO {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = app_config()['database'];
        foreach (['host', 'name', 'user'] as $key) {
            if (($config[$key] ?? '') === '') {
                throw new RuntimeException('Missing required database setting: DB_' . strtoupper($key));
            }
        }

        $driver = $config['driver'] ?? 'mysql';
        if ($driver === 'sqlsrv') {
            $dsn = sprintf(
                'sqlsrv:Server=tcp:%s,%s;Database=%s;Encrypt=yes;TrustServerCertificate=no;ConnectionPooling=0',
                $config['host'],
                $config['port'] ?: '1433',
                $config['name']
            );
        } elseif ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'] ?: '3306',
                $config['name']
            );
        } else {
            throw new RuntimeException('Unsupported database driver: ' . $driver);
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'mysql' && ($config['ssl_ca'] ?? '') !== '') {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        self::$pdo = new PDO($dsn, $config['user'], $config['password'] ?? '', $options);
        return self::$pdo;
    }

    public static function transaction(callable $callback): mixed {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}

function db(): PDO {
    return Database::connection();
}

function db_driver(): string {
    return app_config()['database']['driver'] ?? 'mysql';
}

function db_now_sql(): string {
    return db_driver() === 'sqlsrv' ? 'SYSUTCDATETIME()' : 'UTC_TIMESTAMP()';
}

function db_identity_sql(): string {
    return db_driver() === 'sqlsrv' ? 'SELECT CONVERT(BIGINT, SCOPE_IDENTITY())' : 'SELECT LAST_INSERT_ID()';
}
