<?php
declare(strict_types=1);require __DIR__.'/portal-lib.php';$admin=require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
try{$action=(string)($_POST['action']??'');$service=subscription_entitlement_service();
 if($action==='create_campaign')$service->createCampaign($admin,$_POST);
 elseif($action==='campaign_status')$service->setCampaignStatus($admin,(string)$_POST['campaign_guid'],(string)$_POST['status']);
 elseif($action==='issue_invitation'){$issued=$service->issueInvitation($admin,(string)$_POST['campaign_guid'],(string)$_POST['yuva_id']);$_SESSION['subscription_invitation_code']=$issued['invitation_code'];}
 elseif($action==='grant')$service->grantPremium($admin,(string)$_POST['yuva_id'],$_POST['ends_at']??null);
 elseif($action==='revoke')$service->revoke($admin,(string)$_POST['entitlement_guid'],isset($_POST['block_future']));
 else throw new InvalidArgumentException('Unsupported action.');$_SESSION['subscription_notice']='Subscription action completed.';
}catch(Throwable $e){$_SESSION['subscription_error']=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'The subscription request was rejected safely.';error_log('YUVA subscription admin action failed correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($e));}
header('Location: admin.php#subscriptions',true,303);exit;
