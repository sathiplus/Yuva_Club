<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';
require_parent_student();
$status=clean_text((string)($_GET['status']??''));
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf_token']??null)){redirect_to('parent-confirm-password.php?status=error');}
    $userId=(int)($_SESSION['parent_user_id']??0);
    if(parent_credential_service()->verifyPassword($userId,(string)($_POST['password']??''))){
        session_regenerate_id(true);$_SESSION['parent_authenticated_at']=time();
        $return=(string)($_SESSION['parent_recent_auth_return']??'parent.php');unset($_SESSION['parent_recent_auth_return']);redirect_to($return);
    }
    redirect_to('parent-confirm-password.php?status=error');
}
portal_header('Confirm Parent Password');
?>
<main><section class="band"><div class="form-shell portal-narrow"><div class="section-head"><p class="eyebrow">Account Security</p><h1>Confirm Your Password</h1><p>Confirm your password before continuing with this sensitive action.</p></div><?php if($status==='error'): ?><div class="form-status error">Authentication failed. Check your credentials and try again.</div><?php endif; ?><form class="form-card" method="post"><?php echo csrf_field(); ?><div class="field"><label for="password">Password *</label><input id="password" name="password" type="password" required autocomplete="current-password"><button class="password-visibility-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false">Show Password</button></div><button class="button primary" type="submit">Continue</button></form></div></section></main>
<?php portal_footer(); ?><script src="assets/password-visibility.js" defer></script>
