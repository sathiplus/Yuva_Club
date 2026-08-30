<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN]);
$target=$admin['role']===YUVA_ROLE_ORGANIZATION_ADMIN?'organization-admin.php#quick-challenges':'admin.php#quick-challenges';
try{
    $action=(string)($_POST['action']??'');
    if($action==='create_template'){
        if($admin['role']!==YUVA_ROLE_MASTER_ADMIN)throw new RuntimeException('Master Admin authorization is required.');
        quick_challenge_service()->createTemplate($admin,$_POST);$_SESSION['quick_challenge_admin_notice']='Quick Challenge template and frozen version created.';
    }elseif($action==='publish_template'){
        quick_challenge_service()->publishTemplate($admin,(string)($_POST['template_guid']??''),(string)($_POST['row_version']??''));$_SESSION['quick_challenge_admin_notice']='Quick Challenge template published.';
    }elseif($action==='create_instance'){
        $input=$_POST;if($admin['role']===YUVA_ROLE_ORGANIZATION_ADMIN){$input['scope_type']='organization';$input['organization_code']=$admin['organization_id'];$input['experience_mode']='quick_practice';}
        elseif(($input['scope_type']??'practice')==='organization'){
            $requested=strtoupper(trim((string)($input['organization_code']??'')));$known=false;
            foreach(organization_admin_accounts() as $account){if(($account['status']??'active')==='active'&&hash_equals(strtoupper((string)($account['organization_id']??'')),$requested)){$known=true;break;}}
            if(!$known)throw new InvalidArgumentException('A known active organization is required.');
            $input['organization_code']=$requested;
        }
        quick_challenge_service()->createInstance($admin,$input);$_SESSION['quick_challenge_admin_notice']='Quick Challenge instance created in Draft status.';
    }else throw new InvalidArgumentException('Unsupported Quick Challenge action.');
}catch(Throwable $error){$_SESSION['quick_challenge_admin_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The Quick Challenge request was rejected safely.';error_log('YUVA quick challenge admin action failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: '.$target,true,303);exit;
