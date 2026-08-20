<?php
require __DIR__.'/portal-lib.php';
require_parent_student();
if(!ai_mentor_feature_enabled('media_analysis_enabled')){http_response_code(404);exit('Not available.');}
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){redirect_to('parent.php?status=security-error#parent-mentor');}
if(($_POST['consent_version']??'')!==\YuvaClub\Delivery\MediaConsentService::VERSION){redirect_to('parent.php?status=consent-version-error#parent-mentor');}
$studentId=normalize_yuva_id((string)($_SESSION['parent_student_id']??''));$parentEmail=normalize_email((string)($_SESSION['parent_email']??''));$action=(string)($_POST['action']??'');
try{
    if($studentId===''||$parentEmail==='')throw new RuntimeException('Parent session identity unavailable.');
    $service=media_consent_service();
    if($action==='grant')$service->grantParent($studentId,$parentEmail);elseif($action==='withdraw'){$service->withdrawParent($studentId,$parentEmail);delivery_review_repository()->markUnappliedStale($studentId,'parent_media_consent_withdrawn');}else throw new RuntimeException('Invalid action.');
    audit_log_event($parentEmail,YUVA_ROLE_PARENT,null,'parent.media_ai_consent.'.$action,'student',$studentId,true,['consent_version'=>\YuvaClub\Delivery\MediaConsentService::VERSION]);
    redirect_to('parent.php?status=media-consent-'.$action.'ed#parent-mentor');
}catch(Throwable $error){error_log('YUVA parent media consent failed exception_type='.get_class($error));redirect_to('parent.php?status=media-consent-error#parent-mentor');}
