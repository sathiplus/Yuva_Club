<?php
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN]);$studentId=normalize_yuva_id($_POST['student_id']??'');$repo=delivery_review_repository();$latest=$repo->findLatest($studentId);$review=$latest['review']??[];
if(!ai_mentor_feature_enabled('media_analysis_enabled')){http_response_code(404);exit('Not available.');}
if(($latest['status']??'')!=='Draft'||!is_array($review))redirect_to('admin.php?status=delivery-stale#ai-mentor-delivery-reviews');
foreach(['summary','pacing_feedback','pause_feedback','clarity_feedback','filler_word_feedback','visual_feedback','recommended_next_step','admin_notes'] as $field)$review[$field]=trim((string)($_POST[$field]??$review[$field]??''));
foreach(['strengths','improvements','pronunciation_practice','emphasis_opportunities'] as $field)$review[$field]=array_values(array_filter(array_map('trim',preg_split('/\R/',(string)($_POST[$field]??implode("\n",$review[$field]??[])))?:[])));
$timecoded=json_decode((string)($_POST['timecoded_coaching_json']??'[]'),true);if(!is_array($timecoded))redirect_to('admin.php?status=delivery-invalid#ai-mentor-delivery-reviews');$review['timecoded_coaching']=$timecoded;
$review['suggested_tokens']=(int)($_POST['suggested_tokens']??$review['suggested_tokens']??0);$valid=(new \YuvaClub\Delivery\DeliveryReviewValidator())->validate($review);$adminId=find_sql_admin_user_id((string)$admin['email']);
$saved=($valid['ok']??false)&&$repo->saveAdminEdit($studentId,$valid['review'],(string)($_POST['version']??''),$adminId);
redirect_to('admin.php?status='.($saved?'delivery-saved':'delivery-stale').'#ai-mentor-delivery-reviews');
