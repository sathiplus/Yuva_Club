-- YUVA Club Phase 2A Security Foundation verification.
-- Run after database/02-phase-2a-security-foundation.sql in staging first.
-- This script reports validation state only. It does not modify data.

SELECT
  DB_NAME() AS database_name,
  SYSUTCDATETIME() AS checked_at_utc;

SELECT
  table_name,
  CASE WHEN OBJECT_ID(table_name, 'U') IS NULL THEN 'missing' ELSE 'present' END AS status
FROM (VALUES
  ('organizations'),
  ('user_roles'),
  ('organization_memberships')
) AS expected(table_name);

SELECT
  table_name,
  column_name,
  CASE WHEN COL_LENGTH(table_name, column_name) IS NULL THEN 'missing' ELSE 'present' END AS status
FROM (VALUES
  ('students', 'organization_id'),
  ('registrations', 'organization_id'),
  ('sessions', 'organization_id'),
  ('student_topic_selections', 'organization_id'),
  ('presentation_submissions', 'organization_id'),
  ('certificates', 'organization_id'),
  ('safety_reports', 'organization_id'),
  ('activity_logs', 'organization_id'),
  ('activity_logs', 'success')
) AS expected_columns(table_name, column_name);

SELECT
  'active_master_admin_role' AS check_name,
  COUNT(*) AS matching_rows
FROM user_roles ur
JOIN users u ON u.id = ur.user_id
WHERE LOWER(u.email) = 'admin@yuvaclub.app'
  AND ur.role_name = 'MasterAdmin'
  AND ur.status = 'active'
  AND ur.organization_id IS NULL;

SELECT
  i.name AS index_name,
  OBJECT_NAME(i.object_id) AS table_name
FROM sys.indexes i
WHERE i.name IN (
  'ux_user_roles_active',
  'idx_org_memberships_org_role',
  'idx_org_memberships_user',
  'idx_students_organization',
  'idx_registrations_organization',
  'idx_sessions_organization',
  'idx_topic_selections_organization',
  'idx_submissions_organization',
  'idx_certificates_organization',
  'idx_safety_reports_organization',
  'idx_activity_logs_org_action'
)
ORDER BY table_name, index_name;

