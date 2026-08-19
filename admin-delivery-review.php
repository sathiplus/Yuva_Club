<?php
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
if(!ai_mentor_feature_enabled('media_analysis_enabled')){http_response_code(404);exit('Not available.');}
$studentId=normalize_yuva_id($_POST['student_id']??'');$records=read_json_file(presentation_media_file());$record=$records[$studentId]??[];
if($studentId===''||!is_array($record)||$record===[]){redirect_to('admin.php?status=delivery-missing#ai-mentor-delivery-reviews');}
try{if(!(media_consent_service()->status($studentId)['ready']??false))throw new RuntimeException('Media consent is not current.');$media=presentation_media_resolver()->resolve($studentId,$record);$repo=delivery_review_repository();$id=$repo->createProcessing($studentId,$media);$result=delivery_review_service()->analyze($media);$repo->complete($id,$result);$ok=($result['ok']??false)===true;}
catch(Throwable $error){error_log('YUVA delivery review failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));$ok=false;}
audit_log_event($admin['id'],$admin['role'],$admin['organization_id'],'admin.delivery_review.create','student',$studentId,$ok,[]);
redirect_to('admin.php?status='.($ok?'delivery-reviewed':'delivery-error').'#ai-mentor-delivery-reviews');
