<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';

$status = clean_text((string) ($_GET['status'] ?? ''));
$error = '';
$values = demo_request_values($_SESSION['demo_request_values'] ?? []);
unset($_SESSION['demo_request_values']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = demo_request_values($_POST);
    $startedAt = (int) ($_POST['form_started_at'] ?? 0);
    $network = portal_network_category($_SERVER['REMOTE_ADDR'] ?? null);
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $error = 'Your session expired. Please refresh and try again.';
    } elseif (clean_text((string) ($_POST['website'] ?? '')) !== '' || $startedAt <= 0 || (time() - $startedAt) < 3) {
        $error = 'We could not accept this request. Please try again.';
    } else {
        $error = demo_request_validation_error($values);
    }
    if ($error === '' && demo_request_rate_limited($network)) {
        $error = 'Too many requests were submitted recently. Please try again later.';
    }
    if ($error === '') {
        try {
            $requestId = persist_demo_request($values, $network);
            $adminMessage = "New School or Organization Demo Request\n\nRequest ID: {$requestId}\n"
                . "Organization: {$values['organization_name']}\nOrganization Type: {$values['organization_type']}\n"
                . "Contact: {$values['contact_name']}\nEmail: {$values['email']}\nPhone: {$values['phone']}\n"
                . "City / State: {$values['city_state']}\nApproximate Students: {$values['student_count']}\n"
                . "Student Age Range: {$values['student_age_range']}\nProgram Interest: {$values['program_interest']}\n"
                . "Preferred Contact Time: {$values['preferred_contact_time']}\nMessage: {$values['message']}\n";
            $adminSent = send_yuva_email(demo_request_notification_email(),
                'New YUVA Club Demo Request: ' . $requestId, $adminMessage, $values['email']);
            $confirmationSent = send_yuva_email($values['email'], 'We received your YUVA Club demo request',
                "Hello {$values['contact_name']},\n\nThank you for your interest in bringing YUVA Club to {$values['organization_name']}. We received your request ({$requestId}) and will review the submitted information.\n\nNo organization account or access has been created. If the request is approved, you will receive a separate secure invitation to activate the Organization Admin account and create your password.\n\nYUVA Club");
            if (!$adminSent || !$confirmationSent) {
                error_log('YUVA demo request email delivery incomplete for request ' . $requestId);
            }
            unset($_SESSION['csrf_token']);
            redirect_to('demo-request.php?status=success&reference=' . rawurlencode($requestId));
        } catch (Throwable) {
            error_log('YUVA demo request persistence failed.');
            $error = 'We could not save your request. Please try again or contact YUVA Club support.';
        }
    }
    $_SESSION['demo_request_values'] = $values;
}

portal_header('Request a School or Organization Demo', false, [
    'assets/public-site.css?v=release-1.0.2-20260802', 'assets/demo-request.css?v=20260820'], true);
?>
<a class="public-skip-link" href="#main-content">Skip to main content</a>
<main id="main-content"><section class="band demo-request-page"><div class="form-shell">
  <div class="section-head"><p class="eyebrow">Schools and Organizations</p><h1>Request a YUVA Club Demo</h1><p>Tell us about your students and goals. A request does not create an account or provide access. Master Admin review and approval are required first.</p></div>
  <?php if ($status === 'success'): ?>
    <div class="form-status success" role="status"><h2>Thank you. Your request was received.</h2><p>We sent a confirmation to the email provided. YUVA Club will review the request; if approved, the organization contact will receive a separate secure account-activation invitation.</p><?php if (!empty($_GET['reference'])): ?><p>Reference: <strong><?php echo e(clean_text((string) $_GET['reference'])); ?></strong></p><?php endif; ?></div>
  <?php else: ?>
    <?php if ($error !== ''): ?><div class="form-status error" role="alert"><?php echo e($error); ?></div><?php endif; ?>
    <form class="form-card demo-request-form" method="post" action="demo-request.php">
      <?php echo csrf_field(); ?><input type="hidden" name="form_started_at" value="<?php echo time(); ?>">
      <div class="demo-honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
      <div class="form-grid two-column">
        <div class="field"><label for="organization_name">Organization / School Name *</label><input id="organization_name" name="organization_name" required maxlength="200" value="<?php echo e($values['organization_name']); ?>"></div>
        <div class="field"><label for="organization_type">Organization Type *</label><select id="organization_type" name="organization_type" required><option value="">Select one</option><?php foreach (['School','Library','Community Center','Nonprofit','Youth Organization','Other'] as $option): ?><option<?php echo $values['organization_type'] === $option ? ' selected' : ''; ?>><?php echo e($option); ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="contact_name">Contact Name *</label><input id="contact_name" name="contact_name" required maxlength="200" autocomplete="name" value="<?php echo e($values['contact_name']); ?>"></div>
        <div class="field"><label for="email">Email *</label><input id="email" name="email" type="email" required maxlength="254" autocomplete="email" value="<?php echo e($values['email']); ?>"></div>
        <div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" type="tel" maxlength="50" autocomplete="tel" value="<?php echo e($values['phone']); ?>"></div>
        <div class="field"><label for="city_state">City / State *</label><input id="city_state" name="city_state" required maxlength="200" value="<?php echo e($values['city_state']); ?>"></div>
        <div class="field"><label for="student_count">Approximate Number of Students *</label><input id="student_count" name="student_count" required maxlength="100" inputmode="numeric" value="<?php echo e($values['student_count']); ?>"></div>
        <div class="field"><label for="student_age_range">Student Age Range *</label><input id="student_age_range" name="student_age_range" required maxlength="100" placeholder="Example: 11–17" value="<?php echo e($values['student_age_range']); ?>"></div>
      </div>
      <div class="field"><label for="program_interest">Intended Use / Program Interest *</label><textarea id="program_interest" name="program_interest" required maxlength="2000" rows="4"><?php echo e($values['program_interest']); ?></textarea></div>
      <div class="field"><label for="preferred_contact_time">Preferred Demo / Contact Time *</label><input id="preferred_contact_time" name="preferred_contact_time" required maxlength="200" placeholder="Include your time zone" value="<?php echo e($values['preferred_contact_time']); ?>"></div>
      <div class="field"><label for="message">Message (optional)</label><textarea id="message" name="message" maxlength="2000" rows="4"><?php echo e($values['message']); ?></textarea></div>
      <p class="form-note">Do not include student records, passwords, dates of birth, or other sensitive information.</p>
      <button class="button primary horizon-primary" type="submit">Send Demo Request</button>
    </form>
  <?php endif; ?>
</div></section></main>
<?php portal_footer(false, true); ?>
