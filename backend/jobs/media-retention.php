<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(2); }
$options = array_slice($argv, 1);
if (array_diff($options, ['--execute', '--dry-run']) !== []
    || (in_array('--execute', $options, true) && in_array('--dry-run', $options, true))) {
    fwrite(STDERR, "Use either --dry-run or --execute\n");
    exit(2);
}
require dirname(__DIR__, 2).'/portal-lib.php';

$mode = in_array('--execute', $argv, true) ? 'execute' : 'dry-run';
$runId = bin2hex(random_bytes(12));
$days = media_retention_days();
$pdo = null;
try {
    if (database_settings_present() && db_is_sqlsrv()) {
        $pdo = db();
        $pdo->beginTransaction();
        db_acquire_application_lock($pdo, 'yuva-media-retention', 0, 'Transaction');
    }
    $result = media_retention_service()->run($days, $mode === 'dry-run', $runId);
    if ($pdo?->inTransaction()) $pdo->commit();
    echo json_encode($result, JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(($result['status'] ?? '') === 'partial' ? 1 : 0);
} catch (Throwable $error) {
    if ($pdo?->inTransaction()) db_safe_rollback($pdo);
    error_log('YUVA media retention failed correlation='.$runId.' exception_type='.get_class($error));
    echo json_encode(['run_id'=>$runId,'mode'=>$mode,'status'=>'failed','failure_category'=>'retention_run_failed']), PHP_EOL;
    exit(1);
}
