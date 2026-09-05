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
  read('tests/frontend/fixtures/notifications-preview.html'),
]);

const start = portal.indexOf('<section class="band app-section" id="app-notifications"');
const end = portal.indexOf('<section class="band app-section" id="app-progress"', start);
assert.ok(start >= 0 && end > start, 'Student Notifications section boundaries exist');
const notifications = portal.slice(start, end);

for (const [needle, label] of [
  ['$studentAnnouncements', 'approved hub announcements'],
  ['$notificationSessionDate', 'existing session date'],
  ['$notificationSessionTitle', 'existing session title'],
  ["$aiReviewState === 'approved'", 'approved AI review state'],
  ["$aiReviewState === 'awaiting-approval'", 'pending AI review state'],
  ['$submissionPresentation', 'existing submission state'],
  ['$certificateReady', 'approved certificate availability'],
]) {
  assert.ok(portal.includes(needle), `Notifications use ${label}`);
}

for (const route of ['#app-present', '#research-submission', '#app-achievements', '#announcements']) {
  assert.ok(portal.includes(`'href' => '${route}'`), `Notification action preserves ${route}`);
}
assert.ok(portal.includes("'href' => 'portal.php?view=ai-mentor#app-ai-coach'"), 'AI Mentor notification preserves its anchor and records the beta view');

assert.ok(portal.includes('href="#app-notifications" aria-label="Open student notifications"'), 'Home bell opens Notifications');
assert.ok(notifications.includes('role="status" aria-live="polite"'), 'Notification count is announced accessibly');
assert.ok(notifications.includes('Release 1.0 does not track unread status.'), 'Unread state is not fabricated');
assert.ok(notifications.includes('No current notifications'), 'Honest empty state is present');
assert.ok(notifications.includes('Private to your student account'), 'Privacy boundary is visible');
assert.ok(!/<form\b|<input\b|<button\b/i.test(notifications), 'Notifications remain read-only');

for (const forbidden of [
  'mark as read',
  'Mark all read',
  'notification preference',
  'push notification',
  'Parent Email',
  'Parent/Guardian Name',
  'Organization Code',
  'fetch(',
  'localStorage',
  'sessionStorage',
]) {
  assert.ok(!notifications.includes(forbidden), `Notifications exclude unsupported or private behavior: ${forbidden}`);
}

for (const contract of [
  '#app-notifications',
  '.notifications-hero',
  '.notifications-summary',
  '.notifications-list',
  '.notification-card',
  '.notifications-empty',
  '.notifications-privacy-note',
  '@media (max-width: 820px)',
  '@media (max-width: 560px)',
  '@media (prefers-reduced-motion: reduce)',
]) {
  assert.ok(css.includes(contract), `Notifications CSS includes ${contract}`);
}

assert.ok(library.includes("'bell' =>"), 'Shared student icon system includes bell');
assert.ok(fixture.includes('id="app-main" tabindex="-1"'), 'Preview preserves focusable main landmark');
assert.ok(fixture.includes('aria-labelledby="notifications-title"'), 'Preview labels the Notifications section');
for (const label of ['Home', 'Practice', 'Present', 'Journey', 'Profile']) {
  assert.ok(fixture.includes(`>${label}<`), `Notifications preview preserves ${label} navigation`);
}

process.stdout.write('PASS truthful notification data bindings\n');
process.stdout.write('PASS read-only privacy and no-fabrication boundaries\n');
process.stdout.write('PASS responsive and accessibility contracts\n');
process.stdout.write('PASS Student Notifications contract suite\n');
