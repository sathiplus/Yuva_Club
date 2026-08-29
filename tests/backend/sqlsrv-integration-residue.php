<?php
declare(strict_types=1);

require_once __DIR__ . '/sqlsrv-integration-environment.php';
$config = yuva_configure_sqlsrv_integration_environment();
require_once __DIR__ . '/../../backend/database.php';
$pdo = Database::connection();
yuva_assert_sqlsrv_integration_identity($pdo, $config);
$row = $pdo->query(
    "SELECT
       (SELECT COUNT_BIG(*) FROM dbo.users WHERE email LIKE N'synthetic.%@example.test') users_count,
       (SELECT COUNT_BIG(*) FROM dbo.students WHERE yuva_id LIKE N'YCT%' OR yuva_id LIKE N'YPT%') students_count,
       (SELECT COUNT_BIG(*) FROM dbo.registrations WHERE parent_email LIKE N'synthetic.%@example.test') registrations_count"
)->fetch(PDO::FETCH_ASSOC);
foreach (['users_count', 'students_count', 'registrations_count'] as $field) {
    if ((int)($row[$field] ?? -1) !== 0) {
        throw new RuntimeException('Synthetic SQL integration residue remains: ' . $field);
    }
}
fwrite(STDOUT, "PASS zero SQL integration fixture residue\n");
