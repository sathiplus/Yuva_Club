# Organization Admin Foundation V1

## Status and boundary

Backend Extension 1 adds Organization Admin as a new, SQL-backed platform
capability beside the existing global Master Admin. It does not replace, rename,
or weaken Master Admin authorization.

Extension 1A establishes the organization domain only. It does not add login,
sessions, repositories, routes, user interfaces, invitations, activation,
organization-management actions, or Organization Admin permissions.

Existing students, registrations, sessions, and activity logs remain unassigned
after the migration. No production organization or administrator is seeded.

## Version 1 relationship

Version 1 supports:

```text
Organization
└── Student
```

`organization_students` owns this assignment. A student may have at most one
active organization assignment, while transferred and removed assignments remain
available as history. Organization ownership is never inferred from school name,
email domain, program, or user-supplied display text.

The join-table design avoids placing permanent tenant ownership directly on the
student row. This permits future hierarchy changes without changing student
identity or existing student foreign keys.

## Future hierarchy

The long-term hierarchy may evolve to:

```text
Tenant
└── Organization
    └── Program
        └── Student
```

This hierarchy is roadmap documentation only and is not implemented in Version 1.

A future `tenants` table can become the owner of one or more organizations.
Organization IDs remain stable. A future organization-program membership can
associate existing global program definitions or organization-specific program
instances with an organization. Student assignment history can then reference the
organization-program membership while retaining the existing organization and
student identifiers.

Compatibility requirements for that evolution:

- Existing organization IDs and student IDs remain unchanged.
- Version 1 organization assignments remain valid.
- New tenant and program relationships are additive and initially nullable.
- Authorization continues to require an organization boundary even when tenant
  and program context are introduced.
- No tenant or program is inferred from an email domain or student profile text.
- Repository methods may gain an additional trusted context object, but must not
  remove the organization predicate.

## Future Coach / Mentor role

A future role may sit between Organization Admin and Student:

```text
Organization Admin
└── Coach / Mentor
    └── Assigned Students
```

This role is roadmap documentation only and is not implemented in Version 1.

Coach / Mentor must be an explicit SQL role and an explicit organization
membership. It must not inherit all Organization Admin privileges. Future mentor
access should require a separate assignment between the mentor and approved
students, groups, or programs.

Expected future boundaries:

- Read assigned student preparation and approved progress.
- Provide permitted coaching feedback through an audited workflow.
- Never manage organization administrators.
- Never transfer students between organizations.
- Never change organization settings.
- Never approve account activation or authentication changes.
- Never view students outside explicit assignments.
- Never access unapproved AI output.

Adding this role will require a separately reviewed user-role constraint change,
membership policy, repository predicates, session mapping, and cross-tenant tests.

## Foundation entities

Extension 1A adds:

- `organizations`
- `organization_memberships`
- `organization_students`
- `organization_settings`
- `organization_announcements`

It also adds nullable organization references to:

- `registrations`
- `sessions`
- `activity_logs`

The `users` role constraint is extended with the exact
`organization_admin` role. Organization administrator identity must never be
represented as the global `admin` role.

## Isolation invariants

Later Extension 1 phases must preserve these invariants:

1. Organization context comes only from a revalidated server-side session.
2. A request parameter is never proof of organization membership.
3. Every organization resource query includes an organization predicate.
4. Every mutation repeats the organization predicate in the SQL statement.
5. Student-dependent resources derive ownership through an active
   `organization_students` assignment.
6. Zero-row mutations fail closed.
7. Organization suspension or membership revocation invalidates access.
8. Master Admin and Organization Admin sessions remain distinct.
9. Logs contain sanitized internal identifiers and action categories only.
10. Existing unassigned records remain Master-Admin-only.

## Implementation sequence

The approved sequence remains:

1. Extension 1A — domain migration and schema contracts.
2. Extension 1B — tenant-scoped repository and authorization service.
3. Extension 1C — Organization Admin authentication and session lifecycle.
4. Extension 1D — Master Admin organization workflows.
5. Extension 1E — Organization Admin workflows and frontend.

Each phase requires database-free tests, isolated Azure SQL integration where
applicable, staging validation, and review before commit or merge.
