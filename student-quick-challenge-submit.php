<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$student=require_student();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
try{quick_challenge_service()->submitAttempt(normalize_yuva_id((string)$student['Yuva Club ID']),(string)($_POST['attempt_guid']??''),(string)($_POST['row_version']??''),(string)($_POST['response_text']??''));unset($_SESSION['quick_challenge_attempt']);$_SESSION['competition_student_notice']='Quick Challenge attempt submitted and locked.';}catch(Throwable $error){$_SESSION['competition_student_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The Quick Challenge submission was rejected safely.';error_log('YUVA quick challenge submit failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: portal.php#app-challenges',true,303);exit;
