-- Phase A identity and registration-approval schema additions.
-- This migration is additive, idempotent, and does not initialize counters.

IF COL_LENGTH(N'dbo.registrations', N'reserved_yuva_id') IS NULL
BEGIN
    ALTER TABLE dbo.registrations
        ADD reserved_yuva_id NVARCHAR(40) NULL;
END;

IF COL_LENGTH(N'dbo.registrations', N'approval_error_code') IS NULL
BEGIN
    ALTER TABLE dbo.registrations
        ADD approval_error_code NVARCHAR(80) NULL;
END;

IF COL_LENGTH(N'dbo.registrations', N'approval_attempted_at') IS NULL
BEGIN
    ALTER TABLE dbo.registrations
        ADD approval_attempted_at DATETIME2 NULL;
END;
GO

IF OBJECT_ID(N'dbo.yuva_id_counters', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.yuva_id_counters (
        [year] SMALLINT NOT NULL,
        last_number INT NOT NULL
            CONSTRAINT df_yuva_id_counters_last_number DEFAULT 0,
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_yuva_id_counters_updated_at DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_yuva_id_counters PRIMARY KEY ([year]),
        CONSTRAINT ck_yuva_id_counters_year CHECK ([year] BETWEEN 2000 AND 9999),
        CONSTRAINT ck_yuva_id_counters_last_number CHECK (last_number >= 0)
    );
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND name = N'uq_registrations_reserved_yuva_id'
)
BEGIN
    CREATE UNIQUE INDEX uq_registrations_reserved_yuva_id
        ON dbo.registrations (reserved_yuva_id)
        WHERE reserved_yuva_id IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND name = N'idx_registrations_status_submitted'
)
BEGIN
    CREATE INDEX idx_registrations_status_submitted
        ON dbo.registrations (status, submitted_at DESC, id DESC);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND name = N'idx_registrations_parent_approval_lookup'
)
BEGIN
    CREATE INDEX idx_registrations_parent_approval_lookup
        ON dbo.registrations (parent_email, status, submitted_at DESC)
        INCLUDE (id, student_id);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND name = N'idx_registrations_student_portal_lookup'
)
BEGIN
    CREATE INDEX idx_registrations_student_portal_lookup
        ON dbo.registrations (
            student_id,
            reviewed_at DESC,
            submitted_at DESC,
            id DESC
        )
        INCLUDE (status);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.students', N'U')
      AND name = N'idx_students_yuva_id_lookup'
)
BEGIN
    CREATE INDEX idx_students_yuva_id_lookup
        ON dbo.students (yuva_id)
        INCLUDE (id, user_id, approval_status);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.student_parents', N'U')
      AND name = N'idx_student_parents_primary_lookup'
)
BEGIN
    CREATE INDEX idx_student_parents_primary_lookup
        ON dbo.student_parents (student_id, is_primary DESC, created_at, parent_id)
        INCLUDE (consent_status);
END;
