# Parent Password Authentication — Migration 17 Rehearsal

Use one temporary low-cost PITR copy of `yuva_club`. Never run the rehearsal
against production or a slot still connected to production.

1. Verify `DB_NAME()` is the explicitly authorized rehearsal database.
2. Record baseline users, parents, `student_parents`, and migrations 06–16.
3. Confirm skipped migrations 04/05 remain absent and Migration 17 is absent.
4. Apply only `17-parent-password-authentication.azure-sql.sql`.
5. Run `17-parent-password-authentication-verify.sql` and confirm relationship
   counts are unchanged.
6. Run the migration a second time and verify idempotency.
7. Rehearse `17-parent-password-authentication-rollback.sql`; verify only
   Migration 17 objects/columns are absent and all relationships remain.
8. Reapply Migration 17 and rerun verification.
9. Exercise activation, reset, replay, expiry, concurrent consumption,
   transaction rollback, durable throttling, credential-version session
   invalidation, multi-child selection, cross-parent denial, and recent-auth
   guards through supported application services.
10. Run all authentication and Phase 2A–3A.1 regressions, then remove synthetic
    fixtures. Do not deploy or change `PORTAL_AUTH_MODE` during rehearsal.

Proposed temporary database:
`yuva_club_parent_auth_phase17_rehearsal_20260830`

Delete it promptly after the approved production release closeout.
