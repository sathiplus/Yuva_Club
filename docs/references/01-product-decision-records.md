# Product Decision Records Standard

## Purpose

Product Decision Records preserve important product, architecture, AI, database, security, and operations decisions so future contributors understand why the platform works the way it does.

## When a PDR Is Required

Create or update a PDR when a decision affects:

- Student identity or achievement ownership
- Free individual membership
- Organization subscriptions or billing
- Parent consent or minor privacy
- AI scoring, prompts, coaching, review, or safety behavior
- Azure architecture
- API contracts
- Database schema
- RBAC or permissions
- Audit logging
- Data retention
- Internationalization
- Accessibility standards
- Any durable rule that developers should not casually change

## PDR Template

```md
# PDR-000 - Decision Title

## Date

## Status

Proposed | Accepted | Superseded | Rejected

## Volume / Chapter Affected

## Decision

## Reason

## Alternatives Considered

## Consequences

## Developer Impact

## Follow-Up Work
```

## Required Decision Fields in Chapters

Each chapter must include a Product Decisions section listing decisions that affect implementation.

| Field | Requirement |
|---|---|
| Decision ID | Stable ID, such as PDR-001 |
| Decision | One sentence that can be referenced by engineers |
| Reason | Why the decision exists |
| Status | Proposed, Accepted, Superseded, or Rejected |
| Link | Link to the full PDR if it exists |

## Current Permanent Decisions

- [PDR-001 - Students own their achievements](../decisions/PDR-001-students-own-achievements.md)
- [PDR-002 - Individual student registration remains free](../decisions/PDR-002-individual-registration-free.md)
- [PDR-003 - Organizations pay for platform capabilities](../decisions/PDR-003-organizations-pay-for-platform-capabilities.md)
- [PDR-004 - Lifelong YUVA ID](../decisions/PDR-004-lifelong-yuva-id.md)
- [PDR-005 - Student-led learning](../decisions/PDR-005-student-led-learning.md)
- [PDR-006 - AI as coach](../decisions/PDR-006-ai-as-coach.md)
- [PDR-007 - Multi-tenant architecture](../decisions/PDR-007-multi-tenant-architecture.md)
- [PDR-008 - Azure cloud platform](../decisions/PDR-008-azure-cloud-platform.md)
- [PDR-009 - Shared engineering references before repeated feature specs](../decisions/PDR-009-shared-engineering-references.md)

## Register

The full decision manual and central decision index are maintained at:

- [YUVA Club Product Decision Records](../decisions/YUVA-Club-Product-Decision-Records.md)
- [Product Decision Register](../decisions/00-product-decision-register.md)
