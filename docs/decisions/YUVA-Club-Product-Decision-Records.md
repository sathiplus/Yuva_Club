# YUVA Club Product Decision Records

## Document Control

| Field | Value |
|---|---|
| Document | YUVA Club Product Decision Records |
| Status | Living Decision Manual |
| Version | 1.0 |
| Last Updated | July 10, 2026 |
| Source of Truth | Markdown in `docs/decisions/` |
| Primary Audience | Founders, product managers, designers, engineers, AI developers, security reviewers, operators, advisors |

## Purpose

The Product Decision Records document explains why YUVA Club is designed the way it is.

This is the decision constitution for the platform. Every future product specification, UX specification, API contract, database model, security rule, AI workflow, and operations process should reference these decisions instead of restating the same reasoning.

Future developers should be able to ask, "Why did YUVA Club make this choice?" and find the answer here.

## How to Use This Document

Every implementation-ready chapter should include a Product Decisions section. That section should reference the relevant PDR IDs from this document.

Example:

```md
This workflow is governed by PDR-004, PDR-006, PDR-007, and PDR-021.
```

Do not rewrite the reasoning in every feature chapter. Link to the decision.

## PDR Numbering Note

The repository already contains accepted PDRs numbered PDR-001 through PDR-009. This document preserves those IDs to avoid breaking links. New decisions continue from PDR-010 onward.

## Summary Register

| PDR | Category | Decision | Status |
|---|---|---|---|
| PDR-001 | Student | Students own their achievements | Accepted |
| PDR-002 | Business | Individual student registration remains free | Accepted |
| PDR-003 | Business | Organizations pay for platform capabilities | Accepted |
| PDR-004 | Student | Every student receives a lifelong YUVA ID | Accepted |
| PDR-005 | Vision | YUVA Club uses a student-led learning model | Accepted |
| PDR-006 | AI | AI is a coach, not a decision maker | Accepted |
| PDR-007 | Architecture | The platform uses multi-tenant architecture | Accepted |
| PDR-008 | Technical | Azure is the preferred cloud platform | Accepted |
| PDR-009 | Documentation | Shared engineering references come before repeated feature specs | Accepted |
| PDR-010 | Vision | YUVA Club is a student-led leadership platform | Accepted |
| PDR-011 | Vision | Active learning is more important than passive content consumption | Accepted |
| PDR-012 | Vision | Human mentorship remains part of the learning model | Accepted |
| PDR-013 | Student | Students may belong to multiple organizations over time | Accepted |
| PDR-014 | Student | Student identity is separate from organization membership | Accepted |
| PDR-015 | Student | Student onboarding must be fast but meaningful | Accepted |
| PDR-016 | Student | Parent consent is configurable by age, region, and policy | Accepted |
| PDR-017 | Organization | Organization data is isolated by default | Accepted |
| PDR-018 | Organization | Organizations cannot see students outside their allowed scope | Accepted |
| PDR-019 | Organization | Master Admin has controlled global visibility | Accepted |
| PDR-020 | Organization | Organization offboarding must preserve student continuity | Accepted |
| PDR-021 | Technical | REST APIs are the primary integration pattern | Accepted |
| PDR-022 | Technical | Azure SQL is the primary relational database | Accepted |
| PDR-023 | Technical | Blob Storage is used for files and generated artifacts | Accepted |
| PDR-024 | Technical | Application Insights is the default observability layer | Accepted |
| PDR-025 | Technical | GitHub is the source control and collaboration home | Accepted |
| PDR-026 | Technical | Codex uses Markdown docs as engineering source material | Accepted |
| PDR-027 | AI | AI feedback must be explainable | Accepted |
| PDR-028 | AI | AI prompts and scoring rubrics must be versioned | Accepted |
| PDR-029 | AI | Presentation analysis begins with speech and content before advanced video | Accepted |
| PDR-030 | AI | AI onboarding may personalize the first learning plan | Accepted |
| PDR-031 | Security | Least privilege is required across all roles | Accepted |
| PDR-032 | Security | Sensitive actions must be audited | Accepted |
| PDR-033 | Security | MFA is required for administrators and future high-risk workflows | Accepted |
| PDR-034 | Security | Passwords must follow strong hashing and policy rules | Accepted |
| PDR-035 | Security | Child protection and minor privacy are core requirements | Accepted |
| PDR-036 | Product | Certificates are student-owned achievements | Accepted |
| PDR-037 | Product | Badges represent progress and milestones | Accepted |
| PDR-038 | Product | Portfolio is the durable student showcase | Accepted |
| PDR-039 | Product | Volunteer hours are part of the leadership record | Accepted |
| PDR-040 | Product | Leaderboards must be designed carefully to avoid harmful comparison | Accepted |
| PDR-041 | Product | Levels and points are configurable motivational systems | Accepted |
| PDR-042 | Future | Mobile apps are future platform extensions, not the first source of truth | Accepted |
| PDR-043 | Future | University and scholarship pathways are future expansion areas | Accepted |
| PDR-044 | Future | Recruiter and employer access requires separate governance | Accepted |
| PDR-045 | Future | Marketplace features are deferred until the core platform is stable | Accepted |
| PDR-046 | Security | Platform administrator access model | Accepted |

