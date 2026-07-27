# YUVA Club Authentication & Account Activation Specification — Version 1

## Status and purpose

This document defines the authentication and account-activation model required before SQL-backed portal login is implemented. It is a planning specification only. It does not authorize application, database, migration, frontend, CI, Azure, App Setting, or production changes.

This document is the long-term authentication architecture for YUVA Club. Future authentication features should extend this design rather than replace it.

## Executive decision summary

Version 1 should adopt the preferred parent-led activation model, with two modifications:

1. A student with a verified personal email may activate directly. Parent-assisted activation remains available for younger students and students without a personal email.
2. Backend Commit 5 should establish the authentication repository, compatibility adapter, storage-mode behavior, password login, sessions, and tests, but should not attempt every recovery, notification, social-login, or account-administration feature in one commit.

The target production model is:

- An administrator approves a registration.
- Approval creates or reuses one SQL user per person, but an account cannot authenticate until activated.
- A parent with a real email receives a single-use, time-limited activation link.
- The parent verifies control of the email and creates a password.
- The parent activates or assists activation of a linked younger student.
- A student creates a separate password and, in Version 1, logs in with the canonical YUVA ID. Verified student email remains available for activation, recovery, and future email login.
- A parent logs in with a verified email and password, then selects among linked children.
- Existing filesystem users remain temporarily available through an explicitly enabled hybrid compatibility period.
- DOB, email alone, phone number, YUVA ID, and internal placeholder identities are never passwords.

`PORTAL_AUTH_MODE` controls authentication source selection. Its default is `filesystem`. `PORTAL_STORAGE_MODE` retains its existing meaning and is not changed by this design. SQL approval must remain disabled until Backend Commit 5 and the activation path have passed database-free and staging SQL validation.

## 1. Authentication principles

- Authentication must be understandable for youth and families while resisting guessing, enumeration, replay, and account takeover.
- Each person has one `users` account. A parent reused across registrations is linked to multiple students rather than duplicated.
- Student, parent, organization-admin, and master-admin authority must be distinct. The current SQL role constraint supports student, parent, and admin only; organization-admin and master-admin separation requires a separately designed authorization change.
- Login, activation, verification, and recovery endpoints return generic responses that do not disclose whether an account, email, YUVA ID, child link, or state exists.
- Passwords are stored only through PHP `password_hash(PASSWORD_DEFAULT)`. Tokens are random, high-entropy values; only their cryptographic hashes are stored.
- Passwords must never be plaintext, reversible, emailed, logged, or derived from DOB, email, YUVA ID, phone number, or an internal placeholder identity.
- Authentication data is separate from profile-display data. A portal view or CSV-shaped adapter is not an authorization source.
- Approval, identity verification, activation, and authentication are separate state transitions.
- External identity providers may later link to an existing YUVA user; they must not implicitly create duplicate users.

## 2. Student account lifecycle

1. **Registration:** A student and parent submit registration data. No password is collected at this stage.
2. **Administrative review:** The registration remains pending, rejected, or approved. Pending and rejected registrations cannot authenticate.
3. **Account creation:** Approval creates or reuses the student identity and allocates the canonical YUVA ID. The initial authentication state is `approved_not_activated`; `password_hash` remains null.
4. **Activation invitation:** A student with a real email may receive a direct activation link. Otherwise, an eligible linked and verified parent receives the invitation.
5. **First-time activation:** The invitation is consumed once, the student establishes a password, required consent is confirmed, and the account becomes active.
6. **Login:** In Version 1, the student uses YUVA ID plus the student password. Verified email login is deferred while remaining part of the long-term architecture.
7. **Reset and recovery:** A verified student email can receive a reset link. A student without personal email uses a verified linked parent or audited administrator-assisted recovery.
8. **Suspension/reactivation:** Suspension immediately prevents new authentication and invalidates or revokes existing sessions. Reactivation requires an authorized administrator and is audited.
9. **Graduation/aging out:** The account is archived or transitioned under a separately approved retention policy. Aging out must not silently change roles or transfer parent authority.
10. **Duplicate prevention:** YUVA ID is unique. Normalized real email is unique among users. Approval must reuse an identity only after role and identity compatibility checks; otherwise it fails for review.

