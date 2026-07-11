# PDR-004 - Lifelong YUVA ID

## Date

July 10, 2026

## Status

Accepted

## Volume / Chapter Affected

Student Platform, Parent Platform, Organization Platform, Master Administration, API, Database, Security.

## Decision

Every student receives a permanent lifelong YUVA ID that is immutable, globally unique, never reused, and not dependent on any single organization membership.

## Reason

Students may change schools, cities, countries, organizations, programs, or life stages. A student's identity and achievement record must remain stable through those transitions.

## Alternatives Considered

- Use database ID as the student-facing identifier.
- Create a new student ID per organization.
- Let organizations assign student IDs.
- Use email as the primary student identity.

## Consequences

- YUVA ID generation must happen on the backend only.
- YUVA ID must be protected by a unique database constraint.
- Student identity must be modeled separately from organization membership.
- Organization offboarding must not delete or replace the student's YUVA ID.
- Merge and duplicate-account workflows must preserve YUVA ID history.

## Developer Impact

- Never generate YUVA IDs on the frontend.
- Never make YUVA ID editable by users or organization admins.
- Never reuse a YUVA ID after account deletion, merge, or deactivation.
- Treat YUVA ID as a public student identity, not as an internal database primary key.

## Follow-Up Work

- Define YUVA ID generation service.
- Define duplicate student merge workflow.
- Define indexes and uniqueness constraints.
- Define account recovery and transfer behavior.