---

# Section 1 - Vision Decisions

## PDR-005 - Student-Led Learning Model

**Decision:** YUVA Club uses a student-led learning model.

**Reason:** Students build confidence, communication skill, critical thinking, and leadership through active participation. Presenting, questioning, teaching, mentoring, and reflecting are more valuable than simply consuming content.

**Implementation Impact:**

- Student dashboards must prioritize next actions.
- Presentation, practice, reflection, and feedback workflows are core.
- UX should avoid hiding student action behind too many menus.
- Learning records should capture participation, not just completion.

**Status:** Accepted.

## PDR-010 - Student-Led Leadership Platform

**Decision:** YUVA Club is a student-led leadership platform, not merely a course website or presentation storage tool.

**Reason:** The platform exists to help students become speakers, facilitators, mentors, collaborators, and leaders. The product must support repeated practice and visible growth.

**Alternatives Considered:**

- A content library.
- A simple certificate platform.
- A school-only learning management system.

**Implementation Impact:**

- Every major student workflow should connect to leadership growth.
- Feature success should be measured by participation and improvement, not only signups.
- The platform should support organizations without becoming organization-owned.

**Status:** Accepted.

## PDR-011 - Active Learning Over Passive Consumption

**Decision:** YUVA Club prioritizes active student participation over passive content consumption.

**Reason:** Students learn leadership by doing. Passive lessons can support the experience, but they should not become the center.

**Implementation Impact:**

- Content pages should lead to action: practice, present, reflect, volunteer, join an event, or ask a question.
- The dashboard should surface unfinished action items.
- Analytics should track engagement with learning activities, not just page views.

**Status:** Accepted.

## PDR-012 - Human Mentorship Remains Central

**Decision:** Human mentors remain part of the YUVA learning model even as AI capabilities expand.

**Reason:** Mentors provide context, empathy, encouragement, cultural understanding, and judgment that AI should not replace.

**Implementation Impact:**

- AI feedback must not remove mentor feedback workflows.
- Mentor review should be available for meaningful student work.
- Sensitive or uncertain AI outputs should support human review.

**Status:** Accepted.

---

# Section 2 - Student Decisions

## PDR-001 - Students Own Their Achievements

**Decision:** Students own their YUVA achievements, including presentations, certificates, badges, portfolio items, volunteer hours, and long-term progress history.

**Reason:** Students may move between organizations, change schools, graduate, become alumni, or later become mentors. Their growth record should remain with them through those transitions.

**Implementation Impact:**

- Student identity must be modeled separately from organization membership.
- Organization offboarding must not erase student achievements.
- Reports must distinguish student-owned records from organization-private records.

**Status:** Accepted.

## PDR-004 - Lifelong YUVA ID

**Decision:** Every student receives a permanent lifelong YUVA ID that is immutable, globally unique, never reused, and not dependent on any single organization.

**Reason:** Students may change schools, cities, countries, organizations, programs, or life stages. Their identity should remain stable.

