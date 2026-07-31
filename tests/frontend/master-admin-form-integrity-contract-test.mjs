import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const admin = await readFile(new URL('admin.php', root), 'utf8');

const aiSectionStart = admin.indexOf('<h2>AI Mentor reviews</h2>');
const studentSectionStart = admin.indexOf('id="students"');
const studentSectionEnd = admin.indexOf('</main>', studentSectionStart);
assert.ok(aiSectionStart >= 0, 'Dedicated AI Mentor review section is missing');
assert.ok(studentSectionStart > aiSectionStart, 'Student section ordering changed');
assert.ok(studentSectionEnd > studentSectionStart, 'Student section boundary is missing');

const aiSection = admin.slice(aiSectionStart, studentSectionStart);
const studentSection = admin.slice(studentSectionStart, studentSectionEnd);

for (const action of ['admin-ai-review.php', 'admin-ai-apply.php']) {
  assert.ok(
    aiSection.includes(`action="${action}"`),
    `Dedicated AI action is missing: ${action}`,
  );
  assert.ok(
    !studentSection.includes(`action="${action}"`),
    `AI action must not be nested in the student-update workflow: ${action}`,
  );
}

const formIdExpression = '<?php echo e($studentUpdateFormId); ?>';
const studentFormStart = studentSection.indexOf(
  `<form id="${formIdExpression}" action="admin-actions.php" method="post">`,
);
const studentFormEnd = studentSection.indexOf('</form>', studentFormStart);
assert.ok(studentFormStart >= 0, 'Student-update form owner is missing');
assert.ok(studentFormEnd > studentFormStart, 'Student-update form is not closed');

const studentForm = studentSection.slice(studentFormStart, studentFormEnd);
assert.equal(
  (studentForm.match(/<form\b/g) ?? []).length,
  1,
  'Student-update form must not contain nested forms',
);
assert.ok(
  studentForm.includes('<?php echo csrf_field(); ?>')
    && studentForm.includes('name="student_id"')
    && studentForm.includes('<button class="button primary" type="submit">Save</button>'),
  'Save, student identity, and CSRF must share the admin-actions.php form owner',
);

const associatedControls = [
  'topic_status',
  'research_status',
  'approved',
  'current_rank',
  'rank_status',
  'rank_recommendation',
  'attendance',
  'presentations',
  'service_hours',
  'points',
  'tokens',
  'reward_status',
  'last_duration',
  'score',
  'teacher_feedback',
  'challenge_stage',
  'challenge_month',
  'challenge_region',
  'finalist_status',
  'award_status',
  'judge_feedback',
  'mentor_feedback',
  'ai_feedback_summary',
  'communication_skills',
  'leadership_milestones',
  'student_session_title',
  'student_session_date',
  'student_session_start',
  'student_session_end',
  'student_session_status',
  'student_zoom_url',
  'student_zoom_meeting_id',
  'student_zoom_password',
  'certificate_status',
  'admin_notes',
];
for (const name of associatedControls) {
  const controlLine = studentSection
    .split(/\r?\n/)
    .find((line) => line.includes(`name="${name}"`));
  assert.ok(
    controlLine?.includes(`form="${formIdExpression}"`),
    `Student-update control is not associated with its form: ${name}`,
  );
}
const rubricControl = studentSection
  .split(/\r?\n/)
  .find((line) => line.includes('name="rubric_<?php echo e($rubricKey); ?>"'));
assert.ok(
  rubricControl?.includes(`form="${formIdExpression}"`),
  'Rubric controls are not associated with the student-update form',
);

console.log('PASS Master Admin student-update markup contains no nested forms');
console.log('PASS Save, CSRF, identity, and every update control share one form owner');
console.log('PASS AI review and apply controls remain in dedicated handlers only');
