SET XACT_ABORT ON;

IF OBJECT_ID(N'dbo.leadership_rule_versions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leadership_rule_versions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_leadership_rule_versions PRIMARY KEY,
        rule_version NVARCHAR(80) NOT NULL,
        level_from_id BIGINT NOT NULL,
        level_to_id BIGINT NOT NULL,
        rules_json NVARCHAR(MAX) NOT NULL,
        active BIT NOT NULL CONSTRAINT df_leadership_rules_active DEFAULT 1,
        effective_from DATETIME2 NOT NULL CONSTRAINT df_leadership_rules_effective DEFAULT SYSUTCDATETIME(),
        created_at DATETIME2 NOT NULL CONSTRAINT df_leadership_rules_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_leadership_rules_from FOREIGN KEY (level_from_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_rules_to FOREIGN KEY (level_to_id) REFERENCES dbo.levels(id),
        CONSTRAINT ck_leadership_rules_json CHECK (ISJSON(rules_json) = 1),
        CONSTRAINT uq_leadership_rules_version_transition UNIQUE (rule_version, level_from_id, level_to_id)
    );
END;

IF OBJECT_ID(N'dbo.leadership_evidence', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leadership_evidence (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_leadership_evidence PRIMARY KEY,
        evidence_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_leadership_evidence_guid DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        evidence_type NVARCHAR(60) NOT NULL,
        source_type NVARCHAR(60) NOT NULL,
        source_id NVARCHAR(120) NOT NULL,
        organization_code NVARCHAR(120) NULL,
        [status] NVARCHAR(30) NOT NULL CONSTRAINT df_leadership_evidence_status DEFAULT N'Pending',
        approved_by_email NVARCHAR(190) NULL,
        approved_by_role NVARCHAR(40) NULL,
        evidence_date DATE NOT NULL,
        notes NVARCHAR(2000) NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_leadership_evidence_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_leadership_evidence_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_leadership_evidence_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT ck_leadership_evidence_type CHECK (evidence_type IN (N'presentation',N'applied_ai_review',N'human_review',N'reflection',N'leadership_service',N'peer_support',N'improvement',N'leadership_goal',N'competition_participation',N'competition_finalist',N'competition_award')),
        CONSTRAINT ck_leadership_evidence_status CHECK ([status] IN (N'Pending',N'Approved',N'Rejected',N'Withdrawn')),
        CONSTRAINT ck_leadership_evidence_metadata CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT uq_leadership_evidence_source UNIQUE (student_id, evidence_type, source_type, source_id)
    );
END;

IF OBJECT_ID(N'dbo.student_leadership_reflections', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_leadership_reflections (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_leadership_reflections PRIMARY KEY,
        reflection_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_student_reflections_guid DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        presentation_submission_id BIGINT NULL,
        went_well NVARCHAR(1500) NOT NULL,
        improve_next NVARCHAR(1500) NOT NULL,
        learned NVARCHAR(1500) NOT NULL,
        next_goal NVARCHAR(1500) NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_student_reflections_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_student_reflections_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_student_reflections_submission FOREIGN KEY (presentation_submission_id) REFERENCES dbo.presentation_submissions(id),
        CONSTRAINT uq_student_reflections_guid UNIQUE (reflection_guid)
    );
END;

IF OBJECT_ID(N'dbo.leadership_eligibility_snapshots', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leadership_eligibility_snapshots (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_leadership_eligibility_snapshots PRIMARY KEY,
        snapshot_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_leadership_snapshot_guid DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        current_level_id BIGINT NOT NULL,
        target_level_id BIGINT NULL,
        rule_version NVARCHAR(80) NOT NULL,
        [status] NVARCHAR(40) NOT NULL,
        evidence_snapshot NVARCHAR(MAX) NOT NULL,
        source_revision CHAR(64) NOT NULL,
        evaluated_at DATETIME2 NOT NULL CONSTRAINT df_leadership_snapshot_evaluated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_leadership_snapshot_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_leadership_snapshot_current FOREIGN KEY (current_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_snapshot_target FOREIGN KEY (target_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT ck_leadership_snapshot_status CHECK ([status] IN (N'Building Evidence',N'Eligible for Review',N'Under Review',N'Approved',N'More Evidence Needed')),
        CONSTRAINT ck_leadership_snapshot_json CHECK (ISJSON(evidence_snapshot) = 1),
        CONSTRAINT uq_leadership_snapshot_guid UNIQUE (snapshot_guid)
    );
END;

IF OBJECT_ID(N'dbo.leadership_decisions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leadership_decisions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_leadership_decisions PRIMARY KEY,
        decision_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_leadership_decision_guid DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        previous_level_id BIGINT NOT NULL,
        target_level_id BIGINT NOT NULL,
        eligibility_snapshot_id BIGINT NULL,
        decision NVARCHAR(40) NOT NULL,
        decision_reason NVARCHAR(2000) NOT NULL,
        approved_by_email NVARCHAR(190) NOT NULL,
        approver_role NVARCHAR(40) NOT NULL,
        organization_code NVARCHAR(120) NULL,
        override_used BIT NOT NULL CONSTRAINT df_leadership_decision_override DEFAULT 0,
        decided_at DATETIME2 NOT NULL CONSTRAINT df_leadership_decision_decided DEFAULT SYSUTCDATETIME(),
        created_at DATETIME2 NOT NULL CONSTRAINT df_leadership_decision_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_leadership_decision_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_leadership_decision_previous FOREIGN KEY (previous_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_decision_target FOREIGN KEY (target_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_decision_snapshot FOREIGN KEY (eligibility_snapshot_id) REFERENCES dbo.leadership_eligibility_snapshots(id),
        CONSTRAINT ck_leadership_decision CHECK (decision IN (N'Approved',N'More Evidence Needed')),
        CONSTRAINT ck_leadership_approver_role CHECK (approver_role IN (N'OrganizationAdmin',N'MasterAdmin')),
        CONSTRAINT uq_leadership_decision_guid UNIQUE (decision_guid)
    );
END;

IF OBJECT_ID(N'dbo.leadership_level_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leadership_level_history (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_leadership_level_history PRIMARY KEY,
        student_id BIGINT NOT NULL,
        previous_level_id BIGINT NOT NULL,
        new_level_id BIGINT NOT NULL,
        decision_id BIGINT NOT NULL,
        approved_by_email NVARCHAR(190) NOT NULL,
        approver_role NVARCHAR(40) NOT NULL,
        organization_code NVARCHAR(120) NULL,
        promoted_at DATETIME2 NOT NULL CONSTRAINT df_leadership_history_promoted DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_leadership_history_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_leadership_history_previous FOREIGN KEY (previous_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_history_new FOREIGN KEY (new_level_id) REFERENCES dbo.levels(id),
        CONSTRAINT fk_leadership_history_decision FOREIGN KEY (decision_id) REFERENCES dbo.leadership_decisions(id),
        CONSTRAINT uq_leadership_history_decision UNIQUE (decision_id)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.leadership_evidence') AND name=N'idx_leadership_evidence_student_status')
    CREATE INDEX idx_leadership_evidence_student_status ON dbo.leadership_evidence(student_id,[status],evidence_date DESC);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.leadership_eligibility_snapshots') AND name=N'idx_leadership_snapshot_student')
    CREATE INDEX idx_leadership_snapshot_student ON dbo.leadership_eligibility_snapshots(student_id,evaluated_at DESC);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.leadership_decisions') AND name=N'idx_leadership_decision_student')
    CREATE INDEX idx_leadership_decision_student ON dbo.leadership_decisions(student_id,decided_at DESC);

IF OBJECT_ID(N'dbo.tr_leadership_level_history_immutable', N'TR') IS NULL
    EXEC(N'CREATE TRIGGER dbo.tr_leadership_level_history_immutable ON dbo.leadership_level_history INSTEAD OF UPDATE, DELETE AS BEGIN SET NOCOUNT ON; THROW 51013, ''Leadership level history is immutable.'', 1; END');

DECLARE @explorer BIGINT=(SELECT id FROM dbo.levels WHERE code=N'explorer');
DECLARE @speaker BIGINT=(SELECT id FROM dbo.levels WHERE code=N'speaker');
DECLARE @leader BIGINT=(SELECT id FROM dbo.levels WHERE code=N'leader');
DECLARE @mentor BIGINT=(SELECT id FROM dbo.levels WHERE code=N'mentor');
IF @explorer IS NULL OR @speaker IS NULL OR @leader IS NULL OR @mentor IS NULL
    THROW 51012, 'Migration 12 requires Explorer, Speaker, Leader, and Mentor levels.', 1;

IF NOT EXISTS (SELECT 1 FROM dbo.leadership_rule_versions WHERE rule_version=N'leadership-rules-v1' AND level_from_id=@explorer AND level_to_id=@speaker)
    INSERT dbo.leadership_rule_versions(rule_version,level_from_id,level_to_id,rules_json) VALUES(N'leadership-rules-v1',@explorer,@speaker,N'{"presentations":2,"reviews":1,"reflections":1}');
IF NOT EXISTS (SELECT 1 FROM dbo.leadership_rule_versions WHERE rule_version=N'leadership-rules-v1' AND level_from_id=@speaker AND level_to_id=@leader)
    INSERT dbo.leadership_rule_versions(rule_version,level_from_id,level_to_id,rules_json) VALUES(N'leadership-rules-v1',@speaker,@leader,N'{"presentations":4,"reviews":2,"recent_reflection":1,"reflection_recent_days":180,"leadership_service":1,"improvement":1}');
IF NOT EXISTS (SELECT 1 FROM dbo.leadership_rule_versions WHERE rule_version=N'leadership-rules-v1' AND level_from_id=@leader AND level_to_id=@mentor)
    INSERT dbo.leadership_rule_versions(rule_version,level_from_id,level_to_id,rules_json) VALUES(N'leadership-rules-v1',@leader,@mentor,N'{"presentations":6,"reviews":3,"recent_reflection_or_goal":1,"reflection_recent_days":180,"leadership_service":2,"peer_support":1,"improvement":1}');
