IF OBJECT_ID(N'dbo.parent_authentication_tokens', N'U') IS NULL THROW 51000, 'Migration 17 token table missing.', 1;
IF OBJECT_ID(N'dbo.authentication_attempts', N'U') IS NULL THROW 51000, 'Migration 17 throttle table missing.', 1;
IF COL_LENGTH(N'dbo.users', N'activated_at') IS NULL THROW 51000, 'Migration 17 activated_at missing.', 1;
IF COL_LENGTH(N'dbo.users', N'password_changed_at') IS NULL THROW 51000, 'Migration 17 password_changed_at missing.', 1;
IF COL_LENGTH(N'dbo.users', N'credentials_version') IS NULL THROW 51000, 'Migration 17 credentials_version missing.', 1;
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.parent_authentication_tokens') AND name = N'ux_parent_auth_tokens_hash') THROW 51000, 'Migration 17 token hash index missing.', 1;
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.authentication_attempts') AND name = N'idx_auth_attempts_cleanup') THROW 51000, 'Migration 17 throttle cleanup index missing.', 1;
SELECT N'PASS' AS migration_17_verification;
