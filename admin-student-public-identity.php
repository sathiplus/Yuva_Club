<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
$admin=require_admin_post();
$yuvaId=normalize_yuva_id((string)($_POST['yuva_id']??''));
try{
    $adminUserId=find_sql_admin_user_id((string)($admin['email']??YUVA_PLATFORM_ADMIN_EMAIL));
    if($adminUserId===null)throw new RuntimeException('Master Admin SQL identity is unavailable.');
    public_identity_service()->adminOverride($yuvaId,(string)($_POST['public_handle']??''),$adminUserId,(string)($_POST['reason']??''));
    audit_log_event((string)$admin['id'],$admin['role'],$admin['organization_id'],'master_admin.public_identity.override','student',$yuvaId,true);
    redirect_to('admin.php?status=identity-moderated#students');
}catch(Throwable $error){
    audit_log_event((string)$admin['id'],$admin['role'],$admin['organization_id'],'master_admin.public_identity.override','student',$yuvaId,false,['reason'=>'rejected']);
    redirect_to('admin.php?status=identity-moderation-failed#students');
}
