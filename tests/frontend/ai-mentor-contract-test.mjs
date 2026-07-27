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

const start = portal.indexOf('<section class="band app-section" id="app-ai-coach"');
const end = portal.indexOf('<section class="band app-section" id="app-profile"', start);
assert.ok(start >= 0 && end > start, 'AI Mentor section boundaries exist');
const mentor = portal.slice(start, end);

includes(mentor, 'id="app-ai-coach"', 'preserved AI Coach section ID');
includes(mentor, 'data-app-section="practice"', 'preserved navigation grouping');
includes(mentor, 'AI Mentor', 'AI Mentor visible label');
assert.ok(!mentor.includes('id="app-ai-mentor"'), 'backend-compatible ID must not be renamed');
includes(portalLib, 'portal.php#app-practice', 'preserved Practice navigation route');
includes(studentJs, '[data-app-section]', 'preserved section observer');
pass('AI Mentor label over preserved IDs and navigation');

for (const contract of [
  ["$aiReviewApproved = ($aiReviewRecord['status'] ?? '') === 'Applied by Admin';", 'admin-applied approval gate'],
  ["$approvedAiReview = $aiReviewApproved && is_array($aiReviewRecord['review'] ?? null)", 'approved-review extraction gate'],
  ["$aiReviewState = !$research ? 'no-research'", 'no-research state'],
  ["'not-created'", 'not-created state'],
  ["'awaiting-approval'", 'awaiting-approval state'],
  ["'unavailable'", 'unavailable state'],
  ["'approved'", 'approved state'],
  ["$aiReviewRecord['applied_at']", 'approved review date'],
]) {
  includes(portal, contract[0], contract[1]);
}
pass('authorization and approved-review state machine');

for (const binding of [
  "$approvedAiReview['total_points']",
  "$approvedAiReview['summary']",
  "$approvedAiReview['suggested_tokens']",
  "$approvedAiReview['strengths']",
  "$approvedAiReview['improvements']",
  "$approvedAiReview['communication_skills']",
  "$approvedAiReview['leadership_milestones']",
  "$aiReviewRecord['topic_title']",
  "$aiReviewRecord['topic_category']",
  "$aiReviewRecord['status']",
  '$aiResearchCategories',
  '$rubricScore',
  '$rubricCompleted',
]) {
  includes(mentor, binding, `${binding} approved data binding`);
}
pass('approved AI review and rubric data bindings');

includes(mentor, 'href="#research-submission"', 'existing Research Workspace route');
assert.ok(!/<form\b/i.test(mentor), 'AI Mentor does not add forms');
assert.ok(!/<input\b|<textarea\b|contenteditable/i.test(mentor), 'AI Mentor does not add chat or reflection inputs');
assert.ok(!/fetch\s*\(|XMLHttpRequest|WebSocket/i.test(mentor), 'AI Mentor does not add API or chat transport');
pass('no chat, memory, API, or new workflow surface');

for (const component of [
  '.ai-mentor-hero',
  '.ai-mentor-state',
  '.ai-mentor-guidance-card',
  '.ai-mentor-topic-card',
  '.ai-mentor-perspective-heading',
]) {
  includes(css, component, `${component} styling`);
}
includes(css, ':focus-visible', 'focus-visible treatment');
includes(css, 'prefers-reduced-motion', 'reduced-motion treatment');
pass('Design System V1 and accessibility styling');

process.stdout.write('PASS AI Mentor frontend contract suite\n');
