<?php
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
try{$action=(string)($_POST['action']??'');if($action==='definition_status')growth_profile_service()->setDefinitionStatus($admin,(string)($_POST['achievement_code']??''),(string)($_POST['status']??''));elseif($action==='revoke')growth_profile_service()->revoke($admin,(string)($_POST['achievement_guid']??''),(string)($_POST['reason']??''));else throw new RuntimeException('Unsupported achievement action.');$_SESSION['growth_admin_notice']='Achievement oversight action recorded.';}catch(Throwable $error){$_SESSION['growth_admin_error']='The achievement action was rejected safely.';}redirect_to('admin.php#growth-oversight');
