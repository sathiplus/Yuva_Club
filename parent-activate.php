<?php
require __DIR__ . '/portal-lib.php';

$status = clean_text($_GET['status'] ?? '');
$token = clean_text($_GET['token'] ?? ($_POST['token'] ?? ''));
$activationRecord = $token !== '' ? parent_activation_record($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('parent-activate.php?status=security-error');
    }

    $action = clean_text($_POST['action'] ?? '');
    if ($action === 'request') {
        $parentEmail = normalize_email(clean_text($_POST['parent_email'] ?? ''));
        if ($parentEmail !== '' && !login_rate_limited('parent-activation:' . $parentEmail)) {
            $activationToken = create_parent_activation_token($parentEmail);
            if ($activationToken !== null) {
                send_parent_activation_email($parentEmail, parent_activation_url($activationToken));
            } else {
                audit_log_event(parent_actor_id($parentEmail), YUVA_ROLE_PARENT, null, 'parent.activation.requested', 'parent', $parentEmail, false, ['reason' => 'no_existing_relationship']);
            }
            record_login_attempt('parent-activation:' . $parentEmail, $activationToken !== null);
        }
        redirect_to('parent-activate.php?status=requested');
    }

    if ($action === 'complete') {
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if ($password === '' || !hash_equals($password, $confirmPassword) || password_policy_error($password) !== '') {
            redirect_to('parent-activate.php?token=' . rawurlencode($token) . '&status=password-error');
        }
        if (complete_parent_activation($token, $password)) {
            redirect_to('parent-login.php?status=activated');
        }
        redirect_to('parent-activate.php?status=invalid-token');
    }
}

portal_header('Parent Account Setup');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Parent Account</p>
        <h1>Set Up Parent Access</h1>
        <p>Parents can request a secure password setup link using the email already registered with YUVA Club.</p>
      </div>

      <?php if ($status === 'requested'): ?>
        <div class="form-status success">If that email is connected to a YUVA Club parent record, a setup link has been sent.</div>
      <?php elseif ($status === 'invalid-token'): ?>
        <div class="form-status error">This setup link is invalid or expired. Request a new one below.</div>
      <?php elseif ($status === 'password-error'): ?>
        <div class="form-status error">Password setup failed. Use matching passwords that meet the security rules.</div>
      <?php elseif ($status === 'security-error'): ?>
        <div class="form-status error">This form expired. Please try again.</div>
      <?php endif; ?>

      <?php if ($activationRecord !== null): ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="complete">
          <input type="hidden" name="token" value="<?php echo e($token); ?>">
          <div class="field">
            <label for="password">New Password *</label>
            <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
          </div>
          <div class="field">
            <label for="confirm_password">Confirm New Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="12" required autocomplete="new-password">
          </div>
          <button class="button primary" type="submit">Set Password</button>
        </form>
      <?php else: ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="request">
          <div class="field">
            <label for="parent_email">Parent Email *</label>
            <input id="parent_email" name="parent_email" type="email" required autocomplete="email">
          </div>
          <button class="button primary" type="submit">Send Setup Link</button>
          <p><a href="parent-login.php">Return to Parent Login</a></p>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
