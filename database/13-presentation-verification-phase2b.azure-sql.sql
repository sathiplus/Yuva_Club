SET XACT_ABORT ON;

IF COL_LENGTH(N'dbo.presentation_submissions', N'source_revision_hash') IS NULL
    ALTER TABLE dbo.presentation_submissions ADD source_revision_hash CHAR(64) NULL;
IF COL_LENGTH(N'dbo.presentation_submissions', N'completed_at') IS NULL
    ALTER TABLE dbo.presentation_submissions ADD completed_at DATETIME2 NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.presentation_submissions') AND name=N'ux_presentation_submissions_student_revision')
    EXEC(N'CREATE UNIQUE INDEX ux_presentation_submissions_student_revision
        ON dbo.presentation_submissions(student_id, source_revision_hash)
        WHERE source_revision_hash IS NOT NULL;');

IF OBJECT_ID(N'dbo.presentation_verifications', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.presentation_verifications (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_presentation_verifications PRIMARY KEY,
        verification_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_presentation_verifications_guid DEFAULT NEWID(),
        submission_id BIGINT NOT NULL,
        student_id BIGINT NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_presentation_verifications_status DEFAULT N'Verified',
        reviewer_user_id BIGINT NULL,
        reviewer_email NVARCHAR(190) NOT NULL,
        reviewer_role NVARCHAR(40) NOT NULL,
        organization_code NVARCHAR(120) NULL,
        verification_note NVARCHAR(2000) NULL,
        verified_at DATETIME2 NOT NULL CONSTRAINT df_presentation_verifications_verified DEFAULT SYSUTCDATETIME(),
        revoked_at DATETIME2 NULL,
        revocation_note NVARCHAR(2000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_presentation_verifications_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_presentation_verifications_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_presentation_verifications_guid UNIQUE(verification_guid),
        CONSTRAINT fk_presentation_verifications_submission FOREIGN KEY(submission_id) REFERENCES dbo.presentation_submissions(id),
        CONSTRAINT fk_presentation_verifications_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_presentation_verifications_reviewer FOREIGN KEY(reviewer_user_id) REFERENCES dbo.users(id),
        CONSTRAINT ck_presentation_verifications_status CHECK([status] IN(N'Verified',N'Revoked')),
        CONSTRAINT ck_presentation_verifications_role CHECK(reviewer_role IN(N'MasterAdmin',N'OrganizationAdmin'))
    );
END
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.presentation_verifications') AND name=N'ux_presentation_verifications_active_submission')
    EXEC(N'CREATE UNIQUE INDEX ux_presentation_verifications_active_submission
        ON dbo.presentation_verifications(submission_id) WHERE [status]=N''Verified'';');
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.presentation_verifications') AND name=N'idx_presentation_verifications_student_status')
    EXEC(N'CREATE INDEX idx_presentation_verifications_student_status
        ON dbo.presentation_verifications(student_id,[status],verified_at DESC);');
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.presentation_verifications') AND name=N'idx_presentation_verifications_organization_status')
    EXEC(N'CREATE INDEX idx_presentation_verifications_organization_status
        ON dbo.presentation_verifications(organization_code,[status],verified_at DESC);');

IF OBJECT_ID(N'dbo.presentation_verification_audit', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.presentation_verification_audit (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_presentation_verification_audit PRIMARY KEY,
        verification_id BIGINT NOT NULL,
        action_type NVARCHAR(20) NOT NULL,
        actor_email NVARCHAR(190) NOT NULL,
        actor_role NVARCHAR(40) NOT NULL,
        organization_code NVARCHAR(120) NULL,
        reason NVARCHAR(2000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_presentation_verification_audit_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_presentation_verification_audit_verification FOREIGN KEY(verification_id) REFERENCES dbo.presentation_verifications(id),
        CONSTRAINT ck_presentation_verification_audit_action CHECK(action_type IN(N'Verified',N'Revoked'))
    );
END
