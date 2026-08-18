SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.student_points') AND name = N'uq_student_points_ai_mentor_source')
    DROP INDEX uq_student_points_ai_mentor_source ON dbo.student_points;

IF OBJECT_ID(N'dbo.ai_mentor_reviews', N'U') IS NOT NULL
    DROP TABLE dbo.ai_mentor_reviews;

COMMIT TRANSACTION;
