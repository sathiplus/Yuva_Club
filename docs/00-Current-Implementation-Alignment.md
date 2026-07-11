# Current Implementation Alignment

This document explains how the current YUVA Club repository aligns with the new living blueprint, Product Decision Records, frontend standards, backend standards, and database standards.

It is intentionally direct: the repository has useful working pieces today, but the current app should be treated as an MVP foundation, not the final standards-compliant platform.

## Status Summary

| Area | Current Status | Readiness |
| --- | --- | --- |
| Product Decision Records | PDR manual, decision register, and individual decision files exist under `docs/decisions/`. | Foundation ready |
| Shared engineering references | UI, API, Azure, Azure SQL, security, coding, testing, AI, and notification standards exist under `docs/references/`. | Foundation ready |
| Student registration specification | Implementation-ready Chapter 1 exists under `docs/03-Student-Platform/`. | Spec ready |
| Frontend | Public pages, registration, student portal, parent portal, and admin pages exist as PHP/static files. | MVP present |
| Backend | PHP backend helpers exist for configuration, database access, authentication, repositories, registration approval, and audit logging. | MVP present |
| Database | Azure SQL schema exists with users, students, parents, registrations, sessions, topics, submissions, files, attendance, evaluations, badges, certificates, safety reports, activity logs, and email notifications. | MVP present |
| Full PDR compliance | The current app does not yet implement every PDR, standard, API contract, consent workflow, organization model, or role model. | Needs implementation pass |

## Documentation Source of Truth

The authoritative product and engineering source is now Markdown under `docs/`.

Word and PDF files may be generated for advisors, schools, partners, nonprofit organizations, and grant reviewers, but they should not become the primary source of truth.

Future blueprint work should update the canonical Markdown files and log durable decisions in `docs/decisions/`.

## PDR Alignment

The PDR foundation is in place.

Primary files:

- `docs/decisions/YUVA-Club-Product-Decision-Records.md`
- `docs/decisions/00-product-decision-register.md`
- `docs/decisions/PDR-001-students-own-achievements.md`
- `docs/decisions/PDR-002-individual-registration-free.md`
- `docs/decisions/PDR-003-organizations-pay-for-platform-capabilities.md`
- `docs/decisions/PDR-004-lifelong-yuva-id.md`
- `docs/decisions/PDR-005-student-led-learning.md`
- `docs/decisions/PDR-006-ai-as-coach.md`
- `docs/decisions/PDR-007-multi-tenant-architecture.md`
- `docs/decisions/PDR-008-azure-cloud-platform.md`
- `docs/decisions/PDR-009-shared-engineering-references.md`

Important current decisions already captured:

- Students own their achievements.
- Individual student registration remains free.
- Organizations pay for administrative and platform capabilities.
- Every student receives a lifelong YUVA ID.
- AI is a coach, not a judge.
- The platform uses multi-tenant architecture.
- Azure is the preferred cloud platform.
- Future implementation must follow shared engineering references.
- Platform administrator access is governed by PDR-046. The initial platform administrator account is `admin@yuvaclub.app`, organization administrator accounts are invitation-only, and organizations cannot self-register initially.

## Frontend Alignment

The current frontend is organized as public, student, parent, and admin entry points.

Current user-facing areas:

| Area | Current Files |
| --- | --- |
| Public website | `index.html`, `programs.html`, `curriculum.html`, `resources.html`, `stories.html`, `challenges.html`, `safety.html` |
| Registration | `registration.php`, `submit-registration.php` |
| Student portal | `portal-login.php`, `portal.php`, `portal-submit-topic.php`, `portal-submit-research.php`, `portal-download.php`, `portal-report-issue.php`, `portal-logout.php` |
| Parent portal | `parent-login.php`, `parent.php` |
| Admin portal | `admin-login.php`, `admin.php`, `admin-students.php`, `admin-student-edit.php`, `admin-actions.php`, `admin-ai-review.php`, `admin-ai-apply.php`, `admin-password-actions.php`, `admin-hub-actions.php`, `admin-meeting-actions.php`, `admin-bulk-session-actions.php` |

Frontend gaps to close:

- Apply the YUVA UI Design System consistently across pages.
- Replace one-off registration behavior with the full Student Registration and Onboarding specification.
- Add complete organization invitation and join-code flows.
- Add full parent consent screens and consent status tracking.
- Add accessibility verification for keyboard use, focus states, labels, contrast, and mobile layouts.
- Add future wireframes under `docs/wireframes/` before major screen rebuilds.

