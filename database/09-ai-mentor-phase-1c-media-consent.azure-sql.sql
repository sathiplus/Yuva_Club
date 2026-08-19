SET NOCOUNT ON;
SET XACT_ABORT ON;

IF OBJECT_ID(N'dbo.ai_mentor_media_consents', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.ai_mentor_media_consents (
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_ai_mentor_media_consents PRIMARY KEY,
        consent_key UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_ai_mentor_media_consents_key DEFAULT NEWID(),
        student_id BIGINT NOT NULL,
        yuva_id NVARCHAR(40) NOT NULL,
        consent_version NVARCHAR(80) NOT NULL,
        actor_type NVARCHAR(12) NOT NULL,
        parent_id BIGINT NULL,
        parent_relationship NVARCHAR(80) NULL,
        status NVARCHAR(12) NOT NULL CONSTRAINT df_ai_mentor_media_consents_status DEFAULT N'Granted',
        consented_at DATETIME2(3) NOT NULL CONSTRAINT df_ai_mentor_media_consents_consented DEFAULT SYSUTCDATETIME(),
        withdrawn_at DATETIME2(3) NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT df_ai_mentor_media_consents_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(3) NOT NULL CONSTRAINT df_ai_mentor_media_consents_updated DEFAULT SYSUTCDATETIME(),
        row_version ROWVERSION NOT NULL,
        CONSTRAINT fk_ai_mentor_media_consents_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_ai_mentor_media_consents_parent FOREIGN KEY(parent_id) REFERENCES dbo.parents(id),
        CONSTRAINT ck_ai_mentor_media_consents_actor CHECK(actor_type IN(N'Student',N'Parent')),
        CONSTRAINT ck_ai_mentor_media_consents_status CHECK(status IN(N'Granted',N'Withdrawn')),
        CONSTRAINT ck_ai_mentor_media_consents_parent CHECK((actor_type=N'Student' AND parent_id IS NULL) OR (actor_type=N'Parent' AND parent_id IS NOT NULL))
    );
END;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.ai_mentor_media_consents') AND name=N'ux_ai_mentor_media_consents_key')
    CREATE UNIQUE INDEX ux_ai_mentor_media_consents_key ON dbo.ai_mentor_media_consents(consent_key);
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.ai_mentor_media_consents') AND name=N'ux_ai_mentor_media_consents_actor_version')
    CREATE UNIQUE INDEX ux_ai_mentor_media_consents_actor_version ON dbo.ai_mentor_media_consents(student_id,consent_version,actor_type,parent_id);
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.ai_mentor_media_consents') AND name=N'ix_ai_mentor_media_consents_yuva_status')
    CREATE INDEX ix_ai_mentor_media_consents_yuva_status ON dbo.ai_mentor_media_consents(yuva_id,consent_version,status);
