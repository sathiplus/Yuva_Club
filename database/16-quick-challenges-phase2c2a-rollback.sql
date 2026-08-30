SET XACT_ABORT ON;
IF DB_NAME() NOT LIKE N'%rehearsal%' AND DB_NAME() NOT LIKE N'%test%' AND DB_NAME() NOT LIKE N'%temp%'
    THROW 51016, 'Rollback 16 is isolated-environment only.', 1;
BEGIN TRANSACTION;
DROP TABLE IF EXISTS dbo.student_challenge_personal_bests;
DROP TABLE IF EXISTS dbo.quick_challenge_attempts;
IF EXISTS(SELECT 1 FROM sys.foreign_keys WHERE name=N'fk_competition_quick_template_version' AND parent_object_id=OBJECT_ID(N'dbo.competitions'))
    ALTER TABLE dbo.competitions DROP CONSTRAINT fk_competition_quick_template_version;
IF EXISTS(SELECT 1 FROM sys.check_constraints WHERE name=N'ck_competition_experience_mode' AND parent_object_id=OBJECT_ID(N'dbo.competitions'))
    ALTER TABLE dbo.competitions DROP CONSTRAINT ck_competition_experience_mode;
IF EXISTS(SELECT 1 FROM sys.default_constraints WHERE name=N'df_competition_experience_mode' AND parent_object_id=OBJECT_ID(N'dbo.competitions'))
    ALTER TABLE dbo.competitions DROP CONSTRAINT df_competition_experience_mode;
IF EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competitions') AND name=N'idx_competitions_quick_template')
    DROP INDEX idx_competitions_quick_template ON dbo.competitions;
IF COL_LENGTH(N'dbo.competitions',N'quick_template_version_id') IS NOT NULL ALTER TABLE dbo.competitions DROP COLUMN quick_template_version_id;
IF COL_LENGTH(N'dbo.competitions',N'experience_mode') IS NOT NULL ALTER TABLE dbo.competitions DROP COLUMN experience_mode;
DROP TABLE IF EXISTS dbo.quick_challenge_template_version_skills;
DROP TABLE IF EXISTS dbo.quick_challenge_template_versions;
DROP TABLE IF EXISTS dbo.quick_challenge_templates;
DROP TABLE IF EXISTS dbo.quick_challenge_skills;
COMMIT TRANSACTION;
