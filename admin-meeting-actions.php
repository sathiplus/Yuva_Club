<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$selectedStudents = $_POST['selected_students'] ?? [];
if (!is_array($selectedStudents) || $selectedStudents === []) {
    redirect_to('admin.php?status=meeting-empty');
}

$records = read_json_file(portal_records_file());
$cleared = [
    'student_session_title' => '',
    'student_session_date' => '',
    'student_session_start' => '',
    'student_session_end' => '',
    'student_session_status' => 'Closed',
    'student_zoom_url' => '',
    'student_zoom_meeting_id' => '',
    'student_zoom_password' => '',
    'updated_at' => date('Y-m-d H:i:s'),
];

foreach ($selectedStudents as $studentId) {
    $studentId = normalize_yuva_id((string) $studentId);
    if ($studentId === '' || !isset($records[$studentId])) {
        continue;
    }

    $records[$studentId] = array_merge($records[$studentId], $cleared);
}

write_json_file(portal_records_file(), $records);
redirect_to('admin.php?status=meeting-updated');
