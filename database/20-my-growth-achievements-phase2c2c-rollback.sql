-- Migration 20 rehearsal-only rollback.
SET NOCOUNT ON;
SET XACT_ABORT ON;
IF DB_NAME() NOT LIKE N'%rehearsal%' AND DB_NAME() NOT LIKE N'%test%'
    THROW 51020,'Migration 20 rollback is restricted to rehearsal/test databases.',1;
BEGIN TRANSACTION;
IF EXISTS(SELECT 1 FROM dbo.student_achievements) THROW 51021,'Remove rehearsal achievement evidence before rollback.',1;
DROP TABLE IF EXISTS dbo.student_achievement_audit;
DROP TABLE IF EXISTS dbo.student_achievements;
DROP TABLE IF EXISTS dbo.achievement_definitions;
COMMIT TRANSACTION;
