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

$result = ai_review_research_submission($student, $selection, $research);
$reviews = ai_reviews();
$reviews[$studentId] = [
    'ok' => $result['ok'],
    'review' => $result['review'] ?? [],
    'error' => $result['error'] ?? '',
    'reviewed_at' => date('Y-m-d H:i:s'),
    'topic_title' => $selection['topic_title'] ?? '',
    'topic_category' => $selection['topic_category'] ?? '',
    'status' => ($result['ok'] ?? false) ? 'Draft - Pending Admin Approval' : 'Needs Setup',
];
write_json_file(ai_reviews_file(), $reviews);
audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.ai_review.create', 'student', $studentId, (bool) ($result['ok'] ?? false), ['status' => $reviews[$studentId]['status']]);

redirect_to(($result['ok'] ?? false) ? 'admin.php?status=ai-reviewed' : 'admin.php?status=ai-error');
