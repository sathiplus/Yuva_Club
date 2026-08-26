<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$student=require_student();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
$yuvaId=normalize_yuva_id((string)$student['Yuva Club ID']);$research=read_json_file(research_file())[$yuvaId]??[];
try{$result=competition_foundation_service()->lockResearch($yuvaId,(string)($_POST['entry_guid']??''),$research);$_SESSION['competition_student_notice']=$result['status']==='already-locked'?'Your official submission is already locked.':'Official competition submission locked.';}catch(Throwable $error){$_SESSION['competition_student_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The competition submission was rejected safely.';error_log('YUVA competition submission failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: portal.php#app-challenges',true,303);exit;
