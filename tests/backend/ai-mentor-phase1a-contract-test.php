<?php
declare(strict_types=1);

function phase1a_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
$migration = file_get_contents($root . '/database/06-ai-mentor-phase-1a.azure-sql.sql');
$repository = file_get_contents($root . '/backend/ai/SqlAiReviewRepository.php');
$apply = file_get_contents($root . '/admin-ai-apply.php');
$edit = file_get_contents($root . '/admin-ai-save-draft.php');
$portal = file_get_contents($root . '/portal.php');
$admin = file_get_contents($root . '/admin.php');

foreach (['Processing', 'Draft', 'Failed', 'Applied', 'Stale', 'row_version', 'source_revision_hash'] as $required) {
    phase1a_assert(str_contains($migration, $required), 'Migration missing ' . $required);
}
phase1a_assert(str_contains($migration, 'uq_student_points_ai_mentor_source'), 'Token ledger must enforce idempotency.');
phase1a_assert(str_contains($repository, 'SERIALIZABLE'), 'Apply must be transactional.');
phase1a_assert(str_contains($repository, 'db_acquire_application_lock'), 'Apply must hold a SQL application lock.');
phase1a_assert(str_contains($repository, "status = N'Stale'"), 'Changed research must make a review stale.');
phase1a_assert(str_contains($repository, 'student_points'), 'Tokens must use the student_points ledger.');
phase1a_assert(!str_contains($apply, 'portal_records_file'), 'Apply must not overwrite filesystem official score fields.');
phase1a_assert(!preg_match("/'points'\s*=>|'score'\s*=>|rubric_/", $apply), 'Apply must not overwrite official score fields.');
phase1a_assert(str_contains($edit, 'saveAdminEdit'), 'Admin edits must persist separately.');
phase1a_assert(str_contains($admin, 'Save Draft') && str_contains($admin, 'Apply Review'), 'Admin workflow controls are required.');
phase1a_assert(str_contains($portal, "=== 'Applied'"), 'Only Applied reviews may be student-visible.');
phase1a_assert(str_contains($portal, '$aiMentorRecommendedNextStep'), 'Student view must use the explicit next step.');

fwrite(STDOUT, "PASS Phase 1A SQL, idempotency, edit, and visibility contracts\n");
