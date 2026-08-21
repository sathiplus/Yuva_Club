<?php
declare(strict_types=1);

$expectedRoot = realpath(__DIR__ . '/../..');
$isolatedRoot = realpath((string) getenv('YUVA_TEST_ISOLATED_ROOT'));
if ($expectedRoot === false || $isolatedRoot === false || $expectedRoot !== $isolatedRoot) {
    throw new RuntimeException('Demo request test must use the isolated validation runner.');
}
require_once $expectedRoot . '/portal-lib.php';

function demo_assert(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

$values = demo_request_values([
    'organization_name' => 'Synthetic School', 'organization_type' => 'School',
    'contact_name' => 'Test Contact', 'email' => ' DEMO@EXAMPLE.TEST ', 'phone' => '',
    'city_state' => 'Albany, NY', 'student_count' => '120', 'student_age_range' => '11-17',
    'program_interest' => 'Communication and leadership pilot',
    'preferred_contact_time' => 'Weekday afternoon ET', 'message' => 'Synthetic test only',
]);
demo_assert($values['email'] === 'demo@example.test', 'Email must be normalized.');
demo_assert(demo_request_validation_error($values) === '', 'Complete request must validate.');
$invalid = $values; $invalid['organization_name'] = '';
demo_assert(demo_request_validation_error($invalid) !== '', 'Missing required field must fail.');
$invalid = $values; $invalid['email'] = 'invalid';
demo_assert(demo_request_validation_error($invalid) !== '', 'Invalid email must fail.');

$requestId = persist_demo_request($values, '192.0.2.0/24');
demo_assert(str_starts_with($requestId, 'DEMO-'), 'Request must receive a reference ID.');
$lines = file(demo_requests_file(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
demo_assert(is_array($lines) && count($lines) === 1, 'Exactly one append-only request must persist.');
$stored = json_decode((string) $lines[0], true);
demo_assert(is_array($stored) && ($stored['request_id'] ?? '') === $requestId, 'Stored request identity must match.');
demo_assert(($stored['network_hash'] ?? '') === hash('sha256', '192.0.2.0/24'), 'Only a network hash may persist.');
demo_assert(!str_contains((string) $lines[0], '192.0.2.0/24'), 'Raw network address must not persist.');

for ($i = 0; $i < 5; $i++) {
    demo_assert(!demo_request_rate_limited('198.51.100.0/24', 1900000000), 'First five hourly attempts must be allowed.');
}
demo_assert(demo_request_rate_limited('198.51.100.0/24', 1900000000), 'Sixth hourly attempt must be blocked.');

$page = file_get_contents($expectedRoot . '/demo-request.php');
demo_assert($page !== false && str_contains($page, 'verify_csrf_token'), 'Handler must enforce CSRF.');
demo_assert(str_contains($page, "name=\"website\""), 'Handler must include a honeypot.');
demo_assert(substr_count($page, 'send_yuva_email(') === 2, 'Handler must send admin and requester emails.');
demo_assert(!str_contains($page, 'create_organization_admin'), 'Demo request must not create organization access.');

echo "Demo request workflow tests passed.\n";
