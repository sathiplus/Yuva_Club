SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.ai_mentor_reviews', N'U') IS NULL
    THROW 51007, 'Migration 07 requires Migration 06 ai_mentor_reviews.', 1;

IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_reference') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD source_file_reference NVARCHAR(500) NULL;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_original_name') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD source_file_original_name NVARCHAR(255) NULL;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_mime_type') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD source_file_mime_type NVARCHAR(160) NULL;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_size_bytes') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD source_file_size_bytes BIGINT NULL;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_sha256') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD source_file_sha256 CHAR(64) NULL;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'document_analysis_status') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD document_analysis_status NVARCHAR(30) NOT NULL
        CONSTRAINT df_ai_mentor_reviews_document_status DEFAULT N'NotApplicable' WITH VALUES;
IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'document_analysis_warnings') IS NULL
    ALTER TABLE dbo.ai_mentor_reviews ADD document_analysis_warnings NVARCHAR(MAX) NULL;

IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_document_status', N'C') IS NULL
    EXEC(N'ALTER TABLE dbo.ai_mentor_reviews ADD CONSTRAINT ck_ai_mentor_reviews_document_status
        CHECK (document_analysis_status IN (N''NotApplicable'', N''Pending'', N''Analyzed'', N''Failed''))');
IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_file_size', N'C') IS NULL
    EXEC(N'ALTER TABLE dbo.ai_mentor_reviews ADD CONSTRAINT ck_ai_mentor_reviews_file_size
        CHECK (source_file_size_bytes IS NULL OR (source_file_size_bytes > 0 AND source_file_size_bytes <= 10485760))');
IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_file_sha256', N'C') IS NULL
    EXEC(N'ALTER TABLE dbo.ai_mentor_reviews ADD CONSTRAINT ck_ai_mentor_reviews_file_sha256
        CHECK (source_file_sha256 IS NULL OR (LEN(source_file_sha256) = 64 AND source_file_sha256 NOT LIKE ''%[^0-9A-Fa-f]%''))');
IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_document_warnings_json', N'C') IS NULL
    EXEC(N'ALTER TABLE dbo.ai_mentor_reviews ADD CONSTRAINT ck_ai_mentor_reviews_document_warnings_json
        CHECK (document_analysis_warnings IS NULL OR ISJSON(document_analysis_warnings) = 1)');

COMMIT TRANSACTION;