Students whose SQL user email ends in `.invalid` are addressless accounts. The internal address is an implementation key only: it must never be displayed, emailed, exported as a contact address, accepted as a login email, or used for recovery.

## 3. Parent account lifecycle

- A parent has one `users` row and one `parents` row, linked through `student_parents` to one or more students.
- Repeated approval with the same normalized verified parent email reuses the parent only when role and identity checks succeed.
- A new parent receives a single-use activation link at the submitted real email and creates a password.
- An already active parent is not reactivated when another child is approved. The parent receives a privacy-conscious “new child linked” notice and sees the child after normal login.
- Parent login uses normalized verified email plus password. Child identity is not part of the parent credential.
- After login, the parent selects a linked child. Server-side authorization checks the link on every child access and child switch.
- Adding or removing a child link requires an approved workflow, appropriate consent, audit logging, and notification. Removing the last link does not automatically delete the parent account.
- Multiple parents or guardians may be linked independently. Each must have a separate account and credentials.
- Recovery uses the verified parent email. If that address changes, the old address is notified when safe, the new address must be verified, and sensitive changes may require recent authentication or administrator review.

## 4. First-time activation workflows

All activation links and codes must be random, single-use, time-limited, stored only as hashes, rate-limited, and invalidated after successful use or replacement.

### Student with a real email

1. Approval creates the account in `approved_not_activated`.
2. Send a 24-hour activation link to the student email. For a younger student, notify or include the consenting parent according to policy without exposing unnecessary profile data.
3. The activation endpoint validates the hashed token, expiration, account state, intended purpose, and expected role.
4. The student confirms the non-secret YUVA ID, creates a password, and completes required consent/verification steps.
5. In one transaction, set the password hash, mark the email verified, mark activation complete, invalidate all activation/reset tokens, and audit the event.
6. Regenerate the session before entering the portal, or require a fresh login.

### Student without a personal email

1. Approval creates an addressless student account with an internal `.invalid` identifier and state `approved_not_activated`.
2. Send the activation invitation to a verified linked parent, not to the internal address.
3. After recent parent authentication, the parent chooses the linked student and initiates assisted activation.
4. The student creates a private password. The parent may assist with the process but the application must not reveal or retain the password.
5. Activate the student for YUVA ID login only. Recovery remains parent-assisted until the student adds and verifies a real email.
6. When age and policy make parent assistance inappropriate, use audited administrator-assisted recovery with identity checks.

### Parent with a verified email

1. Approval creates the parent as `approved_not_activated`.
2. Send a 24-hour activation link.
3. The parent verifies email control, creates a password, and reviews the linked child relationship and consent.
4. Activate the account, invalidate the token, audit the event, and require login or establish a regenerated authenticated session.

### Parent reused from an existing child

- If active, do not issue a new activation credential. Add the approved child link transactionally, audit it, and send a “new child linked” notification.
- If approved but not activated, invalidate any superseded invitation and send one replacement invitation. Activation exposes only the minimum linked-child information.
- If suspended, do not reactivate automatically; route the link request to administrator review.

### Accounts created before passwords existed

- Treat a null-password approved account as `approved_not_activated`, never as an account with an empty password.
- Issue a normal activation link to a verified real address or through the parent-assisted path.
- Do not generate or email permanent passwords.
- An administrator-issued temporary activation code is a last resort. It must be random, short-lived (recommended 15 minutes), single-use, purpose-bound, attempt-limited, delivered through a separately verified channel, and fully audited.

## 5. Login identifiers

Students use in Version 1:

- Canonical YUVA ID plus password.

Verified real student email remains available for activation and recovery. Email login is deferred to a later version, but the account model and repository interfaces must retain compatibility for future verified-email login without redesign.

