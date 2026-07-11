# Product Decision Register

## Purpose

This register is the central index of durable YUVA Club product, architecture, engineering, AI, security, database, and operations decisions.

Every implementation-ready chapter should reference the decisions that govern its behavior. If a future feature contradicts one of these decisions, the contradiction must be documented in a new PDR before implementation.

## Current Decisions

| PDR | Decision | Reason | Status | Link |
|---|---|---|---|---|
| PDR-001 | Students own their achievements | Students may move between organizations without losing their record | Accepted | [PDR-001](./PDR-001-students-own-achievements.md) |
| PDR-002 | Individual student registration remains free | Maximize access, adoption, and student-first mission alignment | Accepted | [PDR-002](./PDR-002-individual-registration-free.md) |
| PDR-003 | Organizations pay for platform capabilities | Sustainable revenue while preserving free individual access | Accepted | [PDR-003](./PDR-003-organizations-pay-for-platform-capabilities.md) |
| PDR-004 | Every student receives a lifelong YUVA ID | Students may change schools, cities, or organizations without losing identity | Accepted | [PDR-004](./PDR-004-lifelong-yuva-id.md) |
| PDR-005 | YUVA Club uses a student-led learning model | Active participation builds confidence, leadership, and communication skill | Accepted | [PDR-005](./PDR-005-student-led-learning.md) |
| PDR-006 | AI is a coach, not a decision maker | AI should guide improvement while humans retain mentorship and final judgment | Accepted | [PDR-006](./PDR-006-ai-as-coach.md) |
| PDR-007 | The platform uses multi-tenant architecture | One secure platform must support many organizations | Accepted | [PDR-007](./PDR-007-multi-tenant-architecture.md) |
| PDR-008 | Azure is the preferred cloud platform | Azure aligns with enterprise scale, security, SQL, identity, and AI services | Accepted | [PDR-008](./PDR-008-azure-cloud-platform.md) |
| PDR-009 | Shared engineering references come before repeated feature specs | Reusable standards reduce duplication and improve consistency | Accepted | [PDR-009](./PDR-009-shared-engineering-references.md) |

## Implementation Rule

When a developer implements a feature, they should review:

1. The relevant feature chapter.
2. The shared engineering references.
3. This decision register.
4. Any linked PDRs.

## Change Rule

Do not rewrite history in existing accepted PDRs. If a decision changes, create a new PDR that supersedes the old decision and update this register.

