-- YUVA Club Phase 2A Security Foundation pre-check.
-- Run before database/02-phase-2a-security-foundation.sql.
-- This script reports current schema/data state only. It does not modify data.

SELECT
  DB_NAME() AS database_name,
  SYSUTCDATETIME() AS checked_at_utc;

SELECT
  required_table,
  CASE WHEN OBJECT_ID(required_table, 'U') IS NULL THEN 'missing' ELSE 'present' END AS status
FROM (VALUES
  ('users'),
  ('students'),
  ('registrations'),
  ('sessions'),
  ('student_topic_selections'),
  ('presentation_submissions'),
  ('certificates'),
  ('safety_reports'),
  ('activity_logs')
) AS required(required_table);

SELECT
  optional_table,
  CASE WHEN OBJECT_ID(optional_table, 'U') IS NULL THEN 'will_create' ELSE 'already_present' END AS status
FROM (VALUES
  ('organizations'),
  ('user_roles'),
  ('organization_memberships')
) AS optional(optional_table);

SELECT
  table_name,
  column_name,
  CASE WHEN COL_LENGTH(table_name, column_name) IS NULL THEN 'will_add' ELSE 'already_present' END AS status
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
) AS columns(table_name, column_name);

SELECT
  'master_admin_user' AS check_name,
  COUNT(*) AS matching_rows
FROM users
WHERE LOWER(email) = 'admin@yuvaclub.app';

SELECT
  'duplicate_user_emails' AS check_name,
  LOWER(email) AS email,
  COUNT(*) AS duplicate_count
FROM users
GROUP BY LOWER(email)
HAVING COUNT(*) > 1;

SELECT
  'students_without_user' AS check_name,
  COUNT(*) AS rows_count
FROM students
WHERE user_id IS NULL;

SELECT
  'registrations_without_student' AS check_name,
  COUNT(*) AS rows_count
FROM registrations
WHERE student_id IS NULL;

