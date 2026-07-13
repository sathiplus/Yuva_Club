# YUVA Club Phase 2A Azure Staging Environment Report

Date: 2026-07-12

Branch: `phase-2a-final-validation`

Validation baseline: `7571154` (`Fix Phase 2A parent activation test fixture`)

Status: **Blocked pending Azure staging access**

Phase 2B status: Not started.

Merge status: Pull request must remain open and unmerged.

Production deployment status: Do not deploy this branch to production.

## 1. Executive Summary

The next Phase 2A milestone is:

**Staging environment ready + SQL migration verified**

This environment could not be created from the current Codex sandbox because:

- Azure CLI is not installed locally.
- PHP CLI is not installed locally.
- No Azure staging App Service, deployment slot, or staging Azure SQL connection is available in this environment.
- No permission was available to create Azure resources directly.

This is an infrastructure-access blocker, not a Phase 2A code defect.

No product features were changed. No Phase 2B work was started. No merge was performed.

## 2. Current Azure Architecture Inspection

Known from repository configuration:

| Resource / Setting | Observed Value | Classification | Notes |
| --- | --- | --- | --- |
| Azure Web App name | `yuvaclub` | Production | Found in `.github/workflows/main_yuvaclub.yml`. |
| Production deployment branch | `main` | Production | GitHub Actions deploys only on push to `main`. |
| Production deployment slot | `Production` | Production | Workflow deploys to slot `Production`. |
| App Service runtime | PHP | Production | Build workflow sets up PHP. |
| PHP version in CI | `8.3` | Shared reference | Production runtime should be verified in Azure Portal. |
| Production hostname | `https://www.yuvaclub.app` | Production | Found in app metadata and documented app settings. |
| Azure generated hostname | `https://yuvaclub-dja9ckadbagedja4.eastus-01.azurewebsites.net` | Production/unknown | Live site checked during prior validation. |
| Deployment method | GitHub Actions + Azure Web Apps Deploy | Production | Uses `azure/login@v2` and `azure/webapps-deploy@v3`. |
| Resource group | Unknown | Unknown | Must be confirmed in Azure Portal. |
| App Service Plan | Unknown | Unknown | Must be confirmed in Azure Portal. |
| Azure SQL server | `yuvaclub-sql-central.database.windows.net` | Production | Documented in `AZURE_SQL_APP_SETTINGS.md`; do not reuse for staging unless creating a separate DB on same server is approved. |
| Azure SQL production database | `yuva_club` | Production | Documented in `AZURE_SQL_APP_SETTINGS.md`; do not connect staging app to this DB. |
| Storage resources | `AZURE_STORAGE_*` settings supported | Unknown | Staging storage must be separate if file upload tests are run. |
| Email provider | `MAIL_PROVIDER=azure` default | Unknown/shared | Staging must use safe test delivery or capture mode. |
| Application settings | `DB_*`, `APP_ENV`, `APP_URL`, `MAIL_*`, `ALLOWED_CORS_ORIGINS` | Shared reference | Must be configured separately for staging. |
| Connection string configuration | Environment variables | Shared reference | Do not copy production credentials into staging. |
| Logging configuration | Unknown | Needs isolation | Must be enabled in Azure Portal for staging only. |
| Existing deployment slots | Unknown | Unknown | Must be checked in Azure Portal. |

No secrets were included in this report.

## 3. Selected Staging Architecture

Recommended option: **Azure App Service deployment slot named `staging` + separate Azure SQL staging database**.

Reason:

- Keeps staging deployment close to production runtime.
- Reduces duplicated App Service configuration.
- Allows slot-specific settings.
- Avoids production data exposure by using a separate database.
- Keeps the production slot untouched.

Acceptable fallback:

- Separate Azure App Service + separate Azure SQL staging database.

Do not use the production database for staging.

## 4. Required Staging Application Settings

Set these as slot-specific settings for the staging slot or as application settings on the separate staging App Service:

```text
APP_ENV=staging
APP_URL=https://<staging-hostname>
DB_DRIVER=sqlsrv
DB_HOST=<staging-sql-server>.database.windows.net
DB_PORT=1433
DB_DATABASE=yuva_club_staging
DB_USERNAME=<staging-sql-user>
DB_PASSWORD=<staging-sql-password>
MAIL_PROVIDER=staging
MAIL_FROM_EMAIL=noreply@yuvaclub.app
MAIL_FROM_NAME=YUVA Club Staging
ALLOWED_CORS_ORIGINS=https://<staging-hostname>
YUVA_CAPTURE_PARENT_ACTIVATION_LINKS=1
```

Important:

- Mark database, mail, storage, and environment settings as deployment-slot settings.
- Do not copy the production database password into staging.
- Do not point staging to `yuva_club`.
- Disable production notifications and external payment actions.
- Disable search indexing for staging.

## 5. Required Azure Portal Steps

1. Open Azure Portal.
2. Go to App Services.
3. Open the production Web App `yuvaclub`.
4. Confirm the resource group, App Service Plan, runtime stack, PHP version, hostname, logging, and existing deployment slots.
5. Create a deployment slot named `staging`, or create a separate staging App Service if slots are unavailable.
6. Configure staging app settings using the values in this report.
7. Create a separate Azure SQL staging database named `yuva_club_staging` or equivalent.
8. Confirm staging database firewall/network access permits only trusted access.
9. Do not copy production data.
10. Deploy branch `phase-2a-final-validation` at commit `7571154` to staging.
11. Open `/backend-health.php` on staging and confirm database connectivity.

