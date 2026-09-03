-- Migration 20: Phase 2C.2C My Growth achievement foundation.
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.achievement_definitions',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.achievement_definitions(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_achievement_definitions PRIMARY KEY,
        achievement_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_achievement_definition_guid DEFAULT NEWID(),
        achievement_code NVARCHAR(80) NOT NULL,
        definition_version INT NOT NULL,
        display_name NVARCHAR(140) NOT NULL,
        description NVARCHAR(400) NOT NULL,
        category NVARCHAR(30) NOT NULL,
        rule_json NVARCHAR(2000) NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_achievement_definition_status DEFAULT N'Active',
        created_at DATETIME2 NOT NULL CONSTRAINT df_achievement_definition_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_achievement_definition_guid UNIQUE(achievement_guid),
        CONSTRAINT uq_achievement_definition_version UNIQUE(achievement_code,definition_version),
        CONSTRAINT ck_achievement_definition_category CHECK(category IN(N'Completion',N'Improvement',N'Consistency',N'Verified',N'Leadership')),
        CONSTRAINT ck_achievement_definition_rule CHECK(ISJSON(rule_json)=1),
        CONSTRAINT ck_achievement_definition_status CHECK([status] IN(N'Active',N'Disabled',N'Superseded'))
    );
END;

IF OBJECT_ID(N'dbo.student_achievements',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_achievements(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_achievements PRIMARY KEY,
        achievement_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_student_achievement_guid DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        achievement_definition_id BIGINT NOT NULL,
        earned_at DATETIME2 NOT NULL CONSTRAINT df_student_achievement_earned DEFAULT SYSUTCDATETIME(),
        source_type NVARCHAR(60) NOT NULL,
        source_reference NVARCHAR(180) NOT NULL,
        evidence_json NVARCHAR(2000) NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_student_achievement_status DEFAULT N'Earned',
        corrected_by BIGINT NULL,
        corrected_reason NVARCHAR(500) NULL,
        corrected_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_student_achievement_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_student_achievement_guid UNIQUE(achievement_guid),
        CONSTRAINT uq_student_achievement_once UNIQUE(student_id,achievement_definition_id),
        CONSTRAINT fk_student_achievement_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_student_achievement_definition FOREIGN KEY(achievement_definition_id) REFERENCES dbo.achievement_definitions(id),
        CONSTRAINT fk_student_achievement_corrector FOREIGN KEY(corrected_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_student_achievement_evidence CHECK(ISJSON(evidence_json)=1),
        CONSTRAINT ck_student_achievement_status CHECK([status] IN(N'Earned',N'Revoked')),
        CONSTRAINT ck_student_achievement_correction CHECK(([status]=N'Earned' AND corrected_by IS NULL AND corrected_reason IS NULL AND corrected_at IS NULL) OR ([status]=N'Revoked' AND corrected_by IS NOT NULL AND corrected_reason IS NOT NULL AND corrected_at IS NOT NULL))
    );
END;

IF OBJECT_ID(N'dbo.student_achievement_audit',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_achievement_audit(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_achievement_audit PRIMARY KEY,
        student_achievement_id BIGINT NULL,
        action_type NVARCHAR(40) NOT NULL,
        actor_role NVARCHAR(40) NOT NULL,
        actor_identifier NVARCHAR(180) NULL,
        succeeded BIT NOT NULL,
        metadata_json NVARCHAR(2000) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_student_achievement_audit_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_student_achievement_audit_award FOREIGN KEY(student_achievement_id) REFERENCES dbo.student_achievements(id),
        CONSTRAINT ck_student_achievement_audit_json CHECK(metadata_json IS NULL OR ISJSON(metadata_json)=1)
    );
END;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.student_achievements') AND name=N'idx_student_achievements_student_status')
    EXEC(N'CREATE INDEX idx_student_achievements_student_status ON dbo.student_achievements(student_id,[status],earned_at DESC) INCLUDE(achievement_definition_id,source_type,source_reference);');

MERGE dbo.achievement_definitions AS target
USING(VALUES
 (N'first-challenge',1,N'First Challenge',N'Completed a first meaningful Quick Challenge.',N'Completion',N'{"completed_challenges":1}'),
 (N'first-ai-coached',1,N'First AI-Coached Challenge',N'Completed a first AI-coached Quick Challenge.',N'Completion',N'{"completed_ai_evaluations":1}'),
 (N'new-personal-best',1,N'New Personal Best',N'Established or improved a Personal Best.',N'Improvement',N'{"personal_bests":1}'),
 (N'first-benchmark',1,N'Beat Your First Benchmark',N'Met a first privacy-safe system benchmark.',N'Improvement',N'{"benchmarks_beaten":1}'),
 (N'five-challenges',1,N'5 Challenges Completed',N'Completed five meaningful Quick Challenges.',N'Completion',N'{"completed_challenges":5}'),
 (N'ten-challenges',1,N'10 Challenges Completed',N'Completed ten meaningful Quick Challenges.',N'Completion',N'{"completed_challenges":10}'),
 (N'four-weeks-practice',1,N'Four Weeks of Practice',N'Practiced meaningfully in four consecutive weeks.',N'Consistency',N'{"consecutive_weeks":4}'),
 (N'first-verified-presentation',1,N'First Verified Presentation',N'Completed a first verified presentation.',N'Verified',N'{"verified_presentations":1}'),
 (N'speaker-level',1,N'Speaker Level',N'Reached the approved Speaker leadership level.',N'Leadership',N'{"leadership_level":"Speaker"}'),
 (N'leader-level',1,N'Leader Level',N'Reached the approved Leader leadership level.',N'Leadership',N'{"leadership_level":"Leader"}')
) AS source(achievement_code,definition_version,display_name,description,category,rule_json)
ON target.achievement_code=source.achievement_code AND target.definition_version=source.definition_version
WHEN NOT MATCHED THEN INSERT(achievement_code,definition_version,display_name,description,category,rule_json) VALUES(source.achievement_code,source.definition_version,source.display_name,source.description,source.category,source.rule_json);

COMMIT TRANSACTION;
