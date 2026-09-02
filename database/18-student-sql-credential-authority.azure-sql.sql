SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.student_registration_credentials', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_registration_credentials (
        registration_id BIGINT NOT NULL CONSTRAINT pk_student_registration_credentials PRIMARY KEY,
        password_hash NVARCHAR(255) NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_student_registration_credentials_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_student_registration_credentials_registration FOREIGN KEY (registration_id) REFERENCES dbo.registrations(id) ON DELETE CASCADE,
        CONSTRAINT ck_student_registration_credentials_hash CHECK (LEN(password_hash) >= 20)
    );
END;

IF OBJECT_ID(N'dbo.student_authentication_tokens', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_authentication_tokens (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_authentication_tokens PRIMARY KEY,
        student_user_id BIGINT NOT NULL,
        purpose NVARCHAR(24) NOT NULL,
        token_hash BINARY(32) NOT NULL,
        expires_at DATETIME2 NOT NULL,
        used_at DATETIME2 NULL,
        revoked_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_student_auth_tokens_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_student_auth_tokens_user FOREIGN KEY (student_user_id) REFERENCES dbo.users(id),
        CONSTRAINT ck_student_auth_tokens_purpose CHECK (purpose IN (N'activation', N'password_reset')),
        CONSTRAINT ck_student_auth_tokens_expiry CHECK (expires_at > created_at)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.student_authentication_tokens') AND name = N'ux_student_auth_tokens_hash')
    EXEC(N'CREATE UNIQUE INDEX ux_student_auth_tokens_hash ON dbo.student_authentication_tokens(token_hash)');
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.student_authentication_tokens') AND name = N'idx_student_auth_tokens_user_purpose')
    EXEC(N'CREATE INDEX idx_student_auth_tokens_user_purpose ON dbo.student_authentication_tokens(student_user_id, purpose, created_at DESC)');

COMMIT TRANSACTION;
