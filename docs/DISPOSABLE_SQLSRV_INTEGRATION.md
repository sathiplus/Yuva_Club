# Disposable SQL Server authentication integration

The student and parent authentication integration tests run against a Microsoft
SQL Server 2022 Developer service container created by GitHub Actions. Nothing
is retained or billed between workflow runs.

## Safety boundary

The test helper refuses to connect unless all of these conditions hold:

- the server is `127.0.0.1` or `localhost`;
- the database matches `yuva_club_ci_<run>_<attempt>`;
- the contained user matches `yuva_ci_runner_<run>_<attempt>`;
- `YUVA_TEST_DB_EPHEMERAL=YES` is set;
- all protected database settings are present.

The production name `yuva_club`, remote Azure SQL hosts, `sa`, `yuvaadmin`, and
other non-test users are rejected before PDO is configured.

## Schema and permissions

The bootstrap applies the supported Azure SQL migrations in order while
intentionally excluding historical migrations 04 and 05, matching the live
migration policy. The integration user receives only `SELECT`, `INSERT`,
`UPDATE`, and `DELETE` on schema `dbo`; it receives no DDL, server, login, or
database-management permission.

Both integration tests create their users, students, registrations, parents,
and parent-child links inside real SQL transactions and roll them back in
`finally` blocks. A residue check follows the tests.

## Cleanup guarantees

The workflow deliberately exercises its failure cleanup path, confirms the
database is absent, and also has an idempotent `if: always()` database-drop
step. GitHub then destroys the SQL Server service container regardless of job
outcome.
