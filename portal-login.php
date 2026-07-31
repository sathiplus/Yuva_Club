<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
$authMode = portal_auth_mode();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = normalize_yuva_id($_POST['student_id'] ?? '');
    $credential = $authMode === 'filesystem'
        ? clean_text($_POST['date_of_birth'] ?? '')
        : (string) ($_POST['credential'] ?? '');
    $result = portal_student_login_workflow()->attempt(
        $_SESSION,
        $studentId,
        $credential,
        isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null,
        portal_network_category($_SERVER['REMOTE_ADDR'] ?? null)
    );

    if ($result['authenticated'] === true) {
        redirect_to('portal.php');
    }

    redirect_to('portal-login.php?status=error');
}

portal_header('Student Portal Login', false, ['assets/public-site.css?v=1'], true);
?>
<a class="public-skip-link" href="#main-content">Skip to main content</a>
<main id="main-content">
  <section class="band horizon-login-page">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Student Portal</p>
        <h1>Student Login</h1>
        <?php if ($authMode === 'filesystem'): ?>
          <p>Students can log in with their Yuva Club ID and date of birth after registration.</p>
        <?php elseif ($authMode === 'sql'): ?>
          <p>Students can log in with their Yuva Club ID and password.</p>
        <?php else: ?>
          <p>Students can log in with their Yuva Club ID and current credential.</p>
        <?php endif; ?>
      </div>

      <?php if ($status === 'error'): ?>
        <div class="form-status error">Login failed. Check your credentials and try again.</div>
      <?php elseif ($status === 'password-reset'): ?>
        <div class="form-status success">Your password was updated. Please log in.</div>
      <?php endif; ?>

      <form class="form-card" method="post">
        <?php echo csrf_field(); ?>
        <div class="field">
          <label for="student_id">Yuva Club ID *</label>
          <input id="student_id" name="student_id" type="text" required placeholder="YC2026001">
        </div>
        <?php if ($authMode === 'filesystem'): ?>
          <div class="field">
            <label for="date_of_birth">Date of Birth *</label>
            <input id="date_of_birth" name="date_of_birth" type="date" required>
          </div>
        <?php elseif ($authMode === 'sql'): ?>
          <div class="field">
            <label for="credential">Password *</label>
            <input id="credential" name="credential" type="password" required autocomplete="current-password">
          </div>
        <?php else: ?>
          <div class="field">
            <label for="credential">Credential *</label>
            <input id="credential" name="credential" type="password" required autocomplete="current-password" placeholder="Password or YYYY-MM-DD">
          </div>
        <?php endif; ?>
        <button class="button primary" type="submit">Log In</button>
        <?php if ($authMode !== 'filesystem'): ?>
          <p><a href="forgot-password.php?account=student">Forgot password?</a></p>
        <?php endif; ?>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(false, true); ?>
