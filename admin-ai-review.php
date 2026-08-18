<?php
require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$student = $studentId !== '' ? find_student($studentId) : null;
$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];

if ($student === null || $selection === [] || $research === []) {
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.ai_review.create', 'student', $studentId, false, ['reason' => 'missing_student_selection_or_research']);
    redirect_to('admin.php?status=ai-missing');
}

$adminUserId = find_sql_admin_user_id((string) $admin['email']);
$priorReview = ai_review_repository()->find($studentId);
if ($adminUserId !== null) {
    log_activity($adminUserId, 'ai_mentor.generation_started', 'student', null, ['yuva_id' => $studentId, 'retry' => $priorReview !== []]);
    if ($priorReview !== []) {
        log_activity($adminUserId, 'ai_mentor.retry', 'ai_mentor_review', (int) ($priorReview['id'] ?? 0));
    }
}
$reviewRecord = ai_mentor_service()->createDraft(
    $studentId,
    $student,
    $selection,
    $research
);
if ($adminUserId !== null) {
    $storedReview = ai_review_repository()->find($studentId);
    log_activity($adminUserId, ($reviewRecord['ok'] ?? false) ? 'ai_mentor.generation_succeeded' : 'ai_mentor.generation_failed', 'ai_mentor_review', (int) ($storedReview['id'] ?? 0), ['prompt_version' => $reviewRecord['prompt_version']]);
}
audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.ai_review.create', 'student', $studentId, (bool) ($reviewRecord['ok'] ?? false), [
    'status' => $reviewRecord['status'],
    'prompt_version' => $reviewRecord['prompt_version'],
]);

redirect_to(($reviewRecord['ok'] ?? false) ? 'admin.php?status=ai-reviewed' : 'admin.php?status=ai-error');
