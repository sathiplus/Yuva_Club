<?php
declare(strict_types=1);

// Execute the actual report, replacing only its input tables and clock. No dbo writes.
require_once __DIR__ . '/sqlsrv-integration-environment.php';
$config = yuva_configure_sqlsrv_integration_environment();
require_once __DIR__ . '/../../backend/database.php';
if (getenv('YUVA_RUN_SQL_INTEGRATION') !== 'YES') {
    throw new RuntimeException('Explicit disposable SQL integration opt-in required.');
}
$pdo = Database::connection();
yuva_assert_sqlsrv_integration_identity($pdo, $config);
$tables = [
    'student_entitlements' => 'student_id BIGINT,source_type NVARCHAR(40),source_reference NVARCHAR(40),starts_at DATETIME2',
    'quick_challenge_attempts' => 'id BIGINT,student_id BIGINT,status NVARCHAR(30),started_at DATETIME2,submitted_at DATETIME2',
    'quick_challenge_evaluations' => 'id BIGINT,student_id BIGINT,template_version_id BIGINT,total_score DECIMAL(5,2),benchmark_score INT,completed_at DATETIME2,status NVARCHAR(30)',
    'student_challenge_personal_bests' => 'student_id BIGINT,achieved_at DATETIME2',
    'activity_logs' => 'actor_user_id BIGINT,action NVARCHAR(80),created_at DATETIME2',
    'students' => 'id BIGINT,user_id BIGINT',
];
$sql = file_get_contents(__DIR__ . '/../../tools/beta1-metrics-report.sql');
if (!is_string($sql)) throw new RuntimeException('Report unavailable.');
$sql = str_replace('DECLARE @report_at DATETIME2=SYSUTCDATETIME();', "DECLARE @report_at DATETIME2='2026-09-13T12:00:00';", $sql, $clockReplacements);
if ($clockReplacements !== 1) throw new RuntimeException('Report clock must be controlled by this test.');
foreach ($tables as $name => $columns) {
    $sql = str_replace('dbo.' . $name, '#w2_' . $name, $sql);
}
$t = new DateTimeImmutable('2026-08-30T12:00:00Z'); // Sunday; day 1 is Monday.
$at = static fn(int $seconds): string => $t->modify('+' . $seconds . ' seconds')->format('Y-m-d\TH:i:s');
$day = 86400;
// label, activity offsets, expected numerator, expected denominator, activation offset, status
$cases = [
    ['A before day 7', [7*$day-1], 0, 1],
    ['B exactly day 7', [7*$day], 1, 1],
    ['C day 8', [8*$day], 1, 1],
    ['D before day 14', [14*$day-1], 1, 1],
    ['E exactly day 14', [14*$day], 0, 1],
    ['F week 1 only', [$day,6*$day], 0, 1],
    ['G week 3/4 only', [15*$day,22*$day], 0, 1],
    ['H Sunday Monday', [0,$day], 0, 1],
    ['I week 1 plus week 4', [$day,22*$day], 0, 1],
    ['J immature even with return', [8*$day], 0, 0, 1],
    ['K mature no activity', [], 0, 1],
    ['L mature with return', [8*$day], 1, 1],
    ['M empty cohort', [], 0, 0, null],
    ['Incomplete challenge excluded', [8*$day], 0, 1, 0, 'InProgress'],
    ['Duplicate activity counts once', [8*$day,9*$day], 1, 1],
    ['Passive event excluded', [], 0, 1],
    ['Renewal keeps original anchor', [8*$day], 1, 1],
    ['Other campaign excluded', [8*$day], 0, 0],
    ['Mixed mature and immature cohort', [8*$day], 1, 2],
];
$created = [];
try {
    $pdo->exec('SET NOCOUNT ON');
    foreach ($tables as $name => $columns) {
        $pdo->exec('CREATE TABLE #w2_' . $name . ' (' . $columns . ')');
        $created[] = $name;
    }
    foreach ($cases as $case) {
        [$label,$offsets,$numerator,$denominator] = $case;
        foreach ($tables as $name => $_) $pdo->exec('DELETE FROM #w2_' . $name);
        $activation = array_key_exists(4,$case) ? $case[4] : 0;
        if ($activation !== null) {
            $grant = $pdo->prepare('INSERT #w2_student_entitlements VALUES(1,?,?,?)');
            $grant->execute(['PREMIUM_BETA_PROMO',$label === 'Other campaign excluded' ? 'OTHER' : 'YUVA-BETA-1',$at($activation)]);
            if ($label === 'Renewal keeps original anchor') $grant->execute(['PREMIUM_BETA_PROMO','YUVA-BETA-1',$at(10*$day)]);
        }
        $insert = $pdo->prepare('INSERT #w2_quick_challenge_attempts VALUES(?,1,?,?,?)');
        foreach ($offsets as $i => $offset) {
            // Start deliberately predates completion: meaningful time is submitted_at.
            $insert->execute([$i+1,$case[5] ?? 'Submitted',$at(0),$at($offset)]);
        }
        if ($label === 'Passive event excluded') {
            $pdo->exec("INSERT #w2_activity_logs VALUES(1,N'beta.first_login','2026-09-07'),(1,N'beta.my_growth_viewed','2026-09-07')");
        }
        if ($label === 'Mixed mature and immature cohort') {
            $pdo->exec("INSERT #w2_student_entitlements VALUES(2,N'PREMIUM_BETA_PROMO',N'YUVA-BETA-1','2026-08-30T12:00:00'),(3,N'PREMIUM_BETA_PROMO',N'YUVA-BETA-1','2026-09-01T12:00:00')");
            $pdo->exec("INSERT #w2_quick_challenge_attempts VALUES(3,3,N'Submitted','2026-09-09','2026-09-09')");
        }
        $statement = $pdo->query($sql);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        $rate = $denominator === 0 ? null : 100.0*$numerator/$denominator;
        if (!is_array($row) || (int)$row['week2_students'] !== $numerator
            || (int)$row['week2_eligible_students'] !== $denominator
            || ($rate === null ? $row['week2_return_pct'] !== null : (float)$row['week2_return_pct'] !== $rate)) {
            throw new RuntimeException('Week-2 report failed: ' . $label);
        }
        echo 'PASS Week-2 ' . $label . "\n";
    }
} finally {
    foreach (array_reverse($created) as $name) $pdo->exec('DROP TABLE #w2_' . $name);
}
echo "PASS actual Beta report through PDO SQLSRV; session fixture cleanup complete\n";
