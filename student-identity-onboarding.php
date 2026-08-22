<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';

$student = require_student();
$yuvaId = normalize_yuva_id((string) ($student['Yuva Club ID'] ?? ''));
if (!student_identity_onboarding_required($yuvaId)) {
    redirect_to('portal.php#app-profile');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request.');
    }
    $lastAttempt = (int) ($_SESSION['identity_onboarding_attempt_at'] ?? 0);
    if ($lastAttempt > 0 && time() - $lastAttempt < 3) {
        http_response_code(429);
        exit('Please wait before trying again.');
    }
    $_SESSION['identity_onboarding_attempt_at'] = time();

    if (isset($_POST['skip_identity'])) {
        complete_student_identity_onboarding($yuvaId, 'skipped');
        audit_log_event($yuvaId, YUVA_ROLE_STUDENT, student_organization_id($student), 'student.public_identity.onboarding.skipped', 'student', $yuvaId, true);
        redirect_to('portal.php?status=identity-skipped#app-home');
    }

    try {
        public_identity_service()->updateOwn(
            $yuvaId,
            normalize_yuva_id((string) ($_POST['yuva_id'] ?? '')),
            (string) ($_POST['public_handle'] ?? ''),
            (string) ($_POST['avatar_code'] ?? '')
        );
        complete_student_identity_onboarding($yuvaId, 'saved');
        audit_log_event($yuvaId, YUVA_ROLE_STUDENT, student_organization_id($student), 'student.public_identity.onboarding.completed', 'student', $yuvaId, true);
        redirect_to('portal.php?status=identity-saved#app-home');
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        $safeMessages = [
            \YuvaClub\Identity\PublicIdentityValidator::GENERIC_ERROR,
            'Your YUVA Handle can be changed once every 30 days.',
            'Please choose an available YUVA avatar.',
            'Access denied.',
        ];
        $error = str_starts_with($message, \YuvaClub\Identity\PublicIdentityValidator::GENERIC_ERROR) || in_array($message, $safeMessages, true)
            ? $message
            : 'Your YUVA Identity could not be saved. Please try again.';
        audit_log_event($yuvaId, YUVA_ROLE_STUDENT, student_organization_id($student), 'student.public_identity.onboarding.failed', 'student', $yuvaId, false, ['reason' => 'validation']);
    }
}

$identity = public_student_identity($yuvaId);
$avatar = \YuvaClub\Identity\PublicStudentIdentity::avatar((string) $identity['avatar_code']);
portal_header('Create Your YUVA Identity', false, ['assets/public-identity-onboarding.css?v=1'], true);
?>
<a class="public-skip-link" href="#identity-onboarding">Skip to identity setup</a>
<main id="identity-onboarding" class="identity-onboarding-page">
  <section class="identity-onboarding-card" aria-labelledby="identity-onboarding-title">
    <div class="identity-onboarding-heading">
      <p class="eyebrow">Welcome to YUVA Club</p>
      <h1 id="identity-onboarding-title">Create Your YUVA Identity</h1>
      <p>Choose how you want the YUVA community to recognize you in challenges and leaderboards. Your personal contact information stays private.</p>
    </div>
    <?php if ($error !== ''): ?><div class="form-status error" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <div class="identity-onboarding-layout">
      <aside class="identity-onboarding-preview" aria-label="YUVA Identity preview">
        <span class="identity-onboarding-avatar" data-identity-avatar><?php echo e($avatar['icon']); ?></span>
        <strong data-identity-handle><?php echo e($identity['handle'] ?: $yuvaId); ?></strong>
        <small><?php echo e($yuvaId); ?></small>
        <p>Your YUVA ID is permanent.</p>
      </aside>
      <form class="identity-onboarding-form" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="yuva_id" value="<?php echo e($yuvaId); ?>">
        <div class="field permanent-yuva-id"><label>Permanent YUVA ID</label><output><?php echo e($yuvaId); ?></output></div>
        <div class="field">
          <label for="public_handle">YUVA Handle <small>(optional)</small></label>
          <input id="public_handle" name="public_handle" type="text" minlength="3" maxlength="24" pattern="[A-Za-z0-9][A-Za-z0-9._-]*[A-Za-z0-9]" value="<?php echo e((string) ($identity['handle'] ?? '')); ?>" aria-describedby="handle-help">
          <small id="handle-help">3–24 letters or numbers; single dots, underscores, or hyphens are allowed. After your first choice, handles can change once every 30 days.</small>
        </div>
        <fieldset class="identity-avatar-picker">
          <legend>Choose a preset Avatar</legend>
          <div class="identity-avatar-options">
            <?php foreach (\YuvaClub\Identity\PublicStudentIdentity::AVATARS as $code => $option): ?>
              <label><input type="radio" name="avatar_code" value="<?php echo e($code); ?>" data-avatar-icon="<?php echo e($option['icon']); ?>" <?php echo $identity['avatar_code'] === $code ? 'checked' : ''; ?> required><span aria-hidden="true"><?php echo e($option['icon']); ?></span><small><?php echo e($option['label']); ?></small></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="identity-onboarding-actions">
          <button class="button primary" type="submit">Save and Continue</button>
          <button class="identity-skip-button" type="submit" name="skip_identity" value="1">Skip for now</button>
        </div>
        <p class="identity-privacy-note">If you skip, your permanent YUVA ID will be used as your public fallback. You can complete this later from your profile.</p>
      </form>
    </div>
  </section>
</main>
<script>
  const handleInput = document.getElementById('public_handle');
  const handlePreview = document.querySelector('[data-identity-handle]');
  const idFallback = <?php echo json_encode($yuvaId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  handleInput.addEventListener('input', () => { handlePreview.textContent = handleInput.value.trim() || idFallback; });
  document.querySelectorAll('[data-avatar-icon]').forEach((input) => input.addEventListener('change', () => {
    if (input.checked) document.querySelector('[data-identity-avatar]').textContent = input.dataset.avatarIcon;
  }));
</script>
<?php portal_footer(false, true); ?>
