import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, library, css, fixture] = await Promise.all([
  read('portal.php'),
  read('portal-lib.php'),
  read('assets/student-app.css'),
  read('tests/frontend/fixtures/settings-preview.html'),
]);

const start = portal.indexOf('<section class="band app-section" id="app-settings"');
const end = portal.indexOf('<section class="band">', start);
assert.ok(start >= 0 && end > start, 'Student Settings section boundaries exist');
const settings = portal.slice(start, end);

for (const [needle, label] of [
  ['$name', 'student display name'],
  ['$studentId', 'YUVA ID'],
  ['$membershipGroupLabel', 'program'],
  ["$student['Student Email']", 'student recovery identity'],
]) {
  assert.ok(settings.includes(needle), `Settings use existing ${label}`);
}
assert.ok(settings.includes('Authenticated student account'), 'Settings describe authentication without reading session internals');
assert.ok(!settings.includes('$_SESSION'), 'Settings remain isolated from authentication session wiring');

assert.equal(
  (settings.match(/class="button primary settings-primary-action"/g) || []).length,
  1,
  'Settings expose exactly one primary action'
);
for (const [route, label] of [
  ['forgot-password.php?account=student', 'password recovery'],
  ['portal-logout.php', 'logout'],
  ['privacy.html', 'privacy'],
  ['terms.html', 'terms'],
  ['safety.html', 'safety and accessibility'],
  ['contact.html', 'support'],
  ['#safety-report', 'authenticated issue reporting'],
  ['#app-notifications', 'student notifications'],
]) {
  assert.ok(settings.includes(`href="${route}"`), `Settings preserve ${label} route`);
}

for (const state of [
  'Notification preferences unavailable',
  'Google and other identity-provider linking are not implemented.',
  'Theme switching and language selection are not configurable.',
  'Per-channel notification controls are not available.',
  'There is no self-service workflow.',
]) {
  assert.ok(settings.includes(state), `Settings include honest unavailable state: ${state}`);
}

assert.ok(!/<form\b|<input\b|<select\b|<textarea\b/i.test(settings), 'Settings remain read-only');
for (const forbidden of [
  'billing',
  'subscription',
  'Premium',
  'Parent Email',
  'Parent/Guardian Name',
  'Organization Code',
  'fetch(',
  'localStorage',
  'sessionStorage',
]) {
  assert.ok(!settings.includes(forbidden), `Settings exclude unsupported or private behavior: ${forbidden}`);
}

for (const contract of [
  '#app-settings',
  '.settings-hero',
  '.settings-primary-panel',
  '.settings-content-grid',
  '.settings-detail-list',
  '.settings-availability',
  '.settings-link-list',
  '.settings-unavailable-grid',
  '@media (max-width: 960px)',
  '@media (max-width: 760px)',
  '@media (prefers-reduced-motion: reduce)',
]) {
  assert.ok(css.includes(contract), `Settings CSS includes ${contract}`);
}

assert.ok(library.includes("'settings' =>"), 'Shared student icon system includes settings');
assert.ok(portal.includes('href="#app-settings">Open settings</a>'), 'Profile provides Settings entry point');
assert.ok(settings.includes('aria-labelledby="settings-title"'), 'Settings section has an accessible name');
assert.ok(settings.includes('aria-label="Student privacy and policy links"'), 'Policy navigation has an accessible name');
assert.ok(fixture.includes('id="app-main" tabindex="-1"'), 'Preview preserves focusable main landmark');
for (const label of ['Home', 'Practice', 'Present', 'Journey', 'Profile']) {
  assert.ok(fixture.includes(`>${label}<`), `Settings preview preserves ${label} navigation`);
}

process.stdout.write('PASS supported account, security, legal, accessibility, and support routes\n');
process.stdout.write('PASS honest unavailable settings and no-fabrication boundaries\n');
process.stdout.write('PASS responsive and accessibility contracts\n');
process.stdout.write('PASS Student Settings contract suite\n');
