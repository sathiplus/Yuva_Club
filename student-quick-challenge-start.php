<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$student=require_student();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
try{$state=quick_challenge_service()->startAttempt(normalize_yuva_id((string)$student['Yuva Club ID']),(string)($_POST['competition_guid']??''),(int)($_POST['competition_division_id']??0));$_SESSION['quick_challenge_attempt']=$state;$_SESSION['competition_student_notice']='Attempt '.$state['attempt_number'].' started. Server timing is active.';}catch(Throwable $error){$_SESSION['competition_student_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The Quick Challenge attempt was rejected safely.';error_log('YUVA quick challenge start failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: portal.php#quick-challenge-attempt',true,303);exit;
