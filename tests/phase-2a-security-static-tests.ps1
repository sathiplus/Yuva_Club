$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot

function Assert-FileContains {
    param(
        [string] $Path,
        [string] $Pattern,
        [string] $Message
    )
    $content = Get-Content -LiteralPath (Join-Path $root $Path) -Raw
    if ($content -notmatch $Pattern) {
        throw $Message
    }
}

function Assert-FileNotContains {
    param(
        [string] $Path,
        [string] $Pattern,
        [string] $Message
    )
    $content = Get-Content -LiteralPath (Join-Path $root $Path) -Raw
    if ($content -match $Pattern) {
        throw $Message
    }
}

Assert-FileContains 'parent-login.php' 'parent_password_matches' 'Parent login must use password authentication.'
Assert-FileContains 'parent-login.php' 'verify_csrf_token' 'Parent login must verify CSRF.'
Assert-FileContains 'parent-login.php' 'login_rate_limited' 'Parent login must be rate-limited.'
Assert-FileNotContains 'parent-login.php' "\\$_SESSION\['parent_student_id'\]\s*=" 'Parent login must not grant access using only student ID.'
Assert-FileContains 'parent.php' 'require_parent_for_student' 'Parent dashboard must enforce backend parent-student authorization.'
Assert-FileContains 'portal-lib.php' 'parent_can_access_student' 'Parent-student relationship check must exist.'
Assert-FileContains 'portal-lib.php' 'YUVA_ROLE_MASTER_ADMIN' 'MasterAdmin role constant must exist.'
Assert-FileContains 'portal-lib.php' 'current_admin_identity' 'Admin identity must be resolved server-side.'
Assert-FileContains 'portal-lib.php' 'require_admin\(array \$allowedRoles = \[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin guard must enforce allowed roles.'
Assert-FileContains 'portal-lib.php' "email === YUVA_PLATFORM_ADMIN_EMAIL" 'MasterAdmin must be restricted to admin@yuvaclub.app.'
Assert-FileContains 'admin-login.php' 'verify_csrf_token' 'Admin login must verify CSRF.'
Assert-FileContains 'admin-login.php' 'session_regenerate_id' 'Admin login must regenerate session IDs.'
Assert-FileContains 'admin-login.php' 'authenticate_admin_account' 'Admin login must use the shared admin authentication backend.'
Assert-FileContains 'portal-lib.php' "redirect' => 'organization-admin.php'" 'Shared admin authentication must redirect OrganizationAdmin users to the organization dashboard.'
Assert-FileContains 'admin-password-actions.php' 'YUVA_PLATFORM_ADMIN_EMAIL' 'MasterAdmin email must be fixed.'
Assert-FileContains 'admin-organization-admin-actions.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Organization admin provisioning must be MasterAdmin-only and CSRF-protected.'
Assert-FileNotContains 'admin-organization-admin-actions.php' 'password_hash\s*=' 'MasterAdmin must not assign organization admin password hashes directly.'
Assert-FileContains 'organization-admin-activate.php' 'complete_organization_admin_invitation' 'Organization admins must set their own password through invitation activation.'
Assert-FileContains 'organization-admin.php' 'require_admin\(\[YUVA_ROLE_ORGANIZATION_ADMIN\]\)' 'Organization dashboard must require OrganizationAdmin role.'
Assert-FileContains 'organization-admin.php' 'organization_student_memberships_for_org\(\$organizationId\)' 'Organization dashboard must load memberships using server-side OrganizationId.'
Assert-FileContains 'organization-admin.php' 'Remove From Organization' 'Organization dashboard must remove membership without deleting global student records.'
Assert-FileContains 'organization-student-actions.php' 'require_admin_post\(\[YUVA_ROLE_ORGANIZATION_ADMIN\]\)' 'Organization student actions must require OrganizationAdmin role and CSRF.'
Assert-FileContains 'organization-student-actions.php' "admin\['organization_id'\]" 'Organization student actions must resolve OrganizationId from the authenticated session.'
Assert-FileContains 'organization-student-actions.php' 'archive_membership' 'Organization student actions must archive memberships instead of deleting students.'
Assert-FileNotContains 'organization-student-actions.php' 'unset\(\$students|unlink\(|DELETE FROM students' 'Organization admins must not delete global student records.'
Assert-FileContains 'portal-lib.php' 'organization_student_can_access' 'Organization student access helper must enforce organization memberships.'
Assert-FileContains 'portal-lib.php' 'organization_student.membership.update' 'Organization membership updates must be audited.'
Assert-FileContains 'portal-lib.php' 'organization_student_invitation_delivery_file' 'Student invitations must use dedicated delivery logging.'
Assert-FileContains 'portal-lib.php' 'activate_organization_student_membership_from_registration' 'Student registration must activate matching organization invitations instead of creating duplicates.'
Assert-FileContains 'portal-lib.php' 'organization_student.invitation.accepted' 'Accepted organization student invitations must be audited.'
Assert-FileContains 'portal-lib.php' 'YUVA_EMAIL_CONNECTION_STRING' 'Outbound email must support Azure Communication Services configuration.'
Assert-FileContains 'portal-lib.php' 'send_yuva_email' 'Outbound account and invitation email must use the shared sender.'
Assert-FileContains 'submit-registration.php' 'send_yuva_email' 'Registration notifications must use the shared sender.'
Assert-FileContains 'submit-registration.php' 'activate_organization_student_membership_from_registration' 'Organization student registration must reconcile invited memberships.'
Assert-FileContains 'submit-registration.php' 'Portal registration sync failed' 'Database-backed registration must fail safely if portal account sync fails.'
Assert-FileContains 'submit-registration.php' 'create_student_account\(\$studentId' 'Database-backed registration must create a student portal account.'
Assert-FileContains 'submit-registration.php' 'create_parent_account\(\$parentEmail' 'Database-backed registration must preserve parent-student login linkage.'
Assert-FileContains 'admin-actions.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin student actions must use the POST admin guard.'
Assert-FileContains 'admin-hub-actions.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin hub actions must use the POST admin guard.'
Assert-FileContains 'admin-ai-review.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin AI review actions must use the POST admin guard.'
Assert-FileContains 'admin-ai-apply.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin AI apply actions must use the POST admin guard.'
Assert-FileContains 'admin-bulk-session-actions.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin bulk session actions must use the POST admin guard.'
Assert-FileContains 'admin-meeting-actions.php' 'require_admin_post\(\[YUVA_ROLE_MASTER_ADMIN\]\)' 'Admin meeting actions must use the POST admin guard.'
Assert-FileContains 'admin.php' 'csrf_field' 'Admin forms must include CSRF fields.'
Assert-FileContains 'database/02-phase-2a-security-foundation.sql' 'CREATE TABLE user_roles' 'Migration must create user_roles.'
Assert-FileContains 'database/02-phase-2a-security-foundation.sql' 'CREATE TABLE organizations' 'Migration must create organizations.'
Assert-FileContains 'database/02-phase-2a-security-foundation.sql' 'organization_id' 'Migration must add organization_id foundation.'
Assert-FileContains 'portal-lib.php' 'audit_log_event' 'Audit logging helper must exist.'
Assert-FileContains 'admin-actions.php' 'admin.student_record.update' 'Sensitive admin student updates must be audited.'
Assert-FileContains 'parent-login.php' 'parent.login' 'Parent login attempts must be audited.'
Assert-FileContains 'portal-lib.php' 'parent.student_access' 'Parent student access must be audited through helper.'
Assert-FileContains 'portal-lib.php' 'parent_activation_tokens_file' 'Parent activation tokens must have dedicated storage.'
Assert-FileContains 'portal-lib.php' 'token_hash' 'Parent activation tokens must store a token hash field.'
Assert-FileContains 'portal-lib.php' "hash\('sha256'" 'Parent activation tokens must use SHA-256 hashing.'
Assert-FileContains 'portal-lib.php' 'complete_parent_activation' 'Parent activation completion helper must exist.'
Assert-FileContains 'portal-lib.php' 'password_hash' 'Parent activation must store hashed passwords.'
Assert-FileContains 'portal-lib.php' 'PASSWORD_DEFAULT' 'Parent activation must use PHP password hashing.'
Assert-FileContains 'portal-lib.php' 'organization_admin_invitation_tokens_file' 'Organization admin invitation tokens must have dedicated storage.'
Assert-FileContains 'portal-lib.php' 'complete_organization_admin_invitation' 'Organization admin invitation completion helper must exist.'
Assert-FileContains 'portal-lib.php' 'organization_admin_password_matches' 'Organization admin login must verify activated hashed passwords.'
Assert-FileContains 'portal-lib.php' 'email_delivery' 'Organization admin invitation creation must record email delivery status without failing account creation.'
Assert-FileContains 'portal-lib.php' 'email === YUVA_PLATFORM_ADMIN_EMAIL' 'Organization users must not be allowed to become the platform MasterAdmin.'
Assert-FileContains 'portal-lib.php' 'sync_parent_links_from_registrations' 'Existing parent relationships must be reconciled from registration records.'
Assert-FileContains 'parent-activate.php' 'If that email is connected' 'Parent activation request response must be generic.'
Assert-FileContains 'parent-activate.php' 'verify_csrf_token' 'Parent activation forms must verify CSRF.'
Assert-FileContains 'tools/phase-2a-parent-reconciliation.php' 'email_hash' 'Parent reconciliation must avoid exposing parent emails.'
Assert-FileContains '.github/workflows/phase-2a-validation.yml' "php-version: '8.3'" 'Phase 2A PR validation must use PHP 8.3.'
Assert-FileContains '.github/workflows/phase-2a-validation.yml' 'phase-2a-final-validation' 'Phase 2A PR validation must run on the review branch.'
Assert-FileContains '.github/workflows/phase-2a-validation.yml' 'php -l' 'Phase 2A PR validation must run PHP syntax checks.'
Assert-FileContains '.github/workflows/phase-2a-validation.yml' 'phase-2a-functional-security-tests.php' 'Phase 2A PR validation must run functional security tests.'
Assert-FileContains 'tests/phase-2a-functional-security-tests.php' 'parent.activation.requested' 'Functional tests must verify parent activation audit logging.'
Assert-FileContains 'tests/phase-2a-functional-security-tests.php' 'parent_can_access_student' 'Functional tests must verify parent authorization helpers.'
Assert-FileContains 'tests/phase-2a-functional-security-tests.php' 'verify_csrf_token' 'Functional tests must verify CSRF behavior.'
Assert-FileContains 'tests/phase-2a-functional-security-tests.php' 'organization_admin.invitation.complete' 'Functional tests must verify organization admin activation audit logging.'
Assert-FileContains 'tests/phase-2a-functional-security-tests.php' 'authenticate_admin_account' 'Functional tests must verify shared admin authentication.'

Write-Output 'Phase 2A static security checks passed.'
