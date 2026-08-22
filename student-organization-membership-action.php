<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$student = require_student();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('portal.php?status=membership-security-error#organization-membership');
}
$yuvaId = normalize_yuva_id((string) ($student['Yuva Club ID'] ?? ''));
$guid = trim((string) ($_POST['membership_guid'] ?? ''));
$decision = (string) ($_POST['decision'] ?? '');

try {
    if (preg_match('/^[0-9a-f-]{36}$/i', $guid) !== 1) {
        throw new InvalidArgumentException('Invalid membership request.');
    }
    $result = organization_membership_service()->studentDecision($yuvaId, $guid, $decision);
    if (!empty($result['parent_required'])) {
        $sent = send_organization_membership_parent_email(
            (string) ($result['parent_email'] ?? ''),
            (string) ($result['organization_code'] ?? ''),
            (string) ($result['parent_token'] ?? '')
        );
        if (!$sent) {
            error_log('YUVA organization parent approval delivery failed correlation=' . bin2hex(random_bytes(12)));
        }
    } elseif (in_array((string) ($result['status'] ?? ''), ['Active', 'Declined'], true)) {
        send_organization_membership_status_emails(
            (string) $result['status'],
            (string) ($result['organization_code'] ?? ''),
            (string) ($result['student_email'] ?? '')
        );
    }
    unset($_SESSION['csrf_token']);
    redirect_to('portal.php?status=membership-' . strtolower((string) $result['status']) . '#organization-membership');
} catch (Throwable $error) {
    error_log('YUVA student membership decision failed correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
    redirect_to('portal.php?status=membership-error#organization-membership');
}