**Implementation Impact:**

- YUVA ID generation happens only on the backend.
- YUVA ID is never user-editable.
- YUVA ID is protected by a unique database constraint.
- Duplicate-account workflows must preserve YUVA ID history.

**Status:** Accepted.

## PDR-013 - Multiple Organizations Over Time

**Decision:** Students may belong to multiple organizations over time.

**Reason:** Students may participate through schools, nonprofits, libraries, community programs, or sponsors at different stages.

**Implementation Impact:**

- Membership must be modeled as a history table, not a single field on the student.
- Dashboards must show current organization context clearly.
- Reports must respect organization scope.
- Student achievements remain attached to the student.

**Status:** Accepted.

## PDR-014 - Student Identity Is Separate From Membership

**Decision:** Student identity is separate from organization membership.

**Reason:** Organizations support learning, but they do not define the student's permanent identity.

**Implementation Impact:**

- `Students` and `StudentOrganizationMemberships` must be separate entities.
- Organization suspension must not delete student accounts.
- Students can exist with no organization.

**Status:** Accepted.

## PDR-015 - Fast but Meaningful Onboarding

**Decision:** Student onboarding should take less than five minutes while collecting enough information to personalize the student's learning path.

**Reason:** Long onboarding reduces adoption; shallow onboarding weakens personalization.

**Implementation Impact:**

- Use progressive profile completion.
- Collect only required fields up front.
- Move optional preferences into a wizard.
- Dashboard initialization should happen immediately after onboarding.

**Status:** Accepted.

## PDR-016 - Configurable Parent Consent

**Decision:** Parent or guardian consent rules are configurable by age, region, organization policy, and legal requirement.

**Reason:** Youth privacy rules vary by jurisdiction, age, and program context.

**Implementation Impact:**

- Do not hard-code the consent age threshold.
- Store consent version, timestamp, actor, method, and policy version.
- Preserve consent history.
- Support withdrawal and re-consent.

**Status:** Accepted.

---

# Section 3 - Organization Decisions

## PDR-003 - Organizations Pay for Platform Capabilities

**Decision:** Organizations pay for administrative capabilities, reporting, private groups, branding, mentor workflows, scaled rosters, and operational support.

**Reason:** This creates a sustainable revenue model while preserving free foundational access for individual students.

**Implementation Impact:**

- Subscription plans must be configurable.
- Organization features must be permissioned by plan.
- Billing data must be separated from student-owned learning records.

**Status:** Accepted.

## PDR-007 - Multi-Tenant Architecture

**Decision:** YUVA Club uses a multi-tenant platform architecture so one platform can securely serve many organizations.

**Reason:** Schools, nonprofits, libraries, community programs, sponsors, and future chapters need organization-specific administration without separate codebases.

**Implementation Impact:**

- Organization-scoped APIs must enforce tenant permissions.
- Reports must respect tenant boundaries.
- Master admins need controlled cross-tenant visibility.

**Status:** Accepted.

## PDR-017 - Organization Data Is Isolated

**Decision:** Organization data is isolated by default.

**Reason:** Privacy, trust, and partner confidence require strict separation between organizations.

**Implementation Impact:**

- Queries must include organization scope where applicable.
- Organization admins cannot see unrelated students or organizations.
- Cross-tenant admin access must be audited.

**Status:** Accepted.

## PDR-018 - Organizations Cannot See Outside Their Scope

**Decision:** Organizations cannot see students outside their own organization scope.

**Reason:** Students and families trust that organization access is limited.

**Implementation Impact:**

- Authorization must be enforced in backend services.
- Frontend filtering is not sufficient.
- Reports must suppress or exclude out-of-scope data.

**Status:** Accepted.

## PDR-019 - Master Admin Has Controlled Global Visibility

**Decision:** YUVA Master Admin roles may have global visibility for platform administration, support, security, billing, and operations.

**Reason:** YUVA needs the ability to operate, support, audit, and secure the platform.

**Implementation Impact:**

- Master Admin access must use RBAC.
- Sensitive reads should require audit events.
- Break-glass access must be logged and reviewed.

