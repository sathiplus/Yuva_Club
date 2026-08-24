SET NOCOUNT ON;
SET XACT_ABORT ON;

IF DB_NAME() = N'yuva_club'
    THROW 51000, 'REFUSED: Migration 11 rollback is rehearsal-only.', 1;

IF OBJECT_ID(N'dbo.organization_student_membership_requests', N'U') IS NOT NULL
   AND EXISTS (SELECT 1 FROM dbo.organization_student_membership_requests)
    THROW 51001, 'Rollback stopped: Phase 2A membership data exists.', 1;

IF OBJECT_ID(N'dbo.organization_membership_audit', N'U') IS NOT NULL
    DROP TABLE dbo.organization_membership_audit;
IF OBJECT_ID(N'dbo.organization_membership_tokens', N'U') IS NOT NULL
    DROP TABLE dbo.organization_membership_tokens;
IF OBJECT_ID(N'dbo.organization_student_membership_requests', N'U') IS NOT NULL
    DROP TABLE dbo.organization_student_membership_requests;
