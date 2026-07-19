-- YUVA Club Azure SQL migration ledger.
-- This migration is safe to run repeatedly.

IF OBJECT_ID(N'dbo.schema_migrations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.schema_migrations (
        version NVARCHAR(64) NOT NULL,
        filename NVARCHAR(260) NOT NULL,
        migration_name NVARCHAR(260) NOT NULL,
        checksum_sha256 CHAR(64) NOT NULL,
        applied_at DATETIME2(7) NOT NULL
            CONSTRAINT df_schema_migrations_applied_at DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_schema_migrations PRIMARY KEY (version),
        CONSTRAINT uq_schema_migrations_filename UNIQUE (filename),
        CONSTRAINT ck_schema_migrations_checksum
            CHECK (
                LEN(checksum_sha256) = 64
                AND checksum_sha256 NOT LIKE '%[^0-9a-f]%'
            )
    );
END;
