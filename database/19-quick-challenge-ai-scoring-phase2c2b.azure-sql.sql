-- Migration 19: Phase 2C.2B AI Quick Challenge practice scoring.
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.quick_challenge_scoring_policies',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_scoring_policies(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_scoring_policies PRIMARY KEY,
        policy_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_quick_challenge_scoring_policy_guid DEFAULT NEWID(),
        policy_code NVARCHAR(80) NOT NULL,
        policy_version INT NOT NULL,
        challenge_type NVARCHAR(40) NOT NULL,
        weights_json NVARCHAR(2000) NOT NULL,
        benchmark_type NVARCHAR(30) NOT NULL,
        benchmark_score TINYINT NOT NULL,
        benchmark_label NVARCHAR(120) NOT NULL,
        prompt_version NVARCHAR(100) NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_quick_challenge_scoring_policy_status DEFAULT N'Active',
        created_by BIGINT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_scoring_policy_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_quick_challenge_scoring_policy_guid UNIQUE(policy_guid),
        CONSTRAINT uq_quick_challenge_scoring_policy_version UNIQUE(policy_code,policy_version),
        CONSTRAINT fk_quick_challenge_scoring_policy_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_quick_challenge_scoring_policy_type CHECK(challenge_type IN(N'speech',N'persuasion',N'impromptu',N'explain',N'storytelling',N'elevator_pitch',N'critical_thinking',N'teaching',N'research_summary',N'leadership_reflection')),
        CONSTRAINT ck_quick_challenge_scoring_policy_weights CHECK(ISJSON(weights_json)=1),
        CONSTRAINT ck_quick_challenge_scoring_policy_benchmark_type CHECK(benchmark_type IN(N'Fixed',N'Difficulty',N'LeadershipLevel')),
        CONSTRAINT ck_quick_challenge_scoring_policy_benchmark_score CHECK(benchmark_score BETWEEN 0 AND 100),
        CONSTRAINT ck_quick_challenge_scoring_policy_status CHECK([status] IN(N'Active',N'Disabled',N'Superseded'))
    );
END;

IF COL_LENGTH(N'dbo.quick_challenge_template_versions',N'ai_evaluation_enabled') IS NULL
    EXEC(N'ALTER TABLE dbo.quick_challenge_template_versions ADD ai_evaluation_enabled BIT NOT NULL CONSTRAINT df_quick_challenge_version_ai_enabled DEFAULT 0;');
IF COL_LENGTH(N'dbo.quick_challenge_template_versions',N'scoring_policy_id') IS NULL
    EXEC(N'ALTER TABLE dbo.quick_challenge_template_versions ADD scoring_policy_id BIGINT NULL;');
IF NOT EXISTS(SELECT 1 FROM sys.foreign_keys WHERE parent_object_id=OBJECT_ID(N'dbo.quick_challenge_template_versions') AND name=N'fk_quick_challenge_version_scoring_policy')
    EXEC(N'ALTER TABLE dbo.quick_challenge_template_versions ADD CONSTRAINT fk_quick_challenge_version_scoring_policy FOREIGN KEY(scoring_policy_id) REFERENCES dbo.quick_challenge_scoring_policies(id);');

IF OBJECT_ID(N'dbo.quick_challenge_evaluations',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_evaluations(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_evaluations PRIMARY KEY,
        evaluation_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_quick_challenge_evaluation_guid DEFAULT NEWID(),
        attempt_id BIGINT NOT NULL,
        student_id BIGINT NOT NULL,
        template_version_id BIGINT NOT NULL,
        rubric_version_id BIGINT NOT NULL,
        scoring_policy_id BIGINT NOT NULL,
        source_revision_hash CHAR(64) NOT NULL,
        source_type NVARCHAR(40) NOT NULL,
        template_version INT NOT NULL,
        rubric_version INT NOT NULL,
        scoring_policy_version INT NOT NULL,
        ai_provider NVARCHAR(80) NOT NULL,
        ai_model NVARCHAR(120) NOT NULL,
        prompt_version NVARCHAR(100) NOT NULL,
        [status] NVARCHAR(20) NOT NULL,
        component_scores_json NVARCHAR(MAX) NULL,
        total_score TINYINT NULL,
        coaching_feedback_json NVARCHAR(MAX) NULL,
        benchmark_type NVARCHAR(30) NOT NULL,
        benchmark_score TINYINT NOT NULL,
        benchmark_label NVARCHAR(120) NOT NULL,
        error_code NVARCHAR(80) NULL,
        error_message NVARCHAR(500) NULL,
        processing_started_at DATETIME2 NULL,
        completed_at DATETIME2 NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_evaluation_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_quick_challenge_evaluation_guid UNIQUE(evaluation_guid),
        CONSTRAINT uq_quick_challenge_evaluation_source_policy UNIQUE(attempt_id,source_revision_hash,scoring_policy_id),
        CONSTRAINT fk_quick_challenge_evaluation_attempt FOREIGN KEY(attempt_id) REFERENCES dbo.quick_challenge_attempts(id),
        CONSTRAINT fk_quick_challenge_evaluation_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_quick_challenge_evaluation_template_version FOREIGN KEY(template_version_id) REFERENCES dbo.quick_challenge_template_versions(id),
        CONSTRAINT fk_quick_challenge_evaluation_rubric FOREIGN KEY(rubric_version_id) REFERENCES dbo.competition_rubric_versions(id),
        CONSTRAINT fk_quick_challenge_evaluation_policy FOREIGN KEY(scoring_policy_id) REFERENCES dbo.quick_challenge_scoring_policies(id),
        CONSTRAINT ck_quick_challenge_evaluation_hash CHECK(LEN(source_revision_hash)=64 AND source_revision_hash NOT LIKE '%[^0-9A-Fa-f]%'),
        CONSTRAINT ck_quick_challenge_evaluation_status CHECK([status] IN(N'Pending',N'Processing',N'Completed',N'Failed',N'Stale')),
        CONSTRAINT ck_quick_challenge_evaluation_components CHECK(component_scores_json IS NULL OR ISJSON(component_scores_json)=1),
        CONSTRAINT ck_quick_challenge_evaluation_feedback CHECK(coaching_feedback_json IS NULL OR ISJSON(coaching_feedback_json)=1),
        CONSTRAINT ck_quick_challenge_evaluation_score CHECK(total_score IS NULL OR total_score BETWEEN 0 AND 100),
        CONSTRAINT ck_quick_challenge_evaluation_completed CHECK(([status]=N'Completed' AND total_score IS NOT NULL AND component_scores_json IS NOT NULL AND coaching_feedback_json IS NOT NULL AND completed_at IS NOT NULL) OR [status]<>N'Completed')
    );
END;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.quick_challenge_evaluations') AND name=N'idx_quick_challenge_evaluations_student_created')
    EXEC(N'CREATE INDEX idx_quick_challenge_evaluations_student_created ON dbo.quick_challenge_evaluations(student_id,created_at DESC) INCLUDE([status],total_score,benchmark_score);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.quick_challenge_evaluations') AND name=N'idx_quick_challenge_evaluations_status')
    EXEC(N'CREATE INDEX idx_quick_challenge_evaluations_status ON dbo.quick_challenge_evaluations([status],processing_started_at) INCLUDE(attempt_id,scoring_policy_id);');

