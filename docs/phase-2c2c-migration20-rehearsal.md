# Phase 2C.2C Migration 20 rehearsal

Use one temporary low-cost PITR copy of `yuva_club`, proposed name:

`yuva_club_my_growth_phase2c2c_m20_rehearsal_20260903`

Expected temporary cost is the prorated cost of a Basic/S0-class database for
the short rehearsal window (normally well below US$1 when deleted promptly;
Azure's actual meter is authoritative).

## Gates

1. Verify the exact database identity and production/release2 isolation.
2. Record baseline counts and migrations 06–19.
3. Apply Migration 20 twice and verify idempotency and exactly ten definitions.
4. Exercise no-history, one-score, compatible and incompatible history,
   Personal Best, benchmark, weekly consistency, authorization, award
   deduplication, correction audit, concurrency, and forced rollback cases.
5. Roll back only after removing synthetic awards; verify Migration 20 objects
   are absent and migrations 06–19 plus baseline data are unchanged.
6. Reapply and verify the schema matches first apply.
7. Run PHP/PDO SQLSRV, route-level, privacy, performance, and full regression gates.
8. Remove all synthetic fixtures. Delete the temporary database immediately
   after successful production closeout and Release2 restoration.

No AI provider call is required for My Growth acceptance.
