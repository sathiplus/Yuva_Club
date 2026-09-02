# Student SQL Credential Authority — Migration 18 rehearsal

Migration 18 makes SQL the sole Student password authority whenever `PORTAL_AUTH_MODE=sql`.

Rehearse on one temporary low-cost PITR database. Verify migrations 06–17 are present and 18 is absent, then run first apply, schema inspection, second-run idempotency, rollback, baseline verification, reapply, and the complete Student registration/approval/reset/session test matrix. Migrations 04/05 remain intentionally absent.

Existing active Students with a null SQL password hash are not assigned credentials automatically. They recover through the neutral, single-use, expiring SQL password-reset workflow. The reset updates `dbo.users.password_hash`, timestamps the change, increments `credentials_version`, consumes the token, and writes the audit event in one transaction.

Hybrid is transitional: an established SQL credential remains authoritative; filesystem fallback is limited to legacy unactivated accounts. The intended end state is removal of hybrid mode after all active Students have established SQL credentials.

Suggested temporary database: `yuva_club_student_credentials_m18_rehearsal_20260901`. Use Basic / 5 DTU / 2 GB when PITR permits, then delete it immediately after production closeout.
