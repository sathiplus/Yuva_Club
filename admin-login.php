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
    if (admin_password_matches($email, $password)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = YUVA_PLATFORM_ADMIN_EMAIL;
        $_SESSION['admin_role'] = YUVA_ROLE_MASTER_ADMIN;
        $_SESSION['admin_organization_id'] = YUVA_PLATFORM_ORGANIZATION_ID;
        $_SESSION['admin_session_started_at'] = time();
        audit_log_event(admin_actor_id(YUVA_PLATFORM_ADMIN_EMAIL), YUVA_ROLE_MASTER_ADMIN, YUVA_PLATFORM_ORGANIZATION_ID, 'admin.login', 'admin', YUVA_PLATFORM_ADMIN_EMAIL, true);
        redirect_to('admin.php');
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
        <p>Authorized YUVA Club platform administrators can sign in here. Organization administrator access is temporarily disabled until tenant isolation is complete.</p>
      </div>
      <?php if ($status === 'error'): ?>
        <div class="form-status error">Incorrect admin email or password.</div>
      <?php elseif ($status === 'security-error'): ?>
        <div class="form-status error">This login form expired. Please try again.</div>
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
