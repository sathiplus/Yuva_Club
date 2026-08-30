<?php
declare(strict_types=1);

$workflow = file_get_contents(__DIR__ . '/../../.github/workflows/sqlsrv-auth-integration.yml');
if (!is_string($workflow)) throw new RuntimeException('SQL integration workflow is missing.');
$required = [
    'mcr.microsoft.com/mssql/server:2022-latest',
    'msodbcsql18',
    'YUVA_TEST_DB_EPHEMERAL: "YES"',
    'php tests/backend/student-login-integration.php',
    'php tests/backend/parent-login-integration.php',
    'php tests/backend/sqlsrv-integration-residue.php',
    'if: always()',
    'intentional failure-path probe',
    "grep -qx ABSENT",
    'DROP DATABASE',
    'yuva_club_ci_${{ github.run_id }}_${{ github.run_attempt }}',
];
foreach ($required as $literal) {
    if (!str_contains($workflow, $literal)) throw new RuntimeException('Workflow safety contract missing: ' . $literal);
}
if (str_contains($workflow, 'yuva_club_phasea_test')) throw new RuntimeException('Workflow retains permanent test database dependency.');

$environmentHelper = file_get_contents(__DIR__ . '/sqlsrv-integration-environment.php');
$bootstrap = file_get_contents(__DIR__ . '/../../tools/bootstrap-sqlsrv-integration.php');
if (!is_string($environmentHelper) || !is_string($bootstrap)) {
    throw new RuntimeException('SQL integration environment/bootstrap source is missing.');
}
foreach ([$environmentHelper, $bootstrap] as $source) {
    if (!str_contains($source, "putenv('DB_USERNAME='")) {
        throw new RuntimeException('SQL integration must map credentials to the canonical DB_USERNAME setting.');
    }
    if (str_contains($source, "putenv('DB_USER='")) {
        throw new RuntimeException('Obsolete DB_USER mapping remains in SQL integration setup.');
    }
}
fwrite(STDOUT, "PASS disposable SQL Server workflow and unconditional cleanup contract\n");
fwrite(STDOUT, "PASS canonical database configuration mapping contract\n");
