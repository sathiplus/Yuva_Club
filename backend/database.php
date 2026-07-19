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

    public static function transaction(
        callable $callback,
        ?string $isolationLevel = null,
        bool $xactAbort = false
    ): mixed {
        $pdo = self::connection();
        $driver = db_driver_name($pdo);
        $isolation = $isolationLevel === null
            ? null
            : db_transaction_isolation_sql($isolationLevel);

        if ($driver === 'sqlsrv') {
            if ($xactAbort) {
                $pdo->exec('SET XACT_ABORT ON');
            }
            if ($isolation !== null) {
                $pdo->exec('SET TRANSACTION ISOLATION LEVEL ' . $isolation);
            }
        }

        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $error) {
            db_safe_rollback($pdo);
            throw $error;
        } finally {
            if ($driver === 'sqlsrv') {
                try {
                    if ($isolation !== null) {
                        $pdo->exec(
                            'SET TRANSACTION ISOLATION LEVEL READ COMMITTED'
                        );
                    }
                    if ($xactAbort) {
                        $pdo->exec('SET XACT_ABORT OFF');
                    }
                } catch (Throwable) {
                    // Preserve the original transaction result or exception.
                }
            }
        }
    }
}

function db(): PDO {
    return Database::connection();
}

function db_driver(): string {
    return app_config()['database']['driver'] ?? 'mysql';
}

function db_driver_name(?PDO $pdo = null): string {
    if ($pdo instanceof PDO) {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (is_string($driver) && $driver !== '') {
            return strtolower($driver);
        }
    }
    return strtolower(db_driver());
}

function db_is_sqlsrv(?PDO $pdo = null): bool {
    return db_driver_name($pdo) === 'sqlsrv';
}

function db_now_sql(): string {
    return db_driver() === 'sqlsrv' ? 'SYSUTCDATETIME()' : 'UTC_TIMESTAMP()';
}

function db_identity_sql(): string {
    return db_driver() === 'sqlsrv' ? 'SELECT CONVERT(BIGINT, SCOPE_IDENTITY())' : 'SELECT LAST_INSERT_ID()';
}

function db_transaction_isolation_sql(string $isolationLevel): string {
    $normalized = strtoupper(trim($isolationLevel));
    $allowed = [
        'READ UNCOMMITTED',
        'READ COMMITTED',
        'REPEATABLE READ',
        'SNAPSHOT',
        'SERIALIZABLE',
    ];
    if (!in_array($normalized, $allowed, true)) {
        throw new InvalidArgumentException('Unsupported transaction isolation level.');
    }
    return $normalized;
}

function db_acquire_application_lock(
    PDO $pdo,
    string $resource,
    int $timeoutMilliseconds = 0,
    string $owner = 'Transaction'
): void {
    if (!db_is_sqlsrv($pdo)) {
        return;
    }
    if (!in_array($owner, ['Transaction', 'Session'], true)) {
        throw new InvalidArgumentException('Unsupported SQL application-lock owner.');
    }

    $stmt = $pdo->prepare(
        "DECLARE @lock_result INT;
         EXEC @lock_result = sys.sp_getapplock
             @Resource = :resource,
             @LockMode = N'Exclusive',
             @LockOwner = :lock_owner,
             @LockTimeout = :lock_timeout,
             @DbPrincipal = N'public';
         SELECT @lock_result;"
    );
    $stmt->bindValue(':resource', $resource, PDO::PARAM_STR);
    $stmt->bindValue(':lock_owner', $owner, PDO::PARAM_STR);
    $stmt->bindValue(':lock_timeout', $timeoutMilliseconds, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn();
    if ($result === false || (int) $result < 0) {
        throw new RuntimeException('The requested database operation is already in progress.');
    }
}

function db_inserted_id(PDO $pdo, PDOStatement $statement): int {
    if (db_is_sqlsrv($pdo)) {
        $id = $statement->fetchColumn();
    } else {
        $id = $pdo->lastInsertId();
    }
    if ($id === false || !is_numeric($id) || (int) $id < 1) {
        throw new RuntimeException('Database insert did not return an identity.');
    }
    return (int) $id;
}

function db_safe_rollback(PDO $pdo): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