**Status:** Accepted.

## PDR-020 - Organization Offboarding Preserves Student Continuity

**Decision:** Organization offboarding must preserve student identity and student-owned achievements.

**Reason:** Students should not lose their growth records because an organization cancels, suspends, or leaves the platform.

**Implementation Impact:**

- Membership can end while student records continue.
- Organization-private records follow retention policies.
- Student-owned achievements remain visible to the student.

**Status:** Accepted.

---

# Section 4 - Technical Decisions

## PDR-008 - Azure Cloud Platform

**Decision:** Azure is the preferred cloud platform for YUVA Club architecture.

**Reason:** Azure provides enterprise-grade hosting, Azure SQL, identity integrations, Key Vault, Application Insights, Azure Communication Services, and Azure OpenAI alignment.

**Implementation Impact:**

- Architecture standards should reference Azure services.
- Secrets should use Key Vault.
- Observability should use Application Insights or compatible telemetry.

**Status:** Accepted.

## PDR-021 - REST API as Primary Integration Pattern

**Decision:** REST APIs are the primary integration pattern for YUVA Club application features.

**Reason:** REST is broadly understood, easy to document, and suitable for web, mobile, admin, and integration clients.

**Implementation Impact:**

- APIs use versioned paths such as `/api/v1`.
- Endpoint contracts must include request, response, validation, errors, authentication, authorization, audit events, and rate limits.
- Feature specs link to API Standards.

**Status:** Accepted.

## PDR-022 - Azure SQL as Primary Relational Database

**Decision:** Azure SQL is the primary relational database for YUVA Club.

**Reason:** The platform needs reliable relational modeling for students, organizations, memberships, consent, achievements, roles, billing, audit, and reporting.

**Implementation Impact:**

- Database standards assume Azure SQL.
- Schema changes should use migration scripts.
- Relational integrity is preferred for core business entities.

**Status:** Accepted.

## PDR-023 - Blob Storage for Files and Artifacts

**Decision:** Blob Storage is used for profile images, certificates, exports, generated artifacts, and future media assets.

**Reason:** Files should not be stored directly in the relational database.

**Implementation Impact:**

- Store metadata in SQL.
- Store binary content in Blob Storage.
- Use access policies and signed URLs where appropriate.

**Status:** Accepted.

## PDR-024 - Application Insights for Observability

**Decision:** Application Insights is the default observability layer.

**Reason:** YUVA Club needs visibility into performance, errors, dependency failures, registration funnels, AI processing, and background jobs.

**Implementation Impact:**

- Emit telemetry for critical flows.
- Track registration abandonment.
- Track email, AI, and dashboard initialization failures.

**Status:** Accepted.

## PDR-025 - GitHub as Source Control

**Decision:** GitHub is the source control and collaboration home for YUVA Club.

**Reason:** GitHub supports version control, review, collaboration, issue tracking, and Codex-friendly workflows.

**Implementation Impact:**

- Markdown docs live in the repository.
- Changes should be reviewed through commits and pull requests over time.
- Word/PDF exports are generated artifacts, not source of truth.

**Status:** Accepted.

## PDR-026 - Codex Uses Markdown Docs as Source Material

**Decision:** Codex uses Markdown documentation in `docs/` as implementation source material.

**Reason:** Markdown is searchable, version-controlled, reviewable, and easy for Codex and developers to reference.

**Implementation Impact:**

- Keep docs close to code.
- Use stable filenames and links.
- Avoid scattered Word-only source documents.

**Status:** Accepted.

---

# Section 5 - AI Decisions

## PDR-006 - AI as Coach

**Decision:** AI is a coach, not a decision maker.

**Reason:** AI can provide scalable feedback while humans provide empathy, context, mentorship, and judgment.

**Implementation Impact:**

- AI outputs must be labeled.
- AI outputs must be reviewable when sensitive.
- AI should recommend improvement, not permanently label students.

**Status:** Accepted.

## PDR-027 - AI Feedback Must Be Explainable

**Decision:** AI feedback must be explainable to students, parents, mentors, and admins.

**Reason:** Trust requires users to understand what AI feedback means and what its limits are.

