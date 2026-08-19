SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.ai_mentor_delivery_reviews', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.ai_mentor_delivery_reviews (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_ai_mentor_delivery_reviews PRIMARY KEY,
        review_key UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_ai_mentor_delivery_key DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        yuva_id NVARCHAR(40) NOT NULL,
        media_reference NVARCHAR(500) NOT NULL,
        original_filename NVARCHAR(255) NOT NULL,
        media_mime_type NVARCHAR(160) NOT NULL,
        media_size_bytes BIGINT NOT NULL,
        media_sha256 CHAR(64) NOT NULL,
        media_duration_seconds DECIMAL(10,3) NULL,
        source_revision_hash CHAR(64) NOT NULL,
        status NVARCHAR(20) NOT NULL,
        transcription_provider NVARCHAR(40) NULL,
        transcription_model NVARCHAR(120) NULL,
        transcript NVARCHAR(MAX) NULL,
        transcript_timing_json NVARCHAR(MAX) NULL,
        deterministic_metrics_json NVARCHAR(MAX) NULL,
        audio_analysis_json NVARCHAR(MAX) NULL,
        visual_analysis_json NVARCHAR(MAX) NULL,
        generated_coaching_result NVARCHAR(MAX) NULL,
        admin_edited_result NVARCHAR(MAX) NULL,
        error_code NVARCHAR(80) NULL,
        error_category NVARCHAR(120) NULL,
        generated_at DATETIME2 NULL,
        reviewed_by BIGINT NULL,
        reviewed_at DATETIME2 NULL,
        applied_at DATETIME2 NULL,
        apply_reference UNIQUEIDENTIFIER NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_ai_mentor_delivery_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_ai_mentor_delivery_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT uq_ai_mentor_delivery_key UNIQUE (review_key),
        CONSTRAINT fk_ai_mentor_delivery_student FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_ai_mentor_delivery_reviewer FOREIGN KEY (reviewed_by) REFERENCES dbo.users(id),
        CONSTRAINT ck_ai_mentor_delivery_status CHECK (status IN ('Processing','Draft','Failed','Applied','Stale')),
        CONSTRAINT ck_ai_mentor_delivery_size CHECK (media_size_bytes > 0 AND media_size_bytes <= 26214400),
        CONSTRAINT ck_ai_mentor_delivery_duration CHECK (media_duration_seconds IS NULL OR (media_duration_seconds > 0 AND media_duration_seconds <= 300)),
        CONSTRAINT ck_ai_mentor_delivery_sha CHECK (LEN(media_sha256) = 64 AND media_sha256 NOT LIKE '%[^0-9A-Fa-f]%'),
        CONSTRAINT ck_ai_mentor_delivery_source_sha CHECK (LEN(source_revision_hash) = 64 AND source_revision_hash NOT LIKE '%[^0-9A-Fa-f]%'),
        CONSTRAINT ck_ai_mentor_delivery_timing_json CHECK (transcript_timing_json IS NULL OR ISJSON(transcript_timing_json) = 1),
        CONSTRAINT ck_ai_mentor_delivery_metrics_json CHECK (deterministic_metrics_json IS NULL OR ISJSON(deterministic_metrics_json) = 1),
        CONSTRAINT ck_ai_mentor_delivery_audio_json CHECK (audio_analysis_json IS NULL OR ISJSON(audio_analysis_json) = 1),
        CONSTRAINT ck_ai_mentor_delivery_visual_json CHECK (visual_analysis_json IS NULL OR ISJSON(visual_analysis_json) = 1),
        CONSTRAINT ck_ai_mentor_delivery_generated_json CHECK (generated_coaching_result IS NULL OR ISJSON(generated_coaching_result) = 1),
        CONSTRAINT ck_ai_mentor_delivery_edited_json CHECK (admin_edited_result IS NULL OR ISJSON(admin_edited_result) = 1)
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.ai_mentor_delivery_reviews') AND name=N'idx_ai_mentor_delivery_student_status')
    CREATE INDEX idx_ai_mentor_delivery_student_status ON dbo.ai_mentor_delivery_reviews(student_id,status,created_at DESC);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.ai_mentor_delivery_reviews') AND name=N'uq_ai_mentor_delivery_apply_reference')
    CREATE UNIQUE INDEX uq_ai_mentor_delivery_apply_reference ON dbo.ai_mentor_delivery_reviews(apply_reference) WHERE apply_reference IS NOT NULL;
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.student_points') AND name=N'uq_student_points_ai_mentor_delivery_source')
    CREATE UNIQUE INDEX uq_student_points_ai_mentor_delivery_source ON dbo.student_points(student_id,source_type,source_id)
        WHERE source_type=N'ai_mentor_delivery_review' AND source_id IS NOT NULL;

COMMIT TRANSACTION;
