$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$php = Get-Command php -ErrorAction SilentlyContinue

if ($php -eq $null) {
    throw 'PHP CLI is not installed or is not available on PATH.'
}

$changedPhpFiles = @(
    'admin-actions.php',
    'admin-ai-apply.php',
    'admin-ai-review.php',
    'admin-bulk-session-actions.php',
    'admin-hub-actions.php',
    'admin-login.php',
    'admin-meeting-actions.php',
    'admin-password-actions.php',
    'admin-student-edit.php',
    'admin.php',
    'parent-activate.php',
    'parent-login.php',
    'parent.php',
    'portal-lib.php',
    'portal-logout.php',
    'submit-registration.php',
    'tests/phase-2a-functional-security-tests.php',
    'tools/phase-2a-parent-reconciliation.php'
)

Write-Output ('PHP version: ' + (& php -r "echo PHP_VERSION;"))

foreach ($file in $changedPhpFiles) {
    $path = Join-Path $root $file
    Write-Output ("Checking $file")
    & php -l $path
    if ($LASTEXITCODE -ne 0) {
        throw "PHP syntax check failed for $file"
    }
}

Write-Output 'Phase 2A PHP syntax checks passed.'
