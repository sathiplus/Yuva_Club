# PDR-007 - Multi-Tenant Architecture

## Date

July 10, 2026

## Status

Accepted

## Volume / Chapter Affected

Architecture, Organization Platform, Master Administration, API, Database, Security, Reporting.

## Decision

YUVA Club uses a multi-tenant platform architecture so one platform can securely serve many organizations while preserving student-owned identity and achievements.

## Reason

Schools, nonprofits, libraries, community programs, sponsors, and future chapters need organization-specific administration, rosters, reports, branding, events, and subscriptions without requiring separate codebases.

## Alternatives Considered

- Deploy a separate platform per organization.
- Store all organization data without tenant boundaries.
- Require every student to belong to exactly one organization.

## Consequences

- Organization data must be scoped and isolated.
- APIs must enforce organization authorization server-side.
- Reports must respect tenant boundaries.
- Master admins need controlled cross-tenant visibility.
- Students can have individual participation and multiple organization memberships.

## Developer Impact

- Never trust organization ID from the frontend without authorization checks.
- Every organization-scoped query must include permission enforcement.
- Student identity and organization membership must be separate concepts.
- Audit cross-tenant admin access.

## Follow-Up Work

- Define tenant access rules.
- Define organization membership model.
- Define report isolation behavior.
- Define master admin sensitive-read audit events.

