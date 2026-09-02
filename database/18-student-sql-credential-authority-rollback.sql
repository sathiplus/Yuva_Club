SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.student_authentication_tokens', N'U') IS NOT NULL
    DROP TABLE dbo.student_authentication_tokens;
IF OBJECT_ID(N'dbo.student_registration_credentials', N'U') IS NOT NULL
    DROP TABLE dbo.student_registration_credentials;

COMMIT TRANSACTION;
