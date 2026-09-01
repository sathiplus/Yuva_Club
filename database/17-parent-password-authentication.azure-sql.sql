SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF COL_LENGTH(N'dbo.users', N'activated_at') IS NULL
    ALTER TABLE dbo.users ADD activated_at DATETIME2 NULL;
IF COL_LENGTH(N'dbo.users', N'password_changed_at') IS NULL
    ALTER TABLE dbo.users ADD password_changed_at DATETIME2 NULL;
IF COL_LENGTH(N'dbo.users', N'credentials_version') IS NULL
    ALTER TABLE dbo.users ADD credentials_version INT NOT NULL CONSTRAINT df_users_credentials_version DEFAULT (1);

EXEC sys.sp_executesql N'
UPDATE parent_user
SET activated_at = COALESCE(parent_user.activated_at, parent_user.email_verified_at, parent_user.updated_at),
    password_changed_at = COALESCE(parent_user.password_changed_at, parent_user.updated_at)
FROM dbo.users AS parent_user
INNER JOIN dbo.parents AS parent ON parent.user_id = parent_user.id
WHERE parent_user.role = N''parent''
  AND parent_user.status = N''active''
  AND parent_user.password_hash IS NOT NULL
  AND parent_user.email_verified_at IS NOT NULL;';

IF OBJECT_ID(N'dbo.parent_authentication_tokens', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.parent_authentication_tokens (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_parent_authentication_tokens PRIMARY KEY,
        parent_user_id BIGINT NOT NULL,
        purpose NVARCHAR(24) NOT NULL,
        token_hash BINARY(32) NOT NULL,
        expires_at DATETIME2 NOT NULL,
        used_at DATETIME2 NULL,
        revoked_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_parent_auth_tokens_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_parent_auth_tokens_user FOREIGN KEY (parent_user_id) REFERENCES dbo.users(id),
        CONSTRAINT ck_parent_auth_tokens_purpose CHECK (purpose IN (N'activation', N'password_reset')),
        CONSTRAINT ck_parent_auth_tokens_expiry CHECK (expires_at > created_at)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.parent_authentication_tokens') AND name = N'ux_parent_auth_tokens_hash')
    EXEC(N'CREATE UNIQUE INDEX ux_parent_auth_tokens_hash ON dbo.parent_authentication_tokens(token_hash)');
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.parent_authentication_tokens') AND name = N'idx_parent_auth_tokens_user_purpose')
    EXEC(N'CREATE INDEX idx_parent_auth_tokens_user_purpose ON dbo.parent_authentication_tokens(parent_user_id, purpose, created_at DESC)');

IF OBJECT_ID(N'dbo.authentication_attempts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.authentication_attempts (
        scope NVARCHAR(64) NOT NULL,
        account_hash BINARY(32) NOT NULL,
        network_hash BINARY(32) NOT NULL,
        attempt_count INT NOT NULL CONSTRAINT df_auth_attempts_count DEFAULT (0),
        window_started_at DATETIME2 NOT NULL,
        blocked_until DATETIME2 NULL,
        updated_at DATETIME2 NOT NULL CONSTRAINT df_auth_attempts_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT pk_authentication_attempts PRIMARY KEY (scope, account_hash, network_hash),
        CONSTRAINT ck_auth_attempts_count CHECK (attempt_count >= 0)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.authentication_attempts') AND name = N'idx_auth_attempts_cleanup')
    EXEC(N'CREATE INDEX idx_auth_attempts_cleanup ON dbo.authentication_attempts(updated_at, blocked_until)');

COMMIT TRANSACTION;
