<?php
require __DIR__ . '/portal-lib.php';

$student = require_student();
$studentId = normalize_yuva_id($student['Yuva Club ID'] ?? '');
$reportType = clean_text($_POST['report_type'] ?? '');
$message = trim((string) ($_POST['report_message'] ?? ''));

if ($studentId === '' || $reportType === '' || $message === '') {
    redirect_to('portal.php#safety-report');
}

$reports = safety_reports();
$reports[] = [
    'id' => 'YR' . date('YmdHis') . '-' . substr(hash('sha256', $studentId . microtime(true)), 0, 6),
    'student_id' => $studentId,
    'student_name' => student_display_name($student),
    'program_group' => student_program_group($student) === 'junior' ? 'School Yuva' : 'College Yuva',
    'parent_email' => $student['Parent Email'] ?? '',
    'type' => $reportType,
    'message' => substr($message, 0, 2000),
    'status' => 'Open',
    'submitted_at' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
];

write_json_file(safety_reports_file(), $reports);
redirect_to('portal.php?status=report-sent#safety-report');
