<?php
require __DIR__ . '/portal-lib.php';

$token = (string) ($_GET['token'] ?? ($_POST['token'] ?? ''));
$record = password_reset_token_record($token);
$status = $_GET['status'] ?? '';
$accountType = (string) ($record['account_type'] ?? 'student');
$loginUrl = password_reset_login_url($accountType);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('reset-password.php?status=security-error&token=' . rawurlencode($token));
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $policyError = password_policy_error($password);

    if ($record === null) {
        redirect_to('reset-password.php?status=invalid');
    }
    if ($password !== $confirmPassword || $policyError !== '') {
        redirect_to('reset-password.php?status=password-error&token=' . rawurlencode($token));
    }

    if (complete_password_reset($token, $password)) {
        redirect_to($loginUrl . '?status=password-reset');
    }

    redirect_to('reset-password.php?status=invalid');
}

portal_header('Reset Password');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Account Security</p>
        <h1>Create New Password</h1>
        <p>Use at least 12 characters with uppercase, lowercase, number, and special character.</p>
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
          <div class="field">
            <label for="password">New Password *</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
          </div>
          <div class="field">
            <label for="confirm_password">Confirm New Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password">
          </div>
          <button class="button primary" type="submit">Update Password</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
