<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$student = $studentId !== '' ? find_student($studentId) : null;
$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];

if ($student === null || $selection === [] || $research === []) {
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.ai_review.create', 'student', $studentId, false, ['reason' => 'missing_student_selection_or_research']);
    redirect_to('admin.php?status=ai-missing');
}

$reviewRecord = ai_mentor_service()->createDraft(
    $studentId,
    $student,
    $selection,
    $research
);
audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.ai_review.create', 'student', $studentId, (bool) ($reviewRecord['ok'] ?? false), [
    'status' => $reviewRecord['status'],
    'prompt_version' => $reviewRecord['prompt_version'],
]);

redirect_to(($reviewRecord['ok'] ?? false) ? 'admin.php?status=ai-reviewed' : 'admin.php?status=ai-error');
