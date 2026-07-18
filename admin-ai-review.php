<?php
require __DIR__ . '/portal-lib.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('admin.php?status=security-error');
}

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$student = $studentId !== '' ? find_student($studentId) : null;
$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];

if ($student === null || $selection === [] || $research === []) {
    redirect_to('admin.php?status=ai-missing');
}

$result = ai_review_research_submission($student, $selection, $research);
$reviews = ai_reviews();
$previousReview = $reviews[$studentId] ?? [];
$reviews[$studentId] = [
    'review_id' => bin2hex(random_bytes(16)),
    'ok' => $result['ok'],
    'review' => $result['review'] ?? [],
    'error' => $result['error'] ?? '',
    'reviewed_at' => date('Y-m-d H:i:s'),
    'topic_title' => $selection['topic_title'] ?? '',
    'topic_category' => $selection['topic_category'] ?? '',
    'status' => ($result['ok'] ?? false) ? 'Draft - Pending Admin Approval' : 'Needs Setup',
    'supersedes_review_id' => $previousReview !== [] ? ai_review_identifier($studentId, $previousReview) : '',
    'previous_status' => $previousReview['status'] ?? '',
];
write_json_file(ai_reviews_file(), $reviews);

redirect_to(($result['ok'] ?? false) ? 'admin.php?status=ai-reviewed' : 'admin.php?status=ai-error');
