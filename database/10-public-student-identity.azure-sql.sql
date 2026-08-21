SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF COL_LENGTH(N'dbo.students', N'public_handle') IS NULL
    ALTER TABLE dbo.students ADD public_handle NVARCHAR(24) NULL;
IF COL_LENGTH(N'dbo.students', N'public_handle_normalized') IS NULL
    ALTER TABLE dbo.students ADD public_handle_normalized NVARCHAR(24) NULL;
IF COL_LENGTH(N'dbo.students', N'avatar_code') IS NULL
    ALTER TABLE dbo.students ADD avatar_code NVARCHAR(40) NOT NULL CONSTRAINT df_students_avatar_code DEFAULT N'explorer_rocket' WITH VALUES;
IF COL_LENGTH(N'dbo.students', N'handle_changed_at') IS NULL
    ALTER TABLE dbo.students ADD handle_changed_at DATETIME2(3) NULL;

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.students') AND name=N'ux_students_public_handle_normalized')
    CREATE UNIQUE INDEX ux_students_public_handle_normalized ON dbo.students(public_handle_normalized) WHERE public_handle_normalized IS NOT NULL;

IF OBJECT_ID(N'dbo.student_public_identity_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.student_public_identity_history(
        id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT pk_student_public_identity_history PRIMARY KEY,
        student_id BIGINT NOT NULL,
        previous_handle NVARCHAR(24) NULL,
        new_handle NVARCHAR(24) NULL,
        previous_avatar_code NVARCHAR(40) NULL,
        new_avatar_code NVARCHAR(40) NULL,
        change_type NVARCHAR(30) NOT NULL,
        actor_type NVARCHAR(20) NOT NULL,
        actor_user_id BIGINT NULL,
        reason NVARCHAR(500) NULL,
        changed_at DATETIME2(3) NOT NULL CONSTRAINT df_student_public_identity_history_changed DEFAULT SYSUTCDATETIME(),
        CONSTRAINT fk_student_public_identity_history_student FOREIGN KEY(student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_student_public_identity_history_actor FOREIGN KEY(actor_user_id) REFERENCES dbo.users(id),
        CONSTRAINT ck_student_public_identity_history_change CHECK(change_type IN(N'StudentUpdate',N'ModerationOverride')),
        CONSTRAINT ck_student_public_identity_history_actor CHECK(actor_type IN(N'Student',N'MasterAdmin'))
    );
END;
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID(N'dbo.student_public_identity_history') AND name=N'ix_student_public_identity_history_student')
    CREATE INDEX ix_student_public_identity_history_student ON dbo.student_public_identity_history(student_id,changed_at DESC,id DESC);

COMMIT TRANSACTION;