Parents may use:

- Verified normalized parent email plus password.

YUVA ID remains the canonical student identity and the stable session-facing identifier. Email is a mutable login alias after verification.

Email normalization consists of trimming surrounding whitespace and applying Unicode-safe lowercase/case-insensitive comparison consistent with the database collation. The service must not remove dots, rewrite provider-specific aliases, or otherwise guess mailbox equivalence.

An email ending in `.invalid` is never a user credential. It is rejected before authentication lookup and excluded from verification, notification, display, and recovery flows.

When identifiers conflict, authentication fails closed. Hybrid-source duplicates must pass defined compatibility checks; SQL role mismatches, one email attached to different people, or one YUVA ID attached to incompatible records require manual resolution. The user receives a generic message and the server records a sanitized conflict category.

## 6. Password policy

- Minimum length: 12 characters. Permit long passphrases of at least 64 characters; do not require arbitrary mixtures of character classes.
- Hash with `password_hash($password, PASSWORD_DEFAULT)`.
- Verify with `password_verify()`.
- After successful verification, use `password_needs_rehash()` and replace the hash transactionally when PHP defaults change.
- Reject passwords found in a practical breached-password or high-quality common-password list when this can be performed without disclosing the candidate password to logs or an unsafe third party. A local, versioned denylist is an acceptable Version 1 baseline.
- Do not force periodic changes without evidence of compromise.
- Reset links expire after 60 minutes. Activation links expire after 24 hours.
- Tokens use at least 32 cryptographically random bytes, are purpose-bound, stored as SHA-256 or stronger hashes, compared safely, and consumed once.
- Issuing a replacement token invalidates earlier tokens for that purpose.
- A successful password change invalidates reset/activation tokens and all other authenticated sessions. The current session is regenerated only when the change followed recent authentication; recovery flows should require a fresh login.

## 7. Email verification and recovery

- Generate tokens with `random_bytes(32)` or stronger and encode them safely for URLs.
- Store only the token hash, purpose, subject user, expiration, consumption state, and minimal audit metadata.
- Verification and reset links are single-use. Success clears or consumes the stored token atomically.
- Resend returns the same generic response whether an account exists. Apply per-account, per-IP, and global rate limits, with progressively longer resend intervals.
- Never send mail to `.invalid` addresses.
- A parent email change requires recent authentication, verification of the new email, notification to the old email when safe, duplicate checking, and an audit event.
- A student without personal email recovers through a verified linked parent. The parent must authenticate recently and may act only for a linked student.
- Administrator-assisted recovery requires least-privilege access, documented identity checks, reason capture, an audit record, notification where safe, and a short-lived activation credential—not a permanent password.

## 8. Session security

- Regenerate the session identifier after successful login, activation-based sign-in, password changes, child-context changes that alter authorization, and privilege changes.
- Cookies must be `Secure` in HTTPS environments, `HttpOnly`, and `SameSite=Lax` or stricter where compatible. PHP strict session mode and cookie-only sessions remain enabled.
- Recommended idle timeout: 30 minutes for student/parent portals and 15 minutes for privileged administration.
- Recommended absolute lifetime: 12 hours for student/parent sessions and 4 hours for privileged administration.
- Logout invalidates server-side session state, expires the cookie, and does not expose whether another session exists.
- Version 1 may allow a small number of concurrent student/parent sessions, but password reset, suspension, recovery, and suspected compromise must revoke all sessions. This requires server-side revocation/version support before it can be enforced reliably.
- Parent child-switching keeps the authenticated parent identity separate from the selected child context. Every request rechecks the parent-child link.
- Protected pages revalidate account active state, role, student approval state, and required parent link. A session key alone is never sufficient authorization.

## 9. Login abuse protections

