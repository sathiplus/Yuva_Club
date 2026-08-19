SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF OBJECT_ID(N'dbo.ai_mentor_reviews', N'U') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_document_warnings_json', N'C') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP CONSTRAINT ck_ai_mentor_reviews_document_warnings_json;
    IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_file_sha256', N'C') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP CONSTRAINT ck_ai_mentor_reviews_file_sha256;
    IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_file_size', N'C') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP CONSTRAINT ck_ai_mentor_reviews_file_size;
    IF OBJECT_ID(N'dbo.ck_ai_mentor_reviews_document_status', N'C') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP CONSTRAINT ck_ai_mentor_reviews_document_status;
    IF OBJECT_ID(N'dbo.df_ai_mentor_reviews_document_status', N'D') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP CONSTRAINT df_ai_mentor_reviews_document_status;

    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'document_analysis_warnings') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN document_analysis_warnings;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'document_analysis_status') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN document_analysis_status;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_sha256') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN source_file_sha256;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_size_bytes') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN source_file_size_bytes;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_mime_type') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN source_file_mime_type;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_original_name') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN source_file_original_name;
    IF COL_LENGTH(N'dbo.ai_mentor_reviews', N'source_file_reference') IS NOT NULL
        ALTER TABLE dbo.ai_mentor_reviews DROP COLUMN source_file_reference;
END;

COMMIT TRANSACTION;
