<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$student=require_student();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
$yuvaId=normalize_yuva_id((string)$student['Yuva Club ID']);
try{$result=competition_foundation_service()->join($yuvaId,(string)($_POST['competition_guid']??''),(int)($_POST['competition_division_id']??0));$_SESSION['competition_student_notice']=$result['status']==='already-entered'?'You already joined this challenge.':'Challenge joined.';}catch(Throwable $error){$_SESSION['competition_student_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The challenge entry was rejected safely.';error_log('YUVA competition entry failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: portal.php#app-challenges',true,303);exit;
