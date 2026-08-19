<?php
require __DIR__.'/portal-lib.php';
$student=require_student();
$studentId=normalize_yuva_id((string)$student['Yuva Club ID']);
if(!ai_mentor_feature_enabled('media_analysis_enabled')){http_response_code(404);exit('Not available.');}
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){redirect_to('portal.php?status=security-error#app-present');}
if(($_POST['media_ai_acknowledgement']??'')!=='yes'||($_POST['consent_version']??'')!==\YuvaClub\Delivery\MediaConsentService::VERSION){redirect_to('portal.php?status=media-consent-required#app-present');}
try{$mediaConsent=media_consent_service()->acknowledgeStudent($studentId);}catch(Throwable){redirect_to('portal.php?status=media-consent-unavailable#app-present');}
if(!($mediaConsent['ready']??false)){redirect_to('portal.php?status=media-parent-consent-required#app-present');}
$upload=$_FILES['presentation_media']??null;
if(!is_array($upload)){redirect_to('portal.php?status=media-invalid#app-present');}
$tmp=(string)($upload['tmp_name']??''); $error=(int)($upload['error']??UPLOAD_ERR_NO_FILE); $size=(int)($upload['size']??0); $original=basename((string)($upload['name']??''));
$mime='';$prefix='';
if($error===UPLOAD_ERR_OK&&$tmp!==''&&is_uploaded_file($tmp)){$mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$bytes=file_get_contents($tmp,false,null,0,16);$prefix=is_string($bytes)?$bytes:'';}elseif($error===UPLOAD_ERR_OK){$error=UPLOAD_ERR_CANT_WRITE;}
$validation=(new \YuvaClub\Delivery\MediaUploadValidator())->validate($original,$size,$error,$mime,$prefix);
if(!($validation['ok']??false)){redirect_to('portal.php?status='.rawurlencode((string)($validation['code']??'invalid_media')).'#app-present');}
$sha=hash_file('sha256',$tmp); if(!is_string($sha)||strlen($sha)!==64){redirect_to('portal.php?status=media-invalid#app-present');}
$safeId=preg_replace('/[^A-Za-z0-9_-]/','_',$studentId);$root=portal_path('portal-uploads').DIRECTORY_SEPARATOR.$safeId.DIRECTORY_SEPARATOR.'media';
if(!is_dir($root)&&!mkdir($root,0750,true)&&!is_dir($root)){redirect_to('portal.php?status=media-storage-failed#app-present');}
$rootReal=realpath($root); if($rootReal===false){redirect_to('portal.php?status=media-storage-failed#app-present');}
$stored=gmdate('YmdHis').'-'.bin2hex(random_bytes(8)).'.'.(string)$validation['format'];$target=$rootReal.DIRECTORY_SEPARATOR.$stored;
if(!move_uploaded_file($tmp,$target)){redirect_to('portal.php?status=media-storage-failed#app-present');}
$records=read_json_file(presentation_media_file());$prior=$records[$studentId]??null;
$priorActive=is_array($prior)&&($prior['retention_status']??'Active')==='Active'&&!empty($prior['stored_filename']);
if($priorActive&&!presentation_media_manager()->delete($studentId,$prior)){@unlink($target);redirect_to('portal.php?status=media-storage-failed#app-present');}
$uploadedAt=gmdate('c');
$records[$studentId]=['original_filename'=>$original,'stored_filename'=>$stored,'media_reference'=>$safeId.'/media/'.$stored,'mime_type'=>strtolower($mime),'size_bytes'=>$size,'sha256'=>$sha,'format'=>$validation['format'],'status'=>'Pending Admin Review','retention_status'=>'Active','consent_version'=>\YuvaClub\Delivery\MediaConsentService::VERSION,'acknowledged_at'=>$uploadedAt,'uploaded_at'=>$uploadedAt,'updated_at'=>$uploadedAt];
write_json_file(presentation_media_file(),$records);
audit_log_event($studentId,YUVA_ROLE_STUDENT,student_organization_id($student),'student.presentation_media.upload','student',$studentId,true,['mime'=>strtolower($mime),'size_bytes'=>$size,'replaced'=>$priorActive]);
redirect_to('portal.php?status=media-saved#app-present');
