SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.quick_challenge_skills', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_skills (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_skills PRIMARY KEY,
        skill_code NVARCHAR(60) NOT NULL CONSTRAINT uq_quick_challenge_skill_code UNIQUE,
        display_name NVARCHAR(100) NOT NULL,
        is_active BIT NOT NULL CONSTRAINT df_quick_challenge_skill_active DEFAULT 1,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_skill_created DEFAULT SYSUTCDATETIME()
    );
END;

MERGE dbo.quick_challenge_skills AS target
USING (VALUES
 (N'public-speaking',N'Public Speaking'),(N'storytelling',N'Storytelling'),
 (N'persuasion',N'Persuasion'),(N'research',N'Research'),
 (N'critical-thinking',N'Critical Thinking'),(N'impromptu-speaking',N'Impromptu Speaking'),
 (N'presentation-delivery',N'Presentation Delivery'),(N'communication-clarity',N'Communication Clarity'),
 (N'leadership',N'Leadership'),(N'teaching',N'Teaching')
) AS source(skill_code,display_name)
ON target.skill_code=source.skill_code
WHEN NOT MATCHED THEN INSERT(skill_code,display_name) VALUES(source.skill_code,source.display_name);

IF OBJECT_ID(N'dbo.quick_challenge_templates', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_templates (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_templates PRIMARY KEY,
        template_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_quick_challenge_template_guid DEFAULT NEWID(),
        template_code NVARCHAR(80) NOT NULL,
        display_name NVARCHAR(160) NOT NULL,
        challenge_type NVARCHAR(40) NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_quick_challenge_template_status DEFAULT N'Draft',
        created_by BIGINT NULL,
        created_by_email NVARCHAR(190) NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_template_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_template_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_quick_challenge_template_guid UNIQUE(template_guid),
        CONSTRAINT uq_quick_challenge_template_code UNIQUE(template_code),
        CONSTRAINT fk_quick_challenge_template_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_quick_challenge_template_type CHECK(challenge_type IN(N'speech',N'persuasion',N'impromptu',N'explain',N'storytelling',N'elevator_pitch',N'critical_thinking',N'teaching',N'research_summary',N'leadership_reflection')),
        CONSTRAINT ck_quick_challenge_template_status CHECK([status] IN(N'Draft',N'Published',N'Archived'))
    );
END;

IF OBJECT_ID(N'dbo.quick_challenge_template_versions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_template_versions (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_template_versions PRIMARY KEY,
        version_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_quick_challenge_template_version_guid DEFAULT NEWID(),
        template_id BIGINT NOT NULL,
        version_number INT NOT NULL,
        prompt_text NVARCHAR(2000) NOT NULL,
        instructions NVARCHAR(2000) NOT NULL,
        difficulty NVARCHAR(20) NOT NULL,
        preparation_seconds SMALLINT NOT NULL,
        response_seconds SMALLINT NOT NULL,
        maximum_attempts SMALLINT NOT NULL,
        attempt_policy NVARCHAR(20) NOT NULL,
        prompt_reveal_mode NVARCHAR(20) NOT NULL,
        rubric_version_id BIGINT NOT NULL,
        is_frozen BIT NOT NULL CONSTRAINT df_quick_challenge_version_frozen DEFAULT 1,
        created_by BIGINT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_version_created DEFAULT SYSUTCDATETIME(),
        CONSTRAINT uq_quick_challenge_version_guid UNIQUE(version_guid),
        CONSTRAINT uq_quick_challenge_template_version UNIQUE(template_id,version_number),
        CONSTRAINT fk_quick_challenge_version_template FOREIGN KEY(template_id) REFERENCES dbo.quick_challenge_templates(id),
        CONSTRAINT fk_quick_challenge_version_rubric FOREIGN KEY(rubric_version_id) REFERENCES dbo.competition_rubric_versions(id),
        CONSTRAINT fk_quick_challenge_version_creator FOREIGN KEY(created_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_quick_challenge_version_difficulty CHECK(difficulty IN(N'Beginner',N'Intermediate',N'Advanced')),
        CONSTRAINT ck_quick_challenge_version_timing CHECK(preparation_seconds BETWEEN 0 AND 600 AND response_seconds BETWEEN 15 AND 900),
        CONSTRAINT ck_quick_challenge_version_attempts CHECK(maximum_attempts BETWEEN 1 AND 20),
        CONSTRAINT ck_quick_challenge_version_attempt_policy CHECK(attempt_policy IN(N'best',N'latest')),
        CONSTRAINT ck_quick_challenge_version_reveal CHECK(prompt_reveal_mode IN(N'visible',N'on_start')),
        CONSTRAINT ck_quick_challenge_version_frozen CHECK(is_frozen=1)
    );
END;

IF OBJECT_ID(N'dbo.quick_challenge_template_version_skills', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_template_version_skills (
        template_version_id BIGINT NOT NULL,
        skill_id BIGINT NOT NULL,
        is_primary BIT NOT NULL CONSTRAINT df_quick_challenge_skill_primary DEFAULT 0,
        CONSTRAINT pk_quick_challenge_template_version_skills PRIMARY KEY(template_version_id,skill_id),
        CONSTRAINT fk_quick_challenge_skill_version FOREIGN KEY(template_version_id) REFERENCES dbo.quick_challenge_template_versions(id),
        CONSTRAINT fk_quick_challenge_skill_skill FOREIGN KEY(skill_id) REFERENCES dbo.quick_challenge_skills(id)
    );
END;

IF COL_LENGTH(N'dbo.competitions',N'quick_template_version_id') IS NULL
    ALTER TABLE dbo.competitions ADD quick_template_version_id BIGINT NULL;
IF COL_LENGTH(N'dbo.competitions',N'experience_mode') IS NULL
    ALTER TABLE dbo.competitions ADD experience_mode NVARCHAR(24) NOT NULL CONSTRAINT df_competition_experience_mode DEFAULT N'formal';
IF NOT EXISTS(SELECT 1 FROM sys.foreign_keys WHERE parent_object_id=OBJECT_ID(N'dbo.competitions') AND name=N'fk_competition_quick_template_version')
    EXEC(N'ALTER TABLE dbo.competitions ADD CONSTRAINT fk_competition_quick_template_version FOREIGN KEY(quick_template_version_id) REFERENCES dbo.quick_challenge_template_versions(id);');
IF NOT EXISTS(SELECT 1 FROM sys.check_constraints WHERE parent_object_id=OBJECT_ID(N'dbo.competitions') AND name=N'ck_competition_experience_mode')
    EXEC(N'ALTER TABLE dbo.competitions ADD CONSTRAINT ck_competition_experience_mode CHECK(experience_mode IN(N''formal'',N''quick_practice'',N''quick_ranked''));');

IF OBJECT_ID(N'dbo.quick_challenge_attempts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.quick_challenge_attempts (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_quick_challenge_attempts PRIMARY KEY,
        attempt_guid UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_quick_challenge_attempt_guid DEFAULT NEWID(),
        competition_entry_id BIGINT NOT NULL,
        student_id BIGINT NOT NULL,
        template_version_id BIGINT NOT NULL,
        attempt_number SMALLINT NOT NULL,
        [status] NVARCHAR(20) NOT NULL CONSTRAINT df_quick_challenge_attempt_status DEFAULT N'Started',
        prompt_revealed_at DATETIME2 NOT NULL,
        started_at DATETIME2 NOT NULL,
        response_deadline_at DATETIME2 NOT NULL,
        submitted_at DATETIME2 NULL,
        source_type NVARCHAR(40) NULL,
        source_reference NVARCHAR(500) NULL,
        source_revision_hash CHAR(64) NULL,
        source_snapshot_json NVARCHAR(MAX) NULL,
        practice_score DECIMAL(5,2) NULL,
        score_version NVARCHAR(80) NULL,
        score_source NVARCHAR(30) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_quick_challenge_attempt_created DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_quick_challenge_attempt_guid UNIQUE(attempt_guid),
        CONSTRAINT uq_quick_challenge_attempt_number UNIQUE(competition_entry_id,attempt_number),
        CONSTRAINT fk_quick_challenge_attempt_entry FOREIGN KEY(competition_entry_id) REFERENCES dbo.competition_entries(id),
        CONSTRAINT fk_quick_challenge_attempt_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_quick_challenge_attempt_version FOREIGN KEY(template_version_id) REFERENCES dbo.quick_challenge_template_versions(id),
        CONSTRAINT ck_quick_challenge_attempt_status CHECK([status] IN(N'Started',N'Submitted',N'Expired',N'Withdrawn')),
        CONSTRAINT ck_quick_challenge_attempt_dates CHECK(prompt_revealed_at<=started_at AND started_at<response_deadline_at AND (submitted_at IS NULL OR submitted_at>=started_at)),
        CONSTRAINT ck_quick_challenge_attempt_hash CHECK(source_revision_hash IS NULL OR (LEN(source_revision_hash)=64 AND source_revision_hash NOT LIKE N'%[^0-9A-Fa-f]%')),
        CONSTRAINT ck_quick_challenge_attempt_snapshot CHECK(source_snapshot_json IS NULL OR ISJSON(source_snapshot_json)=1),
        CONSTRAINT ck_quick_challenge_attempt_score CHECK((practice_score IS NULL AND score_version IS NULL AND score_source IS NULL) OR (practice_score BETWEEN 0 AND 100 AND score_version IS NOT NULL AND score_source IN(N'AIPractice',N'HumanPractice')))
    );
END;

IF OBJECT_ID(N'dbo.student_challenge_personal_bests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_challenge_personal_bests (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_challenge_personal_bests PRIMARY KEY,
        student_id BIGINT NOT NULL,
        template_id BIGINT NOT NULL,
        score_version NVARCHAR(80) NOT NULL,
        best_attempt_id BIGINT NOT NULL,
        best_score DECIMAL(5,2) NOT NULL,
        achieved_at DATETIME2 NOT NULL,
        updated_at DATETIME2 NOT NULL CONSTRAINT df_student_challenge_personal_best_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_student_challenge_personal_best UNIQUE(student_id,template_id,score_version),
        CONSTRAINT fk_student_challenge_personal_best_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_student_challenge_personal_best_template FOREIGN KEY(template_id) REFERENCES dbo.quick_challenge_templates(id),
        CONSTRAINT fk_student_challenge_personal_best_attempt FOREIGN KEY(best_attempt_id) REFERENCES dbo.quick_challenge_attempts(id),
        CONSTRAINT ck_student_challenge_personal_best_score CHECK(best_score BETWEEN 0 AND 100)
    );
END;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.quick_challenge_template_versions') AND name=N'idx_quick_template_versions_template_created')
    EXEC(N'CREATE INDEX idx_quick_template_versions_template_created ON dbo.quick_challenge_template_versions(template_id,version_number DESC);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.quick_challenge_attempts') AND name=N'idx_quick_attempts_student_template')
    EXEC(N'CREATE INDEX idx_quick_attempts_student_template ON dbo.quick_challenge_attempts(student_id,template_version_id,[status],attempt_number DESC);');
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.competitions') AND name=N'idx_competitions_quick_template')
    EXEC(N'CREATE INDEX idx_competitions_quick_template ON dbo.competitions(quick_template_version_id,experience_mode,[status],open_at);');

COMMIT TRANSACTION;
