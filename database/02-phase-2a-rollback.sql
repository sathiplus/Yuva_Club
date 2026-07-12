-- YUVA Club Phase 2A Security Foundation rollback / compensating script.
-- Run only after a database backup and only if Phase 2A database changes must be removed.
-- This script refuses to remove organization tables if they contain data other than the seeded MasterAdmin role.

SET XACT_ABORT ON;

BEGIN TRY
BEGIN TRANSACTION;

IF OBJECT_ID('organization_memberships', 'U') IS NOT NULL
AND EXISTS (SELECT 1 FROM organization_memberships)
BEGIN
  THROW 51000, 'Rollback stopped: organization_memberships contains data.', 1;
END;

IF OBJECT_ID('organizations', 'U') IS NOT NULL
AND EXISTS (SELECT 1 FROM organizations)
BEGIN
  THROW 51000, 'Rollback stopped: organizations contains data.', 1;
END;

IF OBJECT_ID('user_roles', 'U') IS NOT NULL
AND EXISTS (
  SELECT 1
  FROM user_roles ur
  LEFT JOIN users u ON u.id = ur.user_id
  WHERE NOT (
    LOWER(u.email) = 'admin@yuvaclub.app'
    AND ur.role_name = 'MasterAdmin'
    AND ur.organization_id IS NULL
  )
)
BEGIN
  THROW 51000, 'Rollback stopped: user_roles contains non-seed data.', 1;
END;

IF OBJECT_ID('user_roles', 'U') IS NOT NULL
BEGIN
  DELETE ur
  FROM user_roles ur
  JOIN users u ON u.id = ur.user_id
  WHERE LOWER(u.email) = 'admin@yuvaclub.app'
    AND ur.role_name = 'MasterAdmin'
    AND ur.organization_id IS NULL;
END;

IF OBJECT_ID('organization_memberships', 'U') IS NOT NULL
  DROP TABLE organization_memberships;

IF OBJECT_ID('user_roles', 'U') IS NOT NULL
  DROP TABLE user_roles;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_activity_logs_org_action')
  DROP INDEX idx_activity_logs_org_action ON activity_logs;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_safety_reports_organization')
  DROP INDEX idx_safety_reports_organization ON safety_reports;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_certificates_organization')
  DROP INDEX idx_certificates_organization ON certificates;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_submissions_organization')
  DROP INDEX idx_submissions_organization ON presentation_submissions;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_topic_selections_organization')
  DROP INDEX idx_topic_selections_organization ON student_topic_selections;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_sessions_organization')
  DROP INDEX idx_sessions_organization ON sessions;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_registrations_organization')
  DROP INDEX idx_registrations_organization ON registrations;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_students_organization')
  DROP INDEX idx_students_organization ON students;

IF COL_LENGTH('activity_logs', 'organization_id') IS NOT NULL
  ALTER TABLE activity_logs DROP COLUMN organization_id;

IF COL_LENGTH('activity_logs', 'success') IS NOT NULL
  ALTER TABLE activity_logs DROP COLUMN success;

IF COL_LENGTH('safety_reports', 'organization_id') IS NOT NULL
  ALTER TABLE safety_reports DROP COLUMN organization_id;

IF COL_LENGTH('certificates', 'organization_id') IS NOT NULL
  ALTER TABLE certificates DROP COLUMN organization_id;

IF COL_LENGTH('presentation_submissions', 'organization_id') IS NOT NULL
  ALTER TABLE presentation_submissions DROP COLUMN organization_id;

IF COL_LENGTH('student_topic_selections', 'organization_id') IS NOT NULL
  ALTER TABLE student_topic_selections DROP COLUMN organization_id;

IF COL_LENGTH('sessions', 'organization_id') IS NOT NULL
  ALTER TABLE sessions DROP COLUMN organization_id;

IF COL_LENGTH('registrations', 'organization_id') IS NOT NULL
  ALTER TABLE registrations DROP COLUMN organization_id;

IF COL_LENGTH('students', 'organization_id') IS NOT NULL
  ALTER TABLE students DROP COLUMN organization_id;

IF OBJECT_ID('organizations', 'U') IS NOT NULL
  DROP TABLE organizations;

COMMIT TRANSACTION;
END TRY
BEGIN CATCH
  IF @@TRANCOUNT > 0
    ROLLBACK TRANSACTION;

  THROW;
END CATCH;

