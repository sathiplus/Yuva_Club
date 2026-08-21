import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, css, fixture] = await Promise.all([
  read('portal.php'),
  read('assets/student-app.css'),
  read('tests/frontend/fixtures/profile-preview.html'),
]);

const profileStart = portal.indexOf('<section class="band app-section" id="app-profile"');
const profileEnd = portal.indexOf('<section class="band">', profileStart);
assert.ok(profileStart >= 0 && profileEnd > profileStart, 'Profile section boundaries exist');
const profile = portal.slice(profileStart, profileEnd);

for (const [needle, label] of [
  ['$studentId', 'YUVA ID'],
  ['$name', 'student display name'],
  ['$level', 'leadership level'],
  ['$membershipGroupLabel', 'current program'],
  ["$student['School']", 'school'],
  ["$record['presentations']", 'presentation count'],
  ["$record['service_hours']", 'volunteer hours'],
  ['$certificateReady', 'certificate state'],
  ['$badges', 'earned badges'],
  ['$profileParentConnected', 'parent connection state'],
  ['$points', 'approved points'],
  ['$challengeStage', 'leadership challenge state'],
]) {
  assert.ok(profile.includes(needle), `Profile preserves ${label} data binding`);
}

for (const state of [
  'Your YUVA Identity',
  'Your YUVA ID is permanent',
  'Your profile is still taking shape.',
  'No school recorded.',
  'No goals recorded.',
  'No certificate earned yet.',
  'No badge earned yet.',
  'No volunteer hours recorded',
  'Parent connection unavailable',
  'Google Login unavailable',
]) {
  assert.ok(profile.includes(state), `Profile includes honest state: ${state}`);
}

assert.equal(
  (profile.match(/class="button primary profile-primary-action"/g) || []).length,
  1,
  'Profile exposes exactly one primary action'
);
assert.ok(profile.includes('href="#app-progress"'), 'Primary action opens Leadership Journey');
assert.ok(profile.includes('href="#app-achievements"'), 'Achievements entry point is preserved');
assert.ok(
  profile.includes('href="forgot-password.php?account=student"'),
  'Existing password-help route is preserved'
);
assert.ok(profile.includes('href="portal-logout.php"'), 'Existing logout route is preserved');
assert.equal((profile.match(/<form\b/g) || []).length, 1, 'Profile exposes only the scoped public identity form');
assert.ok(profile.includes('action="student-public-identity.php"'), 'Profile identity form uses the authenticated update handler');
assert.ok(profile.includes('csrf_field()'), 'Profile identity form includes CSRF protection');
assert.ok(!/<textarea\b|<select\b|<input[^>]+type="file"/i.test(profile), 'Profile excludes unrelated or uploaded-photo editing');
assert.ok(!profile.includes('Organization Code'), 'Internal organization code is not exposed');
assert.ok(!profile.includes('<dt>Parent Email</dt>'), 'Parent email is not rendered');
assert.ok(!profile.includes('<dt>Parent/Guardian Name</dt>'), 'Parent name is not rendered');

for (const forbidden of [
  'fetch(',
  'XMLHttpRequest',
  'new WebSocket',
  'localStorage',
  'sessionStorage',
  '<input type="file"',
  'Google OAuth',
]) {
  assert.ok(!profile.includes(forbidden), `Profile excludes unsupported workflow: ${forbidden}`);
}

for (const contract of [
  '#app-profile',
  '.profile-summary-grid',
  '.profile-content-grid',
  '.profile-state-banner',
  '.profile-honest-empty',
  '.profile-connection-state',
  '.profile-security-list',
  '@media (max-width: 1080px)',
  '@media (max-width: 760px)',
  '@media (prefers-reduced-motion: reduce)',
]) {
  assert.ok(css.includes(contract), `Profile CSS includes ${contract}`);
}

for (const contract of [
  'aria-labelledby="profile-overview-title"',
  'role="status" aria-live="polite"',
  '<dl class="profile-detail-list">',
  '<dt>',
  '<dd>',
]) {
  assert.ok(profile.includes(contract), `Profile includes accessible contract: ${contract}`);
}

for (const label of ['Home', 'Practice', 'Present', 'Journey', 'Profile']) {
  assert.ok(fixture.includes(`>${label}<`), `Profile preview preserves ${label} navigation`);
}
assert.ok(fixture.includes('id="app-main" tabindex="-1"'), 'Preview preserves focusable main landmark');
assert.ok(fixture.includes('aria-current="page"'), 'Preview identifies current navigation');

process.stdout.write('PASS Student Profile identity and growth bindings\n');
process.stdout.write('PASS honest missing-data and unsupported-provider states\n');
process.stdout.write('PASS read-only privacy and account-security boundaries\n');
process.stdout.write('PASS responsive and accessibility contracts\n');
process.stdout.write('PASS Student Profile contract suite\n');
