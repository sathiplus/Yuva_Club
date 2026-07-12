<?php
declare(strict_types=1);

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_HOST'] = 'ci.yuvaclub.test';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Phase2AFunctionalSecurityTests';

require __DIR__ . '/../portal-lib.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function assert_false(bool $condition, string $message): void {
    assert_true(!$condition, $message);
}

function reset_test_environment(): void {
    $paths = [
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-accounts.json',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-student-links.json',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-activation-tokens.json',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-activation-delivery.jsonl',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'security-audit-log.jsonl',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'login-attempts.json',
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations-current.csv',
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations-full.csv',
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations.csv',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

function write_test_registrations(): void {
    $dir = portal_path('submissions');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $headers = registration_headers();
    $rows = [
        [
            'Individual', 'YC2026001', '', 'Linked', 'Student', '', '2012-01-01', '14', 'School Yuva (Ages 13-17)', '8', 'Test School', 'Test City',
            'Linked Parent', 'Mother', 'linked.parent@example.test', '555-0101', 'student1@example.test', '', '', 'Leadership', 'Testing', 'Beginner', 'Leadership', 'Mondays', '', 'Yes', 'Yes', 'Yes', '127.0.0.1',
        ],
        [
            'Individual', 'YC2026002', '', 'Second', 'Student', '', '2011-01-01', '15', 'School Yuva (Ages 13-17)', '9', 'Test School', 'Test City',
            'Linked Parent', 'Mother', 'linked.parent@example.test', '555-0101', 'student2@example.test', '', '', 'Science', 'Testing', 'Beginner', 'Science', 'Tuesdays', '', 'Yes', 'Yes', 'Yes', '127.0.0.1',
        ],
        [
            'Individual', 'YC2026003', '', 'Unlinked', 'Student', '', '2010-01-01', '16', 'School Yuva (Ages 13-17)', '10', 'Other School', 'Other City',
            'Other Parent', 'Father', 'other.parent@example.test', '555-0102', 'student3@example.test', '', '', 'Arts', 'Testing', 'Beginner', 'Arts', 'Wednesdays', '', 'Yes', 'Yes', 'Yes', '127.0.0.1',
        ],
    ];

    $handle = fopen($dir . DIRECTORY_SEPARATOR . 'registrations-current.csv', 'wb');
    assert_true(is_resource($handle), 'registration fixture file must open');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
}

reset_test_environment();
write_test_registrations();

$knownEmail = 'linked.parent@example.test';
$unknownEmail = 'unknown.parent@example.test';

assert_true(parent_has_existing_relationship($knownEmail), 'registered parent relationship should be detected');
assert_false(parent_has_existing_relationship($unknownEmail), 'unknown email must not create a relationship');

$token = create_parent_activation_token($knownEmail);
assert_true(is_string($token) && strlen($token) > 80 && str_contains($token, '.'), 'activation token should be random and structured');

$tokens = parent_activation_tokens();
assert_true(count($tokens) === 1, 'one activation token should be stored');
$stored = reset($tokens);
assert_true(is_array($stored), 'stored activation record should be an array');
assert_true(($stored['parent_email'] ?? '') === $knownEmail, 'activation record should reference parent email');
assert_true(($stored['token_hash'] ?? '') !== '', 'activation token hash should be stored');
assert_false(str_contains(json_encode($tokens), explode('.', $token, 2)[1]), 'raw activation token must not be stored');

$record = parent_activation_record($token);
assert_true(is_array($record), 'valid activation token should resolve before use');

assert_true(complete_parent_activation($token, 'SecureParent@123'), 'valid activation should complete');
assert_false(complete_parent_activation($token, 'SecureParent@123'), 'used activation token must not be reusable');

$account = parent_account_by_email($knownEmail);
assert_true(is_array($account), 'activated parent account should exist');
assert_true(($account['status'] ?? '') === 'active', 'activated parent account should be active');
assert_true(($account['email_verified'] ?? false) === true, 'parent email should be verified only after activation');
assert_true(parent_password_matches($knownEmail, 'SecureParent@123'), 'activated parent password should work');
assert_false(parent_password_matches($knownEmail, 'WrongParent@123'), 'incorrect parent password should fail');
assert_true(password_get_info((string) ($account['password_hash'] ?? ''))['algo'] !== 0, 'parent password must use PHP password hashing');

$linkedStudents = parent_linked_students($knownEmail);
assert_true(isset($linkedStudents['YC2026001'], $linkedStudents['YC2026002']), 'existing sibling links should remain intact');
assert_false(isset($linkedStudents['YC2026003']), 'activation must not create unauthorized student link');
assert_true(parent_can_access_student($knownEmail, 'YC2026001'), 'parent should access linked student');
assert_false(parent_can_access_student($knownEmail, 'YC2026003'), 'parent should not access unlinked student');

$links = parent_student_links();
$links[$knownEmail]['YC2026001']['status'] = 'inactive';
write_parent_student_links($links);
assert_false(parent_can_access_student($knownEmail, 'YC2026001'), 'inactive parent-student links should be denied');
$links[$knownEmail]['YC2026001']['status'] = 'active';
write_parent_student_links($links);

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_email'] = YUVA_PLATFORM_ADMIN_EMAIL;
$_SESSION['admin_role'] = YUVA_ROLE_MASTER_ADMIN;
$_SESSION['admin_organization_id'] = YUVA_PLATFORM_ORGANIZATION_ID;
$_SESSION['admin_session_started_at'] = time();
$admin = current_admin_identity();
assert_true(is_array($admin) && $admin['role'] === YUVA_ROLE_MASTER_ADMIN, 'master admin identity should resolve from server session');

$_SESSION['admin_email'] = 'org.admin@example.test';
$_SESSION['admin_role'] = YUVA_ROLE_ORGANIZATION_ADMIN;
$orgAdmin = current_admin_identity();
assert_true(is_array($orgAdmin) && $orgAdmin['role'] === YUVA_ROLE_ORGANIZATION_ADMIN, 'organization admin role should remain distinct from MasterAdmin');
assert_false($orgAdmin['role'] === YUVA_ROLE_MASTER_ADMIN, 'organization admin must not become MasterAdmin');

$csrf = csrf_token();
assert_true(verify_csrf_token($csrf), 'valid CSRF token should verify');
assert_false(verify_csrf_token('invalid-token'), 'invalid CSRF token should fail');

$audit = file_exists(security_audit_file()) ? file_get_contents(security_audit_file()) : '';
assert_true(is_string($audit) && str_contains($audit, 'parent.activation.requested'), 'activation request should be audited');
assert_true(str_contains($audit, 'parent.activation.completed'), 'activation completion should be audited');
assert_false(str_contains($audit, 'SecureParent@123'), 'audit log must not contain passwords');
assert_false(str_contains($audit, explode('.', $token, 2)[1]), 'audit log must not contain raw activation token');

echo "Phase 2A functional security checks passed.\n";
