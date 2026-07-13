<?php
declare(strict_types=1);

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['HTTP_HOST'] = 'ci.yuvaclub.test';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Phase2AFunctionalSecurityTests';
putenv('YUVA_CAPTURE_ADMIN_INVITATION_LINKS=1');

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
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-accounts.json',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-invitation-tokens.json',
        portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-invitation-delivery.jsonl',
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
    $rows = array_map(
        static fn (array $record): array => array_map(static fn (string $header): string => (string) ($record[$header] ?? ''), $headers),
        [
            [
                'Submitted At' => gmdate('c'),
                'Yuva Club ID' => 'YC2026001',
                'Membership Type' => 'Individual',
                'Student First Name' => 'Linked',
                'Student Last Name' => 'Student',
                'Date of Birth' => '2012-01-01',
                'Age' => '14',
                'Program Group' => 'School Yuva (Ages 13-17)',
                'Grade' => '8',
                'School' => 'Test School',
                'City/State' => 'Test City',
                'Parent/Guardian Name' => 'Linked Parent',
                'Relationship' => 'Mother',
                'Parent Email' => 'linked.parent@example.test',
                'Parent Phone Number' => '555-0101',
                'Student Email' => 'student1@example.test',
                'Interests' => 'Leadership',
                'Why Join' => 'Testing',
                'Presentation Experience' => 'Beginner',
                'Presentation Topics' => 'Leadership',
                'Preferred Schedule' => 'Mondays',
                'Code of Conduct Agreement' => 'Yes',
                'Recording Agreement' => 'Yes',
                'Parent Permission' => 'Yes',
                'IP Address' => '127.0.0.1',
            ],
            [
                'Submitted At' => gmdate('c'),
                'Yuva Club ID' => 'YC2026002',
                'Membership Type' => 'Individual',
                'Student First Name' => 'Second',
                'Student Last Name' => 'Student',
                'Date of Birth' => '2011-01-01',
                'Age' => '15',
                'Program Group' => 'School Yuva (Ages 13-17)',
                'Grade' => '9',
                'School' => 'Test School',
                'City/State' => 'Test City',
                'Parent/Guardian Name' => 'Linked Parent',
                'Relationship' => 'Mother',
                'Parent Email' => 'linked.parent@example.test',
                'Parent Phone Number' => '555-0101',
                'Student Email' => 'student2@example.test',
                'Interests' => 'Science',
                'Why Join' => 'Testing',
                'Presentation Experience' => 'Beginner',
                'Presentation Topics' => 'Science',
                'Preferred Schedule' => 'Tuesdays',
                'Code of Conduct Agreement' => 'Yes',
                'Recording Agreement' => 'Yes',
                'Parent Permission' => 'Yes',
                'IP Address' => '127.0.0.1',
            ],
            [
                'Submitted At' => gmdate('c'),
                'Yuva Club ID' => 'YC2026003',
                'Membership Type' => 'Individual',
                'Student First Name' => 'Unlinked',
                'Student Last Name' => 'Student',
                'Date of Birth' => '2010-01-01',
                'Age' => '16',
                'Program Group' => 'School Yuva (Ages 13-17)',
                'Grade' => '10',
                'School' => 'Other School',
                'City/State' => 'Other City',
                'Parent/Guardian Name' => 'Other Parent',
                'Relationship' => 'Father',
                'Parent Email' => 'other.parent@example.test',
                'Parent Phone Number' => '555-0102',
                'Student Email' => 'student3@example.test',
                'Interests' => 'Arts',
                'Why Join' => 'Testing',
                'Presentation Experience' => 'Beginner',
                'Presentation Topics' => 'Arts',
                'Preferred Schedule' => 'Wednesdays',
                'Code of Conduct Agreement' => 'Yes',
                'Recording Agreement' => 'Yes',
                'Parent Permission' => 'Yes',
                'IP Address' => '127.0.0.1',
            ],
        ]
    );

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

$masterAdmin = [
    'id' => admin_actor_id(YUVA_PLATFORM_ADMIN_EMAIL),
    'email' => YUVA_PLATFORM_ADMIN_EMAIL,
    'role' => YUVA_ROLE_MASTER_ADMIN,
    'organization_id' => YUVA_PLATFORM_ORGANIZATION_ID,
];
$orgAdminEmail = 'chapter.admin@example.test';
assert_true(
    provision_organization_admin_invitation($masterAdmin, 'SSP-NY', 'Chapter Admin', $orgAdminEmail, YUVA_ROLE_ORGANIZATION_ADMIN, 'pending_invitation'),
    'master admin should create organization admin invitation'
);
assert_false(
    provision_organization_admin_invitation($masterAdmin, 'SSP-NY', 'Bad Admin', 'bad.master@example.test', YUVA_ROLE_MASTER_ADMIN, 'pending_invitation'),
    'master admin must not grant MasterAdmin role to organization users'
);

$orgAccount = organization_admin_by_email($orgAdminEmail);
assert_true(is_array($orgAccount), 'pending organization admin account should exist');
assert_true(($orgAccount['role'] ?? '') === YUVA_ROLE_ORGANIZATION_ADMIN, 'organization admin role should be stored');
assert_true(($orgAccount['organization_id'] ?? '') === 'SSP-NY', 'organization assignment should be stored');
assert_true(($orgAccount['status'] ?? '') === 'pending_invitation', 'organization admin should start pending');
assert_true(($orgAccount['password_hash'] ?? '') === '', 'master admin must not assign organization admin password');