## 6. Required SQL Validation

Run these scripts against the staging database only:

1. `database/01-schema.azure-sql.sql` if the staging database is brand new.
2. `database/02-phase-2a-precheck.sql`.
3. `database/02-phase-2a-security-foundation.sql`.
4. `database/02-phase-2a-verify.sql`.
5. Review `database/02-phase-2a-rollback.sql`; do not run rollback unless explicitly needed.

Record:

- Database name.
- Execution time.
- Precheck output.
- Migration output.
- Verification output.
- Row counts before and after.
- Whether the script is safe to rerun.
- Whether expected tables are present.
- Whether expected columns are present.
- Whether expected indexes are present.
- Whether the `admin@yuvaclub.app` user has exactly one active `MasterAdmin` role.

## 7. Required Synthetic Test Accounts

Use synthetic data only.

| Account Type | Required Count | Purpose |
| --- | ---: | --- |
| Master Admin | 1 | Validate platform administrator access. |
| Organization Admin | 2 | Validate organization-specific role separation. |
| Student | 2 | Validate student login/dashboard in separate organizations. |
| Parent linked to one student | 1 | Validate parent access to one linked student. |
| Parent linked to multiple students | 1 | Validate multi-child parent access. |
| Parent not linked to target student | 1 | Validate authorization denial. |

The production identity rule for `admin@yuvaclub.app` must not be weakened. If a staging alias is required, document it as staging-only test data and keep production authorization unchanged.

## 8. Email Test Method

Staging must use one safe email method:

- A staging mailbox.
- A captured-email service.
- A safe test SMTP provider.
- The existing development capture mode using `YUVA_CAPTURE_PARENT_ACTIVATION_LINKS=1`.

Confirm:

- Parent activation emails are generated.
- Activation links point to staging.
- Production users do not receive test emails.
- Raw activation tokens are not logged in plaintext except in a controlled development sink explicitly used for staging validation.
- Unknown emails receive a generic response.

## 9. Required Logging

Enable staging logs for:

- PHP errors.
- Authentication failures.
- Authorization failures.
- CSRF failures.
- Database exceptions.
- Session errors.
- Audit-log failures.
- HTTP 500 errors.

Do not log:

- Passwords.
- Password hashes.
- Activation tokens.
- Session cookies.
- Connection strings.
- Student private content.

## 10. Current Validation Status

| Requirement | Status | Evidence / Notes |
| --- | --- | --- |
| Azure architecture inspected | Pass/partial | Repo-level deployment/config inspected; Azure Portal resource details remain unknown. |
| Staging approach selected | Pass | Recommended deployment slot `staging` plus separate Azure SQL staging database. |
| Staging App Service/slot created | Blocked | No Azure access/tooling available in this sandbox. |
| Staging app settings configured | Blocked | Requires Azure Portal or Azure CLI access. |
| Separate staging SQL database created | Blocked | Requires Azure Portal or Azure CLI access. |
| SQL precheck run | Blocked | No staging database connection available. |
| SQL migration run | Blocked | No staging database connection available. |
| SQL verification run | Blocked | No staging database connection available. |
| Rollback reviewed | Pass/partial | Rollback script exists and includes guardrails; still requires staging review. |
| Branch deployed to staging | Blocked | No staging app/slot available. |
| Parent activation email method configured | Blocked | Requires staging app settings and email/capture setup. |
| Staging logging enabled | Blocked | Requires Azure Portal or Azure CLI access. |
| Production modified | Pass | No production changes were made from this environment. |

## 11. Exact Next Browser-Regression Steps

Do not start these until the staging environment is ready and SQL validation passes.

1. Open the staging URL.
2. Confirm the staging banner/indicator is visible.
3. Confirm public homepage loads.
4. Confirm registration page loads.
5. Submit a synthetic student registration.
6. Confirm synthetic student can log in.
7. Confirm student dashboard loads.
8. Confirm unapproved or invalid student access is denied.
9. Trigger parent activation for a linked synthetic parent.
10. Confirm activation link points to staging.
11. Complete parent password setup.
12. Confirm parent can access linked student.
13. Confirm parent cannot access unlinked student.
14. Confirm parent with multiple linked students sees only linked children.
15. Confirm Master Admin can log in.
16. Confirm Organization Admin cannot access Master Admin-only actions.
17. Confirm CSRF-protected admin POSTs reject missing/invalid CSRF tokens.
18. Confirm unauthorized direct URLs redirect or 403 as expected.
19. Review staging logs for PHP fatal errors and unexpected authorization failures.

## 12. Final Recommendation

Recommendation: **No-Go. Do not merge yet.**

Reason:

- The Phase 2A code baseline `7571154` passed CI validation.
- The staging environment has not been created or verified.
- SQL migration has not been run in staging.
- Authenticated browser regression must wait until staging and SQL validation both pass.

This is an infrastructure-access blocker, not a code blocker.
