<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
$reviews = ai_reviews();
$draft = $reviews[$studentId]['review'] ?? [];

if ($studentId === '' || $draft === []) {
    redirect_to('admin.php?status=ai-missing');
}

$records = read_json_file(portal_records_file());
$record = $records[$studentId] ?? student_record($studentId);
$points = max(0, min(100, (int) ($draft['total_points'] ?? 0)));
$tokens = max(0, (int) ($record['tokens'] ?? 0)) + max(0, min(4, (int) ($draft['suggested_tokens'] ?? intdiv($points, 25))));

$records[$studentId] = array_merge($record, [
    'points' => (string) $points,
    'tokens' => (string) $tokens,
    'score' => (string) $points,
    'rank_recommendation' => rank_eligibility(array_merge($record, ['points' => (string) $points])),
    'ai_feedback_summary' => clean_text((string) ($draft['summary'] ?? '')),
    'communication_skills' => clean_text((string) ($draft['communication_skills'] ?? '')),
    'leadership_milestones' => clean_text((string) ($draft['leadership_milestones'] ?? '')),
    'teacher_feedback' => clean_text((string) ($draft['summary'] ?? ($record['teacher_feedback'] ?? ''))),
    'updated_at' => date('Y-m-d H:i:s'),
]);
write_json_file(portal_records_file(), $records);

$reviews[$studentId]['status'] = 'Applied by Admin';
$reviews[$studentId]['applied_at'] = date('Y-m-d H:i:s');
write_json_file(ai_reviews_file(), $reviews);

redirect_to('admin.php?status=ai-applied');
