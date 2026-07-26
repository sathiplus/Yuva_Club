-- Backend Extension 1A: Organization Admin domain foundation.
-- Additive only. Existing students, registrations, sessions, and logs remain unassigned.

IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE parent_object_id = OBJECT_ID(N'dbo.users', N'U')
      AND LOWER([definition]) LIKE N'%organization_admin%'
)
BEGIN
    DECLARE @role_constraint_name SYSNAME;
    DECLARE role_constraint_cursor CURSOR LOCAL FAST_FORWARD FOR
        SELECT [name]
        FROM sys.check_constraints
        WHERE parent_object_id = OBJECT_ID(N'dbo.users', N'U')
          AND LOWER([definition]) LIKE N'%role%';

    OPEN role_constraint_cursor;
    FETCH NEXT FROM role_constraint_cursor INTO @role_constraint_name;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        EXEC (
            N'ALTER TABLE dbo.users DROP CONSTRAINT '
            + QUOTENAME(@role_constraint_name)
        );
        FETCH NEXT FROM role_constraint_cursor INTO @role_constraint_name;
    END;
    CLOSE role_constraint_cursor;
    DEALLOCATE role_constraint_cursor;

    ALTER TABLE dbo.users WITH CHECK
        ADD CONSTRAINT ck_users_role
        CHECK ([role] IN (
            N'student',
            N'parent',
            N'organization_admin',
            N'admin'
        ));
END;
GO

IF OBJECT_ID(N'dbo.organizations', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organizations (
        id BIGINT IDENTITY(1,1) NOT NULL,
        code NVARCHAR(60) NOT NULL,
        slug NVARCHAR(120) NOT NULL,
        [name] NVARCHAR(220) NOT NULL,
        organization_type NVARCHAR(30) NOT NULL,
        [status] NVARCHAR(20) NOT NULL
            CONSTRAINT df_organizations_status DEFAULT N'pending',
        contact_email NVARCHAR(190) NULL,
        timezone NVARCHAR(80) NOT NULL
            CONSTRAINT df_organizations_timezone DEFAULT N'UTC',
        created_by BIGINT NULL,
        created_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organizations_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organizations_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_organizations PRIMARY KEY (id),
        CONSTRAINT uq_organizations_code UNIQUE (code),
        CONSTRAINT uq_organizations_slug UNIQUE (slug),
        CONSTRAINT ck_organizations_type CHECK (
            organization_type IN (
                N'school',
                N'nonprofit',
                N'library',
                N'community',
                N'other'
            )
        ),
        CONSTRAINT ck_organizations_status CHECK (
            [status] IN (N'pending', N'active', N'suspended', N'disabled')
        ),
        CONSTRAINT fk_organizations_created_by
            FOREIGN KEY (created_by) REFERENCES dbo.users(id)
    );
END;
GO

IF OBJECT_ID(N'dbo.organization_memberships', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_memberships (
        id BIGINT IDENTITY(1,1) NOT NULL,
        organization_id BIGINT NOT NULL,
        user_id BIGINT NOT NULL,
        membership_role NVARCHAR(40) NOT NULL,
        [status] NVARCHAR(20) NOT NULL
            CONSTRAINT df_organization_memberships_status DEFAULT N'invited',
        invited_by BIGINT NULL,
        activated_at DATETIME2(7) NULL,
        revoked_at DATETIME2(7) NULL,
        created_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_memberships_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_memberships_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_organization_memberships PRIMARY KEY (id),
        CONSTRAINT uq_organization_memberships_org_user
            UNIQUE (organization_id, user_id),
        CONSTRAINT ck_organization_memberships_role CHECK (
            membership_role IN (N'organization_admin')
        ),
        CONSTRAINT ck_organization_memberships_status CHECK (
            [status] IN (N'invited', N'active', N'suspended', N'revoked')
        ),
        CONSTRAINT fk_organization_memberships_org
            FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id),
        CONSTRAINT fk_organization_memberships_user
            FOREIGN KEY (user_id) REFERENCES dbo.users(id),
        CONSTRAINT fk_organization_memberships_invited_by
            FOREIGN KEY (invited_by) REFERENCES dbo.users(id)
    );
END;
GO

IF OBJECT_ID(N'dbo.organization_students', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_students (
        id BIGINT IDENTITY(1,1) NOT NULL,
        organization_id BIGINT NOT NULL,
        student_id BIGINT NOT NULL,
        [status] NVARCHAR(20) NOT NULL
            CONSTRAINT df_organization_students_status DEFAULT N'pending',
        assigned_by BIGINT NULL,
        assigned_at DATETIME2(7) NULL,
        ended_at DATETIME2(7) NULL,
        created_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_students_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_students_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_organization_students PRIMARY KEY (id),
        CONSTRAINT ck_organization_students_status CHECK (
            [status] IN (N'pending', N'active', N'transferred', N'removed')
        ),
        CONSTRAINT fk_organization_students_org
            FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id),
        CONSTRAINT fk_organization_students_student
            FOREIGN KEY (student_id) REFERENCES dbo.students(id),
        CONSTRAINT fk_organization_students_assigned_by
            FOREIGN KEY (assigned_by) REFERENCES dbo.users(id)
    );
END;
GO

IF OBJECT_ID(N'dbo.organization_settings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_settings (
        organization_id BIGINT NOT NULL,
        display_name NVARCHAR(220) NULL,
        timezone NVARCHAR(80) NULL,
        contact_email NVARCHAR(190) NULL,
        updated_by BIGINT NULL,
        created_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_settings_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_settings_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_organization_settings PRIMARY KEY (organization_id),
        CONSTRAINT fk_organization_settings_org
            FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id),
        CONSTRAINT fk_organization_settings_updated_by
            FOREIGN KEY (updated_by) REFERENCES dbo.users(id)
    );
