<?php
require __DIR__.'/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN]);$studentId=normalize_yuva_id($_POST['student_id']??'');$record=read_json_file(presentation_media_file())[$studentId]??[];
if(!ai_mentor_feature_enabled('media_analysis_enabled')){http_response_code(404);exit('Not available.');}
try{$media=presentation_media_resolver()->resolve($studentId,is_array($record)?$record:[]);$latest=delivery_review_repository()->findLatest($studentId);$hash=$media->sourceRevisionHash((float)($latest['media_duration_seconds']??0),(string)($latest['transcription_model']??openai_transcription_model_name()));$result=delivery_review_repository()->apply($studentId,$hash,find_sql_admin_user_id((string)$admin['email']));}
catch(Throwable){$result='missing';}
redirect_to('admin.php?status=delivery-'.rawurlencode($result).'#ai-mentor-delivery-reviews');
