SET XACT_ABORT ON;
IF DB_NAME() LIKE N'%rehearsal%'
BEGIN
    DROP TABLE IF EXISTS dbo.presentation_verification_audit;
    DROP TABLE IF EXISTS dbo.presentation_verifications;
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.presentation_submissions') AND name=N'ux_presentation_submissions_student_revision')
        DROP INDEX ux_presentation_submissions_student_revision ON dbo.presentation_submissions;
    IF COL_LENGTH(N'dbo.presentation_submissions',N'completed_at') IS NOT NULL ALTER TABLE dbo.presentation_submissions DROP COLUMN completed_at;
    IF COL_LENGTH(N'dbo.presentation_submissions',N'source_revision_hash') IS NOT NULL ALTER TABLE dbo.presentation_submissions DROP COLUMN source_revision_hash;
END
ELSE THROW 51013, 'Rollback 13 is rehearsal-only.', 1;
