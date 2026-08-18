import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..', '..');
const read = (name) => readFile(path.join(root, name), 'utf8');
const [migration, repository, apply, edit, portal, admin, service, validator] = await Promise.all([
  read('database/06-ai-mentor-phase-1a.azure-sql.sql'), read('backend/ai/SqlAiReviewRepository.php'),
  read('admin-ai-apply.php'), read('admin-ai-save-draft.php'), read('portal.php'), read('admin.php'),
  read('backend/ai/AiMentorService.php'), read('backend/ai/AiReviewValidator.php'),
]);

for (const value of ['Processing', 'Draft', 'Failed', 'Applied', 'Stale', 'row_version', 'source_revision_hash', 'source_submission_reference']) {
  assert.ok(migration.includes(value), `migration missing ${value}`);
}
for (const value of ['SERIALIZABLE', 'db_acquire_application_lock', 'student_points', "status = N'Stale'", 'uq_student_points_ai_mentor_source']) {
  assert.ok((repository + migration).includes(value), `reliability contract missing ${value}`);
}
assert.ok(!apply.includes('portal_records_file'), 'apply must not overwrite filesystem official records');
assert.doesNotMatch(apply, /rubric_|['"](?:points|score)['"]\s*=>/, 'apply must not overwrite official scores');
assert.ok(edit.includes('saveAdminEdit') && admin.includes('Save Draft') && admin.includes('Apply Review'));
assert.ok(portal.includes("=== 'Applied'") && portal.includes('$aiMentorRecommendedNextStep'));
assert.ok(service.includes("'status' => 'Processing'") && service.includes("? 'Draft'") && service.includes(": 'Failed'"));
assert.ok(validator.includes('total did not match its category scores') && validator.includes('unexpected fields'));

process.stdout.write('PASS AI Mentor Phase 1A reliability contracts\n');
