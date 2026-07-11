# PDR-001 - Students Own Their Achievements

## Date

July 10, 2026

## Status

Accepted

## Volume / Chapter Affected

All volumes, especially Student Platform, Organization Platform, Database, API, and Master Administration.

## Decision

Students own their YUVA achievements, including presentations, certificates, badges, portfolio items, volunteer hours, and long-term progress history.

## Reason

Students may move between organizations, change schools, graduate, become alumni, or later become mentors. Their growth record should remain with them through those transitions.

## Alternatives Considered

- Store achievements only under each organization.
- Require students to create a new account for each organization.
- Treat certificates and badges as organization-owned records only.

## Consequences

- The database must model student identity separately from organization membership.
- Organization offboarding must not erase student achievements.
- Exports and reports must distinguish student-owned records from organization-specific records.
- Permissions must protect organization-private notes while preserving student-owned achievements.

## Follow-Up Work

- Define the StudentAchievement, CertificateAward, BadgeAward, PortfolioItem, and VolunteerHourRecord models.
- Define organization offboarding rules.
- Define student export and verification rules.

