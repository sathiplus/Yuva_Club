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
| `03` | `03-phase-a-identity-approval.azure-sql.sql` | Phase A registration identity state, YUVA ID counters, and approval lookup indexes |
| `04` | `04-phase-a-portal-student-view.azure-sql.sql` | Read-only one-row-per-student compatibility view for later portal SQL reads |

## Phase A identity compatibility

Migration `03` adds nullable approval-attempt state to `dbo.registrations`, an
empty `dbo.yuva_id_counters` table, and approval-path indexes. It does not seed
or reconcile counter values. Existing unique constraints on `dbo.users.email`
and `dbo.parents.user_id` remain the authoritative indexes for their respective
identity lookups. Its six idempotently guarded indexes also support reserved-ID
uniqueness, registration review ordering, direct student registration lookup,
student YUVA ID lookup, and deterministic primary-parent selection.

Migration `04` creates or alters `dbo.vw_portal_students`. The view starts from
`dbo.students`, selects at most one linked registration, and selects at most one
linked parent. A primary parent sorts first; ties are resolved by relationship
creation time and parent ID. The view exposes stored YUVA IDs unchanged and does
not expose password hashes, account tokens, audit metadata, or approval error
details. No typed placeholder columns are required because every projected
compatibility field exists in the baseline schema.

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

## Migration 10: public student identity

`10-public-student-identity.azure-sql.sql` adds optional, case-normalized public
handles, a stable preset-avatar code, a handle-change timestamp, a filtered
platform-wide uniqueness index, and a narrowly scoped identity-change audit
table. It does not change permanent YUVA IDs or expose private student fields.
The rollback removes identity history and the new student columns and is for
isolated rehearsal only because it destroys saved public identity choices.

The application enforces deterministic handle safety and the 30-day rename
policy. SQL provides the final case-insensitive uniqueness boundary under the
database's case-insensitive collation.

## Migration 11: organization student memberships

`11-organization-student-memberships.azure-sql.sql` adds the consent-based
Organization Admin student invitation and linking foundation. It creates
membership requests, hashed single-use 72-hour tokens, explicit lifecycle
states, organization-scoped indexes, a one-active-organization-per-student
constraint, rowversion concurrency data, and an immutable action audit trail.
It is self-contained and does not depend on the intentionally skipped
Migration 04 or Migration 05 functional objects.

The rollback is rehearsal-only. It refuses the live `yuva_club` database and
also refuses to remove the tables while any membership request evidence exists.

## Migration 16: Quick Challenges Phase 2C.2A

`16-quick-challenges-phase2c2a.azure-sql.sql` adds reusable, versioned Quick
Challenge templates; the approved ten-skill catalog; frozen prompt, timing,
attempt, division, and rubric references; server-timed attempts; immutable
response snapshots; and versioned personal-best records. Quick Challenge
instances reuse the Phase 2C.1 competition lifecycle and organization scope.

The migration is additive and does not add judging, official Competition Score,
leaderboards, winners, or leadership promotion. The matching rollback is
rehearsal-only and removes only Migration 16 objects and columns.

## Migration 17: Parent password authentication

`17-parent-password-authentication.azure-sql.sql` adds SQL-backed, hashed,
single-use Parent activation/reset tokens, durable multi-instance authentication
throttling, and minimal user credential lifecycle fields. Existing `parents` and
`student_parents` relationships remain authoritative and unchanged. Parent
accounts that already have a verified password receive reconciled lifecycle
timestamps; no password is invented for an unactivated Parent.

The matching rollback is for isolated rehearsal only. It removes Migration 17
objects and credential lifecycle columns but never removes Parent, student, or
relationship rows.

## Migration 18: AI Quick Challenge practice scoring

`18-quick-challenge-ai-scoring-phase2c2b.azure-sql.sql` adds immutable,
versioned AI practice evaluations for submitted Quick Challenge attempts. It
reuses the Phase 2C.2A attempt snapshot, source SHA-256, rubric version, and
Personal Best foundation; the existing AI provider abstraction; and the Phase
3A.1 entitlement resolver. Versioned scoring policies carry challenge-specific
weights and privacy-safe system benchmarks. Evaluation provenance records the
provider, model, prompt, rubric, template, scoring policy, and benchmark used.

These scores are coaching-only. Migration 18 does not add official Competition
Score, judges, winners, leaderboards, or leadership promotion. The matching
rollback is restricted to rehearsal/test databases and removes only Migration
18 tables, guarded columns, and its feature rule.

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
