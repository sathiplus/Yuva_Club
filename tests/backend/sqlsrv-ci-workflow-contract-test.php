<?php
declare(strict_types=1);

$workflow = file_get_contents(__DIR__ . '/../../.github/workflows/sqlsrv-auth-integration.yml');
if (!is_string($workflow)) throw new RuntimeException('SQL integration workflow is missing.');
$required = [
    'mcr.microsoft.com/mssql/server:2022-latest',
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
fwrite(STDOUT, "PASS disposable SQL Server workflow and unconditional cleanup contract\n");
