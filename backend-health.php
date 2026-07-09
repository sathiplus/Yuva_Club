<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$response = [
    'ok' => false,
    'app' => 'Yuva Club',
    'database_configured' => database_settings_present(),
    'database_connected' => false,
    'database_driver' => db_driver(),
    'checked_at' => gmdate('c'),
];

if (!database_settings_present()) {
    $response['message'] = 'Database App Settings are not configured.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $stmt = db()->query('SELECT COUNT(*) AS program_count FROM programs');
    $row = $stmt->fetch();
    $response['ok'] = true;
    $response['database_connected'] = true;
    $response['program_count'] = (int) ($row['program_count'] ?? 0);
    $response['message'] = 'Database connection is healthy.';
} catch (Throwable $error) {
    http_response_code(500);
    $response['message'] = 'Database connection failed.';
}

echo json_encode($response, JSON_PRETTY_PRINT);