- Apply CSRF tokens to login, activation, reset, password-change, logout, and child-switch POST requests. Login CSRF matters because forced sign-in can bind a browser to the attacker’s account.
- Implement application-level limits by normalized identifier hash and IP/network signal. Do not key only by a full sensitive identifier.
- After repeated failures, introduce progressive delays and a short temporary lock. Recommended starting policy: delays after 5 failures and a 15-minute lock after 10 failures within 15 minutes, subject to staging usability testing.
- Do not reveal lock state in the response. Successful verified login clears failure counters.
- Audit successful login, failure category, activation, reset, password change, lock, unlock, suspension, role mismatch, and hybrid conflict.
- Logs must not contain submitted passwords, DOBs, activation codes, reset tokens, token hashes, full sensitive identifiers, or connection secrets. Prefer user IDs after resolution and truncated or keyed-hash identifiers before resolution.
- Use one generic public failure such as: “Unable to sign in with those details.”
- Each endpoint accepts only its intended roles. Student, parent, organization-admin, and master-admin sessions must have distinct authorization checks and must not be inferred from mutable request parameters.

## Security controls

Version 1 requires defense in depth: approved and activated account-state gates; exact role checks; modern one-way password hashing; hashed, expiring, single-use tokens; CSRF protection; application throttling and temporary locks; session regeneration and revocation; secure cookies; generic public responses; sanitized audit logs; verified parent-child authorization; and fail-closed handling of SQL outages and hybrid conflicts. No single profile field, session key, email address, or display view is sufficient proof of authorization.

## 10. Authentication storage modes

Authentication uses:

`PORTAL_AUTH_MODE=filesystem|sql|hybrid`

This setting is independent of `PORTAL_STORAGE_MODE`, whose meaning must not change.

### `filesystem`

- Existing filesystem lookup and legacy credential behavior remain authoritative.
- No SQL authentication lookup is attempted.
- This is the default when the setting is absent.
- An invalid value fails safely to `filesystem` during the compatibility release and emits a configuration warning; production deployment validation should reject invalid values.

### `sql`

- Only SQL accounts may authenticate.
- Students require an approved student, active user, correct role, completed activation, and password hash.
- Parents require an active parent user, completed activation, password hash, and an authorized child link for access.
- A SQL outage does not fall back to filesystem; the public response remains generic.

### `hybrid`

Deterministic processing is:

1. Normalize and classify the identifier.
2. Inspect both eligible sources.
3. If neither matches, fail generically.
4. If exactly one matches, authenticate using that source’s allowed compatibility rules.
5. If both match the same canonical person, require compatibility of canonical YUVA ID, role, DOB where retained, and normalized real contact identities. During the transition, the SQL identity is preferred once it is activated; otherwise the eligible filesystem identity is used.
6. If compatibility cannot be established, fail closed. Do not try the second source after a password failure against an activated SQL identity.

This prevents password-oracle behavior and downgrade from an activated SQL account to weaker legacy factors. Conflicts produce sanitized security logs without DOB, complete email, or credential data.

Rollback consists of restoring `PORTAL_AUTH_MODE=filesystem`. It does not delete SQL identities, change `PORTAL_STORAGE_MODE`, or reverse schema/data.

## 11. Existing-login compatibility

Current legacy factors are:

- Student: YUVA ID plus DOB.
- Parent: child YUVA ID plus parent email.

Version 1 target factors are:

- Student: YUVA ID plus password. Verified student email login is deferred.
- Parent: verified real email plus password, followed by server-authorized child selection.

For a bounded compatibility period:

- `filesystem` preserves current behavior.
- `hybrid` permits a filesystem-only legacy account to use its existing flow while offering activation into SQL.
- Once a matching SQL account is activated, SQL password authentication takes precedence and legacy DOB/email-only fallback is disabled for that identity.
- Every legacy success should prompt activation without exposing account state to unauthenticated users.
- A published end date and migration progress measure are required before production cutover.

DOB and email alone are temporary legacy factors, not passwords. The system must minimize DOB processing and remove it from authentication after transition.

## 12. Data-model assessment

The assessment below reflects `database/01-schema.azure-sql.sql`. No migration is authorized by this document.

