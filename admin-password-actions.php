<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$currentEmail = clean_text($_POST['current_email'] ?? '');
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newEmail = clean_text($_POST['new_email'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if (
    $newEmail === ''
    || $newPassword === ''
    || $newPassword !== $confirmPassword
    || password_policy_error($newPassword) !== ''
    || !admin_password_matches($currentEmail, $currentPassword)
) {
    redirect_to('admin.php?status=password-error');
}

write_json_file(admin_credentials_file(), [
    'email' => $newEmail,
    'password_hash' => password_hash_for_admin($newPassword),
    'updated_at' => date('Y-m-d H:i:s'),
]);

$_SESSION['admin_email'] = $newEmail;
redirect_to('admin.php?status=password-saved');
