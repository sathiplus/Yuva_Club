import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, css, portalLib, studentJs] = await Promise.all([
  read('portal.php'),
  read('assets/student-app.css'),
  read('portal-lib.php'),
  read('assets/student-app.js'),
]);

const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, text, message) =>
  assert.ok(source.includes(text), `${message}: missing ${JSON.stringify(text)}`);

for (const contract of [
  ['action="portal-submit-topic.php"', 'topic form action'],
  ['method="post"', 'POST method'],
  ['name="topic_category"', 'topic category'],
  ['name="topic_title"', 'topic title'],
  ['name="presentation_date"', 'presentation date'],
  ['name="presentation_time"', 'presentation time'],
  ['action="portal-submit-research.php"', 'research form action'],
  ['enctype="multipart/form-data"', 'multipart upload'],
  ['name="research_notes"', 'research notes'],
  ['name="sources_used"', 'research sources'],
  ['name="presentation_outline"', 'presentation outline'],
  ['name="prepared_questions"', 'prepared questions'],
  ['name="research_file"', 'research upload'],
  ['accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png"', 'upload accept list'],
  ['portal-download.php?id=', 'download route'],
]) {
  includes(portal, contract[0], contract[1]);
}
assert.ok(
  portal.split('<?php echo csrf_field(); ?>').length - 1 >= 3,
  'student POST forms retain server CSRF fields',
);
pass('Practice form, field, upload, download, and CSRF contracts');

for (const contract of [
  ['$studentZoomUrl', 'student Zoom URL'],
  ['$studentZoomMeetingId', 'Zoom meeting ID'],
  ['$studentZoomPassword', 'Zoom password'],
  ['$effectiveZoomUrl', 'effective Zoom URL'],
  ['zoom_browser_join_url(', 'browser Zoom URL'],
  ['target="_blank" rel="noopener"', 'safe external Zoom target'],
  ['$schedulerSrc', 'scheduler source'],
  ['$schedulerPageUrl', 'scheduler page URL'],
  ['title="Yuva Club Zoom Scheduler"', 'scheduler iframe title'],
]) {
  includes(portal, contract[0], contract[1]);
}
pass('Presentation session, Zoom, and scheduler contracts');

includes(portal, 'id="app-practice"', 'Practice hash route');
includes(portal, 'id="app-present"', 'Presentation hash route');
includes(portal, 'id="topic-selection"', 'topic anchor');
includes(portal, 'id="research-submission"', 'research anchor');
includes(portalLib, 'portal.php#app-practice', 'Practice shell route');
includes(portalLib, 'portal.php#app-present', 'Presentation shell route');
includes(studentJs, '[data-app-section]', 'section navigation hook');
pass('routes, anchors, and shell hooks');

for (const component of [
  '.studio-hero',
  '.studio-card',
  '.studio-card-featured',
  '.studio-card-heading',
  '#app-practice',
  '#app-present',
]) {
  includes(css, component, `${component} styling`);
}
for (const color of [
  '--yuva-blue',
  '--yuva-orange',
  '--yuva-green',
  '--yuva-purple',
  '--yuva-gold',
]) {
  includes(css, color, `${color} token`);
}
includes(css, '@media (max-width: 760px)', 'mobile responsive treatment');
includes(css, ':focus-visible', 'keyboard focus treatment');
includes(css, 'prefers-reduced-motion', 'reduced-motion treatment');
pass('shared Design System V1.0 and responsive accessibility styling');

const presentationStart = portal.indexOf('<section class="band app-section" id="app-present"');
const practiceStart = portal.indexOf('<section class="band app-section" id="app-practice"');
const practiceEnd = portal.indexOf('<section class="band" id="safety-report"', practiceStart);
const studioMarkup =
  portal.slice(presentationStart, portal.indexOf('<section class="band app-section" id="app-ai-coach"', presentationStart)) +
  portal.slice(practiceStart, practiceEnd);
assert.ok(!studioMarkup.includes('name="practice_score"'), 'no fabricated Practice score field');
assert.ok(!studioMarkup.includes('name="presentation_score"'), 'no fabricated Presentation score field');
assert.ok(!studioMarkup.includes('streak'), 'Studio markup does not add a fabricated streak');
pass('no fabricated Studio data');

process.stdout.write('PASS Phase 2 Practice and Presentation frontend contract suite\n');
