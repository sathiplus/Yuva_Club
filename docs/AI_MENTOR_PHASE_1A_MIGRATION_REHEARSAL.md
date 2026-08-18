# AI Mentor Phase 1A migration rehearsal

## Scope

`database/06-ai-mentor-phase-1a.azure-sql.sql` adds `dbo.ai_mentor_reviews`, two review indexes, and one filtered unique index on the existing `dbo.student_points` ledger. It does not delete or rewrite existing data. Existing JSON review data remains untouched for compatibility and rollback reference.

## Required rehearsal

1. Restore a recent production backup into an isolated Azure SQL rehearsal database.
2. Record row counts for `students`, `presentation_submissions`, `student_points`, and `activity_logs`.
3. run the Phase 1A migration twice. Both executions must succeed without adding duplicate objects.
4. Verify all five review states and JSON constraints.
5. Use a test student to generate a Draft, save an admin edit, and apply it twice.
6. Confirm exactly one `student_points` row exists for `(student_id, 'ai_mentor_review', review_id)`.
7. Change the source research, then confirm applying its previous Draft returns Stale and writes no ledger row.
8. Force a failure between the ledger insert and review update; confirm the SQL transaction rolls both back.
9. Confirm presentation `evaluations`, official rubric data, leadership level, badges, challenges, and certificates are unchanged.
10. Recheck the baseline row counts and retain the rehearsal transcript as release evidence.

## Release ordering

The additive migration must complete before application code that selects `dbo.ai_mentor_reviews` is released. Do not enable live production AI until migration rehearsal and controlled acceptance pass.

## Rollback and recovery

Before production migration, create or verify an Azure SQL point-in-time restore point. If application code has not written Phase 1A data, run `database/06-ai-mentor-phase-1a-rollback.sql`. If reviews or token-ledger entries have been created, prefer application rollback plus PITR/export of the new table; do not drop auditable records without explicit approval. The application rollback may temporarily read the retained legacy JSON compatibility data.
