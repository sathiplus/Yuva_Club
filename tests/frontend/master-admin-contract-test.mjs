import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const admin = await readFile(new URL('admin.php', root), 'utf8');
const organizationAdmin = await readFile(new URL('organization-admin.php', root), 'utf8');
const organizationApproval = await readFile(new URL('admin-organization-request-actions.php', root), 'utf8');
const portalLib = await readFile(new URL('portal-lib.php', root), 'utf8');
const css = await readFile(new URL('assets/master-admin.css', root), 'utf8');

const preservedActions = [
  'admin-registration-approve.php',
  'admin-password-actions.php',
  'admin-hub-actions.php',
  'admin-bulk-session-actions.php',
  'admin-meeting-actions.php',
  'admin-ai-review.php',
  'admin-ai-apply.php',
  'admin-actions.php',
];

for (const action of preservedActions) {
  assert.ok(admin.includes(`action="${action}"`), `Missing preserved action: ${action}`);
}

for (const name of [
  'registration_id',
  'current_email',
  'current_password',
  'new_email',
  'new_password',
  'selected_students[]',
  'student_id',
  'approved',
  'certificate_status',
]) {
  assert.ok(admin.includes(`name="${name}"`), `Missing preserved field: ${name}`);
}

assert.ok(admin.includes('require_admin();'), 'Master Admin authorization changed');
assert.ok(admin.includes('csrf_field()'), 'Existing CSRF integration is missing');
assert.ok(admin.includes("portal_header('Master Admin', false, ['assets/master-admin.css?v=1'])"));
assert.ok(portalLib.includes('array $localStylesheets = []'), 'Optional local stylesheet contract missing');

for (const section of [
  'organizations',
  'organization-admins',
  'students',
  'parents',
  'sql-registrations',
  'certificates',
  'reports',
  'settings',
  'system-health',
]) {
  assert.ok(admin.includes(`id="${section}"`), `Missing Master Admin section: ${section}`);
}

assert.ok(admin.includes('System-wide control center'), 'Master Admin must retain global oversight');
assert.ok(admin.includes('require_admin();'), 'Master Admin must retain the default Master Admin role boundary');
assert.ok(organizationAdmin.includes('require_admin([YUVA_ROLE_ORGANIZATION_ADMIN])'), 'Organization Admin must use its separate role');
assert.ok(organizationAdmin.includes('student_organization_id($student) === $organizationId'), 'Organization Admin students must remain organization-scoped');
assert.ok(organizationAdmin.includes('student_organization_id(find_student'), 'Organization Admin reports must remain organization-scoped');
assert.ok(organizationAdmin.includes('$organizationId === YUVA_PLATFORM_ORGANIZATION_ID'), 'Organization Admin must not receive platform-wide scope');
assert.ok(organizationApproval.includes('require_admin_post([YUVA_ROLE_MASTER_ADMIN])'), 'Only Master Admin may approve organization access');
assert.ok(organizationApproval.includes("'pending_invitation'"), 'Approval must not grant active access before activation');
assert.ok(!admin.includes('organization-management.php'));
assert.ok(!admin.includes('organization-admin-actions.php'));
assert.ok(css.includes('@media (max-width: 900px)'));
assert.ok(css.includes('@media (prefers-reduced-motion: reduce)'));
assert.ok(css.includes('.master-skip-link:focus'));

console.log('PASS Master Admin routes and form actions preserved');
console.log('PASS Master Admin field names and security hooks preserved');
console.log('PASS distinct global Master Admin and organization-scoped Admin boundaries');
console.log('PASS responsive and reduced-motion contracts');
