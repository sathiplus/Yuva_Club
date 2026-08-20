<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';

$admin = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
$action = strtolower(clean_text((string) ($_POST['action'] ?? '')));
$requestId = strtoupper(clean_text((string) ($_POST['request_id'] ?? '')));
$request = organization_demo_request($requestId);

if ($request === null || !in_array($action, ['approve', 'reject'], true)) {
    redirect_to('admin.php?status=organization-request-error#organization-requests');
}

if (($request['status'] ?? 'new') !== 'new') {
    redirect_to('admin.php?status=organization-request-already-reviewed#organization-requests');
}

if ($action === 'reject') {
    $ok = record_demo_request_decision($admin, $requestId, 'rejected');
    redirect_to('admin.php?status=' . ($ok ? 'organization-request-rejected' : 'organization-request-error') . '#organization-requests');
}

$organizationId = strtoupper(clean_text((string) ($_POST['organization_id'] ?? '')));
$email = normalize_email((string) ($request['email'] ?? ''));
$existingAccount = organization_admin_by_email($email);
if (!organization_id_is_valid($organizationId) || $email === '' || ($existingAccount !== null
    && normalize_organization_id((string) ($existingAccount['organization_id'] ?? '')) !== $organizationId)) {
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'],
        'organization_demo_request.approve', 'organization_demo_request', $requestId, false,
        ['reason' => 'invalid_or_duplicate', 'organization_id' => $organizationId]);
    redirect_to('admin.php?status=organization-request-error#organization-requests');
}

$invited = $existingAccount !== null || provision_organization_admin_invitation(
    $admin, $organizationId, (string) ($request['contact_name'] ?? ''), $email,
    YUVA_ROLE_ORGANIZATION_ADMIN, 'pending_invitation');
$recorded = $invited && record_demo_request_decision($admin, $requestId, 'approved', $organizationId);
redirect_to('admin.php?status=' . ($recorded ? 'organization-request-approved' : 'organization-request-error') . '#organization-requests');
