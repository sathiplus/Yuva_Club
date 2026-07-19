# Azure SQL Migration Framework

## Purpose

The migration framework evolves the existing YUVA Club Azure SQL foundation
through ordered, additive, auditable changes. It does not replace the baseline
schema and does not change application storage authority by itself.

`PORTAL_STORAGE_MODE` remains unchanged. The application continues to use its
configured storage behavior until a separately approved migration phase changes
that setting.

## Filename convention

Migration files live directly under `database/` and use:

```text
NN-description.azure-sql.sql
```

Rules:

- `NN` is a unique, zero-padded numeric version with at least two digits.
- The description uses lowercase letters, digits, and hyphens.
- Files are discovered and applied in natural filename order.
- An applied file is immutable. Corrections require a new migration.
- Only Azure SQL-compatible syntax is allowed.
- Use schema-qualified object names such as `dbo.students`.
- Use `IF OBJECT_ID(...) IS NULL` for additive object creation where appropriate.
- Separate SQL Server batches with a line containing only `GO` when needed.

Current versions:

| Version | File | Purpose |
| --- | --- | --- |
| `01` | `01-schema.azure-sql.sql` | Existing application baseline |
| `02` | `02-schema-migrations.azure-sql.sql` | Migration ledger |

## Migration ledger

`dbo.schema_migrations` records:

- Migration version.
- Filename.
- Human-readable migration name.
- Canonical SHA-256 checksum.
- UTC applied timestamp.

Checksums normalize CRLF and CR line endings to LF before hashing so the same
committed migration has one checksum across supported checkout environments.
If the filename or checksum of an applied version changes, the runner stops
without applying later migrations.

## Baseline compatibility

The original schema predates the ledger:

- On a blank database, the runner bootstraps the ledger, applies the version `01`
  baseline, and records both versions `01` and `02`.
- On an existing database, the runner verifies the required baseline tables
  before adopting version `01` into the ledger. It does not rerun the original
  `CREATE TABLE` statements.
- A partial legacy baseline is refused because automatically adopting or
  repairing an unknown partial schema is unsafe.

Baseline adoption checks the presence of:

- `dbo.programs`
- `dbo.levels`
- `dbo.users`
- `dbo.students`
- `dbo.parents`
- `dbo.student_parents`
- `dbo.registrations`
- `dbo.sessions`
- `dbo.topic_categories`
- `dbo.topics`
- `dbo.student_topic_selections`
- `dbo.presentation_submissions`
- `dbo.files`
- `dbo.attendance`
- `dbo.evaluations`
- `dbo.badges`
- `dbo.student_badges`
- `dbo.student_points`
- `dbo.certificates`
- `dbo.safety_reports`
- `dbo.activity_logs`
- `dbo.email_notifications`

Detailed schema reconciliation remains a separate staging gate before any
production migration.

## Locking and failure behavior

The runner acquires an exclusive SQL Server application lock named
`yuva-club-schema-migrations` for its database session. With the default
zero-millisecond lock timeout, a concurrent runner fails immediately.

Each migration and its ledger record are handled transactionally. A failed
migration is rolled back, later files are not attempted, and the process exits
with a failure status. The runner releases the session lock in all normal error
paths.

Errors redact configured database host, database name, username, and password.
The runner never prints a DSN, connection string, or credential.

## Environment safety

The runner is CLI-only and refuses browser execution.

Production requires both:

```text
--allow-production
YUVA_ALLOW_PRODUCTION_MIGRATIONS=YES
```

This dual safeguard is not authorization to run production migrations. A
production migration still requires an approved release, backup, compatibility
review, staging evidence, and rollback plan.

## Validation

Run the filesystem-only test:

```text
php tests/migrations/migration-framework-test.php
```

The isolated Azure SQL integration test requires:

- `APP_ENV=test`
- `DB_DRIVER=sqlsrv`
- A newly created, empty database whose name contains `test`, `ci`, `scratch`,
  or `temp`
- `YUVA_RUN_SQL_INTEGRATION=YES`
- A database principal allowed to create the baseline schema and acquire an
  application lock

Then run:

```text
php tests/migrations/migration-framework-integration.php
```

The integration test applies the blank database, reruns for idempotency,
verifies checksum mismatch refusal, and verifies concurrent application-lock
exclusion. It never drops or cleans the database; use a disposable isolated
database.
