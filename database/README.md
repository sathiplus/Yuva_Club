# YUVA Club Azure SQL Database

This directory contains the Azure SQL baseline and ordered migrations.

## Current files

1. `01-schema.azure-sql.sql` — original Azure SQL application baseline.
2. `02-schema-migrations.azure-sql.sql` — idempotent migration ledger.
3. `03-phase-a-identity-approval.azure-sql.sql` — Phase A identity, approval, and portal lookup additions.
4. `04-phase-a-portal-student-view.azure-sql.sql` — read-only portal compatibility view.

The previously documented `02-import-hostinger-data.azure-sql.sql` is not
present in this repository and is not part of the automated migration set.
Legacy data import will use a separately reviewed, idempotent data-migration
process.

## Running migrations

Use the CLI-only runner from the repository root:

```text
php tools/run-azure-sql-migrations.php
```

The runner uses `backend/config.php` and `backend/database.php`. It requires the
PDO SQL Server driver and the existing `DB_*` environment variables. It acquires
a SQL Server application lock, validates SHA-256 checksums, and applies pending
files in deterministic filename order.

Do not run migrations against production during Phase A development or staging
validation. See `database/MIGRATIONS.md` for naming, safety, and validation
rules.

## Phase A approval service

`backend/repositories.php` contains an Azure SQL-compatible registration
approval service, but Commit 3 does not connect that service to an admin action,
portal read, login flow, dual write, or storage-mode change. Filesystem behavior
therefore remains authoritative.

Before the SQL approval service is enabled in a later approved phase, the
current UTC year must have a reconciled row in `dbo.yuva_id_counters`. Normal
request handling deliberately refuses a missing counter instead of deriving or
guessing a value from production student records.

SQL approval locking follows one order: registration application lock,
lexically sorted SHA-256 identity-email application locks, registration row,
program and level, optional counter row, then identity and relationship rows.
Raw email addresses are never used as application-lock resource names.

The database-free approval contract test is:

```text
php tests/backend/backend-approval-test.php
```

The guarded integration test is excluded from normal CI. It requires
`APP_ENV=test`, `DB_DRIVER=sqlsrv`, `YUVA_RUN_SQL_INTEGRATION=YES`, a disposable
database name containing `test`, `ci`, `scratch`, or `temp`, and an already
migrated Phase A schema.
