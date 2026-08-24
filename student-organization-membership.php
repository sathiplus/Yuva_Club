<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$token = trim((string) ($_GET['token'] ?? ''));
$record = $token !== '' ? organization_membership_service()->tokenRecord($token, 'StudentAccept') : null;
if ($record === null) {
    http_response_code(410);
    portal_header('Organization Invitation');
    echo '<main><section class="band"><div class="form-card"><h1>Invitation unavailable</h1><p>This invitation is invalid, expired, already used, or unavailable.</p></div></section></main>';
    portal_footer();
    exit;
}

if (($record['request_type'] ?? '') === 'InviteNew' && empty($record['student_id']) && empty($record['registration_id'])) {
    $_SESSION['organization_membership_registration_token'] = $token;
    redirect_to('registration.php?organization-invitation=1');
}

$studentId = logged_in_student_id();
if ($studentId === null) {
    $_SESSION['organization_membership_student_token'] = $token;
    redirect_to('portal-login.php?status=organization-invitation');
}
redirect_to('portal.php#organization-membership');
