# Phase 2C.2B Migration 19 rehearsal

Use one newly authorized temporary Basic (5 DTU / 2 GB) Azure SQL point-in-time restore. Do not reuse the deleted Phase 2C.2B Migration 18 rehearsal database. Verify the target database name before every mutation; never point production at it.

Sequence: verify migrations 06–18 and the absence of 04/05 as expected; verify Migration 19 absent; apply once; validate both tables, the audit table, guarded columns, constraints, FKs, indexes, policies, and feature rules; apply a second time for idempotency; execute the rehearsal-only rollback; confirm migrations 06–18 and baseline counts; reapply; then run typed plus media-backed attempt evaluation, malformed/provider-failure behavior, duplicate/concurrent Analyze, Personal Best 72→80→76, incompatible-policy separation, fixed/difficulty/leadership-label benchmarks, parent and organization scoping, transaction rollback, privacy/fairness, and all regression suites.

The benchmark is system-defined and versioned. No real-student ghost, official Competition Score, winner, ranking, or leadership promotion is part of this phase. Delete the temporary database immediately after production closeout.
