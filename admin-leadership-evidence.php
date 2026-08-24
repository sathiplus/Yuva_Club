<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN]);$yuvaId=normalize_yuva_id((string)($_POST['yuva_id']??''));
try{if(($_POST['action']??'review')==='add'){leadership_eligibility_service()->addHumanEvidence($yuvaId,$admin,$_POST);}else{leadership_eligibility_service()->reviewEvidence($yuvaId,(string)($_POST['evidence_guid']??''),$admin,(string)($_POST['decision']??''),(string)($_POST['reason']??''));}leadership_eligibility_service()->evaluateByYuvaId($yuvaId);$_SESSION['leadership_admin_notice']='Leadership evidence decision recorded.';}catch(Throwable $e){$_SESSION['leadership_admin_error']=$e instanceof InvalidArgumentException?$e->getMessage():'The evidence decision was rejected safely.';error_log('YUVA leadership evidence failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($e));}
$target=$admin['role']===YUVA_ROLE_ORGANIZATION_ADMIN?'organization-admin.php#leadership-reviews':'admin.php#leadership-reviews';header('Location: '.$target,true,303);exit;