$orgTokens = organization_admin_invitation_tokens();
assert_true(count($orgTokens) === 1, 'one organization admin invitation token should be stored');
$orgTokenRecord = reset($orgTokens);
assert_true(is_array($orgTokenRecord), 'organization admin token record should be an array');
assert_true(($orgTokenRecord['token_hash'] ?? '') !== '', 'organization admin token hash should be stored');
$delivery = file_get_contents(organization_admin_invitation_delivery_file());
assert_true(is_string($delivery) && str_contains($delivery, 'organization-admin-activate.php'), 'invitation delivery should capture staging-safe activation URL in test mode');
$invitationUrl = json_decode(trim((string) $delivery), true)['activation_url'] ?? '';
parse_str((string) parse_url((string) $invitationUrl, PHP_URL_QUERY), $query);
$orgToken = (string) ($query['token'] ?? '');
assert_true($orgToken !== '' && str_contains($orgToken, '.'), 'organization admin activation token should be present in captured URL');
assert_false(str_contains(json_encode($orgTokens), explode('.', $orgToken, 2)[1]), 'raw organization admin token must not be stored');

assert_true(complete_organization_admin_invitation($orgToken, 'SecureOrgAdmin@123'), 'organization admin should activate with valid token and password');
assert_false(complete_organization_admin_invitation($orgToken, 'SecureOrgAdmin@123'), 'organization admin invitation token must be single use');
$activatedOrgAccount = organization_admin_by_email($orgAdminEmail);
assert_true(is_array($activatedOrgAccount), 'activated organization admin account should exist');
assert_true(($activatedOrgAccount['status'] ?? '') === 'active', 'organization admin should become active after password setup');
assert_true(($activatedOrgAccount['email_verified'] ?? false) === true, 'organization admin email should be verified after activation');
assert_true(password_get_info((string) ($activatedOrgAccount['password_hash'] ?? ''))['algo'] !== 0, 'organization admin password must use PHP password hashing');

$authenticatedOrgAdmin = authenticate_admin_account($orgAdminEmail, 'SecureOrgAdmin@123');
assert_true(is_array($authenticatedOrgAdmin), 'activated organization admin should authenticate through shared admin login');
assert_true($authenticatedOrgAdmin['role'] === YUVA_ROLE_ORGANIZATION_ADMIN, 'shared admin login should preserve OrganizationAdmin role');
assert_true($authenticatedOrgAdmin['organization_id'] === 'SSP-NY', 'shared admin login should preserve organization assignment');
$_SESSION['admin_email'] = $orgAdminEmail;
$_SESSION['admin_role'] = YUVA_ROLE_ORGANIZATION_ADMIN;
$_SESSION['admin_organization_id'] = 'SSP-NY';
$_SESSION['admin_session_started_at'] = time();
$orgAdmin = current_admin_identity();
assert_true(is_array($orgAdmin) && $orgAdmin['role'] === YUVA_ROLE_ORGANIZATION_ADMIN, 'organization admin role should remain distinct from MasterAdmin');
assert_false($orgAdmin['role'] === YUVA_ROLE_MASTER_ADMIN, 'organization admin must not become MasterAdmin');
assert_false(authenticate_admin_account($orgAdminEmail, 'WrongOrgAdmin@123') !== null, 'wrong organization admin password should fail');
assert_false(authenticate_admin_account(YUVA_PLATFORM_ADMIN_EMAIL, 'SecureOrgAdmin@123') !== null, 'organization admin password must not authenticate MasterAdmin');
assert_true(update_organization_admin_status($masterAdmin, $orgAdminEmail, 'suspended'), 'master admin should suspend organization admin');
assert_false(authenticate_admin_account($orgAdminEmail, 'SecureOrgAdmin@123') !== null, 'suspended organization admin should not authenticate');
assert_true(update_organization_admin_status($masterAdmin, $orgAdminEmail, 'active'), 'master admin should reactivate organization admin');
assert_true(update_organization_admin_assignment($masterAdmin, $orgAdminEmail, 'TEAK-NY'), 'master admin should change organization assignment');
assert_true((organization_admin_by_email($orgAdminEmail)['organization_id'] ?? '') === 'TEAK-NY', 'organization assignment should update');

$csrf = csrf_token();
assert_true(verify_csrf_token($csrf), 'valid CSRF token should verify');
assert_false(verify_csrf_token('invalid-token'), 'invalid CSRF token should fail');

$audit = file_exists(security_audit_file()) ? file_get_contents(security_audit_file()) : '';
assert_true(is_string($audit) && str_contains($audit, 'parent.activation.requested'), 'activation request should be audited');
assert_true(str_contains($audit, 'parent.activation.completed'), 'activation completion should be audited');
assert_true(str_contains($audit, 'organization_admin.invitation.send'), 'organization admin invitation should be audited');
assert_true(str_contains($audit, 'organization_admin.invitation.complete'), 'organization admin activation should be audited');
assert_false(str_contains($audit, 'SecureParent@123'), 'audit log must not contain passwords');
assert_false(str_contains($audit, 'SecureOrgAdmin@123'), 'audit log must not contain organization admin passwords');
assert_false(str_contains($audit, explode('.', $token, 2)[1]), 'audit log must not contain raw activation token');
assert_false(str_contains($audit, explode('.', $orgToken, 2)[1]), 'audit log must not contain raw organization admin invitation token');

echo "Phase 2A functional security checks passed.\n";
