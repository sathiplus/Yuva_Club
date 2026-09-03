<?php
declare(strict_types=1);

require_once __DIR__ . '/run-azure-sql-migrations.php';

const PHASE2C2B_M19_DATABASE = 'yuva_club_quick_scoring_phase2c2b_m19_rehearsal_20260902';
const PHASE2C2B_M19_VERSION = '19';
const PHASE2C2B_M19_FILENAME = '19-quick-challenge-ai-scoring-phase2c2b.azure-sql.sql';

function m19_assert_target(PDO $pdo): void
{
    migration_assert_sqlsrv($pdo);
    $database = (string) $pdo->query('SELECT DB_NAME()')->fetchColumn();
    if (!hash_equals(PHASE2C2B_M19_DATABASE, $database)) {
        throw new RuntimeException('Refusing non-rehearsal database target.');
    }
}

/** @return array{version:string,filename:string,name:string,path:string,checksum:string} */
function m19_definition(): array
{
    foreach (migration_discover(__DIR__ . '/../database') as $migration) {
        if ($migration['version'] === PHASE2C2B_M19_VERSION
            && $migration['filename'] === PHASE2C2B_M19_FILENAME) {
            return $migration;
        }
    }
    throw new RuntimeException('Migration 19 definition is unavailable.');
}

/** @return list<string> */
function m19_versions(PDO $pdo): array
{
    return array_map('strval', $pdo->query(
        'SELECT version FROM dbo.schema_migrations ORDER BY version'
    )->fetchAll(PDO::FETCH_COLUMN));
}

function m19_ledger_exists(PDO $pdo): bool
{
    return (int) $pdo->query(
        "SELECT CASE WHEN OBJECT_ID(N'dbo.schema_migrations', N'U') IS NULL THEN 0 ELSE 1 END"
    )->fetchColumn() === 1;
}

function m19_assert_baseline(PDO $pdo, bool $expectMigration19): void
{
    if (m19_ledger_exists($pdo)) {
        $versions = m19_versions($pdo);
        foreach (range(6, 18) as $version) {
            $expected = str_pad((string) $version, 2, '0', STR_PAD_LEFT);
            if (!in_array($expected, $versions, true)) {
                throw new RuntimeException('Required baseline migration is absent.');
            }
        }
        foreach (['04', '05'] as $skipped) {
            if (in_array($skipped, $versions, true)) {
                throw new RuntimeException('Intentionally skipped migration is unexpectedly present.');
            }
        }
        if (in_array(PHASE2C2B_M19_VERSION, $versions, true) !== $expectMigration19) {
            throw new RuntimeException('Migration 19 ledger state is unexpected.');
        }
        return;
    }

    foreach ([
        'ai_mentor_reviews', 'ai_mentor_delivery_reviews', 'ai_mentor_media_consents',
        'student_public_identity_history', 'organization_student_membership_requests',
        'leadership_decisions', 'presentation_verifications', 'competitions',
        'subscription_plans', 'quick_challenge_templates', 'parent_authentication_tokens',
        'student_registration_credentials',
    ] as $table) {
        if (m19_object_count($pdo, 'U', $table) !== 1) {
            throw new RuntimeException('Required baseline migration object is absent.');
        }
    }
    if (m19_object_count($pdo, 'V', 'vw_portal_students') !== 0
        || m19_object_count($pdo, 'U', 'organizations') !== 0) {
        throw new RuntimeException('Intentionally skipped migration object is unexpectedly present.');
    }
}

function m19_object_count(PDO $pdo, string $type, string $name): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT_BIG(*) FROM sys.objects WHERE [type] = :type AND [name] = :name'
    );
    $statement->execute(['type' => $type, 'name' => $name]);
    return (int) $statement->fetchColumn();
}

function m19_assert_schema(PDO $pdo, bool $present): void
{
    $expected = $present ? 1 : 0;
    foreach (['quick_challenge_scoring_policies', 'quick_challenge_evaluations',
              'quick_challenge_evaluation_audit'] as $table) {
        if (m19_object_count($pdo, 'U', $table) !== $expected) {
            throw new RuntimeException('Migration 19 table state is unexpected.');
        }
    }
    foreach (['ai_evaluation_enabled', 'scoring_policy_id'] as $column) {
        $statement = $pdo->prepare(
            "SELECT COUNT_BIG(*) FROM sys.columns
             WHERE object_id = OBJECT_ID(N'dbo.quick_challenge_template_versions')
               AND [name] = :name"
        );
        $statement->execute(['name' => $column]);
        if ((int) $statement->fetchColumn() !== $expected) {
            throw new RuntimeException('Migration 19 column state is unexpected.');
        }
    }
    if ($present && (int) $pdo->query(
        'SELECT COUNT_BIG(*) FROM dbo.quick_challenge_scoring_policies'
    )->fetchColumn() !== 10) {
        throw new RuntimeException('Seeded scoring-policy count is unexpected.');
    }
}

function m19_apply(PDO $pdo): void
{
    $migration = m19_definition();
    m19_execute_sql_file($pdo, $migration['path']);
    if (m19_ledger_exists($pdo)) {
        $applied = migration_applied($pdo);
        migration_assert_checksum($migration, $applied);
        if (!isset($applied[PHASE2C2B_M19_VERSION])) {
            migration_record($pdo, $migration);
        }
    }
    m19_assert_baseline($pdo, true);
    m19_assert_schema($pdo, true);
}

function m19_execute_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Migration SQL file could not be read.');
    }
    $pdo->beginTransaction();
    try {
        foreach (migration_sql_batches($sql) as $batch) {
            $pdo->exec($batch);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function m19_rollback(PDO $pdo): void
{
    m19_assert_baseline($pdo, true);
    m19_execute_sql_file(
        $pdo,
        __DIR__ . '/../database/19-quick-challenge-ai-scoring-phase2c2b-rollback.sql'
    );
    if (m19_ledger_exists($pdo)) {
        $statement = $pdo->prepare(
            'DELETE FROM dbo.schema_migrations WHERE version = :version'
        );
        $statement->execute(['version' => PHASE2C2B_M19_VERSION]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Migration 19 ledger rollback count is unexpected.');
        }
    }
    m19_assert_baseline($pdo, false);
    m19_assert_schema($pdo, false);
}

function m19_main(array $arguments): int
{
    if (PHP_SAPI !== 'cli') {
        return 1;
    }
    $operation = $arguments[1] ?? '';
    try {
        $pdo = db();
        m19_assert_target($pdo);
        if ($operation === 'baseline') {
            m19_assert_baseline($pdo, false);
            m19_assert_schema($pdo, false);
        } elseif (in_array($operation, ['apply', 'idempotency', 'reapply'], true)) {
            m19_apply($pdo);
        } elseif ($operation === 'rollback') {
            m19_rollback($pdo);
        } elseif ($operation === 'verify') {
            m19_assert_baseline($pdo, true);
            m19_assert_schema($pdo, true);
        } else {
            throw new RuntimeException('Unsupported Migration 19 rehearsal operation.');
        }
        fwrite(STDOUT, 'PASS ' . $operation . PHP_EOL);
        return 0;
    } catch (Throwable $error) {
        $category = $error instanceof PDOException
            ? 'PDO/' . (string) $error->getCode()
            : $error::class;
        fwrite(STDERR, 'FAIL ' . $operation . ' [' . $category . ']: '
            . migration_safe_error_message($error) . PHP_EOL);
        return 1;
    }
}

exit(m19_main($argv));
