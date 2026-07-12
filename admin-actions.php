<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
if ($studentId === '') {
    redirect_to('admin.php');
}

$records = read_json_file(portal_records_file());
$existingRecord = $records[$studentId] ?? student_record($studentId);
$updatedRecord = array_merge($existingRecord, [
    'approved' => clean_text($_POST['approved'] ?? 'Pending'),
    'attendance' => clean_text($_POST['attendance'] ?? '0'),
    'presentations' => clean_text($_POST['presentations'] ?? '0'),
    'service_hours' => clean_text($_POST['service_hours'] ?? '0'),
    'last_duration' => clean_text($_POST['last_duration'] ?? ''),
    'score' => clean_text($_POST['score'] ?? ''),
    'teacher_feedback' => clean_text($_POST['teacher_feedback'] ?? ''),
    'certificate_status' => clean_text($_POST['certificate_status'] ?? 'Not Ready'),
    'admin_notes' => clean_text($_POST['admin_notes'] ?? ''),
    'student_session_title' => clean_text($_POST['student_session_title'] ?? ''),
    'student_session_date' => clean_text($_POST['student_session_date'] ?? ''),
    'student_session_start' => clean_text($_POST['student_session_start'] ?? ''),
    'student_session_end' => clean_text($_POST['student_session_end'] ?? ''),
    'student_session_status' => clean_text($_POST['student_session_status'] ?? 'Closed'),
    'student_zoom_url' => clean_text($_POST['student_zoom_url'] ?? ''),
    'student_zoom_meeting_id' => clean_text($_POST['student_zoom_meeting_id'] ?? ''),
    'student_zoom_password' => clean_text($_POST['student_zoom_password'] ?? ''),
    'current_rank' => clean_text($_POST['current_rank'] ?? 'Explorer'),
    'rank_status' => clean_text($_POST['rank_status'] ?? 'Approved'),
    'rank_recommendation' => clean_text($_POST['rank_recommendation'] ?? ''),
    'mentor_feedback' => clean_text($_POST['mentor_feedback'] ?? ''),
    'points' => clean_text($_POST['points'] ?? ''),
    'tokens' => clean_text($_POST['tokens'] ?? ''),
    'reward_status' => clean_text($_POST['reward_status'] ?? 'Not Yet'),
    'ai_feedback_summary' => clean_text($_POST['ai_feedback_summary'] ?? ''),
    'communication_skills' => clean_text($_POST['communication_skills'] ?? ''),
    'leadership_milestones' => clean_text($_POST['leadership_milestones'] ?? ''),
    'challenge_stage' => clean_text($_POST['challenge_stage'] ?? 'Practice Session'),
    'challenge_region' => clean_text($_POST['challenge_region'] ?? ''),
    'challenge_month' => clean_text($_POST['challenge_month'] ?? date('Y-m')),
    'finalist_status' => clean_text($_POST['finalist_status'] ?? 'Not Qualified'),
    'award_status' => clean_text($_POST['award_status'] ?? 'None'),
    'judge_feedback' => clean_text($_POST['judge_feedback'] ?? ''),
    'updated_at' => date('Y-m-d H:i:s'),
]);

foreach (array_keys(rubric_categories()) as $key) {
    $updatedRecord['rubric_' . $key] = clean_text($_POST['rubric_' . $key] ?? '');
}

$records[$studentId] = $updatedRecord;
write_json_file(portal_records_file(), $records);
audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.student_record.update', 'student', $studentId, true);

$selections = read_json_file(topic_selections_file());
if (isset($selections[$studentId])) {
    $selections[$studentId]['status'] = clean_text($_POST['topic_status'] ?? 'Pending Admin Review');
    write_json_file(topic_selections_file(), $selections);
}

$researchAll = read_json_file(research_file());
if (isset($researchAll[$studentId])) {
    $researchAll[$studentId]['status'] = clean_text($_POST['research_status'] ?? 'Pending Admin Review');
    write_json_file(research_file(), $researchAll);
}

redirect_to('admin.php?status=saved');
