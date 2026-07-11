# YUVA Club Documentation Repository

This folder contains the official Markdown source for the YUVA Club Platform Blueprint.

## Source of Truth

Markdown files in this folder are the canonical source. Word and PDF files are export formats, not the primary source.

## Working Principles

- Write for implementation, not presentation.
- Keep each chapter consistent enough that developers know where to find product rules, UI requirements, APIs, database tables, tests, and acceptance criteria.
- Store durable product decisions in `docs/decisions/`.
- Store diagrams as Mermaid or image assets in `docs/diagrams/`.
- Store wireframes and screen references in `docs/wireframes/`.
- Never create competing files called `v1`, `v2`, `final`, or `reviewed`.

## Core References

- [Platform Constitution](./00-Platform-Constitution.md)
- [YUVA Club Design Bible](./00-YUVA-Club-Design-Bible.md)
- [Master Blueprint Map](./00-Master-Blueprint-Map.md)
- [Current Implementation Alignment](./00-Current-Implementation-Alignment.md)
- [Chapter Template](./00-Chapter-Template.md)
- [Engineering Specification Standard](./00-Engineering-Specification-Standard.md)
- [Core Engineering References](./references/README.md)
- [Product Decision Records Manual](./decisions/YUVA-Club-Product-Decision-Records.md)
- [Product Decision Register](./decisions/00-product-decision-register.md)

## Phase 2 Reference Documents

Before expanding many more feature chapters, build and maintain these shared references:

- [Product Decision Records Standard](./references/01-product-decision-records.md)
- [YUVA UI Design System](./references/02-ui-design-system.md)
- [API Standards](./references/03-api-standards.md)
- [Azure Architecture Standards](./references/04-azure-architecture-standards.md)
- [Azure SQL Database Standards](./references/05-azure-sql-database-standards.md)
- [Security Standards](./references/06-security-standards.md)
- [Coding Standards](./references/07-coding-standards.md)
- [Testing Standards](./references/08-testing-standards.md)
- [AI Standards](./references/09-ai-standards.md)
- [Notification Standards](./references/10-notification-standards.md)

## Phase 2 Roadmap

Phase 2 is Engineering Specifications. Each feature chapter should be a build-ready mini project.

### Sprint 1 - Shared Foundation

The first sprint focuses on documents every feature depends on:

1. [Product Decision Records](./decisions/YUVA-Club-Product-Decision-Records.md)
2. YUVA Design System
3. API Standards
4. Azure Architecture Standards
5. Azure SQL Database Standards
6. Security Standards
7. Coding Standards
8. Testing Standards
9. AI Standards
10. Notification Standards

Initial chapters:

- Student Registration and Onboarding
- Student Dashboard
- Presentation Center
- AI Coach
- Digital Portfolio
- Parent Platform
- Mentor Platform
- Organization Platform expanded specification
