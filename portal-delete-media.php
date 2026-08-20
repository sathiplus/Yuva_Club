<?php
require __DIR__.'/portal-lib.php';
$student=require_student();
$studentId=normalize_yuva_id((string)$student['Yuva Club ID']);
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf_token($_POST['csrf_token']??null)){redirect_to('portal.php?status=security-error#app-present');}
$records=read_json_file(presentation_media_file());$record=$records[$studentId]??null;
$active=is_array($record)&&($record['retention_status']??'Active')==='Active';
$deleted=$active&&presentation_media_manager()->delete($studentId,$record);
if($deleted){
    $records[$studentId]['retention_status']='StudentDeleted';
    $records[$studentId]['media_deleted_at']=gmdate('c');
    $records[$studentId]['deletion_reason']='student_requested';
    $records[$studentId]['updated_at']=gmdate('c');
    write_json_file(presentation_media_file(),$records);
}
try{if(database_settings_present()&&db_is_sqlsrv())delivery_review_repository()->markUnappliedStale($studentId,'media_deleted');}catch(Throwable $error){error_log('YUVA media deletion state update failed exception_type='.get_class($error));}
audit_log_event($studentId,YUVA_ROLE_STUDENT,student_organization_id($student),'student.presentation_media.delete','student',$studentId,$deleted,[]);
redirect_to('portal.php?status='.($deleted?'media-deleted':'media-missing').'#app-present');
