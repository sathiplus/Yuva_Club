<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(2); }
// Explicit dry-run must override the scheduled default, including in artifacts.
if (count($argv) === 1) {
    $argv[] = '--execute';
}
require dirname(__DIR__, 4).'/backend/jobs/media-retention.php';
