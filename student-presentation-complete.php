<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
$student=require_student();$yuvaId=(string)$student['Yuva Club ID'];$researchAll=read_json_file(research_file());
try{$result=presentation_verification_service()->submitCompleted($yuvaId,$researchAll[$yuvaId]??[]);leadership_eligibility_service()->evaluateByYuvaId($yuvaId);$_SESSION['leadership_notice']=$result['status']==='already-submitted'?'This completed presentation is already awaiting or has received verification.':'Your completed presentation was submitted for human verification.';}catch(Throwable $e){$_SESSION['leadership_error']=$e instanceof InvalidArgumentException?$e->getMessage():'The completed presentation could not be submitted safely.';error_log('YUVA presentation completion failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($e));}
header('Location: portal.php#app-progress',true,303);exit;
