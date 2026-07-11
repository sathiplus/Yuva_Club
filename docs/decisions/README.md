# Product Decision Records

Product Decision Records capture durable decisions that affect product behavior, architecture, security, data, AI, pricing, operations, or long-term maintainability.

## Decision Register

Start with the full decision manual, then use the register for quick lookup:

- [YUVA Club Product Decision Records](./YUVA-Club-Product-Decision-Records.md)
- [Product Decision Register](./00-product-decision-register.md)

## Current Core Decisions

| PDR | Decision | Link |
|---|---|---|
| PDR-001 | Students own their achievements | [PDR-001](./PDR-001-students-own-achievements.md) |
| PDR-002 | Individual student registration remains free | [PDR-002](./PDR-002-individual-registration-free.md) |
| PDR-003 | Organizations pay for platform capabilities | [PDR-003](./PDR-003-organizations-pay-for-platform-capabilities.md) |
| PDR-004 | Every student receives a lifelong YUVA ID | [PDR-004](./PDR-004-lifelong-yuva-id.md) |
| PDR-005 | YUVA Club uses a student-led learning model | [PDR-005](./PDR-005-student-led-learning.md) |
| PDR-006 | AI is a coach, not a decision maker | [PDR-006](./PDR-006-ai-as-coach.md) |
| PDR-007 | The platform uses multi-tenant architecture | [PDR-007](./PDR-007-multi-tenant-architecture.md) |
| PDR-008 | Azure is the preferred cloud platform | [PDR-008](./PDR-008-azure-cloud-platform.md) |
| PDR-009 | Shared engineering references come before repeated feature specs | [PDR-009](./PDR-009-shared-engineering-references.md) |
| PDR-046 | Platform administrator access model | [PDR-046](./PDR-046-platform-administrator-access-model.md) |

## When to Create a PDR

Create a PDR when a decision:

- Changes product scope
- Defines ownership of student data or achievements
- Changes registration, consent, identity, or privacy behavior
- Affects organization billing or subscriptions
- Changes AI policy, scoring, prompts, or review behavior
- Changes database architecture or API contracts
- Introduces a security or compliance rule
- Establishes a permanent product philosophy

## PDR Format

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

## Follow-Up Work
```
