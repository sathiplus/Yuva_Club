<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/database.php';

const YUVA_MIGRATION_LOCK_RESOURCE = 'yuva-club-schema-migrations';
const YUVA_MIGRATION_LOCK_TIMEOUT_MS = 0;

/**
 * @return list<array{version:string,filename:string,name:string,path:string,checksum:string}>
 */
function migration_discover(string $directory): array {
    if (!is_dir($directory)) {
        throw new RuntimeException('Migration directory does not exist.');
    }

    $paths = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.azure-sql.sql');
    if ($paths === false) {
        throw new RuntimeException('Migration directory could not be scanned.');
    }

    sort($paths, SORT_STRING);
    $migrations = [];
    $versions = [];
    foreach ($paths as $path) {
        $filename = basename($path);
        if (!preg_match('/^([0-9]{2,})-([a-z0-9][a-z0-9-]*)\.azure-sql\.sql$/', $filename, $matches)) {
            throw new RuntimeException('Invalid migration filename: ' . $filename);
        }

        $version = $matches[1];
        if (isset($versions[$version])) {
            throw new RuntimeException('Duplicate migration version: ' . $version);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Migration file could not be read: ' . $filename);
        }

        $canonicalContents = str_replace(["\r\n", "\r"], "\n", $contents);
        $migrations[] = [
            'version' => $version,
            'filename' => $filename,
            'name' => str_replace('-', ' ', $matches[2]),
            'path' => $path,
            'checksum' => hash('sha256', $canonicalContents),
        ];
        $versions[$version] = true;
    }

    if ($migrations === []) {
        throw new RuntimeException('No Azure SQL migration files were found.');
    }

    usort(
        $migrations,
        static fn(array $left, array $right): int =>
            strnatcmp($left['filename'], $right['filename'])
    );

    return $migrations;
}

/**
 * Split SQL Server scripts only on a line containing GO.
 *
 * @return list<string>
 */
function migration_sql_batches(string $sql): array {
    $parts = preg_split('/^\s*GO\s*;?\s*$/mi', $sql);
    if ($parts === false) {
        throw new RuntimeException('Migration SQL could not be parsed.');
    }

    $batches = [];
    foreach ($parts as $part) {
        $batch = trim($part);
        if ($batch !== '') {
            $batches[] = $batch;
        }
    }
    return $batches;
}

function migration_assert_sqlsrv(PDO $pdo): void {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlsrv') {
        throw new RuntimeException('The migration runner requires the PDO SQL Server driver.');
    }
}

function migration_assert_safe_environment(bool $allowProduction): void {
    if (app_environment() !== 'production') {
        return;
    }

    $confirmed = env_value('YUVA_ALLOW_PRODUCTION_MIGRATIONS') === 'YES';
    if (!$allowProduction || !$confirmed) {
        throw new RuntimeException(
            'Production migrations are disabled. Both --allow-production and '
            . 'YUVA_ALLOW_PRODUCTION_MIGRATIONS=YES are required.'
        );
    }
}

