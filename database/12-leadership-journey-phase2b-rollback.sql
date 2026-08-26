SET XACT_ABORT ON;
IF DB_NAME() LIKE N'%rehearsal%'
BEGIN
    DROP TABLE IF EXISTS dbo.leadership_level_history;
    DROP TABLE IF EXISTS dbo.leadership_decisions;
    DROP TABLE IF EXISTS dbo.leadership_eligibility_snapshots;
    DROP TABLE IF EXISTS dbo.student_leadership_reflections;
    DROP TABLE IF EXISTS dbo.leadership_evidence;
    DROP TABLE IF EXISTS dbo.leadership_rule_versions;
END
ELSE THROW 51012, 'Rollback 12 is rehearsal-only.', 1;