| Field or capability | Exists now | Safely derivable | Migration required | Assessment |
|---|---:|---:|---:|---|
| `users.password_hash` | Yes, nullable | No | No | Suitable for `PASSWORD_DEFAULT`; null means not activated, never an empty password. |
| `users.email_verified_at` | Yes | No | No | Suitable for real email verification. Must remain null for `.invalid` identities. |
| `account_activated_at` | No | Partially | Recommended | A non-null password plus active status is an imperfect proxy and loses activation audit semantics. Add explicitly in a later migration. |
| `users.last_login_at` | Yes | No | No | Suitable for successful-login tracking; update only after full authentication. |
| `password_changed_at` | No | No | Recommended | Needed for recovery review, session revocation policy, and security notifications. |
| `failed_login_count` | No | No | Recommended or separate table/cache | Needed for durable account throttling. A dedicated attempt/lock table may age and purge more cleanly. |
| `last_failed_login_at` | No | No | Recommended or separate table/cache | Needed to define the failure window without relying on sensitive logs. |
| `locked_until` | No | No | Recommended or separate table/cache | Needed for deterministic temporary lock enforcement across app instances. |
| Activation token hash | No dedicated field | No | Required for persisted activation | Existing verification token fields should not be overloaded across purposes because replacement and consumption rules differ. |
| Activation token expiration | No dedicated field | No | Required for persisted activation | Pair with purpose-specific activation token storage. |
| `users.password_reset_token_hash` | Yes | No | No for one outstanding token | Suitable for a single current reset token; clear after use. |
| `users.password_reset_expires_at` | Yes | No | No | Suitable for reset expiration. |
| `users.email_verification_token_hash` | Yes | No | No for one outstanding token | Suitable for email verification, not simultaneous activation purposes. |
| `users.email_verification_expires_at` | Yes | No | No | Suitable for verification expiration. |
| Session revocation/version | No | No | Required for reliable revocation | PHP sessions alone cannot reliably invalidate all concurrent sessions. A session table or `auth_version` is needed. |
| External identity links | No | No | Required for social sign-in | A future provider/subject link table is necessary; do not put provider IDs in `users.email`. |
| Audit events | Yes (`activity_logs`) | N/A | No | Suitable if metadata is minimized and sanitized. |
| Notification queue | Yes (`email_notifications`) | N/A | No | Suitable for queued templates and delivery state. |

The current `users.status` values are `pending`, `active`, `suspended`, and `disabled`. The richer state model below cannot be represented faithfully by `status` alone. Version 1 implementation should either add explicit activation/archive state fields in a separately reviewed migration or define a temporary composite interpretation with strict invariants.

## 13. State-transition table

| State | Meaning | May authenticate? | Allowed transitions |
|---|---|---:|---|
| `registration_pending` | Registration awaits decision; account may not exist | No | Approved, rejected |
| `approved_not_activated` | Identity exists and approval passed, but no usable credential/activation | No | Active, suspended, archived |
| `activation_expired` | Approval remains valid, but the activation invitation expired or was superseded | No | Approved not activated after rate-limit and identity checks permit a new invitation; suspended; archived |
| `active` | Approved, activated, correct role, usable credential | Yes | Temporarily locked, suspended, archived |
| `temporarily_locked` | Time-limited abuse control | No | Active after expiry/recovery, suspended |
| `suspended` | Administrative or safety restriction | No | Active by authorized review, archived |
| `rejected` | Registration was rejected | No | Registration review only; never direct activation |
| `archived` | Membership ended or account retained under policy | No | Reactivation only through authorized, audited process |

Approval moves a registration to `approved_not_activated`; it does not make the person authenticated. Activation requires valid approval, verified activation authority, and password creation. In `activation_expired`, approval remains valid, authentication is prohibited, and a replacement invitation may be issued only after rate-limit and identity checks. This state may be represented operationally by token expiration or supersession rather than as a permanent `users.status` value when that produces a cleaner model. Temporary lock does not erase approval. Suspension overrides activation and lock expiry. Rejection and archive must not be bypassed by password reset.