IF OBJECT_ID(N'dbo.quick_challenge_evaluation_audit',N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_evaluation_audit(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_evaluation_audit PRIMARY KEY,
        evaluation_id BIGINT NULL,
        action_type NVARCHAR(80) NOT NULL,
        actor_role NVARCHAR(40) NOT NULL,
        actor_identifier NVARCHAR(180) NULL,
        entity_reference NVARCHAR(180) NOT NULL,
        succeeded BIT NOT NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_evaluation_audit_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_quick_challenge_evaluation_audit_evaluation FOREIGN KEY(evaluation_id) REFERENCES dbo.quick_challenge_evaluations(id),
        CONSTRAINT ck_quick_challenge_evaluation_audit_json CHECK(metadata_json IS NULL OR ISJSON(metadata_json)=1)
    );
END;

MERGE dbo.quick_challenge_scoring_policies AS target
USING(VALUES
 (N'speech-v1',1,N'speech',N'{"Content":30,"Structure":25,"Clarity":30,"Time Discipline":15}',N'Difficulty',65,N'Beginner Benchmark'),
 (N'persuasion-v1',1,N'persuasion',N'{"Claim":25,"Reasoning":30,"Evidence":25,"Persuasive Structure":20}',N'Fixed',80,N'Persuasion Benchmark'),
 (N'impromptu-v1',1,N'impromptu',N'{"Relevance":30,"Structure":30,"Clarity":30,"Time Discipline":10}',N'Difficulty',75,N'Intermediate Benchmark'),
 (N'explain-v1',1,N'explain',N'{"Accuracy":30,"Simplicity":30,"Clarity":25,"Audience Adaptation":15}',N'Fixed',75,N'Clear Explanation Benchmark'),
 (N'storytelling-v1',1,N'storytelling',N'{"Narrative Structure":30,"Clarity":25,"Engagement":25,"Conclusion":20}',N'Fixed',80,N'Storytelling Benchmark'),
 (N'elevator-pitch-v1',1,N'elevator_pitch',N'{"Problem":25,"Solution":30,"Relevance":20,"Clarity":25}',N'Fixed',80,N'Pitch Benchmark'),
 (N'critical-thinking-v1',1,N'critical_thinking',N'{"Position":20,"Reasoning":35,"Evidence":25,"Counterpoint":20}',N'LeadershipLevel',82,N'Speaker Benchmark'),
 (N'teaching-v1',1,N'teaching',N'{"Accuracy":30,"Structure":25,"Clarity":30,"Audience Adaptation":15}',N'Fixed',80,N'Teaching Benchmark'),
 (N'research-summary-v1',1,N'research_summary',N'{"Accuracy":30,"Synthesis":30,"Organization":25,"Clarity":15}',N'Fixed',80,N'Research Summary Benchmark'),
 (N'leadership-reflection-v1',1,N'leadership_reflection',N'{"Relevance":25,"Reflection":35,"Evidence":20,"Actionable Learning":20}',N'LeadershipLevel',82,N'Speaker Benchmark')
) AS source(policy_code,policy_version,challenge_type,weights_json,benchmark_type,benchmark_score,benchmark_label)
ON target.policy_code=source.policy_code AND target.policy_version=source.policy_version
WHEN NOT MATCHED THEN INSERT(policy_code,policy_version,challenge_type,weights_json,benchmark_type,benchmark_score,benchmark_label,prompt_version,[status]) VALUES(source.policy_code,source.policy_version,source.challenge_type,source.weights_json,source.benchmark_type,source.benchmark_score,source.benchmark_label,N'quick-challenge-score-v1',N'Active');

INSERT dbo.plan_feature_rules(feature_version_id,feature_key,is_enabled,policy_json)
SELECT version.id,N'ai_quick_challenge_scoring',1,N'{"enforced":false,"usage_accounting":"future-phase-3a3"}'
FROM dbo.plan_feature_versions version
WHERE version.[status]=N'active'
  AND NOT EXISTS(SELECT 1 FROM dbo.plan_feature_rules rule_version WHERE rule_version.feature_version_id=version.id AND rule_version.feature_key=N'ai_quick_challenge_scoring');

COMMIT TRANSACTION;