**Implementation Impact:**

- Feedback categories must be named and described.
- AI outputs should include plain-language rationale.
- UI should include AI limitation notes where appropriate.

**Status:** Accepted.

## PDR-028 - AI Prompts and Rubrics Are Versioned

**Decision:** AI prompts, rubrics, and scoring configurations must be versioned.

**Reason:** AI behavior changes over time. Versioning allows review, rollback, comparison, and audit.

**Implementation Impact:**

- Store prompt version.
- Store rubric version.
- Store AI policy version with generated feedback.

**Status:** Accepted.

## PDR-029 - Presentation Analysis Starts With Speech and Content

**Decision:** Presentation analysis begins with speech and content before advanced video analysis.

**Reason:** Speech, structure, clarity, and reflection are valuable and easier to govern than full video/body-language analysis.

**Implementation Impact:**

- Start with transcript, audio, text, and rubric analysis.
- Treat video analysis as future enhancement.
- Avoid making body-language scoring a core MVP dependency.

**Status:** Accepted.

## PDR-030 - AI Onboarding Personalizes the First Learning Plan

**Decision:** AI may personalize a student's initial learning plan during onboarding.

**Reason:** Students benefit when the platform quickly understands goals, confidence, interests, and communication challenges.

**Implementation Impact:**

- AI onboarding questions must be age-appropriate.
- Students can continue without over-sharing.
- AI outputs initialize recommendations, not permanent labels.

**Status:** Accepted.

---

# Section 6 - Security Decisions

## PDR-031 - Least Privilege Across All Roles

**Decision:** YUVA Club uses least privilege across students, parents, mentors, organization admins, master admins, and support roles.

**Reason:** The platform handles minors, organization data, learning records, and sensitive account workflows.

**Implementation Impact:**

- Permissions are enforced server-side.
- Organization scope is always checked.
- Support and admin access are limited and audited.

**Status:** Accepted.

## PDR-032 - Sensitive Actions Are Audited

**Decision:** Sensitive actions must create audit records.

**Reason:** Trust, compliance, support, and security require traceability.

**Implementation Impact:**

- Audit actor, action, target, timestamp, reason where required, and before/after values when applicable.
- Sensitive reads by admins should be auditable.
- Audit logs must not be casually editable.

**Status:** Accepted.

## PDR-033 - MFA for Administrators

**Decision:** MFA is required for administrators and future high-risk workflows.

**Reason:** Administrative accounts can access sensitive platform controls.

**Implementation Impact:**

- Master Admin accounts require MFA.
- Organization admin MFA may be required by plan, policy, or future security posture.
- Break-glass access must be separately governed.

**Status:** Accepted.

## PDR-034 - Strong Password and Hashing Policy

**Decision:** Passwords must follow strong policy and approved hashing requirements.

**Reason:** Account security is foundational, especially for students and administrators.

**Implementation Impact:**

- Enforce password complexity or passphrase standards.
- Hash passwords with approved modern algorithms.
- Never store plaintext passwords.
- Password reset tokens are time-bound and single-use.

**Status:** Accepted.

## PDR-035 - Child Protection and Minor Privacy Are Core Requirements

**Decision:** Child protection and minor privacy are core platform requirements.

**Reason:** YUVA Club serves students and may handle data subject to COPPA, FERPA, GDPR, state privacy laws, and organization agreements.

**Implementation Impact:**

- Consent workflows must be configurable.
- Parent-child links must be verified.
- Sensitive student data access must be controlled.
- Safety reporting paths must exist.

**Status:** Accepted.

---

# Section 7 - Product Decisions

## PDR-036 - Certificates Are Student-Owned Achievements

**Decision:** Certificates are student-owned achievements, even when issued by an organization.

**Reason:** Certificates represent student work and should remain part of the student's long-term record.

**Implementation Impact:**

- Store certificate issuer and organization context.
- Preserve certificates after organization transitions unless revoked by valid policy.
- Support verification links.

**Status:** Accepted.

## PDR-037 - Badges Represent Progress and Milestones

**Decision:** Badges represent progress, milestones, skills, and participation.

