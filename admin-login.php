<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        audit_log_event(null, YUVA_ROLE_MASTER_ADMIN, YUVA_PLATFORM_ORGANIZATION_ID, 'admin.login', 'admin', null, false, ['reason' => 'csrf']);
        redirect_to('admin-login.php?status=security-error');
    }

    $email = clean_text($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $identity = authenticate_admin_account($email, $password);
    if ($identity !== null) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $identity['email'];
        $_SESSION['admin_role'] = $identity['role'];
        $_SESSION['admin_organization_id'] = $identity['organization_id'];
        $_SESSION['admin_session_started_at'] = time();
        if ($identity['role'] === YUVA_ROLE_ORGANIZATION_ADMIN) {
            record_organization_admin_login($identity['email']);
        }
        audit_log_event($identity['id'], $identity['role'], $identity['organization_id'], 'admin.login', 'admin', $identity['email'], true);
        redirect_to($identity['redirect']);
    }

    audit_log_event(admin_actor_id($email), YUVA_ROLE_MASTER_ADMIN, YUVA_PLATFORM_ORGANIZATION_ID, 'admin.login', 'admin', $email, false);
    redirect_to('admin-login.php?status=error');
}

portal_header('Admin Login');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">YUVA Club Administration</p>
        <h1>Admin Login</h1>
        <p>Authorized YUVA Club platform and organization administrators can sign in here with their email address and password.</p>
      </div>
      <?php if ($status === 'error'): ?>
        <div class="form-status error">Incorrect admin email or password.</div>
      <?php elseif ($status === 'security-error'): ?>
        <div class="form-status error">This login form expired. Please try again.</div>
      <?php elseif ($status === 'org-admin-activated'): ?>
        <div class="form-status success">Your organization administrator account is active. Please log in.</div>
      <?php endif; ?>
      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="email">Admin Email *</label>
          <input id="email" name="email" type="email" required autocomplete="email">
        </div>
        <div class="field">
          <label for="password">Password *</label>
          <input id="password" name="password" type="password" required>
        </div>
        <button class="button primary" type="submit">Log In</button>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
