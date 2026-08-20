SET NOCOUNT ON;
SET XACT_ABORT ON;

IF OBJECT_ID(N'dbo.ai_mentor_media_consents', N'U') IS NOT NULL
    DROP TABLE dbo.ai_mentor_media_consents;
