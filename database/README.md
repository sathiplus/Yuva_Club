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
