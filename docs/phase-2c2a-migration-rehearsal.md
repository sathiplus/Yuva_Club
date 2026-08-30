# Phase 2C.2A Migration 16 rehearsal plan

No Azure database is created by this implementation task. After separate
authorization, create one low-cost PITR restore of production named
`yuva_club_quick_challenges_phase2c2a_rehearsal_20260830` and record the exact
UTC restore point and Azure configuration actually used.

## Required gates

1. Verify `DB_NAME()` is exactly the authorized rehearsal database and confirm
   production and release2 still target `yuva_club`.
2. Record baseline table counts and migration inventory. Require Migrations
   06–15 as expected, Migrations 04/05 absent, and Migration 16 absent.
3. Apply only `16-quick-challenges-phase2c2a.azure-sql.sql`.
4. Verify all tables, columns, foreign keys, checks, unique constraints, seeded
   skills, indexes, frozen version links, and rowversion columns.
5. Apply Migration 16 a second time and verify idempotency and unchanged counts.
6. Exercise template publication, practice and own-organization instances,
   server timing, attempt limits, immutable submission snapshots, versioned
   personal bests, duplicate/stale concurrency rejection, authorization,
   rollback safety, and privacy contracts.
7. Run `16-quick-challenges-phase2c2a-rollback.sql`; verify only Migration 16
   objects are absent and baseline data/migrations remain unchanged.
8. Reapply Migration 16 and repeat schema plus bounded lifecycle verification.
9. Run Phase 2A, Phase 2B, Phase 2C.1, Phase 3A.1, Public Student Identity,
   authentication, registration, AI Mentor 1A/1B/1C, frontend/security, PHP
   lint, migration framework, and `git diff --check` regressions.
10. Preserve the same database for isolated release acceptance, then delete it
    immediately after release closeout.

Stop on any wrong database target, unexpected baseline, partial persistence,
privacy leak, authorization bypass, concurrency failure, or regression.
