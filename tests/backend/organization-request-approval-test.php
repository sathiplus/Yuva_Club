<?php
declare(strict_types=1);

$expectedRoot = realpath(__DIR__ . '/../..');
$isolatedRoot = realpath((string) getenv('YUVA_TEST_ISOLATED_ROOT'));
if ($expectedRoot === false || $isolatedRoot === false || $expectedRoot !== $isolatedRoot) {
    throw new RuntimeException('Organization approval test must use the isolated validation runner.');
}
putenv('YUVA_CAPTURE_ADMIN_INVITATION_LINKS=1');
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_HOST'] = 'release2.yuvaclub.test';
require $expectedRoot . '/portal-lib.php';

function org_request_assert(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

$values = demo_request_values([
    'organization_name' => 'Synthetic Learning Center', 'organization_type' => 'School',
    'contact_name' => 'Synthetic Admin', 'email' => 'org.admin@example.test', 'phone' => '',
    'city_state' => 'Albany, NY', 'student_count' => '40', 'student_age_range' => '12-17',
    'program_interest' => 'Leadership pilot', 'preferred_contact_time' => 'Afternoons', 'message' => '',
]);
$requestId = persist_demo_request($values, '192.0.2.0/24');
$request = organization_demo_request($requestId);
org_request_assert(is_array($request) && ($request['status'] ?? '') === 'new', 'New request must enter review queue.');
org_request_assert(!array_key_exists('network_hash', $request), 'Master Admin view must not expose network hashes.');
org_request_assert(suggested_organization_id('Synthetic Learning Center') === 'SYNTHETIC-LEARNING-CENTER', 'Organization code suggestion must be stable.');
org_request_assert(organization_id_is_valid('SYNTHETIC-LEARNING-CENTER'), 'Valid organization code must pass.');
org_request_assert(!organization_id_is_valid('../OTHER'), 'Unsafe organization code must fail.');

$masterAdmin = ['id' => 'master-admin', 'email' => YUVA_PLATFORM_ADMIN_EMAIL,
    'role' => YUVA_ROLE_MASTER_ADMIN, 'organization_id' => YUVA_PLATFORM_ORGANIZATION_ID];
org_request_assert(provision_organization_admin_invitation($masterAdmin, 'SYNTHETIC-LEARNING-CENTER',
    (string) $request['contact_name'], (string) $request['email'], YUVA_ROLE_ORGANIZATION_ADMIN,
    'pending_invitation'), 'Approval must provision a secure invitation.');
org_request_assert(record_demo_request_decision($masterAdmin, $requestId, 'approved',
    'SYNTHETIC-LEARNING-CENTER'), 'Approval decision must persist.');

$approved = organization_demo_request($requestId);
org_request_assert(($approved['status'] ?? '') === 'approved', 'Approved status must appear in Master Admin queue.');
$account = organization_admin_by_email('org.admin@example.test');
org_request_assert(is_array($account) && ($account['status'] ?? '') === 'pending_invitation', 'Approved administrator must remain pending until activation.');
org_request_assert(($account['password_hash'] ?? '') === '', 'Master Admin must not assign the administrator password.');
$delivery = file_get_contents(organization_admin_invitation_delivery_file());
org_request_assert(is_string($delivery) && str_contains($delivery, 'organization-admin-activate.php?token='), 'Activation invitation must contain the secure activation route.');
preg_match('/organization-admin-activate\.php\?token=([^"\\s]+)/', $delivery, $matches);
$token = isset($matches[1]) ? rawurldecode($matches[1]) : '';
org_request_assert($token !== '' && complete_organization_admin_invitation($token, 'SecureOrgAdmin@123'), 'Invited administrator must set a policy-compliant password.');
$identity = authenticate_admin_account('org.admin@example.test', 'SecureOrgAdmin@123');
org_request_assert(is_array($identity) && ($identity['role'] ?? '') === YUVA_ROLE_ORGANIZATION_ADMIN, 'Activated administrator must authenticate with the organization role.');
org_request_assert(($identity['redirect'] ?? '') === 'organization-admin.php', 'Organization administrator must reach the scoped dashboard.');

$handler = file_get_contents($expectedRoot . '/admin-organization-request-actions.php');
org_request_assert(is_string($handler) && str_contains($handler, 'require_admin_post([YUVA_ROLE_MASTER_ADMIN])'), 'Approval handler must enforce Master Admin POST and CSRF.');
org_request_assert(!str_contains($handler, 'password_hash'), 'Approval handler must never create a password.');
$dashboard = file_get_contents($expectedRoot . '/organization-admin.php');
org_request_assert(is_string($dashboard) && str_contains($dashboard, 'require_admin([YUVA_ROLE_ORGANIZATION_ADMIN])'), 'Organization dashboard must enforce the organization role.');

echo "Organization request approval tests passed.\n";
