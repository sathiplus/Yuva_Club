SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.competition_division_versions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_division_versions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_division_versions PRIMARY KEY,
        division_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_competition_division_guid DEFAULT NEWID(),
        division_code NVARCHAR(40) NOT NULL,
        display_name NVARCHAR(100) NOT NULL,
        version_number INT NOT NULL,
        min_age TINYINT NOT NULL,
        max_age TINYINT NOT NULL,
        eligibility_rule_json NVARCHAR(MAX) NOT NULL,
        is_frozen BIT NOT NULL CONSTRAINT df_competition_division_frozen DEFAULT 1,
        created_by BIGINT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_competition_division_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT uq_competition_division_guid UNIQUE(division_guid),
        CONSTRAINT uq_competition_division_version UNIQUE(division_code, version_number),
        CONSTRAINT fk_competition_division_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_competition_division_ages CHECK(min_age >= 8 AND max_age <= 21 AND min_age <= max_age),
        CONSTRAINT ck_competition_division_rule_json CHECK(ISJSON(eligibility_rule_json) = 1),
        CONSTRAINT ck_competition_division_frozen CHECK(is_frozen = 1)
    );
END;

IF OBJECT_ID(N'dbo.competition_rubric_versions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_rubric_versions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_rubric_versions PRIMARY KEY,
        rubric_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_competition_rubric_guid DEFAULT NEWID(),
        rubric_code NVARCHAR(60) NOT NULL,
        display_name NVARCHAR(140) NOT NULL,
        version_number INT NOT NULL,
        criteria_json NVARCHAR(MAX) NOT NULL,
        maximum_score DECIMAL(8,2) NOT NULL,
        is_frozen BIT NOT NULL CONSTRAINT df_competition_rubric_frozen DEFAULT 1,
        created_by BIGINT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_competition_rubric_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT uq_competition_rubric_guid UNIQUE(rubric_guid),
        CONSTRAINT uq_competition_rubric_version UNIQUE(rubric_code, version_number),
        CONSTRAINT fk_competition_rubric_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_competition_rubric_json CHECK(ISJSON(criteria_json) = 1),
        CONSTRAINT ck_competition_rubric_score CHECK(maximum_score > 0 AND maximum_score <= 1000),
        CONSTRAINT ck_competition_rubric_frozen CHECK(is_frozen = 1)
    );
END;

IF OBJECT_ID(N'dbo.competitions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competitions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competitions PRIMARY KEY,
        competition_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_competition_guid DEFAULT NEWID(),
        title NVARCHAR(180) NOT NULL,
        [description] NVARCHAR(2000) NOT NULL,
        scope_type NVARCHAR(20) NOT NULL,
        owner_organization_code NVARCHAR(120) NULL,
        [status] NVARCHAR(30) NOT NULL CONSTRAINT df_competition_status DEFAULT N'Draft',
        open_at DATETIME2 NOT NULL,
        submission_deadline DATETIME2 NOT NULL,
        rubric_version_id BIGINT NOT NULL,
        created_by BIGINT NULL,
        created_by_email NVARCHAR(190) NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_competition_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_competition_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_competition_guid UNIQUE(competition_guid),
        CONSTRAINT fk_competition_rubric FOREIGN KEY(rubric_version_id) REFERENCES dbo.competition_rubric_versions(id),
        CONSTRAINT fk_competition_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_competition_scope CHECK(scope_type IN(N'practice',N'organization')),
        CONSTRAINT ck_competition_owner CHECK((scope_type=N'practice' AND owner_organization_code IS NULL) OR (scope_type=N'organization' AND owner_organization_code IS NOT NULL)),
        CONSTRAINT ck_competition_status CHECK([status] IN(N'Draft',N'Scheduled',N'Open',N'SubmissionsClosed',N'Archived')),
        CONSTRAINT ck_competition_dates CHECK(open_at < submission_deadline)
    );
END;

IF OBJECT_ID(N'dbo.competition_divisions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_divisions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_divisions PRIMARY KEY,
        competition_id BIGINT NOT NULL,
        division_version_id BIGINT NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_competition_divisions_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_competition_divisions_competition FOREIGN KEY(competition_id) REFERENCES dbo.competitions(id),
        CONSTRAINT fk_competition_divisions_version FOREIGN KEY(division_version_id) REFERENCES dbo.competition_division_versions(id),
        CONSTRAINT uq_competition_division UNIQUE(competition_id, division_version_id)
    );
END;

