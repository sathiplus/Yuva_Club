import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const admin = await readFile(new URL('admin.php', root), 'utf8');
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

assert.ok(admin.includes('Foundation only'));
assert.ok(admin.includes('Not configured'));
assert.ok(!admin.includes('organization-management.php'));
assert.ok(!admin.includes('organization-admin-actions.php'));
assert.ok(css.includes('@media (max-width: 900px)'));
assert.ok(css.includes('@media (prefers-reduced-motion: reduce)'));
assert.ok(css.includes('.master-skip-link:focus'));

console.log('PASS Master Admin routes and form actions preserved');
console.log('PASS Master Admin field names and security hooks preserved');
console.log('PASS system-wide navigation and honest foundation states');
console.log('PASS responsive and reduced-motion contracts');
