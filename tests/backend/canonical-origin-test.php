<?php
declare(strict_types=1);
require __DIR__.'/../../backend/config.php';
function assertOrigin(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
putenv('APP_ENV=production');
foreach (['https://www.yuvaclub.net','https://yuvaclub.app','https://www.yuvaclub.app/','https://host.invalid'] as $configured) {
    putenv('APP_URL='.$configured);
    assertOrigin(app_url()==='https://www.yuvaclub.app','production has one canonical origin');
}
putenv('APP_ENV=test');putenv('APP_URL=http://localhost:8080');
assertOrigin(app_url()==='http://localhost:8080','explicit test origin preserved');
$source=(string)file_get_contents(__DIR__.'/../../portal-lib.php');
preg_match('/function public_base_url\(\): string \{.*?^\}/ms',$source,$match);
eval($match[0]);
putenv('APP_ENV=production');putenv('APP_URL=https://www.yuvaclub.net');
$_SERVER['HTTP_HOST']='attacker.invalid';$_SERVER['HTTP_X_FORWARDED_PROTO']='http';
assertOrigin(public_base_url()==='https://www.yuvaclub.app','email link base ignores hostile request host and stale environment');
assertOrigin(str_contains($source,"return public_base_url() . '/parent-activate.php?token='"),'parent activation uses canonical base');
assertOrigin(str_contains($source,"return public_base_url() . '/reset-password.php?token='"),'legacy reset links use canonical base');
foreach (['parent_activation_url' => '/parent-activate.php?token=', 'organization_admin_invitation_url' => '/organization-admin-activate.php?token='] as $function => $route) {
    preg_match('/function '.preg_quote($function, '/').'\\(.*?^\\}/ms',$source,$definition);
    if (!isset($definition[0])) throw new RuntimeException('URL builder missing');
    eval($definition[0]);
    assertOrigin($function('synthetic/token')==='https://www.yuvaclub.app'.$route.'synthetic%2Ftoken','actual activation builder uses canonical origin and encodes token');
}
$reset=(string)file_get_contents(__DIR__.'/../../forgot-password.php');
foreach (['parent','student'] as $role) assertOrigin(str_contains($reset,"public_base_url() . '/reset-password.php?account=".$role."&token='"),'SQL reset uses canonical base for '.$role);
echo "PASS canonical production and local origins\n";
