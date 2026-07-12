-- YUVA Club Phase 2A Security Foundation migration.
-- Idempotent Azure SQL script. Review before running in production.

SET XACT_ABORT ON;

BEGIN TRY
BEGIN TRANSACTION;

IF OBJECT_ID('organizations', 'U') IS NULL
BEGIN
  CREATE TABLE organizations (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    organization_code NVARCHAR(80) NOT NULL UNIQUE,
    name NVARCHAR(180) NOT NULL,
    status NVARCHAR(30) NOT NULL CONSTRAINT df_organizations_status DEFAULT 'active',
    created_at DATETIME2 NOT NULL CONSTRAINT df_organizations_created DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_organizations_updated DEFAULT SYSUTCDATETIME()
  );
END;

IF OBJECT_ID('user_roles', 'U') IS NULL
BEGIN
  CREATE TABLE user_roles (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_name NVARCHAR(40) NOT NULL CHECK (role_name IN ('MasterAdmin','OrganizationAdmin','Counselor','Coach','Teacher','Moderator','Parent','Student')),
    organization_id BIGINT NULL REFERENCES organizations(id),
    status NVARCHAR(20) NOT NULL CONSTRAINT df_user_roles_status DEFAULT 'active',
    assigned_by BIGINT NULL REFERENCES users(id),
    assigned_at DATETIME2 NOT NULL CONSTRAINT df_user_roles_assigned DEFAULT SYSUTCDATETIME(),
    revoked_at DATETIME2 NULL
  );
  CREATE UNIQUE INDEX ux_user_roles_active
    ON user_roles(user_id, role_name, organization_id)
    WHERE status = 'active';
END;

IF OBJECT_ID('organization_memberships', 'U') IS NULL
BEGIN
  CREATE TABLE organization_memberships (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    student_id BIGINT NULL REFERENCES students(id),
    role_name NVARCHAR(40) NOT NULL,
    status NVARCHAR(20) NOT NULL CONSTRAINT df_org_memberships_status DEFAULT 'active',
    created_at DATETIME2 NOT NULL CONSTRAINT df_org_memberships_created DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_org_memberships_updated DEFAULT SYSUTCDATETIME()
  );
  CREATE INDEX idx_org_memberships_org_role ON organization_memberships(organization_id, role_name, status);
  CREATE INDEX idx_org_memberships_user ON organization_memberships(user_id, status);
END;

IF COL_LENGTH('students', 'organization_id') IS NULL
BEGIN
  ALTER TABLE students ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_students_organization ON students(organization_id, approval_status);
END;

IF COL_LENGTH('registrations', 'organization_id') IS NULL
BEGIN
  ALTER TABLE registrations ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_registrations_organization ON registrations(organization_id, status);
END;

IF COL_LENGTH('sessions', 'organization_id') IS NULL
BEGIN
  ALTER TABLE sessions ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_sessions_organization ON sessions(organization_id, status);
END;

IF COL_LENGTH('student_topic_selections', 'organization_id') IS NULL
BEGIN
  ALTER TABLE student_topic_selections ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_topic_selections_organization ON student_topic_selections(organization_id, status);
END;

IF COL_LENGTH('presentation_submissions', 'organization_id') IS NULL
BEGIN
  ALTER TABLE presentation_submissions ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_submissions_organization ON presentation_submissions(organization_id, status);
END;

IF COL_LENGTH('certificates', 'organization_id') IS NULL
BEGIN
  ALTER TABLE certificates ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_certificates_organization ON certificates(organization_id, status);
END;

IF COL_LENGTH('safety_reports', 'organization_id') IS NULL
BEGIN
  ALTER TABLE safety_reports ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_safety_reports_organization ON safety_reports(organization_id, status);
END;

IF COL_LENGTH('activity_logs', 'success') IS NULL
BEGIN
  ALTER TABLE activity_logs ADD success BIT NULL;
END;

IF COL_LENGTH('activity_logs', 'organization_id') IS NULL
BEGIN
  ALTER TABLE activity_logs ADD organization_id BIGINT NULL REFERENCES organizations(id);
  CREATE INDEX idx_activity_logs_org_action ON activity_logs(organization_id, action, created_at);
END;

IF NOT EXISTS (
  SELECT 1
  FROM users
  WHERE LOWER(email) = 'admin@yuvaclub.app'
)
BEGIN
  INSERT INTO users (email, password_hash, role, display_name, status, email_verified_at)
  VALUES ('admin@yuvaclub.app', NULL, 'admin', 'YUVA Club Master Admin', 'active', SYSUTCDATETIME());
END;

DECLARE @MasterAdminUserId BIGINT = (
  SELECT TOP 1 id FROM users WHERE LOWER(email) = 'admin@yuvaclub.app'
);

IF @MasterAdminUserId IS NOT NULL
AND NOT EXISTS (
  SELECT 1 FROM user_roles
  WHERE user_id = @MasterAdminUserId
    AND role_name = 'MasterAdmin'
    AND status = 'active'
)
BEGIN
  INSERT INTO user_roles (user_id, role_name, organization_id, status)
  VALUES (@MasterAdminUserId, 'MasterAdmin', NULL, 'active');
END;

COMMIT TRANSACTION;
END TRY
BEGIN CATCH
  IF @@TRANCOUNT > 0
    ROLLBACK TRANSACTION;

  THROW;
END CATCH;