Until dedicated fields exist, the application may interpret approved student plus active user plus non-null password as active, but this is transitional and must be protected by tests. `users.status='active'` alone is insufficient.

## 14. Notification flows

All messages use minimal identifying information, age-appropriate wording, no passwords or sensitive profile data, and a “If you did not request this” path.

- **Approval:** Explain approval and that activation is still required.
- **Activation:** Send a time-limited link, expiration, and safe support guidance.
- **Verification:** Confirm the address purpose without exposing other linked accounts.
- **Password reset:** Give a single-use link and ignore guidance; never confirm account existence on the request page.
- **Password changed:** Notify verified recovery channels with time and support instructions, not IP detail unless policy approves it.
- **New child linked:** Tell the parent a child relationship was added and how to report an error, using minimal child identity.
- **Suspicious login or lock:** Explain protective action and recovery steps without revealing the attempted password or precise attacker data.
- **Suspension:** State access is paused and provide an appropriate review/support route without disclosing private administrative notes.

In-app notices may supplement email after authentication but cannot replace verification of a recovery channel.

## Authentication Events

The authentication audit vocabulary must include at minimum:

- `student.activated`
- `parent.activated`
- `login.succeeded`
- `login.failed`
- `password.change.completed`
- `password.reset.requested`
- `password.reset.completed`
- `account.locked`
- `account.unlocked`
- `account.suspended`
- `account.reactivated`
- `child.linked`
- `child.unlinked`
- `hybrid.conflict_detected`
- `activation.expired`
- `activation.resent`

Event logs contain sanitized IDs and categories only. They must not contain passwords, DOBs, tokens, activation codes, full sensitive identifiers, or connection details.

## 15. Future social sign-in

Google, Apple, and Microsoft identities should be stored in a future table keyed by provider plus immutable provider subject, linked to `users.id`. Provider email is an attribute, not the stable external key.

Linking requires:

- Existing authenticated session with recent authentication, or a controlled activation/recovery flow.
- Verified provider assertion and expected issuer/audience.
- Compatibility checks against role and verified YUVA identity.
- Explicit confirmation before linking.
- Audit and notification.

A first social sign-in must not automatically create a second YUVA person merely because the provider supplies an email. Conflicts fail safely for assisted resolution. Account unlinking must preserve at least one usable authentication or recovery method.

## 16. Privacy and youth safety

- Collect and retain only authentication data needed for security, support, consent, and legal obligations.
- Students without personal email use YUVA ID and parent-assisted activation; the service must not pressure a child to create an email account.
- Parent assistance is limited to linked students and governed by consent and age policy.
- DOB must not appear in logs, URLs, tokens, generic errors, or long-term authentication once compatibility ends.
- Internal `.invalid` addresses are implementation-only and excluded from all user-visible or outbound communication.
- Administrator-assisted recovery records actor, reason, affected user ID, action, and time without copying identity evidence into general logs.
- Authentication repositories return authorization facts and identifiers; profile adapters return display data. Display views must not expose password hashes, tokens, failure counters, or lock details.

## 17. Recommended Version 1 decision

Adopt the preferred direction with the direct-student and commit-boundary modifications described above.

Parent-led activation is the safest practical default because every approved registration already has a parent email and some students lack a personal address. It avoids inventing credentials from personal data, supports younger users, and naturally reuses a parent across children. Direct activation for a student with a verified real email respects older students’ independence and avoids making the parent a permanent authentication intermediary.

The system should not activate SQL login until it can securely issue and consume activation credentials. Merely reading approved SQL rows into the existing DOB/email login would preserve weak factors and would not meet this specification.

## 18. Backend Commit 5 boundary

Backend Commit 5 should implement:

