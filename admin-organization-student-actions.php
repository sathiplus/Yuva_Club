<?php
require __DIR__ . '/portal-lib.php';

$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
$action = clean_text((string) ($_POST['action'] ?? ''));
$ok = false;

if ($action === 'archive_membership') {
    $ok = master_archive_organization_student_membership($admin, (string) ($_POST['membership_key'] ?? ''));
}

redirect_to('admin.php?status=' . ($ok ? 'org-membership-archived' : 'org-admin-error'));
