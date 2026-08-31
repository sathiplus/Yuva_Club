import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath, encoding = 'utf8') =>
  readFile(path.join(root, relativePath), encoding);

const [
  portal,
  portalLib,
  studentCss,
  studentJs,
  manifest,
  serviceWorker,
  parentPortal,
  adminPortal,
  officialSymbol,
] = await Promise.all([
  read('portal.php'),
  read('portal-lib.php'),
  read('assets/student-app.css'),
  read('assets/student-app.js'),
  read('manifest.webmanifest'),
  read('service-worker.js'),
  read('parent.php'),
  read('admin.php'),
  read('assets/yuva-symbol.png', null),
]);

const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, text, message) => {
  assert.ok(source.includes(text), `${message}: missing ${JSON.stringify(text)}`);
};

includes(portal, '$student = require_student();', 'student authorization guard');
includes(portal, '$studentId = $student[\'Yuva Club ID\'];', 'student identity binding');
pass('student authorization and identity bindings');

for (const contract of [
  ['action="portal-submit-topic.php"', 'topic form action'],
  ['name="topic_category"', 'topic category field'],
  ['name="topic_title"', 'topic title field'],
  ['name="presentation_date"', 'presentation date field'],
  ['name="presentation_time"', 'presentation time field'],
  ['action="portal-submit-research.php"', 'research form action'],
  ['enctype="multipart/form-data"', 'research multipart encoding'],
  ['name="research_notes"', 'research notes field'],
  ['name="sources_used"', 'research sources field'],
  ['name="presentation_outline"', 'presentation outline field'],
  ['name="prepared_questions"', 'prepared questions field'],
  ['name="research_file"', 'research upload field'],
  ['action="portal-report-issue.php"', 'safety form action'],
  ['name="report_type"', 'safety report type'],
  ['name="report_message"', 'safety report message'],
  ['portal-download.php?id=', 'research download route'],
  ['certificate.php?id=', 'certificate route'],
  ['leaderboard.php', 'leaderboard route'],
  ['portal-logout.php', 'student logout route'],
]) {
  includes(portal, contract[0], contract[1]);
}
assert.ok(
  portal.split('<?php echo csrf_field(); ?>').length - 1 >= 3,
  'all three student POST forms must retain CSRF fields',
);
pass('forms, fields, CSRF, upload, download, and route contracts');

for (const [id, section] of [
  ['app-home', 'home'],
  ['app-practice', 'practice'],
  ['app-present', 'present'],
  ['app-progress', 'progress'],
  ['app-profile', 'profile'],
]) {
  includes(portal, `id="${id}"`, `${id} hash destination`);
  includes(portalLib, `portal.php#${id}`, `${id} navigation route`);
  includes(portal, `data-app-section="${section}"`, `${id} navigation section`);
}
includes(studentJs, '[data-app-nav]', 'navigation hook');
includes(studentJs, '[data-app-section]', 'section-observer hook');
pass('hash navigation, focus, and JavaScript hooks');

includes(portal, 'id="app-ai-coach"', 'AI Coach backend-compatible ID');
includes(portal, '$aiReviewApproved', 'AI review authorization binding');
includes(portal, 'AI Mentor', 'AI Mentor user-facing label');
assert.ok(!portal.includes('id="app-ai-mentor"'), 'AI Coach ID must not be renamed');
pass('AI Mentor remains a display label over authorized AI Coach data');

for (const token of [
  '--yuva-blue',
  '--yuva-navy',
  '--yuva-orange',
  '--yuva-green',
  '--yuva-purple',
  '--yuva-gold',
  '--surface-primary',
  '--text-primary',
  '--border-soft',
  '--shadow-soft',
  '--shadow-elevated',
]) {
  includes(studentCss, token, `${token} design token`);
}
includes(studentCss, ':focus-visible', 'visible focus styling');
includes(studentCss, 'prefers-reduced-motion', 'reduced-motion support');
pass('design tokens, focus visibility, and reduced-motion contracts');

assert.ok(officialSymbol.length > 100_000, 'official center symbol must be a substantive PNG');
assert.deepEqual(
  [...officialSymbol.subarray(0, 8)],
  [137, 80, 78, 71, 13, 10, 26, 10],
  'official center symbol must be a PNG',
);
includes(portalLib, 'assets/yuva-symbol.png', 'small student-shell branding');
includes(portalLib, '<aside class="student-app-rail"><a class="student-app-rail-brand" href="portal.php#app-home"><img src="assets/logo.png"', 'full desktop-rail branding');
assert.ok(!manifest.includes('yuva-symbol.png'), 'manifest icons remain unchanged');
assert.ok(!serviceWorker.includes('yuva-symbol.png'), 'PWA cache remains unchanged');
pass('approved full-logo and center-symbol standards');

assert.ok(
  !/@import\s+url\(\s*["']?https?:\/\//i.test(studentCss),
  'student CSS must not import an external font or stylesheet',
);
assert.ok(
  !/<script[^>]+src=["']https?:\/\//i.test(portalLib),
  'student shell must not load an external script',
);
assert.ok(
  !/<link[^>]+(?:stylesheet|preconnect)[^>]+href=["']https?:\/\//i.test(portalLib),
  'student shell must not load an external stylesheet or font connection',
);
pass('no external runtime frontend dependencies');

includes(parentPortal, '$parentContext = require_authenticated_parent();', 'authoritative SQL Parent portal guard');
includes(adminPortal, 'require_admin();', 'admin portal guard');
pass('parent and admin protected-page contracts remain present');

process.stdout.write('PASS Phase 1 Student Home frontend contract suite\n');
