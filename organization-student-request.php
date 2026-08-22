<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';

$admin = require_admin_post([YUVA_ROLE_ORGANIZATION_ADMIN]);
$neutral = 'If the student can be contacted, a secure request will be sent.';

try {
    $result = organization_membership_service()->createRequest($admin, $_POST);
    if (!empty($result['token']) && !empty($result['student_email'])) {
        $sent = send_organization_membership_student_email($result);
        if (!$sent) {
            error_log('YUVA organization student invitation delivery failed correlation=' . bin2hex(random_bytes(12)));
        }
    }
    $_SESSION['organization_membership_notice'] = ($result['neutral'] ?? false)
        ? $neutral
        : 'Invitation created. The student must accept before membership can become active.';
    unset($_SESSION['csrf_token']);
    redirect_to('organization-admin.php#student-memberships');
} catch (InvalidArgumentException $error) {
    $_SESSION['organization_membership_error'] = $error->getMessage();
    redirect_to('organization-admin.php#student-memberships');
} catch (Throwable $error) {
    error_log('YUVA organization membership request failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    $_SESSION['organization_membership_error'] = 'The request could not be completed safely. Please try again.';
    redirect_to('organization-admin.php#student-memberships');
}
