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
Assert-FileContains 'admin-password-actions.php' 'YUVA_PLATFORM_ADMIN_EMAIL' 'MasterAdmin email must be fixed.'
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
Assert-FileContains 'portal-lib.php' 'sync_parent_links_from_registrations' 'Existing parent relationships must be reconciled from registration records.'
Assert-FileContains 'parent-activate.php' 'If that email is connected' 'Parent activation request response must be generic.'
Assert-FileContains 'parent-activate.php' 'verify_csrf_token' 'Parent activation forms must verify CSRF.'
Assert-FileContains 'tools/phase-2a-parent-reconciliation.php' 'email_hash' 'Parent reconciliation must avoid exposing parent emails.'

Write-Output 'Phase 2A static security checks passed.'
