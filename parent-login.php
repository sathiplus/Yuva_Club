<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
$step = $_GET['step'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'login');
    if ($action === 'select_child') {
        $selected = portal_parent_login_workflow()->selectChild(
            $_SESSION,
            normalize_yuva_id($_POST['student_id'] ?? ''),
            isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null,
            portal_network_category($_SERVER['REMOTE_ADDR'] ?? null)
        );
        if ($selected) {
            redirect_to('parent.php');
        }
        redirect_to('parent-login.php?status=error');
    }

    $parentEmail = strtolower(clean_text($_POST['parent_email'] ?? ''));
    $credential = (string) ($_POST['credential'] ?? '');
    $result = portal_parent_login_workflow()->attempt(
        $_SESSION,
        $parentEmail,
        $credential,
        null,
        isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null,
        portal_network_category($_SERVER['REMOTE_ADDR'] ?? null)
    );
    if ($result['authenticated'] === true) {
        redirect_to(
            $result['requires_child_selection'] === true
                ? 'parent-login.php?step=children'
                : 'parent.php'
        );
    }

    redirect_to('parent-login.php?status=error');
}

$children = null;
if ($step === 'children') {
    $children = portal_parent_login_workflow()->authorizedChildren($_SESSION);
    if ($children === null) {
        redirect_to('parent-login.php?status=error');
    }
}

portal_header('Parent Dashboard Login', false, [
    'assets/public-site.css?v=release-1.0.2-20260802',
    'assets/password-visibility.css?v=parent-auth-20260831',
], true);
?>
<a class="public-skip-link" href="#main-content">Skip to main content</a>
<main id="main-content">
  <section class="band horizon-login-page">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Parent Dashboard</p>
        <?php if (is_array($children)): ?>
          <h1>Select a Student</h1>
          <p>Choose one of your authorized linked students.</p>
        <?php else: ?>
          <h1>Parent Login</h1>
          <p>Parents can view attendance, upcoming presentations, feedback, hours, certificates, recordings, and announcements.</p>
        <?php endif; ?>
      </div>
      <?php if ($status === 'error'): ?><div class="form-status error">Authentication failed. Check your credentials and try again.</div><?php endif; ?>
      <?php if ($status === 'activated'): ?><div class="form-status success">Parent account setup is complete. Please log in.</div><?php endif; ?>
      <?php if ($status === 'password-reset'): ?><div class="form-status success">Your password was updated. Please log in.</div><?php endif; ?>
      <?php if (is_array($children)): ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="select_child">
          <?php if ($children === []): ?>
            <p>No authorized linked students are currently available.</p>
          <?php else: ?>
            <?php foreach ($children as $index => $child): ?>
              <?php
              $childYuvaId = normalize_yuva_id((string) ($child['yuva_id'] ?? ''));
              $childName = trim(
                  (string) ($child['student_first_name'] ?? '')
                  . ' '
                  . (string) ($child['student_last_name'] ?? '')
              );
              ?>
              <div class="field">
                <label>
                  <input
                    name="student_id"
                    type="radio"
                    value="<?php echo e($childYuvaId); ?>"
                    <?php echo $index === 0 ? 'checked' : ''; ?>
                    required
                  >
                  <?php echo e($childName !== '' ? $childName : $childYuvaId); ?>
                  (<?php echo e($childYuvaId); ?>)
                </label>
              </div>
            <?php endforeach; ?>
            <button class="button primary" type="submit">Open Dashboard</button>
          <?php endif; ?>
        </form>
      <?php else: ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="login">
          <div class="field">
            <label for="parent_email">Parent Email *</label>
            <input id="parent_email" name="parent_email" type="email" required autocomplete="email">
          </div>
            <div class="field">
              <label for="credential">Password *</label>
              <div class="password-input-wrap">
                <input id="credential" name="credential" type="password" required autocomplete="current-password">
                <button class="password-toggle" type="button" data-password-toggle="credential" aria-controls="credential" aria-label="Show password" aria-pressed="false">
                  <svg class="password-toggle-icon" width="20" height="20" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M2.2 12s3.4-6 9.8-6 9.8 6 9.8 6-3.4 6-9.8 6-9.8-6-9.8-6Zm9.8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/></svg>
                </button>
              </div>
            </div>
          <button class="button primary" type="submit">View Dashboard</button>
            <p><a href="forgot-password.php?account=parent">Forgot password?</a></p>
            <p><a href="parent-activate.php">Set up parent access</a></p>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/password-visibility.js" defer></script>
<?php portal_footer(false, true); ?>
