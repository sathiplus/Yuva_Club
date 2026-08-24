SET NOCOUNT ON;
SET XACT_ABORT ON;

IF OBJECT_ID(N'dbo.organization_student_membership_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_student_membership_requests (
        id BIGINT IDENTITY(1,1) NOT NULL,
        membership_guid UNIQUEIDENTIFIER NOT NULL
            CONSTRAINT df_org_student_membership_guid DEFAULT NEWID(),
        organization_code NVARCHAR(60) NOT NULL,
        request_type NVARCHAR(20) NOT NULL,
        student_id BIGINT NULL,
        registration_id BIGINT NULL,
        student_first_name NVARCHAR(120) NULL,
        student_last_name NVARCHAR(120) NULL,
        student_email_snapshot NVARCHAR(190) NOT NULL,
        student_email_normalized NVARCHAR(190) NOT NULL,
        parent_email_snapshot NVARCHAR(190) NULL,
        cohort_label NVARCHAR(120) NULL,
        invitation_purpose NVARCHAR(220) NOT NULL,
        invitation_message NVARCHAR(1000) NULL,
        [status] NVARCHAR(30) NOT NULL
            CONSTRAINT df_org_student_membership_status DEFAULT N'Invited',
        invited_by_email NVARCHAR(190) NOT NULL,
        student_accepted_at DATETIME2(3) NULL,
        parent_approved_at DATETIME2(3) NULL,
        activated_at DATETIME2(3) NULL,
        declined_at DATETIME2(3) NULL,
        withdrawn_at DATETIME2(3) NULL,
        archived_at DATETIME2(3) NULL,
        removed_at DATETIME2(3) NULL,
        expires_at DATETIME2(3) NOT NULL,
        created_at DATETIME2(3) NOT NULL
            CONSTRAINT df_org_student_membership_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(3) NOT NULL
            CONSTRAINT df_org_student_membership_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT pk_org_student_membership_requests PRIMARY KEY (id),
        CONSTRAINT uq_org_student_membership_guid UNIQUE (membership_guid),
        CONSTRAINT fk_org_student_membership_student
            FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_org_student_membership_registration
            FOREIGN KEY (registration_id) REFERENCES dbo.registrations(id),
        CONSTRAINT ck_org_student_membership_type CHECK (
            request_type IN (N'InviteNew', N'LinkExisting')
        ),
        CONSTRAINT ck_org_student_membership_status CHECK (
            [status] IN (
                N'Invited', N'StudentAccepted', N'ParentApprovalPending',
                N'Active', N'Declined', N'Expired', N'Withdrawn',
                N'Archived', N'Removed'
            )
        )
    );
END;

IF OBJECT_ID(N'dbo.organization_membership_tokens', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_membership_tokens (
        id BIGINT IDENTITY(1,1) NOT NULL,
        membership_id BIGINT NOT NULL,
        token_hash BINARY(32) NOT NULL,
        token_type NVARCHAR(24) NOT NULL,
        expires_at DATETIME2(3) NOT NULL,
        used_at DATETIME2(3) NULL,
        revoked_at DATETIME2(3) NULL,
        created_at DATETIME2(3) NOT NULL
            CONSTRAINT df_org_membership_tokens_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_org_membership_tokens PRIMARY KEY (id),
        CONSTRAINT uq_org_membership_tokens_hash UNIQUE (token_hash),
        CONSTRAINT fk_org_membership_tokens_membership
            FOREIGN KEY (membership_id)
            REFERENCES dbo.organization_student_membership_requests(id),
        CONSTRAINT ck_org_membership_tokens_type CHECK (
            token_type IN (N'StudentAccept', N'ParentApprove')
        )
    );
END;

IF OBJECT_ID(N'dbo.organization_membership_audit', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_membership_audit (
        id BIGINT IDENTITY(1,1) NOT NULL,
        membership_id BIGINT NULL,
        organization_code NVARCHAR(60) NULL,
        actor_type NVARCHAR(30) NOT NULL,
        actor_identifier NVARCHAR(190) NULL,
        action_name NVARCHAR(80) NOT NULL,
        succeeded BIT NOT NULL,
        detail_json NVARCHAR(MAX) NULL,
        created_at DATETIME2(3) NOT NULL
            CONSTRAINT df_org_membership_audit_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_org_membership_audit PRIMARY KEY (id),
        CONSTRAINT fk_org_membership_audit_membership
            FOREIGN KEY (membership_id)
            REFERENCES dbo.organization_student_membership_requests(id),
        CONSTRAINT ck_org_membership_audit_actor CHECK (
            actor_type IN (
                N'OrganizationAdmin', N'Student', N'Parent',
                N'MasterAdmin', N'System', N'Anonymous'
            )
        ),
        CONSTRAINT ck_org_membership_audit_json CHECK (
            detail_json IS NULL OR ISJSON(detail_json) = 1
        )
    );
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_student_membership_requests')
      AND [name] = N'ux_org_student_membership_active_student'
)
BEGIN
    CREATE UNIQUE INDEX ux_org_student_membership_active_student
        ON dbo.organization_student_membership_requests(student_id)
        WHERE [status] = N'Active' AND student_id IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_student_membership_requests')
      AND [name] = N'ix_org_student_membership_org_status'
)
BEGIN
    CREATE INDEX ix_org_student_membership_org_status
        ON dbo.organization_student_membership_requests(
            organization_code, [status], created_at DESC, id DESC
        )
        INCLUDE(student_id, membership_guid, request_type, expires_at);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_student_membership_requests')
      AND [name] = N'ix_org_student_membership_student_status'
)
BEGIN
    CREATE INDEX ix_org_student_membership_student_status
        ON dbo.organization_student_membership_requests(
            student_id, [status], created_at DESC, id DESC
        )
        INCLUDE(organization_code, membership_guid, request_type, registration_id);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_student_membership_requests')
      AND [name] = N'ix_org_student_membership_registration'
)
BEGIN
    CREATE INDEX ix_org_student_membership_registration
        ON dbo.organization_student_membership_requests(registration_id)
        WHERE registration_id IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_membership_tokens')
      AND [name] = N'ix_org_membership_tokens_membership_type'
)
BEGIN
    CREATE INDEX ix_org_membership_tokens_membership_type
        ON dbo.organization_membership_tokens(membership_id, token_type, expires_at DESC)
        INCLUDE(used_at, revoked_at);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_membership_audit')
      AND [name] = N'ix_org_membership_audit_membership_created'
)
BEGIN
    CREATE INDEX ix_org_membership_audit_membership_created
        ON dbo.organization_membership_audit(membership_id, created_at DESC, id DESC);
END;