**Reason:** Badges help students see growth and give the platform lightweight recognition tools.

**Implementation Impact:**

- Badge definitions are configurable.
- Badge awards are tied to evidence or criteria.
- Badge visibility is student-aware.

**Status:** Accepted.

## PDR-038 - Portfolio Is the Durable Student Showcase

**Decision:** Portfolio is the durable student showcase for presentations, reflections, certificates, badges, volunteer hours, and achievements.

**Reason:** Students need a long-term way to demonstrate growth.

**Implementation Impact:**

- Portfolio items are student-curated.
- Visibility controls are required.
- Export and sharing require privacy rules.

**Status:** Accepted.

## PDR-039 - Volunteer Hours Are Part of Leadership Record

**Decision:** Volunteer hours are part of the student leadership record.

**Reason:** Service is a major expression of leadership.

**Implementation Impact:**

- Volunteer hours need submission, approval, organization context, and audit.
- Volunteer records can connect to certificates or badges.

**Status:** Accepted.

## PDR-040 - Leaderboards Require Careful Design

**Decision:** Leaderboards must be designed carefully to avoid harmful comparison.

**Reason:** Youth platforms should motivate without humiliating, discouraging, or over-ranking students.

**Implementation Impact:**

- Prefer team, cohort, personal progress, and opt-in leaderboards.
- Avoid public ranking of sensitive learning ability.
- Organization admins may configure leaderboard visibility.

**Status:** Accepted.

## PDR-041 - Levels and Points Are Configurable

**Decision:** Levels and points are configurable motivational systems.

**Reason:** Different organizations and age groups may need different achievement models.

**Implementation Impact:**

- Do not hard-code levels.
- Do not hard-code point values.
- Store definitions in configurable reference tables.

**Status:** Accepted.

---

# Section 8 - Future Decisions

## PDR-042 - Mobile Apps Are Future Extensions

**Decision:** Mobile apps are future platform extensions, not the first source of truth.

**Reason:** The platform should first establish web workflows and backend foundations before duplicating experiences in native apps.

**Implementation Impact:**

- Design APIs to support future mobile clients.
- Build responsive web experience first.
- Avoid mobile-only product assumptions in core workflows.

**Status:** Accepted.

## PDR-043 - University and Scholarship Pathways Are Future Expansion

**Decision:** University and scholarship pathways are future expansion areas.

**Reason:** Student portfolios and achievements may later support applications, scholarships, recommendations, and opportunity matching.

**Implementation Impact:**

- Preserve achievement history.
- Design export and verification foundations.
- Do not expose student records to external institutions without future consent and governance.

**Status:** Accepted.

## PDR-044 - Recruiter and Employer Access Requires Separate Governance

**Decision:** Recruiter and employer access requires separate governance before implementation.

**Reason:** Student data, age, consent, equity, and privacy concerns are too significant for casual access.

**Implementation Impact:**

- Do not build recruiter/employer portals until a future PDR defines scope, consent, eligibility, and safeguards.
- Student portfolio sharing must be student-controlled.

**Status:** Accepted.

## PDR-045 - Marketplace Features Are Deferred

**Decision:** Marketplace features are deferred until the core platform is stable.

**Reason:** Marketplace dynamics add complexity around payments, trust, quality, safety, moderation, and incentives.

**Implementation Impact:**

- Do not design early architecture around marketplace assumptions.
- Preserve extensibility for future opportunities, mentors, programs, or content marketplaces.

**Status:** Accepted.

---

# Developer Guidance

When building a feature:

1. Read the feature chapter.
2. Read this PDR document.
3. Read the relevant shared engineering references.
4. Link implementation choices back to PDR IDs in comments, specs, or pull request descriptions when helpful.

## Never Do These Without a New PDR

- Change who owns student achievements.
- Make individual membership paid at the foundation level.
- Replace human mentorship with AI-only evaluation.
- Remove tenant isolation.
- Expose organization data across tenants.
- Change cloud platform assumptions.
- Build recruiter, employer, university, or marketplace access.
- Change child privacy, consent, or audit requirements.
