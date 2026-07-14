<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        audit_log_event(null, YUVA_ROLE_PARENT, null, 'parent.login', 'parent', null, false, ['reason' => 'csrf']);
        redirect_to('parent-login.php?status=security-error');
    }

    $parentEmail = normalize_email(clean_text($_POST['parent_email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (login_rate_limited('parent:' . $parentEmail)) {
        audit_log_event(parent_actor_id($parentEmail), YUVA_ROLE_PARENT, null, 'parent.login', 'parent', $parentEmail, false, ['reason' => 'rate_limited']);
        redirect_to('parent-login.php?status=locked');
    }

    if (parent_password_matches($parentEmail, $password) && parent_linked_students($parentEmail) !== []) {
        record_login_attempt('parent:' . $parentEmail, true);
        session_regenerate_id(true);
        $_SESSION['parent_email'] = $parentEmail;
        $_SESSION['parent_session_started_at'] = time();
        audit_log_event(parent_actor_id($parentEmail), YUVA_ROLE_PARENT, null, 'parent.login', 'parent', $parentEmail, true);
        redirect_to('parent.php');
    }

    record_login_attempt('parent:' . $parentEmail, false);
    audit_log_event(parent_actor_id($parentEmail), YUVA_ROLE_PARENT, null, 'parent.login', 'parent', $parentEmail, false);
    redirect_to('parent-login.php?status=error');
}

portal_header('Parent Dashboard Login');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Parent Dashboard</p>
        <h1>Parent Login</h1>
        <p>Parents sign in with their verified parent email and password. Yuva Club ID and parent email alone cannot open a dashboard.</p>
      </div>
      <?php if ($status === 'error'): ?><div class="form-status error">Login failed. Check your parent email and password.</div><?php endif; ?>
      <?php if ($status === 'locked'): ?><div class="form-status error">Too many login attempts. Please wait 15 minutes and try again.</div><?php endif; ?>
      <?php if ($status === 'expired'): ?><div class="form-status error">Your parent session expired. Please log in again.</div><?php endif; ?>
      <?php if ($status === 'security-error'): ?><div class="form-status error">This login form expired. Please try again.</div><?php endif; ?>
      <?php if ($status === 'activated'): ?><div class="form-status success">Parent account setup is complete. Please log in.</div><?php endif; ?>
      <?php if ($status === 'password-reset'): ?><div class="form-status success">Your password was updated. Please log in.</div><?php endif; ?>
      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="parent_email">Parent Email *</label>
          <input id="parent_email" name="parent_email" type="email" required autocomplete="email">
        </div>
        <div class="field">
          <label for="password">Password *</label>
          <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="button primary" type="submit">Log In</button>
        <p><a href="forgot-password.php?account=parent">Forgot password?</a></p>
        <p><a href="parent-activate.php">Set up parent access</a></p>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
