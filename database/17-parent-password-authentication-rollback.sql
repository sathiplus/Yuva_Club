SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.authentication_attempts', N'U') IS NOT NULL DROP TABLE dbo.authentication_attempts;
IF OBJECT_ID(N'dbo.parent_authentication_tokens', N'U') IS NOT NULL DROP TABLE dbo.parent_authentication_tokens;

IF COL_LENGTH(N'dbo.users', N'credentials_version') IS NOT NULL
BEGIN
    DECLARE @credentials_default NVARCHAR(128);
    SELECT @credentials_default = dc.name FROM sys.default_constraints dc
    JOIN sys.columns c ON c.default_object_id = dc.object_id
    WHERE dc.parent_object_id = OBJECT_ID(N'dbo.users') AND c.name = N'credentials_version';
    IF @credentials_default IS NOT NULL
    BEGIN
        DECLARE @drop_credentials_default_sql NVARCHAR(MAX);
        SET @drop_credentials_default_sql = N'ALTER TABLE dbo.users DROP CONSTRAINT ' + QUOTENAME(@credentials_default);
        EXEC sys.sp_executesql @drop_credentials_default_sql;
    END;
    EXEC(N'ALTER TABLE dbo.users DROP COLUMN credentials_version');
END;
IF COL_LENGTH(N'dbo.users', N'password_changed_at') IS NOT NULL EXEC(N'ALTER TABLE dbo.users DROP COLUMN password_changed_at');
IF COL_LENGTH(N'dbo.users', N'activated_at') IS NOT NULL EXEC(N'ALTER TABLE dbo.users DROP COLUMN activated_at');

COMMIT TRANSACTION;