## Backend Alignment

Current backend foundation:

| File | Role |
| --- | --- |
| `backend/config.php` | Environment and app configuration |
| `backend/database.php` | Database connection and transactions |
| `backend/auth.php` | Password hashing, login helpers, tokens, roles |
| `backend/repositories.php` | Registration persistence, approval, YUVA ID generation, activity logging |

Backend strengths already present:

- Password hashing exists through backend authentication helpers.
- Database transactions exist for multi-step operations.
- Registration approval can generate YUVA IDs.
- Activity logging exists for sensitive actions.
- The app has a path toward Azure SQL through the database schema.

Backend gaps to close:

- Implement versioned REST endpoints under the API standards, such as `/api/v1/auth/register`, `/api/v1/auth/verify-email`, `/api/v1/students/me`, and `/api/v1/organizations/join`.
- Move all final validation rules server-side and document each error code.
- Add email verification workflow.
- Add parent consent request, approval, withdrawal, and re-consent workflows.
- Add organization invitation and join-code services.
- Add tenant-aware authorization checks for organization-scoped data.
- Add rate limiting and bot protection for registration and login.
- Add structured audit events for account, consent, organization, and admin workflows.

## Database Alignment

Current database foundation:

- `database/01-schema.azure-sql.sql`

Current schema includes:

- Programs
- Levels
- Users
- Students
- Parents
- Student-parent relationships
- Registrations
- Sessions
- Topic categories
- Topics
- Student topic selections
- Presentation submissions
- Files
- Attendance
- Evaluations
- Badges
- Student badges
- Student points
- Certificates
- Safety reports
- Activity logs
- Email notifications

Database gaps to close:

- Add organization, organization membership, invitation, join-code, subscription, and billing tables.
- Ensure student identity and organization membership are separate, per PDR-007.
- Ensure YUVA ID has a permanent unique constraint and generation strategy.
- Add consent policy versioning and consent history tables.
- Add email verification tokens and account recovery tables.
- Add API-aligned audit tables or audit event conventions.
- Add migration/versioning process for database changes.

## Student Registration Alignment

The current registration flow collects student and parent details and can store submissions. It is a useful starting point.

The target implementation is defined here:

- `docs/03-Student-Platform/chapter-01-student-registration-onboarding.md`

Required implementation upgrades:

- Individual student registration
- Organization invitation registration
- Organization join-code registration
- Parent-assisted registration
- Configurable age-based consent rules
- Email verification
- Lifelong YUVA ID generation after server-side account creation or approval
- Profile completion wizard
- AI onboarding questions
- Welcome/dashboard initialization
- Registration API endpoints
- Full test coverage

Implementation pass completed on 2026-07-10:

- Registration now creates a student login password during account creation.
- Passwords must satisfy the YUVA password policy before submission is accepted.
- Registration uses CSRF protection.
- The current file-backed flow creates a protected student account record when the YUVA ID is generated.
- Login no longer uses date of birth as a credential.
- Student portal forms now use CSRF protection.
- The student dashboard now shows a next-action panel.

Remaining larger PDR-alignment work:

- Move account creation, email verification, consent, organization join codes, and audit events into the versioned API and Azure SQL implementation.
- Replace file-backed student account storage with the canonical database-backed user model before production launch.
- Add automated tests for registration, login, dashboard actions, rate limiting, and security failures.

## Release Readiness Gates

Before calling the rebuilt platform production-ready, each major feature should satisfy these gates:

1. Product decision references are linked.
2. UI follows the YUVA UI Design System.
3. Backend follows the API Standards.
4. Database changes follow the Azure SQL Database Standards.
5. Security requirements are implemented and tested.
6. Accessibility checks pass for core workflows.
7. Positive, negative, boundary, security, and accessibility test cases exist.
8. Audit logging exists for sensitive actions.
9. Organization-scoped data is tenant-authorized server-side.
10. Documentation and implementation are updated together.

## Next Implementation Priority

The recommended next implementation pass is:

1. Commit the documentation foundation.
2. Convert Student Registration and Onboarding into implemented frontend, backend, database, and API changes.
3. Add tests for registration, consent, login, YUVA ID generation, and admin approval.
4. Expand the Student Dashboard chapter only after registration is aligned.

This keeps the project disciplined: first decisions, then standards, then one fully aligned feature at a time.
