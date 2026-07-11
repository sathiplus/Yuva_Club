# PDR-046 - Platform Administrator Access Model

## Metadata

| Field | Value |
| --- | --- |
| Decision ID | PDR-046 |
| Date | 2026-07-10 |
| Volume Affected | Master Administration, Organization Platform, Security, Operations |
| Status | Accepted |

## Decision

YUVA Club uses a Platform Administrator role for platform-level administration.

The first platform administrator account is:

```text
admin@yuvaclub.app
```

This account is created only by the system. Public users cannot create platform administrator accounts.

Future platform administrators may be created only by an existing authorized platform administrator.

## Reason

YUVA Club is an independent product and must separate public student registration from privileged administration.

Platform administrators control global configuration, organizations, subscriptions, organization administrators, security settings, AI settings, Zoom settings, and platform operations. These privileges must never be available through public registration.

## Access Rules

- Students may register publicly.
- Parents may register independently or by invitation in the future Parent Platform workflow.
- Organizations cannot self-register initially.
- Organization administrators are created or invited only by a Platform Administrator.
- Mentors are invited only by a Platform Administrator or authorized Organization Administrator.
- Platform administrators can create organizations, assign administrators, configure subscriptions, enable AI, enable Zoom, and configure organization branding.

## Alternatives Considered

- Allow public organization administrator signup.
- Allow organizations to self-register.
- Hard-code a single permanent admin account with no future platform administrator model.

## Consequences

- The public website must not expose an organization administrator signup page.
- Organization access pages must communicate that organization administration is invitation-only.
- Admin screens should use Platform Administrator language.
- Future RBAC must include a platform administrator role separate from organization administrator.
- Audit logs must capture platform administrator actions.

## Developer Notes

- Never let a public form create a platform administrator, organization administrator, or mentor account.
- Do not treat organization administrators as platform administrators.
- Keep `admin@yuvaclub.app` as the initial system-created platform administrator, but design the role model to support more platform administrators later.
