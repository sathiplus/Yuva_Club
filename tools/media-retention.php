<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(2); }
// Developer compatibility entry point; production uses the packaged runner.
require dirname(__DIR__).'/backend/jobs/media-retention.php';
