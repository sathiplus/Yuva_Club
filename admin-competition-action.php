<?php
declare(strict_types=1);
require __DIR__.'/portal-lib.php';
$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN]);
$target=$admin['role']===YUVA_ROLE_ORGANIZATION_ADMIN?'organization-admin.php#challenges':'admin.php#challenges';
try{
    $action=(string)($_POST['action']??'create');
    if($action==='create'){
        $input=$_POST;
        if($admin['role']===YUVA_ROLE_ORGANIZATION_ADMIN){$input['scope_type']='organization';$input['organization_code']=$admin['organization_id'];}
        elseif(($input['scope_type']??'')==='organization'){
            $requested=normalize_organization_id((string)($input['organization_code']??''));$known=false;
            foreach(organization_admin_accounts()as$account){if(($account['status']??'')==='active'&&normalize_organization_id((string)($account['organization_id']??''))===$requested){$known=true;break;}}
            if(!$known)throw new InvalidArgumentException('Select an active approved organization.');$input['organization_code']=$requested;
        }
        competition_foundation_service()->create($admin,$input);
        $_SESSION['competition_admin_notice']='Challenge created in Draft status.';
    }elseif($action==='transition'){
        competition_foundation_service()->transition($admin,(string)($_POST['competition_guid']??''),(string)($_POST['target_status']??''),(string)($_POST['row_version']??''));
        $_SESSION['competition_admin_notice']='Challenge status updated.';
    }else throw new InvalidArgumentException('Unsupported challenge action.');
}catch(Throwable $error){$_SESSION['competition_admin_error']=$error instanceof InvalidArgumentException||$error instanceof RuntimeException?$error->getMessage():'The challenge request was rejected safely.';error_log('YUVA competition admin action failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
header('Location: '.$target,true,303);exit;
