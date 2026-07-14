<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
$accountType = clean_text($_GET['account'] ?? ($_POST['account_type'] ?? 'student'));
if (!in_array($accountType, ['student', 'parent', 'admin'], true)) {
    $accountType = 'student';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('forgot-password.php?status=security-error&account=' . rawurlencode($accountType));
    }

    $email = normalize_email($_POST['email'] ?? '');
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $rateKey = 'reset:' . $accountType . ':' . $email;
        if (!login_rate_limited($rateKey)) {
            request_password_reset($email, $accountType);
        }
        record_login_attempt($rateKey, false);
    }

    redirect_to('forgot-password.php?status=sent&account=' . rawurlencode($accountType));
}

$labels = [
    'student' => 'Student account',
    'parent' => 'Parent account',
    'admin' => 'Administrator account',
];

portal_header('Forgot Password');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Account Security</p>
        <h1>Reset Password</h1>
        <p>Enter the email address for your YUVA Club account. If the account is active, we will send a secure reset link.</p>
      </div>

      <?php if ($status === 'sent'): ?>
        <div class="form-status success">If an active account exists for that email, a password reset link has been sent.</div>
      <?php elseif ($status === 'security-error'): ?>
        <div class="form-status error">This reset form expired. Please try again.</div>
      <?php endif; ?>

      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="account_type">Account Type *</label>
          <select id="account_type" name="account_type" required>
            <?php foreach ($labels as $value => $label): ?>
              <option value="<?php echo e($value); ?>" <?php echo $accountType === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="email">Email *</label>
          <input id="email" name="email" type="email" required autocomplete="email">
        </div>
        <button class="button primary" type="submit">Send Reset Link</button>
        <p><a href="<?php echo e(password_reset_login_url($accountType)); ?>">Back to login</a></p>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
