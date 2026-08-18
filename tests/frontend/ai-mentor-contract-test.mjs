import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, portalLib, reviewState, config, css, studentJs] = await Promise.all([
  read('portal.php'),
  read('portal-lib.php'),
  read('backend/ai/AiReviewState.php'),
  read('backend/config.php'),
  read('assets/student-app.css'),
  read('assets/student-app.js'),
]);
const normalizedConfig = config.replace(/\r\n/g, '\n');

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
  ["$aiReviewApproved = ($aiReviewRecord['status'] ?? '') === 'Applied';", 'admin-applied approval gate'],
  ["$approvedAiReview = $aiReviewApproved && is_array($aiReviewRecord['review'] ?? null)", 'approved-review extraction gate'],
  ["$aiReviewState = ai_review_state($research !== null, $aiReviewRecord);", 'central review-state delegation'],
  ["$aiReviewRecord['applied_at']", 'approved review date'],
]) {
  includes(portal, contract[0], contract[1]);
}
for (const [constant, value] of [
  ['NO_RESEARCH', 'no-research'],
  ['NOT_CREATED', 'not-created'],
  ['AWAITING_APPROVAL', 'awaiting-approval'],
  ['UNAVAILABLE', 'unavailable'],
  ['APPROVED', 'approved'],
]) {
  includes(
    reviewState,
    `public const ${constant} = '${value}';`,
    `${value} state`
  );
}
pass('authorization and approved-review state machine');

for (const contract of [
  ['$aiMentorFirstName', 'personalized greeting data'],
  ['$aiMentorTopic', 'current presentation context'],
  ['$aiMentorTodayFocus', 'approved-review focus derivation'],
  ['Latest Approved Review', 'latest approved review summary'],
  ['Review pending', 'review-pending state'],
  ['First time here', 'first-time state'],
  ['No active presentation', 'no-active-presentation state'],
  ['Coach Me', 'single Coach Me action'],
  ['aria-describedby="ai-mentor-coach-status"', 'Coach Me accessible status'],
  ['role="status" aria-live="polite"', 'review state announcement'],
]) {
  includes(mentor, contract[0], contract[1]);
}
assert.equal(
  (mentor.match(/>Coach Me<\/button>/g) || []).length,
  1,
  'AI Mentor Home exposes one Coach Me CTA'
);
includes(
  normalizedConfig,
  "'coach_me_enabled' => env_bool(",
  'Coach Me capability remains feature-flagged'
);
includes(
  normalizedConfig,
  "'AI_MENTOR_COACH_ME_ENABLED',\n                    false",
  'Coach Me defaults disabled'
);
includes(portal, '$aiMentorCoachMeEnabled = ai_mentor_feature_enabled(', 'Coach Me flag resolution');
assert.match(
  mentor,
  /<button[\s\S]*?class="button primary ai-mentor-coach-button"[\s\S]*?\bdisabled\b[\s\S]*?>Coach Me<\/button>/,
  'Coach Me remains safely disabled in Milestone 2'
);
pass('truthful AI Mentor Home states and disabled Coach Me capability');

for (const binding of [
  '$aiMentorTotal',
  '$aiMentorSummary',
  '$aiMentorSuggestedTokens',
  '$aiMentorStrengths',
  '$aiMentorImprovements',
  '$aiMentorCommunicationNote',
  '$aiMentorLeadershipNote',
  '$aiMentorTopic',
  '$aiMentorCategory',
  'Review status: Applied',
  '$aiResearchCategories',
  '$rubricScore',
  '$rubricCompleted',
]) {
  includes(mentor, binding, `${binding} approved data binding`);
}
pass('approved AI review and rubric data bindings');

for (const contract of [
  ['AI Research Review', 'research review label'],
  ['Official Presentation Rubric', 'official rubric label'],
  ['It is not part of the AI research review above.', 'explicit review/rubric separation'],
  ['$aiMentorHasValidTotal', 'valid overall-result guard'],
  ['$aiMentorHasSuggestedTokens', 'suggested-token presence guard'],
  ['$aiCategoryValid', 'research-metric validity guard'],
  ['$rubricCompleted > 0', 'official-rubric presence guard'],
  ['No valid overall research result was stored', 'missing overall-result state'],
  ['No official rubric scores recorded', 'missing official-rubric state'],
  ['Recommended Next Step', 'approved next-action priority'],
  ['Start With Strengths', 'strength-first presentation'],
  ['Communication Note', 'communication note'],
  ['Leadership Note', 'leadership note'],
  ['role="img" aria-label="', 'non-color-only metric description'],
]) {
  includes(mentor, contract[0], contract[1]);
}
assert.ok(
  mentor.indexOf('AI Research Review') < mentor.indexOf('Official Presentation Rubric'),
  'research review is presented before the separate official rubric'
);
assert.ok(
  !/unified score|combined score|score trend|previous review comparison/i.test(mentor),
  'no fabricated unified score, trend, or historical comparison'
);
includes(
  portalLib,
  "$reviews[$studentId]['status'] = 'Stale - ' . $reason;",
  'review staleness continues to remove exact approval status'
);
pass('truthful approved-review hierarchy and missing-data states');

includes(mentor, 'href="#research-submission"', 'existing Research Workspace route');
assert.ok(!/<form\b/i.test(mentor), 'AI Mentor does not add forms');
assert.ok(!/<input\b|<textarea\b|contenteditable/i.test(mentor), 'AI Mentor does not add chat or reflection inputs');
assert.ok(!/fetch\s*\(|XMLHttpRequest|WebSocket/i.test(mentor), 'AI Mentor does not add API or chat transport');
assert.ok(!/upgrade|subscribe|billing|payment/i.test(mentor), 'AI Mentor does not imply Premium enforcement');
pass('no chat, memory, API, or new workflow surface');

for (const component of [
  '.ai-mentor-hero',
  '.ai-mentor-home-grid',
  '.ai-mentor-home-card',
  '.ai-mentor-latest-review',
  '.ai-mentor-coach-button[disabled]',
  '.ai-approved-review-section',
  '.ai-score-unavailable',
  '.ai-priority-action-card',
  '.ai-rubric-empty',
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
