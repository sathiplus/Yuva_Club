# Phase 2C.1 Migration 14 rehearsal

Target after explicit approval: `yuva_club_competitions_phase2c1_rehearsal_20260826`.

The database must be a current production PITR copy. Production and release2 remain unchanged. Before execution, verify `DB_NAME()` equals the rehearsal target; Migrations 06–13 are present; Migrations 04/05 and 14 are absent.

Apply only `database/14-competition-foundation-phase2c1.azure-sql.sql`. Do not use the generic runner against the restored production database because skipped Migrations 04/05 must remain absent.

Gates:

1. first application and schema/constraint/index verification;
2. second application/idempotency;
3. rehearsal-only rollback;
4. reapply;
5. Practice and Organization challenge lifecycle;
6. division and rubric immutability;
7. eligible and rejected entries, duplicate protection, and organization scoping;
8. immutable source snapshot/hash after normal workspace edits;
9. cross-student source rejection, rowversion concurrency, and transactional rollback;
10. existing data counts and Migrations 06–13 unchanged; Migrations 04/05 still absent.

Delete the rehearsal database after release2 acceptance to avoid ongoing Azure charges.
