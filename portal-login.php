<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('portal-login.php?status=security-error');
    }

    $identifier = normalize_login_identifier($_POST['login_identifier'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (login_rate_limited($identifier)) {
        redirect_to('portal-login.php?status=locked');
    }

    $account = find_student_account_by_identifier($identifier);
    $hash = (string) ($account['password_hash'] ?? '');

    if ($account !== null && $hash !== '' && password_verify($password, $hash)) {
        $studentId = normalize_yuva_id((string) ($account['yuva_id'] ?? ''));
        $student = find_student($studentId);
        if ($student === null) {
            record_login_attempt($identifier, false);
            redirect_to('portal-login.php?status=missing');
        }

        record_login_attempt($identifier, true);
        session_regenerate_id(true);
        $_SESSION['student_id'] = $studentId;
        redirect_to('portal.php');
    }

    record_login_attempt($identifier, false);
    redirect_to('portal-login.php?status=error');
}

portal_header('Student Portal Login');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Student Portal</p>
        <h1>Student Login</h1>
        <p>Students log in with their Yuva Club ID or email and the password created during registration.</p>
      </div>

      <?php if ($status === 'error'): ?>
        <div class="form-status error">Login failed. Check the Yuva Club ID/email and password.</div>
      <?php elseif ($status === 'missing'): ?>
        <div class="form-status error">Your registration record was not found. Please contact the Yuva Club admin.</div>
      <?php elseif ($status === 'locked'): ?>
        <div class="form-status error">Too many login attempts. Please wait 15 minutes and try again.</div>
      <?php elseif ($status === 'security-error'): ?>
        <div class="form-status error">This login form expired. Please try again.</div>
      <?php elseif ($status === 'password-reset'): ?>
        <div class="form-status success">Your password was updated. Please log in.</div>
      <?php endif; ?>

      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="login_identifier">Yuva Club ID or Email *</label>
          <input id="login_identifier" name="login_identifier" type="text" required autocomplete="username" placeholder="YC2026001 or student@example.com">
        </div>
        <div class="field">
          <label for="password">Password *</label>
          <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="button primary" type="submit">Log In</button>
        <p><a href="forgot-password.php?account=student">Forgot password?</a></p>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
