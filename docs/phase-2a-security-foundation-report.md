# YUVA Club Phase 2A Security Foundation Report

Date: 2026-07-12

Source baseline commit: `20b1824570034b71b842c5b3b6512d65c139f247`

Target branch requested: `phase-2a-security-foundation`

Branch status: not created locally because Windows blocked writes inside `.git/refs/heads`. The implementation is present in the working tree and can be committed from Git Bash after marking the folder as safe or fixing ownership.

Deployment status: not deployed.

## Scope Completed

Phase 2A focused only on the highest-priority security foundation items:

- Secure parent authentication
- Server-side Master Admin authorization
- Organization data-isolation foundation
- Audit logging for sensitive access and updates
- Static security validation
- Database migration script
- Rollback notes

## Parent Authentication

Before Phase 2A, parent dashboard access depended on a student identifier and parent email. That allowed weak access control and made it easier to enumerate student records.

Phase 2A changes:

- Parent login now requires verified parent email and password.
- Parent login uses CSRF validation.
- Parent login uses existing rate limiting.
- Parent sessions store `parent_email` and `parent_session_started_at`.
- Parent sessions expire after two hours.
- Parent dashboard access is checked server-side through `require_parent_for_student`.
- Parent-to-student links are stored separately from the student record.
- Parent login and student access attempts are audit logged.
- Student login no longer accepts parent email as a student identifier.

Files changed:

- `parent-login.php`
- `parent.php`
- `portal-lib.php`
- `portal-logout.php`
- `submit-registration.php`

Known limitation:

- File-backed registrations create the parent account and student link after registration.
- Azure SQL registration still needs a database-backed parent account/link creation flow in Phase 2B, because the current SQL registration path does not yet create the full student account and parent link at submit time.

## Admin Authorization

Before Phase 2A, admin access relied mainly on a generic session flag.

Phase 2A changes:

- Added explicit roles:
  - `MasterAdmin`
  - `OrganizationAdmin`
  - `Parent`
  - `Student`
- Master Admin is restricted to `admin@yuvaclub.app`.
- Admin session stores:
  - admin email
  - admin role
  - organization ID
  - session start time
- Admin session expires after two hours.
- Admin session ID regenerates after successful login.
- Admin pages call `require_admin([YUVA_ROLE_MASTER_ADMIN])`.
- Admin POST actions call `require_admin_post([YUVA_ROLE_MASTER_ADMIN])`.
- Admin POST actions now require CSRF.
- Organization admin dashboard access is intentionally disabled until tenant isolation is fully complete.

Files changed:

- `admin-login.php`
- `admin.php`
- `admin-actions.php`
- `admin-hub-actions.php`
- `admin-ai-review.php`
- `admin-ai-apply.php`
- `admin-bulk-session-actions.php`
- `admin-meeting-actions.php`
- `admin-password-actions.php`
- `admin-student-edit.php`
- `portal-lib.php`
- `portal-logout.php`

## Organization Isolation Foundation

Phase 2A does not enable public organization administration. It creates the foundation needed to safely enable it later.

Implemented foundation:

- `organizations` table
- `user_roles` table
- `organization_memberships` table
- `organization_id` columns on major student and operating tables
- indexes for future tenant-filtered queries
- seeded active `MasterAdmin` role for `admin@yuvaclub.app`

Migration file:

- `database/02-phase-2a-security-foundation.sql`

Important:

- The migration is idempotent and written for Azure SQL review.
- It has not been run automatically.
- Before production use, run it first in a staging copy of the database.

## Audit Logging

Added JSONL audit logging for file-backed operations:

- Admin login success/failure
- Admin page access success/failure
- Admin CSRF/method rejection
- Admin password settings update
- Admin student record update
- Admin hub settings update
- Admin AI review creation
- Admin AI review apply
- Admin bulk session update
- Admin meeting clear
- Admin registration update
- Parent login success/failure
- Parent student access success/failure
- Parent session rejection

Audit file:

- `portal-data/security-audit-log.jsonl`

Each event includes:

- timestamp
- actor user ID
- role
- organization ID
- action
- target type
- target ID
- success flag
- IP address
- user agent
- metadata

## Validation

Static security test added:

- `tests/phase-2a-security-static-tests.ps1`

Validated:

- Parent login uses password authentication.
- Parent login verifies CSRF.
- Parent login is rate-limited.
- Parent login no longer grants access by `parent_student_id`.
- Parent dashboard enforces server-side authorization.
- Master Admin role constant exists.
- Admin identity is resolved server-side.
- Admin guard enforces allowed roles.
- Master Admin is restricted to `admin@yuvaclub.app`.
- Admin login verifies CSRF.
- Admin login regenerates session ID.
- Admin action endpoints use the POST admin guard.
- Admin forms include CSRF fields.
- Migration creates role and organization tables.
- Migration adds organization foundation.
- Audit logging helper exists.
- Sensitive admin and parent actions are audited.

Latest result:

- `Phase 2A static security checks passed.`

Not completed:

- PHP syntax checks could not be run because PHP CLI is not installed in the local shell.
- Browser/manual runtime QA was not completed in this local pass.
- No deployment was performed.

## Rollback Plan

Application rollback:

1. Revert the Phase 2A changed PHP files to the previous deployed commit.
2. Remove or ignore `tests/phase-2a-security-static-tests.ps1`.
3. Remove or ignore this report if the implementation is abandoned.

Data rollback:

1. Back up the database before running the migration.
2. If the migration causes issues before production data depends on it, drop:
   - `organization_memberships`
   - `user_roles`
   - `organizations`
3. Remove `organization_id` columns and indexes only if no new code depends on them.
4. Preserve `security-audit-log.jsonl` unless there is a legal or privacy reason to delete it.

Recommended safer rollback:

- Revert application code first.
- Leave additive database columns/tables in place until Phase 2B is planned.

## Phase 2B Recommendations

Do next:

- Move parent account/link storage into Azure SQL tables.
- Update SQL registration approval to create student, parent, and link records together.
- Add organization admin login only after every organization query enforces `organization_id`.
- Add database-backed audit logging into `activity_logs`.
- Add manual test checklist for parent login, admin login, expired sessions, CSRF rejection, and tenant isolation.
- Install PHP CLI locally or run syntax checks in CI.

