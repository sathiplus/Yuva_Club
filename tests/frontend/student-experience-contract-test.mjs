import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, css, homeFixture, studioFixture, journeyFixture, mentorFixture, achievementsFixture, profileFixture, identityHandler, identityService, publicIdentity] = await Promise.all([
  read('portal.php'),
  read('assets/student-app.css'),
  read('tests/frontend/fixtures/student-home-preview.html'),
  read('tests/frontend/fixtures/studio-preview.html'),
  read('tests/frontend/fixtures/leadership-journey-preview.html'),
  read('tests/frontend/fixtures/ai-mentor-preview.html'),
  read('tests/frontend/fixtures/achievements-preview.html'),
  read('tests/frontend/fixtures/profile-preview.html'),
  read('student-public-identity.php'),
  read('backend/identity/PublicIdentityService.php'),
  read('backend/identity/PublicStudentIdentity.php'),
]);

const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, text, message) =>
  assert.ok(source.includes(text), `${message}: missing ${JSON.stringify(text)}`);
const section = (id, nextId) => {
  const start = portal.indexOf(`<section class="band app-section" id="${id}"`);
  const end = nextId ? portal.indexOf(`<section class="band app-section" id="${nextId}"`, start) : portal.length;
  assert.ok(start >= 0 && end > start, `${id} section boundaries exist`);
  return portal.slice(start, end);
};

for (const fixture of [homeFixture, studioFixture, journeyFixture, mentorFixture, achievementsFixture, profileFixture]) {
  for (const label of ['Home', 'Practice', 'Present', 'Journey', 'Profile']) {
    includes(fixture, `>${label}<`, `${label} navigation item`);
  }
  includes(fixture, 'aria-label="Student app navigation"', 'navigation accessible name');
  includes(fixture, 'aria-current="page"', 'active navigation state');
  assert.equal((fixture.match(/<main\b/g) ?? []).length, 1, 'one main landmark per fixture');
  assert.ok(/<h1\b/.test(fixture), 'fixture has a top-level heading');
}
pass('complete five-item shell, landmarks, headings, and active navigation');

includes(homeFixture, 'assets/yuva-symbol.png', 'approved small Home symbol');
for (const fixture of [studioFixture, journeyFixture, mentorFixture, achievementsFixture, profileFixture]) {
  includes(fixture, 'assets/yuva-symbol.png', 'approved small symbol');
  includes(fixture, 'assets/logo.png', 'approved full rail logo');
}
pass('approved responsive branding standards');

for (const token of [
  '--font-student:',
  '--layout-gutter:',
  '--layout-hero-gap:',
  '--layout-section-gap:',
  '--layout-card-padding:',
  '--layout-heading-gap:',
  '--layout-mobile-nav-clearance:',
  '--motion-fast:',
  '--motion-standard:',
  '--semantic-primary:',
  '--semantic-structure:',
  '--semantic-progress:',
  '--semantic-achievement:',
  '--semantic-learning:',
  '--semantic-danger:',
]) {
  includes(css, token, `${token} shared token`);
}
includes(css, '.ds-story-hero', 'story hero primitive');
includes(css, '.ds-workspace-hero', 'workspace hero primitive');
includes(css, '.ds-section-heading', 'section heading primitive');
includes(css, 'prefers-reduced-motion: reduce', 'reduced motion alternative');
includes(css, ':focus-visible', 'focus-visible treatment');
pass('shared layout, semantic color, motion, and accessibility primitives');

const achievements = section('app-achievements', 'app-present');
for (const binding of [
  '$level',
  '$badges',
  '$rubricScore',
  '$record[\'presentations\']',
  '$record[\'service_hours\']',
  '$record[\'attendance\']',
  '$record[\'leadership_milestones\']',
  '$record[\'rank_recommendation\']',
  '$record[\'finalist_status\']',
  '$record[\'award_status\']',
  '$certificateReady',
]) {
  includes(achievements, binding, `${binding} achievement binding`);
}
includes(achievements, 'certificate.php?id=', 'certificate link');
includes(achievements, 'id="app-achievements"', 'preserved Achievements ID');
assert.ok(!/<form\b|<input\b|<textarea\b|<select\b/i.test(achievements), 'Achievements adds no workflow inputs');
pass('Achievements preserves verified recognition bindings and routes');

const profileStart = portal.indexOf('<section class="band app-section" id="app-profile"');
const profileEnd = portal.indexOf('<section class="band app-section" id="app-settings"', profileStart);
assert.ok(profileStart >= 0 && profileEnd > profileStart, 'Profile section boundaries exist');
const profile = portal.slice(profileStart, profileEnd);
for (const binding of [
  '$studentId',
  '$student',
  '$name',
  '$level',
  '$membershipGroupLabel',
  '$record[\'rank_status\']',
  '$record[\'leadership_milestones\']',
]) {
  includes(profile, binding, `${binding} profile binding`);
}
includes(profile, 'id="app-profile"', 'preserved Profile ID');
includes(profile, 'href="portal-logout.php"', 'preserved logout route');
assert.match(
  profile,
  /<a\b[^>]*href="#app-progress"[^>]*>View Leadership Journey<\/a>/,
  'profile provides the approved Leadership Journey action and destination',
);
assert.equal((profile.match(/<form\b/g) ?? []).length, 1, 'Profile contains only the approved public-identity form');
includes(profile, 'action="student-public-identity.php"', 'approved public-identity form route');
includes(profile, 'name="public_handle"', 'YUVA Handle control');
includes(profile, 'name="avatar_code"', 'preset avatar control');
includes(profile, '<?php echo csrf_field(); ?>', 'public-identity CSRF field');
includes(profile, '<strong>Your YUVA ID is permanent:</strong>', 'permanent YUVA ID display');
assert.ok(!profile.includes('student-organization-membership-action.php'), 'Phase 2A membership workflow remains outside Profile');
includes(identityHandler, '$student = require_student();', 'identity handler requires authenticated student context');
includes(identityHandler, 'verify_csrf_token', 'identity handler verifies CSRF');
includes(identityService, 'hash_equals(strtoupper(trim($authenticatedYuvaId)), strtoupper(trim($targetYuvaId)))', 'identity service prevents cross-student edits');
includes(identityService, 'public const CHANGE_DAYS = 30', '30-day handle-change policy');
includes(publicIdentity, "return ['yuva_id' => $yuvaId, 'handle' => $handle !== '' ? $handle : null, 'avatar_code' => $avatarCode]", 'privacy-safe public identity projection');
assert.ok(!/<textarea\b|<select\b/i.test(profile), 'Profile adds no unrelated workflow fields');
pass('Profile preserves identity, account, and privacy-safe bindings');

for (const forbidden of ['fetch(', 'XMLHttpRequest', 'new WebSocket', 'localStorage', 'sessionStorage']) {
  assert.ok(!achievements.includes(forbidden), `Achievements excludes ${forbidden}`);
  assert.ok(!profile.includes(forbidden), `Profile excludes ${forbidden}`);
}
pass('no new APIs, storage, or client-side data fabrication');

process.stdout.write('PASS Student Experience cross-screen contract suite\n');
