SET XACT_ABORT ON;
IF DB_NAME() LIKE N'%rehearsal%'
BEGIN
    DROP TABLE IF EXISTS dbo.competition_audit;
    DROP TABLE IF EXISTS dbo.competition_submissions;
    DROP TABLE IF EXISTS dbo.competition_entries;
    DROP TABLE IF EXISTS dbo.competition_divisions;
    DROP TABLE IF EXISTS dbo.competitions;
    DROP TABLE IF EXISTS dbo.competition_rubric_versions;
    DROP TABLE IF EXISTS dbo.competition_division_versions;
END
ELSE THROW 51014, 'Rollback 14 is rehearsal-only.', 1;
