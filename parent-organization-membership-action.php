<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$parentContext = require_authenticated_parent();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('parent.php?status=membership-security-error#organization-membership');
}
$yuvaId = (string) $parentContext['student_id'];
$parentEmail = normalize_email((string) ($parentContext['identity']['email'] ?? ''));
$guid = trim((string) ($_POST['membership_guid'] ?? ''));
$decision = (string) ($_POST['decision'] ?? '');
if ($decision === 'withdraw') require_recent_parent_authentication('parent.php#organization-membership');

try {
    if (preg_match('/^[0-9a-f-]{36}$/i', $guid) !== 1) {
        throw new RuntimeException('Parent access denied.');
    }
    $result = organization_membership_service()->parentDecision($parentEmail, $yuvaId, $guid, $decision);
    $status = (string) ($result['status'] ?? '');
    send_organization_membership_status_emails(
        $status,
        (string) ($result['organization_code'] ?? ''),
        (string) ($result['student_email'] ?? ''),
        $parentEmail
    );
    unset($_SESSION['csrf_token']);
    redirect_to('parent.php?status=membership-' . strtolower($status) . '#organization-membership');
} catch (Throwable $error) {
    error_log('YUVA parent membership decision failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    redirect_to('parent.php?status=membership-error#organization-membership');
}
