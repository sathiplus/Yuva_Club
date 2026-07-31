<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/config.php';

$healthy = mutable_storage_is_healthy();
http_response_code($healthy ? 200 : 503);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'status' => $healthy ? 'ok' : 'unavailable',
], JSON_UNESCAPED_SLASHES);