IF OBJECT_ID(N'dbo.competition_entries', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_entries (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_entries PRIMARY KEY,
        entry_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_competition_entry_guid DEFAULT NEWID(),
        competition_id BIGINT NOT NULL,
        competition_division_id BIGINT NOT NULL,
        student_id BIGINT NOT NULL,
        yuva_id NVARCHAR(40) NOT NULL,
        eligibility_snapshot_json NVARCHAR(MAX) NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_competition_entry_status DEFAULT N'Entered',
        entered_at DATETIME2 NOT NULL CONSTRAINT df_competition_entry_entered DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_competition_entry_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_competition_entry_guid UNIQUE(entry_guid),
        CONSTRAINT uq_competition_entry_student_division UNIQUE(competition_id, competition_division_id, student_id),
        CONSTRAINT fk_competition_entry_competition FOREIGN KEY(competition_id) REFERENCES dbo.competitions(id),
        CONSTRAINT fk_competition_entry_division FOREIGN KEY(competition_division_id) REFERENCES dbo.competition_divisions(id),
        CONSTRAINT fk_competition_entry_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT ck_competition_entry_status CHECK([status] IN(N'Entered',N'Submitted',N'Withdrawn')),
        CONSTRAINT ck_competition_entry_eligibility_json CHECK(ISJSON(eligibility_snapshot_json) = 1)
    );
END;

IF OBJECT_ID(N'dbo.competition_submissions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_submissions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_submissions PRIMARY KEY,
        submission_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_competition_submission_guid DEFAULT NEWID(),
        entry_id BIGINT NOT NULL,
        source_type NVARCHAR(40) NOT NULL,
        source_reference NVARCHAR(500) NOT NULL,
        source_submission_id BIGINT NULL,
        source_revision_hash CHAR(64) NOT NULL,
        source_snapshot_json NVARCHAR(MAX) NOT NULL,
        provenance_json NVARCHAR(MAX) NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_competition_submission_status DEFAULT N'Locked',
        submitted_at DATETIME2 NOT NULL CONSTRAINT df_competition_submission_submitted DEFAULT SYSUTCDATETIME(),
        locked_at DATETIME2 NOT NULL CONSTRAINT df_competition_submission_locked DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_competition_submission_guid UNIQUE(submission_guid),
        CONSTRAINT uq_competition_submission_entry UNIQUE(entry_id),
        CONSTRAINT fk_competition_submission_entry FOREIGN KEY(entry_id) REFERENCES dbo.competition_entries(id),
        CONSTRAINT fk_competition_submission_source FOREIGN KEY(source_submission_id) REFERENCES dbo.presentation_submissions(id),
        CONSTRAINT ck_competition_submission_type CHECK(source_type IN(N'research_snapshot',N'presentation_submission',N'document_snapshot',N'media_snapshot')),
        CONSTRAINT ck_competition_submission_status CHECK([status]=N'Locked'),
        CONSTRAINT ck_competition_submission_hash CHECK(LEN(source_revision_hash)=64 AND source_revision_hash NOT LIKE N'%[^0-9A-Fa-f]%'),
        CONSTRAINT ck_competition_submission_snapshot CHECK(ISJSON(source_snapshot_json)=1),
        CONSTRAINT ck_competition_submission_provenance CHECK(provenance_json IS NULL OR ISJSON(provenance_json)=1)
    );
END;

IF OBJECT_ID(N'dbo.competition_audit', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.competition_audit (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_competition_audit PRIMARY KEY,
        competition_id BIGINT NULL,
        entry_id BIGINT NULL,
        submission_id BIGINT NULL,
        action_type NVARCHAR(60) NOT NULL,
        actor_type NVARCHAR(40) NOT NULL,
        actor_identifier NVARCHAR(190) NULL,
        organization_code NVARCHAR(120) NULL,
        succeeded BIT NOT NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_competition_audit_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_competition_audit_competition FOREIGN KEY(competition_id) REFERENCES dbo.competitions(id),
        CONSTRAINT fk_competition_audit_entry FOREIGN KEY(entry_id) REFERENCES dbo.competition_entries(id),
        CONSTRAINT fk_competition_audit_submission FOREIGN KEY(submission_id) REFERENCES dbo.competition_submissions(id),
        CONSTRAINT ck_competition_audit_metadata CHECK(metadata_json IS NULL OR ISJSON(metadata_json)=1)
    );
END;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competitions') AND name=N'idx_competitions_scope_status_dates')
    EXEC(N'CREATE INDEX idx_competitions_scope_status_dates ON dbo.competitions(scope_type,owner_organization_code,[status],open_at,submission_deadline);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competition_entries') AND name=N'idx_competition_entries_student_status')
    EXEC(N'CREATE INDEX idx_competition_entries_student_status ON dbo.competition_entries(student_id,[status],entered_at DESC);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competition_entries') AND name=N'idx_competition_entries_competition_status')
    EXEC(N'CREATE INDEX idx_competition_entries_competition_status ON dbo.competition_entries(competition_id,[status],entered_at);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competition_audit') AND name=N'idx_competition_audit_entity')
    EXEC(N'CREATE INDEX idx_competition_audit_entity ON dbo.competition_audit(competition_id,entry_id,created_at DESC);');

COMMIT TRANSACTION;
