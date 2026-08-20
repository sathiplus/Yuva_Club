import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [parent, css, fixture, portalLib] = await Promise.all([
  read('parent.php'),
  read('assets/parent-experience.css'),
  read('tests/frontend/fixtures/parent-experience-preview.html'),
  read('portal-lib.php'),
]);
const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, text, message) =>
  assert.ok(source.includes(text), `${message}: missing ${JSON.stringify(text)}`);

includes(parent, '$student = require_parent_student();', 'parent authorization guard');
includes(parent, "$_SESSION['parent_student_id']", 'parent child-context session');
includes(portalLib, 'function require_parent_student()', 'protected parent access helper');
pass('parent authorization and child-context contracts');

for (const binding of [
  '$student',
  '$studentId',
  '$selection',
  '$research',
  '$record',
  '$hub',
  '$badges',
  '$points',
  '$tokens',
  '$rewardLevel',
  '$rank',
  '$eligibleRank',
  '$challengeStage',
  '$rubricScore',
  "$record['attendance']",
  "$record['presentations']",
  "$record['service_hours']",
  "$record['ai_feedback_summary']",
  "$record['communication_skills']",
  "$record['leadership_milestones']",
  "$record['teacher_feedback']",
  "$record['mentor_feedback']",
  "$hub['announcements']",
  "$hub['recordings']",
]) includes(parent, binding, `${binding} binding`);
pass('existing student growth, guidance, recording, and announcement data');

for (const route of [
  'href="portal-logout.php"',
  'href="leaderboard.php"',
  'certificate.php?id=',
]) includes(parent, route, `${route} route`);
includes(parent, 'parse_link_lines($hub[\'recordings\'])', 'recording link parser');
includes(parent, 'rel="noopener"', 'external-link protection');
assert.equal((parent.match(/<form\b/g)||[]).length,1,'only the approved media-consent form is added');
includes(parent,'action="parent-media-consent.php"','media consent route');
assert.ok(!/<select\b|<textarea\b/i.test(parent),'parent consent adds no unrelated fields');
pass('preserved routes and scoped media-consent workflow');

for (const landmark of [
  'id="parent-main"',
  'aria-label="Parent portal sections"',
  'aria-labelledby="parent-overview-title"',
  'aria-labelledby="parent-growth-title"',
  'aria-labelledby="parent-presentations-title"',
  'aria-labelledby="parent-mentor-title"',
  'aria-labelledby="parent-account-title"',
]) includes(parent, landmark, `${landmark} accessibility contract`);
includes(css, ':focus-visible', 'focus-visible treatment');
includes(css, 'prefers-reduced-motion: reduce', 'reduced-motion treatment');
pass('landmarks, accessible names, focus, and reduced motion');

for (const component of [
  '.parent-hero',
  '.parent-growth-grid',
  '.parent-metric',
  '.parent-progress-card',
  '.parent-two-grid',
  '.parent-mentor-grid',
  '.parent-recognition-grid',
  '.parent-empty',
  '.parent-account-section',
]) includes(css, component, `${component} component`);
includes(css, '@media (max-width: 680px)', 'mobile breakpoint');
includes(fixture, 'assets/yuva-symbol.png', 'approved small brand symbol');
includes(fixture, 'assets/logo.png', 'approved large brand');
pass('Design System V1 components, branding, and responsive behavior');

for (const forbidden of ['fetch(', 'XMLHttpRequest', 'new WebSocket', 'localStorage', 'sessionStorage']) {
  assert.ok(!parent.includes(forbidden), `Parent Experience excludes ${forbidden}`);
}
pass('no new API, storage, or fabricated client data');

process.stdout.write('PASS Parent Experience frontend contract suite\n');
