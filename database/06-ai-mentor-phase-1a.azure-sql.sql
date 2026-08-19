SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.ai_mentor_reviews', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.ai_mentor_reviews (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_ai_mentor_reviews PRIMARY KEY,
        review_key UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_ai_mentor_reviews_key DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        yuva_id NVARCHAR(40) NOT NULL,
        source_submission_id BIGINT NULL,
        source_submission_reference NVARCHAR(190) NOT NULL,
        source_revision_hash CHAR(64) NOT NULL,
        provider NVARCHAR(40) NOT NULL,
        model NVARCHAR(120) NOT NULL,
        prompt_version NVARCHAR(80) NOT NULL,
        status NVARCHAR(20) NOT NULL,
        generated_result NVARCHAR(MAX) NULL,
        admin_edited_result NVARCHAR(MAX) NULL,
        recommended_next_step NVARCHAR(1200) NULL,
        error_code NVARCHAR(80) NULL,
        error_category NVARCHAR(80) NULL,
        generated_at DATETIME2 NULL,
        reviewed_by BIGINT NULL,
        reviewed_at DATETIME2 NULL,
        applied_at DATETIME2 NULL,
        apply_reference UNIQUEIDENTIFIER NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_ai_mentor_reviews_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_ai_mentor_reviews_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_ai_mentor_reviews_key UNIQUE (review_key),
        CONSTRAINT fk_ai_mentor_reviews_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_ai_mentor_reviews_submission FOREIGN KEY (source_submission_id) REFERENCES dbo.presentation_submissions(id),
        CONSTRAINT fk_ai_mentor_reviews_reviewer FOREIGN KEY (reviewed_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_ai_mentor_reviews_status CHECK (status IN ('Processing','Draft','Failed','Applied','Stale')),
        CONSTRAINT ck_ai_mentor_reviews_generated_json CHECK (generated_result IS NULL OR ISJSON(generated_result) = 1),
        CONSTRAINT ck_ai_mentor_reviews_edited_json CHECK (admin_edited_result IS NULL OR ISJSON(admin_edited_result) = 1)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.ai_mentor_reviews') AND name = N'idx_ai_mentor_reviews_student_status')
    CREATE INDEX idx_ai_mentor_reviews_student_status ON dbo.ai_mentor_reviews (student_id, status, created_at DESC);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.ai_mentor_reviews') AND name = N'uq_ai_mentor_reviews_apply_reference')
    CREATE UNIQUE INDEX uq_ai_mentor_reviews_apply_reference ON dbo.ai_mentor_reviews (apply_reference) WHERE apply_reference IS NOT NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(N'dbo.student_points') AND name = N'uq_student_points_ai_mentor_source')
    CREATE UNIQUE INDEX uq_student_points_ai_mentor_source
        ON dbo.student_points (student_id, source_type, source_id)
        WHERE source_type = N'ai_mentor_review' AND source_id IS NOT NULL;

COMMIT TRANSACTION;
