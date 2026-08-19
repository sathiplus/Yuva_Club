# AI Mentor Phase 1B Migration 07 rehearsal

Run only against a fresh, isolated copy of the current production database.
Never use the generic migration runner because migrations 04 and 05 remain intentionally unapplied.

1. Record `DB_NAME()`, UTC time, Migration 06 row/constraint/index counts, and the absence of all seven Migration 07 columns.
2. Execute `database/07-ai-mentor-phase-1b-document.azure-sql.sql` once.
3. Verify all seven columns, the default, and four check constraints. Verify existing rows have `NotApplicable` and all Migration 06 GUID, JSON, rowversion, Apply, and token-ledger objects remain unchanged.
4. Execute Migration 07 a second time and verify object counts and existing data are unchanged.
5. Execute `database/07-ai-mentor-phase-1b-document-rollback.sql`. This destroys only document-provenance metadata; it does not delete reviews or token rows.
6. Verify the seven columns and four checks are absent while all Migration 06 objects and data remain present.
7. Reapply Migration 07 and repeat step 3.
8. Confirm migrations 04 and 05 were not introduced.

The rehearsal is PASS only when first-run, second-run, rollback, and reapply checks all pass and Phase 1A lifecycle regression tests remain green.
