import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, portalLib, css, studentJs] = await Promise.all([
  read('portal.php'),
  read('portal-lib.php'),
  read('assets/student-app.css'),
  read('assets/student-app.js'),
]);

const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, text, message) =>
  assert.ok(source.includes(text), `${message}: missing ${JSON.stringify(text)}`);

const start = portal.indexOf('<section class="band app-section" id="app-progress"');
const end = portal.indexOf('<section class="band app-section" id="app-achievements"', start);
assert.ok(start >= 0 && end > start, 'Leadership Journey section boundaries exist');
const journey = portal.slice(start, end);

includes(journey, 'id="app-progress"', 'preserved Progress section ID');
includes(journey, 'data-app-section="progress"', 'preserved Progress navigation key');
includes(portalLib, "['progress', 'Journey', 'portal.php#app-progress']", 'preserved navigation route');
includes(studentJs, '[data-app-section]', 'preserved navigation observer');
pass('Progress IDs, route, and navigation contracts');

for (const binding of [
  '$points',
  '$tokens',
  '$level',
  '$eligibleRank',
  '$nextRank',
  '$challengeStage',
  '$certificateStatus',
  '$certificateReady',
  '$badges',
  '$rubricScore',
  '$rubricCompleted',
  '$record[\'attendance\']',
  '$record[\'service_hours\']',
  '$record[\'presentations\']',
  '$record[\'rank_status\']',
  '$record[\'challenge_month\']',
  '$record[\'challenge_region\']',
  '$record[\'finalist_status\']',
  '$record[\'award_status\']',
  '$record[\'judge_feedback\']',
]) {
  includes(journey, binding, `${binding} data binding`);
}
pass('existing Leadership Journey PHP data bindings');

includes(journey, 'certificate.php?id=', 'certificate route');
includes(journey, 'href="leaderboard.php"', 'leaderboard route');
includes(journey, 'href="#app-achievements"', 'Achievements route');
assert.ok(!/<form\b/i.test(journey), 'Leadership Journey must not add forms');
assert.ok(!/<input\b|<select\b|<textarea\b/i.test(journey), 'Leadership Journey must not add fields');
pass('preserved routes and no new workflow inputs');

for (const component of [
  '.journey-story',
  '.journey-story-heading',
  '.journey-chapter-index',
  '.journey-stage-path',
  '.journey-card',
]) {
  includes(css, component, `${component} styling`);
}
includes(css, '@media (max-width: 760px)', 'mobile breakpoint');
includes(css, ':focus-visible', 'focus-visible treatment');
includes(css, 'prefers-reduced-motion', 'reduced-motion treatment');
pass('Design System V1 journey and accessibility styling');

assert.ok(!journey.includes('data-progress='), 'no fabricated progress percentage');
assert.ok(!journey.includes('streak_count'), 'no fabricated streak binding');
assert.ok(!journey.includes('weekly_goal_progress'), 'no fabricated goal binding');
pass('no fabricated Leadership Journey progress');

process.stdout.write('PASS Leadership Journey frontend contract suite\n');
