<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$admin = require_admin_post([YUVA_ROLE_ORGANIZATION_ADMIN]);
$guid = trim((string) ($_POST['membership_guid'] ?? ''));

try {
    $ok = preg_match('/^[0-9a-f-]{36}$/i', $guid) === 1
        && organization_membership_service()->organizationArchive($admin, $guid);
    $_SESSION['organization_membership_notice'] = $ok
        ? 'The organization membership was closed.'
        : 'The membership could not be changed.';
} catch (Throwable $error) {
    error_log('YUVA organization membership archive failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    $_SESSION['organization_membership_error'] = 'The membership could not be changed.';
}
unset($_SESSION['csrf_token']);
redirect_to('organization-admin.php#student-memberships');
