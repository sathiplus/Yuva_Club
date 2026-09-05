<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = normalize_login_identifier($_POST['login_identifier'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $result = portal_student_login_workflow()->attempt(
        $_SESSION,
        $identifier,
        $password,
        isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null,
        portal_network_category($_SERVER['REMOTE_ADDR'] ?? null)
    );

    if ($result['authenticated'] === true) {
        try {
            $userId = (int) ($_SESSION['student_user_id'] ?? 0);
            if ($userId > 0) {
                log_beta_event('beta.first_login', $userId, 'student_account', $userId);
            }
        } catch (Throwable $error) {
            error_log('YUVA Beta login measurement unavailable correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
        }
        $studentId = normalize_yuva_id((string) ($_SESSION['student_id'] ?? ''));
        if (student_identity_onboarding_required($studentId)) {
            redirect_to('student-identity-onboarding.php');
        }
        redirect_to('portal.php');
    }

    redirect_to('portal-login.php?status=error');
}

portal_header('Student Portal Login', false, [
    'assets/public-site.css?v=release-1.0.2-20260802',
    'assets/password-visibility.css?v=auth-ux-20260820',
], true);
?>
<a class="public-skip-link" href="#main-content">Skip to main content</a>
<main id="main-content">
  <section class="band horizon-login-page">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Student Portal</p>
        <h1>Student Login</h1>
        <p>Students log in with their email address and the password created during registration.</p>
      </div>

      <?php if ($status === 'error'): ?>
        <div class="form-status error">Login failed. Check your credentials and try again.</div>
      <?php elseif ($status === 'password-reset'): ?>
        <div class="form-status success">Your password was updated. Please log in.</div>
      <?php endif; ?>

      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="login_identifier">Email Address *</label>
          <input id="login_identifier" name="login_identifier" type="text" required autocomplete="username" aria-describedby="login-identifier-help" placeholder="student@example.com">
          <p id="login-identifier-help">You may also use your Yuva Club ID.</p>
        </div>
        <div class="field">
          <label for="password">Password *</label>
          <div class="password-input-wrap">
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="password-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-label="Show password" aria-pressed="false">
              <svg class="password-toggle-icon" width="20" height="20" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M2.2 12s3.4-6 9.8-6 9.8 6 9.8 6-3.4 6-9.8 6-9.8-6-9.8-6Zm9.8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/></svg>
            </button>
          </div>
        </div>
        <button class="button primary" type="submit">Log In</button>
        <p><a href="forgot-password.php?account=student">Forgot password?</a></p>
      </form>
    </div>
  </section>
</main>
<script src="assets/password-visibility.js" defer></script>
<?php portal_footer(false, true); ?>
