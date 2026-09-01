# Phase 2C.2B Migration 18 rehearsal

Use one temporary Basic (5 DTU / 2 GB) Azure SQL point-in-time restore named `yuva_club_quick_scoring_phase2c2b_rehearsal_20260901`. Verify the target database name before every mutation; never point production at it.

Sequence: verify migrations 06–17 and the absence of 04/05 as expected; verify Migration 18 absent; apply once; validate both tables, the audit table, guarded columns, constraints, FKs, indexes, policies, and feature rules; apply a second time for idempotency; execute the rehearsal-only rollback; confirm migrations 06–17 and baseline counts; reapply; then run typed plus media-backed attempt evaluation, malformed/provider-failure behavior, duplicate/concurrent Analyze, Personal Best 72→80→76, incompatible-policy separation, fixed/difficulty/leadership-label benchmarks, parent and organization scoping, transaction rollback, privacy/fairness, and all regression suites.

The benchmark is system-defined and versioned. No real-student ghost, official Competition Score, winner, ranking, or leadership promotion is part of this phase. Delete the temporary database immediately after production closeout.
