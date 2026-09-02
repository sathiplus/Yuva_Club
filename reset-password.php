<?php
require __DIR__ . '/portal-lib.php';

$token = (string) ($_GET['token'] ?? ($_POST['token'] ?? ''));
$requestedAccount = clean_text((string) ($_GET['account'] ?? ($_POST['account'] ?? '')));
$record = null;
try {
    if ($requestedAccount === 'parent') $record = parent_credential_service()->tokenRecord($token, 'password_reset');
    elseif ($requestedAccount === 'student' && portal_auth_mode() === 'sql') $record = student_credential_service()->tokenRecord($token, 'password_reset');
    else $record = password_reset_token_record($token);
}
catch(Throwable $error) { error_log('YUVA password reset lookup failed exception_type='.get_class($error)); }
$status = $_GET['status'] ?? '';
$accountType = $requestedAccount === 'parent' ? 'parent' : (string) ($record['account_type'] ?? 'student');
$loginUrl = password_reset_login_url($accountType);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('reset-password.php?status=security-error&account=' . rawurlencode($accountType) . '&token=' . rawurlencode($token));
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $policyError = $accountType === 'student'
        ? student_password_policy_error($password)
        : password_policy_error($password);

    if ($record === null) {
        redirect_to('reset-password.php?status=invalid');
    }
    if ($password !== $confirmPassword || $policyError !== '') {
        redirect_to('reset-password.php?status=password-error&account=' . rawurlencode($accountType) . '&token=' . rawurlencode($token));
    }

    try {
        if ($accountType === 'parent') $completed = parent_credential_service()->consume($token, 'password_reset', $password);
        elseif ($accountType === 'student' && portal_auth_mode() === 'sql') $completed = student_credential_service()->consume($token, 'password_reset', $password);
        else $completed = complete_password_reset($token, $password);
    }
    catch(Throwable $error) { $completed=false; error_log('YUVA password reset completion failed exception_type='.get_class($error)); }
    if ($completed) {
        redirect_to($loginUrl . '?status=password-reset');
    }

    redirect_to('reset-password.php?status=invalid');
}

portal_header('Reset Password', false, ['assets/public-site.css?v=release-1.0.2-20260802'], true);
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Account Security</p>
        <h1>Create New Password</h1>
        <p>Use at least <?php echo $accountType === 'student' ? '8' : '12'; ?> characters with uppercase, lowercase, number, and special character.</p>
      </div>

      <?php if ($status === 'invalid' || $record === null): ?>
        <div class="form-status error">This password reset link is invalid or expired. Please request a new link.</div>
        <p><a class="button primary" href="forgot-password.php">Request New Reset Link</a></p>
      <?php else: ?>
        <?php if ($status === 'password-error'): ?>
          <div class="form-status error">Passwords must match and meet the YUVA Club password policy.</div>
        <?php elseif ($status === 'security-error'): ?>
          <div class="form-status error">This reset form expired. Please try again.</div>
        <?php endif; ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="token" value="<?php echo e($token); ?>">
          <input type="hidden" name="account" value="<?php echo e($accountType); ?>">
          <div class="field">
            <label for="password">New Password *</label>
            <input id="password" name="password" type="password" minlength="<?php echo $accountType === 'student' ? '8' : '12'; ?>" required autocomplete="new-password">
            <button class="password-visibility-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false">Show Password</button>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm New Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="<?php echo $accountType === 'student' ? '8' : '12'; ?>" required autocomplete="new-password">
            <button class="password-visibility-toggle" type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-pressed="false">Show Password</button>
          </div>
          <button class="button primary" type="submit">Update Password</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/password-visibility.js" defer></script>
<?php portal_footer(false, true); ?>
