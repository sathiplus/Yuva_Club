-- Migration 19 rollback: Phase 2C.2B AI Quick Challenge practice scoring.
SET NOCOUNT ON;
SET XACT_ABORT ON;
IF DB_NAME() NOT LIKE N'%rehearsal%' AND DB_NAME() NOT LIKE N'%test%'
    THROW 51019,'Migration 19 rollback is restricted to rehearsal/test databases.',1;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.quick_challenge_evaluation_audit',N'U') IS NOT NULL DROP TABLE dbo.quick_challenge_evaluation_audit;
IF OBJECT_ID(N'dbo.quick_challenge_evaluations',N'U') IS NOT NULL DROP TABLE dbo.quick_challenge_evaluations;
IF EXISTS(SELECT 1 FROM sys.foreign_keys WHERE parent_object_id=OBJECT_ID(N'dbo.quick_challenge_template_versions') AND name=N'fk_quick_challenge_version_scoring_policy')
    ALTER TABLE dbo.quick_challenge_template_versions DROP CONSTRAINT fk_quick_challenge_version_scoring_policy;
IF COL_LENGTH(N'dbo.quick_challenge_template_versions',N'scoring_policy_id') IS NOT NULL ALTER TABLE dbo.quick_challenge_template_versions DROP COLUMN scoring_policy_id;
IF COL_LENGTH(N'dbo.quick_challenge_template_versions',N'ai_evaluation_enabled') IS NOT NULL
BEGIN
    IF EXISTS(SELECT 1 FROM sys.default_constraints WHERE parent_object_id=OBJECT_ID(N'dbo.quick_challenge_template_versions') AND name=N'df_quick_challenge_version_ai_enabled') ALTER TABLE dbo.quick_challenge_template_versions DROP CONSTRAINT df_quick_challenge_version_ai_enabled;
    ALTER TABLE dbo.quick_challenge_template_versions DROP COLUMN ai_evaluation_enabled;
END;
IF OBJECT_ID(N'dbo.quick_challenge_scoring_policies',N'U') IS NOT NULL DROP TABLE dbo.quick_challenge_scoring_policies;
DELETE FROM dbo.plan_feature_rules WHERE feature_key=N'ai_quick_challenge_scoring';
COMMIT TRANSACTION;
