<?php
require __DIR__ . '/portal-lib.php';

$status = clean_text($_GET['status'] ?? '');
$token = clean_text($_GET['token'] ?? ($_POST['token'] ?? ''));
$activationRecord = null;
if ($token !== '') { try { $activationRecord=parent_credential_service()->tokenRecord($token,'activation'); } catch(Throwable $error) { error_log('YUVA Parent activation lookup failed exception_type='.get_class($error)); } }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('parent-activate.php?status=security-error');
    }

    $action = clean_text($_POST['action'] ?? '');
    if ($action === 'request') {
        $parentEmail = normalize_email(clean_text($_POST['parent_email'] ?? ''));
        $throttle=parent_authentication_throttle();$network=portal_network_category($_SERVER['REMOTE_ADDR']??null);
        if ($parentEmail !== '' && !$throttle->isBlocked('parent-activation',$parentEmail,$network)) {
            try { $activationToken = parent_credential_service()->issueToken($parentEmail, 'activation'); } catch(Throwable $error) { $activationToken=null; error_log('YUVA Parent activation request failed exception_type='.get_class($error)); }
            if ($activationToken !== null) {
                send_parent_activation_email($parentEmail, parent_activation_url($activationToken));
            } else {
                audit_log_event(parent_actor_id($parentEmail), YUVA_ROLE_PARENT, null, 'parent.activation.requested', 'parent', $parentEmail, false, ['reason' => 'no_existing_relationship']);
            }
            $throttle->recordFailure('parent-activation',$parentEmail,$network);
        }
        redirect_to('parent-activate.php?status=requested');
    }

    if ($action === 'complete') {
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if ($password === '' || !hash_equals($password, $confirmPassword) || password_policy_error($password) !== '') {
            redirect_to('parent-activate.php?token=' . rawurlencode($token) . '&status=password-error');
        }
        try { $activated=parent_credential_service()->consume($token, 'activation', $password); } catch(Throwable $error) { $activated=false; error_log('YUVA Parent activation completion failed exception_type='.get_class($error)); }
        if ($activated) {
            redirect_to('parent-login.php?status=activated');
        }
        redirect_to('parent-activate.php?status=invalid-token');
    }
}

portal_header('Parent Account Setup', false, ['assets/public-site.css?v=release-1.0.2-20260802'], true);
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
            <button class="password-visibility-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false">Show Password</button>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm New Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="12" required autocomplete="new-password">
            <button class="password-visibility-toggle" type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-pressed="false">Show Password</button>
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
<script src="assets/password-visibility.js" defer></script>
<?php portal_footer(false, true); ?>
