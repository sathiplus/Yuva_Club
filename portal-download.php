<?php
require __DIR__ . '/portal-lib.php';
$requestedId = normalize_yuva_id($_GET['id'] ?? '');
$studentId = normalize_yuva_id(logged_in_student_id() ?? '');
$isAdmin = ($_SESSION['admin_logged_in'] ?? false) === true;

if (!$isAdmin && $studentId !== $requestedId) {
    http_response_code(403);
    exit('Access denied.');
}

$researchAll = read_json_file(research_file());
$research = $researchAll[$requestedId] ?? null;
if (!$research || empty($research['file_stored'])) {
    http_response_code(404);
    exit('File not found.');
}

$file = portal_path('portal-uploads') . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_-]/', '_', $requestedId) . DIRECTORY_SEPARATOR . basename($research['file_stored']);
if (!is_file($file)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($research['file_original'] ?? $research['file_stored']) . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
