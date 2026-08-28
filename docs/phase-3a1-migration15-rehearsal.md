# Phase 3A.1 Migration 15 rehearsal

Proposed isolated database: `yuva_club_subscriptions_phase3a1_rehearsal_20260827`.

Create a current production PITR copy on Azure SQL Basic (5 DTU, 2 GB). Expected list price is approximately **$4.90/month**, prorated until deletion; verify Azure's displayed estimate before creation.

## Required gates

1. Confirm `DB_NAME()` is the rehearsal target and production/release2 are not connected to it.
2. Record the exact PITR timestamp and baseline counts.
3. Confirm migrations 06–14 are present, skipped 04/05 remain absent, and Migration 15 is absent.
4. Apply only Migration 15; verify all tables, constraints, indexes, Free/Premium seeds, and feature rules.
5. Apply Migration 15 again and prove idempotency.
6. Exercise Free fallback, manual Premium grant/revoke, campaign activation/disable, intended-student redemption, 72-hour expiry, single use, capacity, duplicate redemption, revoke-and-block, transaction rollback, audit immutability, and concurrent redemption.
7. Prove no raw token appears in tables/logs and current AI Mentor routes remain ungated.
8. Run the rehearsal-only rollback; verify only Migration 15 objects are removed and baseline counts are unchanged.
9. Reapply Migration 15 and rerun focused/regression suites.
10. Do not deploy release2 until a separate authorization. Delete the rehearsal database after release validation.
