<?php
require __DIR__ . '/portal-lib.php';

$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
$action = clean_text((string) ($_POST['action'] ?? ''));
$email = normalize_email((string) ($_POST['email'] ?? ''));

if ($action === 'invite') {
    $ok = provision_organization_admin_invitation(
        $admin,
        (string) ($_POST['organization_id'] ?? ''),
        (string) ($_POST['full_name'] ?? ''),
        $email,
        (string) ($_POST['role'] ?? ''),
        (string) ($_POST['status'] ?? 'pending_invitation')
    );
    redirect_to('admin.php?status=' . ($ok ? 'org-admin-invited' : 'org-admin-error'));
}

if ($email === '' || $email === YUVA_PLATFORM_ADMIN_EMAIL) {
    redirect_to('admin.php?status=org-admin-error');
}

$ok = false;
if ($action === 'resend') {
    $ok = send_organization_admin_invitation($admin, $email, 'invitation');
} elseif ($action === 'suspend') {
    $ok = update_organization_admin_status($admin, $email, 'suspended');
} elseif ($action === 'reactivate') {
    $ok = update_organization_admin_status($admin, $email, 'active');
} elseif ($action === 'password_reset') {
    $ok = send_organization_admin_invitation($admin, $email, 'password_reset');
} elseif ($action === 'assignment') {
    $ok = update_organization_admin_assignment($admin, $email, (string) ($_POST['organization_id'] ?? ''));
}

redirect_to('admin.php?status=' . ($ok ? 'org-admin-updated' : 'org-admin-error'));
