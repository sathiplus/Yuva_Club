<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){http_response_code(403);exit('Request rejected.');}
$student=require_student();$yuvaId=(string)$student['Yuva Club ID'];
try{leadership_eligibility_service()->addReflection($yuvaId,$_POST);leadership_eligibility_service()->evaluateByYuvaId($yuvaId);$_SESSION['leadership_notice']='Your reflection was saved as leadership evidence.';}catch(Throwable $e){$_SESSION['leadership_error']=$e instanceof InvalidArgumentException?$e->getMessage():'The reflection could not be saved safely.';error_log('YUVA leadership reflection failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($e));}
header('Location: portal.php#app-progress',true,303);exit;
