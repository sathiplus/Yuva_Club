<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_text($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (admin_password_matches($email, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        redirect_to('admin.php');
    }
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
        <p>Authorized YUVA Club and organization administrators can sign in here. Access is limited by each administrator's role and organization permissions.</p>
      </div>
      <?php if ($status === 'error'): ?>
        <div class="form-status error">Incorrect platform administrator email or password.</div>
      <?php endif; ?>
      <form class="form-card" method="post">
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