- A validated `PORTAL_AUTH_MODE`.
- A read-only SQL authentication repository.
- A SQL-to-legacy compatibility adapter.
- Student login using YUVA ID plus password.
- Parent login using verified email plus password.
- Parent child selection and link authorization.
- Deterministic hybrid authentication behavior.
- Existing session compatibility.
- Generic authentication failures.
- CSRF protection and application-level rate limiting.
- Database-free and synthetic Azure SQL integration tests.

Backend Commit 5 should be deferred until a separately reviewed activation-state/token migration is approved if secure activation cannot be expressed with current fields without overloading verification tokens.

Defer to Backend Commit 6:

- Activation UI and delivery workflow.
- Password reset UI and delivery.
- Advanced recovery.
- Notifications beyond minimum required test hooks.
- Social sign-in.
- Session/device dashboard.
- Full account-management redesign.

Also outside Backend Commit 5:

- Organization-admin and master-admin role redesign.
- Automated aging-out and retention workflows.
- Bulk production account migration or approval enablement.
- Azure, App Setting, mail-provider, and production changes.

## User journeys

### New student with email

Register → administrator approves → student receives activation link → verifies email and creates password → account becomes active → logs in with YUVA ID in Version 1 → enters unchanged student portal. A later version may add verified-email login without changing the identity model.

### New student without email

Register → administrator approves → linked parent activates own account → parent starts assisted student activation → student creates private password → student logs in with YUVA ID → recovery remains linked-parent assisted.

### Reused parent

Second child is approved → existing parent-child link is added → parent receives notification → parent logs in normally → selects either authorized child → server verifies each link.

### Legacy filesystem user

Hybrid mode finds filesystem-only identity → legacy sign-in succeeds during the bounded transition → user is invited to activate SQL credentials → after activation, SQL password login becomes authoritative and legacy fallback is disabled for that identity.

## Compatibility strategy

- Preserve current session keys: canonical YUVA ID remains `student_id` for students and selected `parent_student_id` for parents.
- Keep portal pages and Student UI V1 consuming the existing filesystem-shaped student record.
- Convert SQL rows through a compatibility adapter; do not make portal templates aware of storage type.
- Keep parent identity in authenticated session state separate from the selected child context, even if the legacy session key is retained during transition.
- Do not expose SQL-only security fields to portal templates.
- Inventory duplicate YUVA IDs and emails before enabling hybrid mode.
- End the legacy period only after eligible users have activation coverage, support procedures exist, and fallback use is acceptably low.

## Rollout plan

1. Approve this specification and resolve the open questions.
2. Review and approve any minimal activation/lock/session migration separately.
3. Implement Backend Commit 5 with `PORTAL_AUTH_MODE` absent/defaulting to `filesystem`.
4. Run database-free authentication and compatibility tests.
5. Run synthetic integration tests against the approved non-production SQL test database.
6. Deploy to staging without enabling SQL approval and with authentication still in filesystem mode.
7. Validate filesystem regressions and public smoke checks.
8. Configure and test activation delivery in staging without permanent passwords or production data.
9. Enable hybrid authentication in staging only; validate all user journeys, conflicts, abuse controls, audit logs, cleanup, and mail privacy.
10. Keep SQL approval disabled until the complete staging gate passes.
11. Produce a separate production cutover plan and obtain explicit authorization.

Production prerequisites include an approved activation mechanism, schema support, verified mail delivery, conflict inventory, recovery runbook, monitoring, privacy review, youth-consent review, support training, tested rollback, and successful staging gates.

## Rollback plan

- Restore `PORTAL_AUTH_MODE=filesystem`.
- Do not change `PORTAL_STORAGE_MODE`.
- Keep `SQL_APPROVAL_ENABLED=false` unless separately authorized after validation.
- If required, redeploy the last validated pre-Commit-5 artifact.
- Preserve SQL accounts and audit data for diagnosis; do not delete or reverse identity data automatically.
- Invalidate any staging activation/reset tokens created during the failed rollout.
- Verify legacy student and parent login, sessions, and public portal smoke checks.
- Document the reason, impact, conflicts encountered, and conditions required before retry.

