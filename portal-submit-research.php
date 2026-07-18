<?php
require __DIR__ . '/portal-lib.php';
$student = require_student();
$studentId = $student['Yuva Club ID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('portal.php?status=security-error');
}

$researchAll = read_json_file(research_file());
$existing = $researchAll[$studentId] ?? [];
$record = [
    'research_notes' => clean_text($_POST['research_notes'] ?? ''),
    'sources_used' => clean_text($_POST['sources_used'] ?? ''),
    'presentation_outline' => clean_text($_POST['presentation_outline'] ?? ''),
    'prepared_questions' => clean_text($_POST['prepared_questions'] ?? ''),
    'status' => $existing['status'] ?? 'Pending Admin Review',
    'updated_at' => date('Y-m-d H:i:s'),
];
$researchChanged = $existing === []
    || ($existing['research_notes'] ?? '') !== $record['research_notes']
    || ($existing['sources_used'] ?? '') !== $record['sources_used']
    || ($existing['presentation_outline'] ?? '') !== $record['presentation_outline']
    || ($existing['prepared_questions'] ?? '') !== $record['prepared_questions'];

if (in_array('', [$record['research_notes'], $record['sources_used'], $record['presentation_outline'], $record['prepared_questions']], true)) {
    redirect_to('portal.php?status=error');
}

if (!empty($_FILES['research_file']['name']) && is_uploaded_file($_FILES['research_file']['tmp_name'])) {
    $original = basename((string) $_FILES['research_file']['name']);
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    if (!in_array($extension, $allowed, true)) {
        if ($researchChanged) {
            $record['status'] = 'Pending Admin Review';
        }
        $researchAll[$studentId] = array_merge($existing, $record);
        write_json_file(research_file(), $researchAll);
        if ($researchChanged) {
            mark_ai_review_stale($studentId, 'Research Changed');
        }
        redirect_to('portal.php?status=upload-error');
    }

    $studentUploadDir = portal_path('portal-uploads') . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_-]/', '_', $studentId);
    if (!is_dir($studentUploadDir)) {
        mkdir($studentUploadDir, 0755, true);
    }
    $storedName = date('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
    $target = $studentUploadDir . DIRECTORY_SEPARATOR . $storedName;
    if (move_uploaded_file($_FILES['research_file']['tmp_name'], $target)) {
        $record['file_original'] = $original;
        $record['file_stored'] = $storedName;
        $researchChanged = true;
    }
} else {
    $record['file_original'] = $existing['file_original'] ?? '';
    $record['file_stored'] = $existing['file_stored'] ?? '';
}

$record['status'] = $researchChanged ? 'Pending Admin Review' : ($existing['status'] ?? 'Pending Admin Review');
$researchAll[$studentId] = $record;
write_json_file(research_file(), $researchAll);
if ($researchChanged) {
    mark_ai_review_stale($studentId, 'Research Changed');
}
redirect_to('portal.php?status=research-saved');
