<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin([YUVA_ROLE_MASTER_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.credentials.update', 'admin', YUVA_PLATFORM_ADMIN_EMAIL, false, ['reason' => 'csrf']);
    redirect_to('admin.php?status=security-error');
}

$currentEmail = clean_text($_POST['current_email'] ?? '');
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if (
    normalize_email($currentEmail) !== YUVA_PLATFORM_ADMIN_EMAIL
    || $newPassword === ''
    || $newPassword !== $confirmPassword
    || password_policy_error($newPassword) !== ''
    || !admin_password_matches($currentEmail, $currentPassword)
) {
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.credentials.update', 'admin', YUVA_PLATFORM_ADMIN_EMAIL, false);
    redirect_to('admin.php?status=password-error');
}

write_json_file(admin_credentials_file(), [
    'email' => YUVA_PLATFORM_ADMIN_EMAIL,
    'password_hash' => password_hash_for_admin($newPassword),
    'role' => YUVA_ROLE_MASTER_ADMIN,
    'organization_id' => YUVA_PLATFORM_ORGANIZATION_ID,
    'updated_at' => date('Y-m-d H:i:s'),
]);

$_SESSION['admin_email'] = YUVA_PLATFORM_ADMIN_EMAIL;
$_SESSION['admin_role'] = YUVA_ROLE_MASTER_ADMIN;
$_SESSION['admin_organization_id'] = YUVA_PLATFORM_ORGANIZATION_ID;
audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.credentials.update', 'admin', YUVA_PLATFORM_ADMIN_EMAIL, true);
redirect_to('admin.php?status=password-saved');
