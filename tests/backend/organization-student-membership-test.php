<?php
declare(strict_types=1);

function membership_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
$service = file_get_contents($root . '/backend/organization-membership.php');
$migration = file_get_contents($root . '/database/11-organization-student-memberships.azure-sql.sql');
$rollback = file_get_contents($root . '/database/11-organization-student-memberships-rollback.sql');
$organizationHandler = file_get_contents($root . '/organization-student-request.php');
$studentHandler = file_get_contents($root . '/student-organization-membership-action.php');
$parentHandler = file_get_contents($root . '/parent-organization-membership-action.php');
$organizationPage = file_get_contents($root . '/organization-admin.php');
$studentPage = file_get_contents($root . '/portal.php');
$parentPage = file_get_contents($root . '/parent.php');
$registration = file_get_contents($root . '/registration.php');
$submission = file_get_contents($root . '/submit-registration.php');

foreach ([$service, $migration, $rollback, $organizationHandler, $studentHandler, $parentHandler, $organizationPage, $studentPage, $parentPage, $registration, $submission] as $contents) {
    membership_check($contents !== false, 'A Phase 2A implementation file is missing.');
}

foreach (['Invited', 'StudentAccepted', 'ParentApprovalPending', 'Active', 'Declined', 'Expired', 'Withdrawn', 'Archived', 'Removed'] as $status) {
    membership_check(str_contains($migration, "N'{$status}'"), "Migration 11 is missing status {$status}.");
}
foreach (['organization_student_membership_requests', 'organization_membership_tokens', 'organization_membership_audit', 'ROWVERSION', 'ux_org_student_membership_active_student'] as $contract) {
    membership_check(str_contains($migration, $contract), "Migration 11 is missing {$contract}.");
}
membership_check(str_contains($migration, "OBJECT_ID(N'dbo.organization_student_membership_requests', N'U') IS NULL"), 'Migration 11 must be idempotent.');
membership_check(str_contains($rollback, "DB_NAME() = N'yuva_club'"), 'Rollback must refuse the live database.');
membership_check(!str_contains($migration, 'dbo.organizations'), 'Migration 11 must not depend on skipped Migration 05 organization tables.');

membership_check(str_contains($service, 'random_bytes(32)'), 'Membership tokens must be cryptographically random.');
membership_check(str_contains($service, "hash('sha256', \$rawToken)"), 'Only hexadecimal SHA-256 membership token hashes may be queried.');
membership_check(str_contains($service, "hash('sha256', \$raw)"), 'Only hexadecimal SHA-256 membership token hashes may be persisted.');
membership_check(substr_count($service, 'CONVERT(BINARY(32), :token_hash, 2)') === 2, 'Token insert and lookup must use the same SQLSRV-safe hexadecimal conversion.');
membership_check(substr_count($service, "bindValue(':token_hash'") === 2 && substr_count($service, 'PDO::PARAM_STR') >= 2, 'Token hashes must be bound as strings, not raw binary LOB values.');
membership_check(!str_contains($service, "hash('sha256', \$rawToken, true)") && !str_contains($service, "hash('sha256', \$raw, true)"), 'Raw binary token hashes must not cross the PDO SQLSRV boundary.');
membership_check(str_contains($service, 'used_at IS NULL') && str_contains($service, 'expires_at > SYSUTCDATETIME()'), 'Tokens must be single-use and expiring.');
membership_check(str_contains($service, "if (\$dateOfBirth === '')") && str_contains($service, 'catch (Throwable)'), 'Missing or invalid DOB must fail closed to parent approval.');
membership_check(str_contains($service, 'hasConflictingActiveMembership'), 'Cross-organization active membership conflicts must be rejected.');
membership_check(str_contains($service, "'neutral' => true"), 'Unresolved existing-student requests must return a neutral result.');

membership_check(str_contains($organizationHandler, 'require_admin_post([YUVA_ROLE_ORGANIZATION_ADMIN])'), 'Organization request handler must enforce role and CSRF.');
membership_check(str_contains($organizationHandler, 'If the student can be contacted'), 'Existing-student result must be anti-enumeration neutral.');
membership_check(str_contains($studentHandler, 'require_student()') && str_contains($studentHandler, 'verify_csrf_token'), 'Student decisions must require student auth and CSRF.');
membership_check(str_contains($parentHandler, 'require_parent_student()') && str_contains($parentHandler, 'parent_can_access_student'), 'Parent decisions must require the linked parent.');
membership_check(str_contains($organizationPage, 'requestsForOrganization($organizationId)'), 'Organization Admin membership lists must be organization-scoped.');
membership_check(!str_contains($organizationPage, 'password_hash'), 'Organization Admin UI must not expose password hashes.');
membership_check(str_contains($studentPage, 'student-organization-membership-action.php'), 'Student portal must expose accept/decline controls.');
membership_check(str_contains($parentPage, 'parent-organization-membership-action.php'), 'Parent portal must expose approval controls.');
membership_check(str_contains($registration, 'organization_invitation_token') && str_contains($submission, 'attachRegistration'), 'New registration must securely retain the invitation relationship.');

echo "Organization student membership contracts: PASS\n";