END;
GO

IF OBJECT_ID(N'dbo.organization_announcements', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.organization_announcements (
        id BIGINT IDENTITY(1,1) NOT NULL,
        organization_id BIGINT NOT NULL,
        title NVARCHAR(220) NOT NULL,
        body NVARCHAR(MAX) NOT NULL,
        [status] NVARCHAR(20) NOT NULL
            CONSTRAINT df_organization_announcements_status DEFAULT N'draft',
        published_at DATETIME2(7) NULL,
        created_by BIGINT NULL,
        updated_by BIGINT NULL,
        created_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_announcements_created DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(7) NOT NULL
            CONSTRAINT df_organization_announcements_updated DEFAULT SYSUTCDATETIME(),
        CONSTRAINT pk_organization_announcements PRIMARY KEY (id),
        CONSTRAINT ck_organization_announcements_status CHECK (
            [status] IN (N'draft', N'published', N'archived')
        ),
        CONSTRAINT fk_organization_announcements_org
            FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id),
        CONSTRAINT fk_organization_announcements_created_by
            FOREIGN KEY (created_by) REFERENCES dbo.users(id),
        CONSTRAINT fk_organization_announcements_updated_by
            FOREIGN KEY (updated_by) REFERENCES dbo.users(id)
    );
END;
GO

IF COL_LENGTH(N'dbo.registrations', N'organization_id') IS NULL
BEGIN
    ALTER TABLE dbo.registrations ADD organization_id BIGINT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE parent_object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND [name] = N'fk_registrations_organization'
)
BEGIN
    ALTER TABLE dbo.registrations WITH CHECK
        ADD CONSTRAINT fk_registrations_organization
        FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id);
END;
GO

IF COL_LENGTH(N'dbo.sessions', N'organization_id') IS NULL
BEGIN
    ALTER TABLE dbo.sessions ADD organization_id BIGINT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE parent_object_id = OBJECT_ID(N'dbo.sessions', N'U')
      AND [name] = N'fk_sessions_organization'
)
BEGIN
    ALTER TABLE dbo.sessions WITH CHECK
        ADD CONSTRAINT fk_sessions_organization
        FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id);
END;
GO

IF COL_LENGTH(N'dbo.activity_logs', N'organization_id') IS NULL
BEGIN
    ALTER TABLE dbo.activity_logs ADD organization_id BIGINT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE parent_object_id = OBJECT_ID(N'dbo.activity_logs', N'U')
      AND [name] = N'fk_activity_logs_organization'
)
BEGIN
    ALTER TABLE dbo.activity_logs WITH CHECK
        ADD CONSTRAINT fk_activity_logs_organization
        FOREIGN KEY (organization_id) REFERENCES dbo.organizations(id);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_memberships', N'U')
      AND [name] = N'idx_organization_memberships_user_status'
)
BEGIN
    CREATE INDEX idx_organization_memberships_user_status
        ON dbo.organization_memberships (user_id, [status], organization_id)
        INCLUDE (membership_role, activated_at, revoked_at);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_students', N'U')
      AND [name] = N'uq_organization_students_active_student'
)
BEGIN
    CREATE UNIQUE INDEX uq_organization_students_active_student
        ON dbo.organization_students (student_id)
        WHERE [status] = N'active';
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_students', N'U')
      AND [name] = N'idx_organization_students_org_status'
)
BEGIN
    CREATE INDEX idx_organization_students_org_status
        ON dbo.organization_students (organization_id, [status], student_id)
        INCLUDE (assigned_at, ended_at);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.organization_announcements', N'U')
      AND [name] = N'idx_organization_announcements_org_status'
)
BEGIN
    CREATE INDEX idx_organization_announcements_org_status
        ON dbo.organization_announcements (
            organization_id,
            [status],
            published_at DESC,
            id DESC
        );
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.registrations', N'U')
      AND [name] = N'idx_registrations_org_status'
)
BEGIN
    CREATE INDEX idx_registrations_org_status
        ON dbo.registrations (organization_id, [status], submitted_at DESC, id DESC)
        INCLUDE (student_id);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.sessions', N'U')
      AND [name] = N'idx_sessions_org_starts'
)
BEGIN
    CREATE INDEX idx_sessions_org_starts
        ON dbo.sessions (organization_id, starts_at DESC, [status], id DESC);
END;

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.activity_logs', N'U')
      AND [name] = N'idx_activity_logs_org_created'
)
BEGIN
    CREATE INDEX idx_activity_logs_org_created
        ON dbo.activity_logs (organization_id, created_at DESC, id DESC)
        INCLUDE (actor_user_id, action, entity_type, entity_id);
END;
GO
