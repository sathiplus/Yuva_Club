<?php
declare(strict_types=1);
require_once __DIR__.'/../../backend/beta-invitation-email.php';
$canonical='https://www.yuvaclub.app';
function app_url():string {return $GLOBALS['canonical'];}
function inviteCheck(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);}
$code='YUVA-BETA-'.strtoupper(bin2hex(random_bytes(12)));
$url=beta_invitation_redemption_url();
$mail=beta_invitation_email($code,'2026-09-10 12:34:56',$url);
inviteCheck($mail['subject']==="You're invited to YUVA Club Beta",'Subject');
foreach(['text','html'] as $kind) {
    inviteCheck(str_contains($mail[$kind],$code),'Code readable in intended email');
    inviteCheck(str_contains($mail[$kind],'2026-09-10 12:34 UTC'),'Database expiry in copy');
    inviteCheck(str_contains($mail[$kind],'Sign in'),'Signed-in instructions');
}
inviteCheck(str_contains($mail['html'],'Activate Beta Access'),'CTA');
inviteCheck(!str_contains($url,$code)&&parse_url($url,PHP_URL_QUERY)===null,'Secret-free URL');
foreach(['http://www.yuvaclub.app','https://evil.example','https://www.yuvaclub.app@evil.example','https://www.yuvaclub.app/?token=x'] as $bad) {
    $canonical=$bad;$rejected=false;
    try{beta_invitation_redemption_url();}catch(RuntimeException){$rejected=true;}
    inviteCheck($rejected,'Untrusted canonical origin rejected');
}
$canonical='https://yuvaclub.app';
inviteCheck(beta_invitation_redemption_url()===$canonical.'/portal.php#app-profile','Canonical apex accepted');
$root=dirname(__DIR__,2);
$action=file_get_contents($root.'/admin-subscription-action.php');
$panel=file_get_contents($root.'/subscription-admin-panel.php');
$service=file_get_contents($root.'/backend/subscription-entitlement.php');
inviteCheck(str_contains($action,'require_admin_post([YUVA_ROLE_MASTER_ADMIN])'),'Master Admin POST and CSRF');
inviteCheck(!str_contains($action,"['invitation_code']")&&!str_contains($panel,'subscriptionInvitationCode'),'No code in Admin session/UI');
inviteCheck(!str_contains($action,'getMessage()'),'No exception-message disclosure');
inviteCheck(str_contains($service,'send_yuva_email($to,$subject,$text,\'\',$html)'),'Existing provider abstraction');
inviteCheck(str_contains($service,'InvitationEmailAttempted')&&str_contains($service,'InvitationEmailFailed'),'Safe delivery audit');
$redeem=file_get_contents($root.'/student-promo-redeem.php');
inviteCheck(str_contains($redeem,'require_student_post()'),'Authenticated Student/CSRF redemption unchanged');
inviteCheck(!str_contains($service,'Stripe')&&!str_contains($service,'billing'),'No billing integration');
echo "PASS Beta invitation email content, canonical URL, privacy and authorization contracts\n";
