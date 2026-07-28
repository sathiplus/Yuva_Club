import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (relativePath) => readFile(path.join(root, relativePath), 'utf8');
const [portal, submit, validator, state, css] = await Promise.all([
  read('portal.php'),
  read('portal-submit-research.php'),
  read('backend/submission/ResearchUploadValidator.php'),
  read('backend/submission/ResearchSubmissionState.php'),
  read('assets/student-app.css'),
]);
const pass = (message) => process.stdout.write(`PASS ${message}\n`);
const includes = (source, value, message) =>
  assert.ok(source.includes(value), `${message}: missing ${JSON.stringify(value)}`);

for (const stateName of [
  'NO_SUBMISSION',
  'DRAFT_INCOMPLETE',
  'SUBMISSION_RECEIVED',
  'REVIEW_NOT_STARTED',
  'REVIEW_PROCESSING',
  'REVIEW_PENDING_APPROVAL',
  'REVIEW_APPROVED',
  'REVIEW_UNAVAILABLE',
  'NEEDS_RESUBMISSION',
  'UNSUPPORTED_FILE',
  'UPLOAD_FAILURE',
]) {
  includes(state, `public const ${stateName}`, `${stateName} state`);
}
for (const message of [
  'No submission',
  'Submission received',
  'Review not started',
  'Review processing',
  'Pending administrator approval',
  'Review approved',
  'Review unavailable',
  'Resubmission needed',
  'File not accepted',
  'Upload did not finish',
]) {
  includes(state, message, `${message} student-facing message`);
}
pass('truthful submission and review states');

includes(validator, 'public const MAX_BYTES = 10 * 1024 * 1024;', '10 MB application limit');
for (const extension of ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'jpg', 'jpeg', 'png']) {
  includes(validator, `'${extension}' =>`, `${extension} validation rule`);
}
for (const securityControl of [
  'FILEINFO_MIME_TYPE',
  'ResearchUploadValidator',
  'is_uploaded_file(',
  'move_uploaded_file(',
  "preg_replace('/[^A-Za-z0-9._-]/'",
  "preg_replace('/[^A-Za-z0-9_-]/'",
]) {
  includes(submit, securityControl, `${securityControl} upload control`);
}
includes(submit, "'type-mismatch' => 'upload-mismatch'", 'mismatched content response');
includes(submit, "'too-large' => 'upload-too-large'", 'oversized response');
includes(submit, "$rejectUpload('upload-failed')", 'storage failure response');
pass('size, MIME, signature, filename, ownership-directory, and failure controls');

includes(portal, 'name="MAX_FILE_SIZE"', 'client advisory upload limit');
includes(portal, 'Maximum 10 MB.', 'visible upload limit');
includes(portal, 'aria-describedby="research-file-help', 'file help binding');
includes(portal, 'aria-invalid="true"', 'file error binding');
includes(portal, 'role="alert"', 'assertive upload failure announcement');
includes(portal, 'role="status" aria-live="polite"', 'polite state announcement');
includes(portal, 'Review and Resubmit', 'resubmission action');
includes(portal, 'practice-submission-status', 'Practice Studio status');
includes(portal, 'ai-submission-status', 'AI Mentor status');
pass('Practice Studio and AI Mentor submission-state surfaces');

for (const selector of [
  '.ai-submission-status',
  '.practice-submission-status',
  '.practice-research-card input[aria-invalid="true"]',
  '@media (max-width: 760px)',
  ':focus-visible',
  'prefers-reduced-motion',
]) {
  includes(css, selector, `${selector} responsive accessibility styling`);
}
assert.ok(!/video|audio|microphone|camera|transcript|timeline/i.test(submit), 'upload workflow adds no media pipeline');
assert.ok(!/fetch\s*\(|XMLHttpRequest|WebSocket/i.test(portal), 'submission states add no API transport');
pass('scope boundaries and accessibility contracts');

process.stdout.write('PASS submission processing frontend contract suite\n');
