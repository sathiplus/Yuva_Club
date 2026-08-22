<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$token = trim((string) ($_GET['token'] ?? ''));
$record = $token !== '' ? organization_membership_service()->tokenRecord($token, 'ParentApprove') : null;
if ($record === null) {
    http_response_code(410);
    portal_header('Parent Organization Approval');
    echo '<main><section class="band"><div class="form-card"><h1>Approval link unavailable</h1><p>This approval link is invalid, expired, already used, or unavailable.</p></div></section></main>';
    portal_footer();
    exit;
}
$_SESSION['organization_membership_parent_token'] = $token;
if (empty($_SESSION['parent_student_id'])) {
    redirect_to('parent-login.php?status=organization-approval');
}
redirect_to('parent.php#organization-membership');
