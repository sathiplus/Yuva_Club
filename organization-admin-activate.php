<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';

$token = (string) ($_GET['token'] ?? ($_POST['token'] ?? ''));
$record = $token !== '' ? organization_admin_token_record($token) : null;
$status = (string) ($_GET['status'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        redirect_to('organization-admin-activate.php?status=security-error');
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if ($password === '' || $password !== $confirm || password_policy_error($password) !== '') {
        $status = 'password-error';
    } elseif (complete_organization_admin_invitation($token, $password)) {
        redirect_to('admin-login.php?status=org-admin-activated');
    } else {
        $status = 'invalid';
    }
}

$email = normalize_email((string) ($record['admin_email'] ?? ''));
$account = $email !== '' ? organization_admin_by_email($email) : null;

portal_header('Organization Admin Activation', false, [
    'assets/public-site.css?v=release-1.0.2-20260802',
    'assets/password-visibility.css?v=auth-ux-20260820',
], true);
?>
<a class="public-skip-link" href="#main-content">Skip to main content</a>
<main id="main-content">
  <section class="band horizon-login-page">
    <div class="form-shell portal-narrow">
      <div class="section-head"><p class="eyebrow">Organization Administrator</p><h1>Create Your Password</h1><p>Activate the approved organization administrator account using this single-use invitation.</p></div>
      <?php if ($status === 'security-error'): ?><div class="form-status error">This activation form expired. Please try again.</div>
      <?php elseif ($status === 'password-error'): ?><div class="form-status error">Choose matching passwords that meet the password policy.</div>
      <?php elseif ($status === 'invalid' || $record === null || $account === null): ?><div class="form-status error">This invitation is invalid, expired, already used, or unavailable.</div><?php endif; ?>
      <?php if ($record !== null && $account !== null): ?>
        <form class="form-card" method="post">
          <?php echo csrf_field(); ?><input type="hidden" name="token" value="<?php echo e($token); ?>">
          <div class="field"><label>Email</label><input type="email" value="<?php echo e($email); ?>" readonly></div>
          <div class="field"><label>Organization</label><input type="text" value="<?php echo e((string) ($account['organization_id'] ?? '')); ?>" readonly></div>
          <div class="field"><label for="password">Password *</label><div class="password-input-wrap"><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-label="Show password" aria-pressed="false"><svg class="password-toggle-icon" width="20" height="20" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.2 12s3.4-6 9.8-6 9.8 6 9.8 6-3.4 6-9.8 6-9.8-6-9.8-6Zm9.8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/></svg></button></div></div>
          <div class="field"><label for="confirm_password">Confirm Password *</label><div class="password-input-wrap"><input id="confirm_password" name="confirm_password" type="password" required minlength="12" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-label="Show password" aria-pressed="false"><svg class="password-toggle-icon" width="20" height="20" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.2 12s3.4-6 9.8-6 9.8 6 9.8 6-3.4 6-9.8 6-9.8-6-9.8-6Zm9.8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/></svg></button></div></div>
          <button class="button primary" type="submit">Activate Account</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/password-visibility.js" defer></script>
<?php portal_footer(false, true); ?>
