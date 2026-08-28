<?php
declare(strict_types=1);require __DIR__.'/portal-lib.php';$student=require_student_post();$yuva=(string)$student['Yuva Club ID'];
try{subscription_entitlement_service()->redeem($yuva,(string)($_POST['invitation_code']??''));$_SESSION['subscription_student_notice']='Premium invitation redeemed.';}catch(Throwable $e){$_SESSION['subscription_student_error']='This invitation is invalid, expired, already used, or unavailable.';error_log('YUVA promo redemption rejected correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($e));}
header('Location: portal.php#app-profile',true,303);exit;
