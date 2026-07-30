<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);

$studentId = normalize_yuva_id($_POST['student_id'] ?? '');
if ($studentId === '') {
    redirect_to('admin.php');
}

/**
 * @param array<string, mixed> $post
 */
function admin_student_post_value(
    array $post,
    string $field,
    mixed $existingValue
): mixed {
    if (!array_key_exists($field, $post)) {
        return $existingValue;
    }
    if (!is_scalar($post[$field]) && $post[$field] !== null) {
        redirect_to('admin.php');
    }

    return clean_text((string) ($post[$field] ?? ''));
}

/**
 * @param array<string, mixed> $post
 * @param list<string> $allowedValues
 */
function admin_student_status_value(
    array $post,
    string $field,
    mixed $existingValue,
    array $allowedValues
): mixed {
    if (!array_key_exists($field, $post)) {
        return $existingValue;
    }
    if (!is_string($post[$field])) {
        redirect_to('admin.php');
    }

    $value = clean_text($post[$field]);
    if (!in_array($value, $allowedValues, true)) {
        redirect_to('admin.php');
    }

    return $value;
}

$records = read_json_file(portal_records_file());
$existingRecord = $records[$studentId] ?? student_record($studentId);
$updatedRecord = $existingRecord;
$recordChanged = false;

$statusFields = [
    'approved' => ['Pending', 'Approved', 'Waitlist', 'Inactive'],
    'certificate_status' => ['Not Ready', 'Ready', 'Issued'],
    'student_session_status' => ['Closed', 'Open', 'Starting Soon', 'Completed'],
    'current_rank' => array_keys(rank_definitions()),
    'rank_status' => [
        'Approved',
        'Eligible for Review',
        'Needs More Evidence',
        'Pending Mentor Review',
    ],
    'reward_status' => [
        'Not Yet',
        'Bronze Reward',
        'Silver Reward',
        'Gold Reward',
        'Gift Eligible',
        'Gift Sent',
    ],
    'challenge_stage' => challenge_stages(),
    'finalist_status' => ['Not Qualified', 'Eligible', 'Finalist', 'Champion'],
    'award_status' => [
        'None',
        'Badge Earned',
        'Certificate Ready',
        'Trophy Eligible',
        'Award Issued',
    ],
];
foreach ($statusFields as $field => $allowedValues) {
    if (!array_key_exists($field, $_POST)) {
        continue;
    }
    $updatedRecord[$field] = admin_student_status_value(
        $_POST,
        $field,
        $existingRecord[$field] ?? null,
        $allowedValues
    );
    $recordChanged = true;
}

$textFields = [
    'attendance',
    'presentations',
    'service_hours',
    'last_duration',
    'score',
    'teacher_feedback',
    'admin_notes',
    'student_session_title',
    'student_session_date',
    'student_session_start',
    'student_session_end',
    'student_zoom_url',
    'student_zoom_meeting_id',
    'student_zoom_password',
    'rank_recommendation',
    'mentor_feedback',
    'points',
    'tokens',
    'ai_feedback_summary',
    'communication_skills',
    'leadership_milestones',
    'challenge_region',
    'challenge_month',
    'judge_feedback',
];
foreach ($textFields as $field) {
    if (!array_key_exists($field, $_POST)) {
        continue;
    }
    $updatedRecord[$field] = admin_student_post_value(
        $_POST,
        $field,
        $existingRecord[$field] ?? null
    );
    $recordChanged = true;
}

foreach (array_keys(rubric_categories()) as $key) {
    $field = 'rubric_' . $key;
    if (!array_key_exists($field, $_POST)) {
        continue;
    }
    $updatedRecord[$field] = admin_student_post_value(
        $_POST,
        $field,
        $existingRecord[$field] ?? null
    );
    $recordChanged = true;
}

$topicStatus = admin_student_status_value(
    $_POST,
    'topic_status',
    null,
    ['Pending Admin Review', 'Approved', 'Needs Changes']
);
$researchStatus = admin_student_status_value(
    $_POST,
    'research_status',
    null,
    ['Pending Admin Review', 'Approved', 'Needs Changes']
);

if ($recordChanged) {
    $updatedRecord['updated_at'] = date('Y-m-d H:i:s');
    $records[$studentId] = $updatedRecord;
    write_json_file(portal_records_file(), $records);
}

$selections = read_json_file(topic_selections_file());
if (
    array_key_exists('topic_status', $_POST)
    && isset($selections[$studentId])
) {
    $selections[$studentId]['status'] = $topicStatus;
    write_json_file(topic_selections_file(), $selections);
}

$researchAll = read_json_file(research_file());
if (
    array_key_exists('research_status', $_POST)
    && isset($researchAll[$studentId])
) {
    $researchAll[$studentId]['status'] = $researchStatus;
    write_json_file(research_file(), $researchAll);
}

audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.student_record.update', 'student', $studentId, true);
redirect_to('admin.php?status=saved');