## Test plan

### Database-free

- Mode parsing defaults to filesystem and rejects unsupported values safely.
- Filesystem mode performs no SQL authentication lookup.
- SQL and hybrid repositories enforce active, approved, activated, and exact-role conditions.
- Student login succeeds by YUVA ID with the correct password.
- Verified student email remains usable for activation and recovery, and repository contracts retain future email-login compatibility without enabling it in Version 1.
- Parent login succeeds by verified email and exposes only linked children.
- Wrong password, missing user, pending, rejected, inactive, suspended, locked, wrong-role, and unlinked-child cases return indistinguishable public failures.
- `.invalid` addresses cannot be login, verification, notification, or recovery identifiers.
- Password hashes use `PASSWORD_DEFAULT`; successful verification rehashes when needed.
- Activation, verification, and reset tokens expire, are single-use, and invalidate replacements.
- Hybrid order is deterministic; activated SQL identities cannot downgrade to legacy factors.
- Compatible duplicates follow policy; conflicts fail closed and log sanitized categories.
- Existing filesystem login continues unchanged during compatibility mode.
- Student and parent session keys remain portal-compatible.
- Session IDs regenerate on login, activation sign-in, password change, and relevant privilege/context changes.
- CSRF and throttling apply without leaking account state.
- No sensitive values appear in logs or notifications.

### Azure SQL integration

- Synthetic approved student and parent activation.
- Student login by YUVA ID in Version 1; verified-email login remains a later-version integration case.
- Addressless student activation through a linked parent.
- Parent reuse across multiple synthetic students and child selection.
- Prevention of access to an unlinked child.
- Pending/rejected/inactive/suspended/locked and role-confusion rejection.
- Wrong-password counters, temporary lock, expiry, and successful reset.
- Atomic single-use activation and reset tokens under repeat/concurrent requests.
- Password rehash and last-login updates.
- Hybrid filesystem fallback, SQL precedence after activation, and conflict failure.
- Activity-log and notification-queue behavior without sensitive data.
- Transaction rollback and complete synthetic cleanup.
- Existing filesystem login regression.

No production data, production credentials, automatic SQL approval, Gate 5, frontend redesign, or Azure authentication/configuration change is part of these tests.

## Open questions

1. What age or program rule determines when parent-assisted activation is required, optional, or inappropriate?
2. May a parent initiate student password recovery indefinitely, or should that authority end at a defined age?
3. Should activation automatically establish a session, or always require a fresh login? A fresh login is recommended for simpler audit semantics.
4. Is a dedicated activation-token table preferred over new purpose-specific columns? A table is recommended for clean single-use history, resend handling, and multiple channels.
5. Should throttling state live on `users`, in a dedicated authentication-attempt table, or in a shared cache? A durable database design is needed across App Service instances.
6. What concurrent-session limit and session-revocation mechanism are acceptable for Version 1?
7. What exact identity fields qualify filesystem and SQL duplicates as compatible, and who resolves conflicts?
8. What is the legacy compatibility end date, and how will activation progress be measured?
9. Which verified support process is permitted for an addressless student who has no available linked parent?
10. What youth privacy, consent, and retention requirements apply by jurisdiction and participant age?
11. How will organization-admin and master-admin roles be represented later, given the current SQL role constraint?
12. Is login CSRF and rate limiting currently provided anywhere outside the PHP application? Backend Commit 5 must not assume that without verification.

## Final recommendation

Approve the parent-led, password-based activation model with direct activation for students who control a verified real email. Keep YUVA ID plus password as the Version 1 student login, retain verified email for activation, recovery, and future login support, make SQL authentication authoritative only after activation, and use hybrid mode solely as a bounded migration bridge.

Before Backend Commit 5 begins, approve a minimal authentication-state design covering activation timestamps/tokens, password-change time, durable throttling, and session revocation. Until that design and staging validation are complete, keep implementation paused, `PORTAL_AUTH_MODE` at its filesystem default, and SQL approval disabled.
