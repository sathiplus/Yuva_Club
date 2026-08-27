$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$php = Get-Command php -ErrorAction SilentlyContinue
if ($null -eq $php) { throw 'PHP CLI is not installed or is not available on PATH.' }
$files = @(
    'backend/competition.php',
    'admin-competition-action.php',
    'student-competition-entry.php',
    'student-competition-submit.php',
    'competition-admin-panel.php',
    'admin.php',
    'organization-admin.php',
    'portal-lib.php',
    'portal.php',
    'tests/backend/competition-foundation-phase2c1-test.php'
)
foreach ($file in $files) {
    & php -l (Join-Path $root $file)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed for $file" }
}
Write-Output 'Phase 2C.1 PHP syntax checks passed.'