function migration_acquire_lock(PDO $pdo, int $timeoutMs = YUVA_MIGRATION_LOCK_TIMEOUT_MS): void {
    $statement = $pdo->prepare(
        "DECLARE @lock_result INT;
         EXEC @lock_result = sys.sp_getapplock
             @Resource = :resource,
             @LockMode = 'Exclusive',
             @LockOwner = 'Session',
             @LockTimeout = :timeout_ms,
             @DbPrincipal = 'public';
         SELECT @lock_result AS lock_result;"
    );
    $statement->bindValue(':resource', YUVA_MIGRATION_LOCK_RESOURCE, PDO::PARAM_STR);
    $statement->bindValue(':timeout_ms', $timeoutMs, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetchColumn();
    $statement->closeCursor();

    if ($result === false || (int) $result < 0) {
        throw new RuntimeException('Another migration process holds the application lock.');
    }
}

function migration_release_lock(PDO $pdo): void {
    $statement = $pdo->prepare(
        "DECLARE @lock_result INT;
         EXEC @lock_result = sys.sp_releaseapplock
             @Resource = :resource,
             @LockOwner = 'Session',
             @DbPrincipal = 'public';
         SELECT @lock_result AS lock_result;"
    );
    $statement->execute(['resource' => YUVA_MIGRATION_LOCK_RESOURCE]);
    $statement->closeCursor();
}

function migration_ledger_exists(PDO $pdo): bool {
    $statement = $pdo->query(
        "SELECT CASE WHEN OBJECT_ID(N'dbo.schema_migrations', N'U') IS NULL THEN 0 ELSE 1 END"
    );
    return (int) $statement->fetchColumn() === 1;
}

/**
 * @param list<array{version:string,filename:string,name:string,path:string,checksum:string}> $migrations
 */
function migration_bootstrap_ledger(PDO $pdo, array $migrations): void {
    if (migration_ledger_exists($pdo)) {
        return;
    }

    $ledgerMigration = null;
    foreach ($migrations as $migration) {
        if ($migration['filename'] === '02-schema-migrations.azure-sql.sql') {
            $ledgerMigration = $migration;
            break;
        }
    }
    if ($ledgerMigration === null) {
        throw new RuntimeException('The schema migration ledger bootstrap file is missing.');
    }

    $sql = file_get_contents($ledgerMigration['path']);
    if ($sql === false) {
        throw new RuntimeException('The schema migration ledger bootstrap file could not be read.');
    }

    $pdo->beginTransaction();
    try {
        foreach (migration_sql_batches($sql) as $batch) {
            $pdo->exec($batch);
        }
        if (!migration_ledger_exists($pdo)) {
            throw new RuntimeException('The schema migration ledger was not created.');
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/**
 * @return array<string,array{filename:string,name:string,checksum:string,applied_at:string}>
 */
function migration_applied(PDO $pdo): array {
    if (!migration_ledger_exists($pdo)) {
        return [];
    }

    $rows = $pdo->query(
        'SELECT version, filename, migration_name, checksum_sha256, applied_at
         FROM dbo.schema_migrations
         ORDER BY version'
    )->fetchAll();

    $applied = [];
    foreach ($rows as $row) {
        $applied[(string) $row['version']] = [
            'filename' => (string) $row['filename'],
            'name' => (string) $row['migration_name'],
            'checksum' => strtolower((string) $row['checksum_sha256']),
            'applied_at' => (string) $row['applied_at'],
        ];
    }
    return $applied;
}

/**
 * @param list<string> $tableNames
 * @return array<string,bool>
 */
function migration_table_presence(PDO $pdo, array $tableNames): array {
    $statement = $pdo->prepare(
        "SELECT COUNT_BIG(*)
         FROM sys.tables AS tables
         INNER JOIN sys.schemas AS schemas ON schemas.schema_id = tables.schema_id
         WHERE schemas.name = N'dbo' AND tables.name = :table_name"
    );
    $presence = [];
    foreach ($tableNames as $tableName) {
        $statement->execute(['table_name' => $tableName]);
        $presence[$tableName] = (int) $statement->fetchColumn() === 1;
        $statement->closeCursor();
    }
    return $presence;
}

function migration_should_adopt_baseline(PDO $pdo, string $version): bool {
    if ($version !== '01') {
        return false;
    }

    $requiredTables = [
        'programs',
        'levels',
        'users',
        'students',
        'parents',
        'student_parents',
        'registrations',
        'sessions',
        'topic_categories',
        'topics',
        'student_topic_selections',
        'presentation_submissions',
        'files',
        'attendance',
        'evaluations',
        'badges',
        'student_badges',
        'student_points',
        'certificates',
        'safety_reports',
        'activity_logs',
        'email_notifications',
    ];
    $presence = migration_table_presence($pdo, $requiredTables);
    $existingCount = count(array_filter($presence));

    if ($existingCount === 0) {
        return false;
    }
    if ($existingCount !== count($requiredTables)) {
        throw new RuntimeException(
            'The database contains a partial legacy baseline; automatic adoption is unsafe.'
        );
    }
    return true;
}

/**
 * @param array{version:string,filename:string,name:string,path:string,checksum:string} $migration
 */
function migration_assert_checksum(array $migration, array $applied): void {
    if (!isset($applied[$migration['version']])) {
        return;
    }

    $record = $applied[$migration['version']];
    if (
        !hash_equals($record['checksum'], strtolower($migration['checksum']))
        || $record['filename'] !== $migration['filename']
    ) {
        throw new RuntimeException(
            'Applied migration has changed: ' . $migration['filename']
        );
    }
}

/**
 * @param array{version:string,filename:string,name:string,path:string,checksum:string} $migration
 */
function migration_record(PDO $pdo, array $migration): void {
    $statement = $pdo->prepare(
        'INSERT INTO dbo.schema_migrations
            (version, filename, migration_name, checksum_sha256, applied_at)
         VALUES
            (:version, :filename, :migration_name, :checksum, SYSUTCDATETIME())'
    );
    $statement->execute([
        'version' => $migration['version'],
        'filename' => $migration['filename'],
        'migration_name' => $migration['name'],
        'checksum' => strtolower($migration['checksum']),
    ]);
}

/**
 * @param array{version:string,filename:string,name:string,path:string,checksum:string} $migration
 */
function migration_apply(PDO $pdo, array $migration, bool $adopt): void {
    $sql = file_get_contents($migration['path']);
    if ($sql === false) {
        throw new RuntimeException('Migration file could not be read: ' . $migration['filename']);
    }

    $pdo->beginTransaction();
    try {
        if (!$adopt) {
            foreach (migration_sql_batches($sql) as $batch) {
                $pdo->exec($batch);
            }
        }

        if (!migration_ledger_exists($pdo)) {
            if ($migration['version'] === '01') {
                $pdo->commit();
                return;
            }
            throw new RuntimeException('The schema migration ledger was not created.');
        }

        migration_record($pdo, $migration);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

/**
 * @param list<array{version:string,filename:string,name:string,path:string,checksum:string}> $migrations
 * @return array{applied:list<string>,adopted:list<string>,skipped:list<string>}
 */
function migration_run(PDO $pdo, array $migrations): array {
    migration_assert_sqlsrv($pdo);
    migration_acquire_lock($pdo);

    $result = ['applied' => [], 'adopted' => [], 'skipped' => []];
    try {
        migration_bootstrap_ledger($pdo, $migrations);
        $applied = migration_applied($pdo);
        foreach ($migrations as $migration) {
            migration_assert_checksum($migration, $applied);
            if (isset($applied[$migration['version']])) {
                $result['skipped'][] = $migration['filename'];
                continue;
            }

            $adopt = migration_should_adopt_baseline($pdo, $migration['version']);
            migration_apply($pdo, $migration, $adopt);

            if ($adopt) {
                $result['adopted'][] = $migration['filename'];
            } else {
                $result['applied'][] = $migration['filename'];
            }
            $applied = migration_applied($pdo);
        }
    } finally {
        migration_release_lock($pdo);
    }

    return $result;
}

function migration_safe_error_message(Throwable $error): string {
    if ($error instanceof PDOException) {
        return 'A database operation failed. Connection details were withheld.';
    }

    $message = $error->getMessage();
    $config = app_config();
    $sensitiveValues = [
        $config['database']['password'] ?? '',
        $config['database']['user'] ?? '',
        $config['database']['host'] ?? '',
        $config['database']['name'] ?? '',
    ];
    foreach ($sensitiveValues as $value) {
        if (is_string($value) && $value !== '') {
            $message = str_replace($value, '[redacted]', $message);
        }
    }
    return $message;
}

function migration_cli_main(array $arguments): int {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        return 1;
    }

    $allowProduction = in_array('--allow-production', $arguments, true);
    $directory = realpath(__DIR__ . '/../database');
    if ($directory === false) {
        fwrite(STDERR, "Migration directory is unavailable.\n");
        return 1;
    }

    try {
        migration_assert_safe_environment($allowProduction);
        $migrations = migration_discover($directory);
        $result = migration_run(db(), $migrations);

        foreach ($result['adopted'] as $filename) {
            fwrite(STDOUT, "ADOPTED  {$filename}\n");
        }
        foreach ($result['applied'] as $filename) {
            fwrite(STDOUT, "APPLIED  {$filename}\n");
        }
        foreach ($result['skipped'] as $filename) {
            fwrite(STDOUT, "CURRENT  {$filename}\n");
        }
        fwrite(STDOUT, "Migration run completed successfully.\n");
        return 0;
    } catch (Throwable $error) {
        fwrite(STDERR, 'Migration failed: ' . migration_safe_error_message($error) . PHP_EOL);
        return 1;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(migration_cli_main($argv));
}
