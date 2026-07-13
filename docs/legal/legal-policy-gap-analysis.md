# YUVA Club Legal Policy Gap Analysis

Draft status: Internal review  
Last updated: July 13, 2026

## 1. Scope Reviewed

Reviewed current public legal pages:

- `privacy.html`
- `terms.html`
- `safety.html`

Reviewed implementation areas:

- Student registration and student account creation.
- Parent account creation, activation, and parent-student relationship checks.
- Master Admin login and platform dashboard.
- Organization Admin invitation, activation, login, and organization student membership management.
- Student dashboard, topic selection, research submission, uploads, safety reports, certificates, badges, volunteer/service hours, and AI review.
- Backend database support and file-based storage fallback.
- Session cookies, CSRF protection, password hashing, login attempts, and audit logs.

## 2. Current Legal Page Assessment

The existing `privacy.html` and `terms.html` are short summaries. They do not sufficiently describe the implemented platform.

The existing `safety.html` has useful parent-trust messaging, but it is not a complete Child Safety & Community Guidelines policy.

There is no separate Parent & Student Consent Policy page or draft in the current app.

## 3. Draft Documents Produced

Created review drafts:

- `docs/legal/privacy-policy-draft.md`
- `docs/legal/terms-of-service-draft.md`
- `docs/legal/parent-student-consent-policy-draft.md`
- `docs/legal/child-safety-community-guidelines-draft.md`
- `docs/legal/legal-policy-gap-analysis.md`

No application code or live legal pages were modified.

## 4. Implemented Features Covered by Drafts

The drafts cover:

- Student accounts and student registration.
- Parent accounts, parent activation, and parent-student relationship checks.
- Organization administrator accounts and invitation-only activation.
- Master Admin role and platform-level access.
- Organization-level membership management.
- Student ownership of global YUVA identity and organization ownership of only organization-specific memberships.
- AI-assisted research and presentation feedback through OpenAI configuration.
- Research uploads and supported file types.
- Certificate, badge, portfolio summary, and volunteer/service-hour tracking.
- Safety reports.
- Email communications through noreply@yuvaclub.app and support@yuvaclub.app.
- Authentication, password hashing, sessions, CSRF, role checks, and audit logs.
- Azure App Service hosting and Azure SQL support.
- International access.
- Data retention, account deletion, and privacy request concepts.
- Recording acknowledgement and Zoom meeting fields.

## 5. Policy Statements That Describe Features Not Fully Implemented

These statements are included carefully with internal review comments or limiting language because implementation is partial or operational rather than fully coded:

- Presentation recordings: registration includes recording acknowledgement and admin Zoom fields, but automated recording storage, retention, access control, and deletion workflows are not implemented.
- Privacy request workflow: policies describe contacting support, but there is no formal in-app privacy request dashboard or workflow.
- Account deletion workflow: policies describe requesting deletion, but there is no automated deletion feature.
- Data retention schedule: policies mention retention but final retention periods are not coded.
- International transfer process: policies mention international access, but no formal transfer framework is implemented.
- Legal compliance with COPPA, FERPA, state student privacy laws, GDPR, or other laws: drafts intentionally do not claim verified compliance.
- Adult conduct/background-check/mandatory reporting policies: safety draft notes these require operational and legal review.
- Analytics: current code does not show Google Analytics or advertising trackers; policy states that analytics must be updated if added.
- AI vendor retention/settings: policy describes AI-assisted feedback but requires final vendor/data processing review.

## 6. Implemented Features Needing Stronger Policy Coverage Before Publishing

Before publishing, review and strengthen:

- Exact registration age range and whether students under 13 are allowed now or in the future.
- Whether parent consent is only an acknowledgement or a verified consent workflow.
- What organizations may export, download, or see.
- How long uploaded files, safety reports, audit logs, parent links, invitation tokens, and membership records are retained.
- Whether student portfolios are private, parent-visible, organization-visible, or shareable.
- Whether certificates can be public or only accessed by link.
- Who may see AI feedback and whether AI input/output can be used for official decisions.
- Whether support emails are monitored by YUVA Club only or by service providers.
- How to handle student transfer between organizations.
- How to handle organization suspension and student continuity.

## 7. Codebase Features Not Yet Reflected in Current Public Legal Pages

The current live legal pages do not adequately cover:

- Password hashing and account security.
- Parent account activation.
- Organization Admin invitation and role separation.
- Organization-specific student membership.
- Non-destructive organization removal.
- AI-assisted feedback.
- Research file uploads.
- Volunteer/service-hour tracking.
- Certificates and badges.
- Safety report handling.
- Audit logging.
- Azure SQL and file fallback storage.
- Data retention and deletion request limitations.
- International access.

## 8. Recommended Next Steps

1. Legal counsel review all four draft policies.
2. Decide final retention periods and deletion workflow.
3. Decide verified parent consent approach.
4. Decide recording policy before recording any live sessions.
5. Decide AI disclosure, opt-out, and vendor data handling.
6. Convert approved drafts into public HTML pages.
7. Add links to Parent & Student Consent Policy and Child Safety & Community Guidelines in the footer.
8. Add a version/date and owner for each policy.

