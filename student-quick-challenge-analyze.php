<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$student=require_student();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
try{$result=quick_challenge_evaluation_service()->analyze(normalize_yuva_id((string)$student['Yuva Club ID']),(string)($_POST['attempt_guid']??''));$_SESSION['competition_student_notice']=($result['status']??'')==='completed'?'Your AI practice score and coaching are ready.':(($result['status']??'')==='disabled'?'AI coaching is not enabled for this challenge.':'Your analysis request is already being handled.');}
catch(Throwable $error){$_SESSION['competition_student_error']='The AI practice analysis was rejected safely.';error_log('YUVA quick challenge analysis failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: portal.php#app-challenges',true,303);exit;
