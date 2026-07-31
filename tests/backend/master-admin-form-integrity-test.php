<?php
declare(strict_types=1);

$expectedRoot = realpath(__DIR__ . '/../..');
$isolatedRoot = realpath((string) getenv('YUVA_TEST_ISOLATED_ROOT'));
if ($expectedRoot === false || $isolatedRoot === false || $expectedRoot !== $isolatedRoot) {
    throw new RuntimeException(
        'Master Admin form-integrity regression must run through the isolated validation runner.'
    );
}

require_once $expectedRoot . '/portal-lib.php';

function master_admin_integrity_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param array<string, mixed> $session
 * @param array<string, mixed> $post
 */
function master_admin_integrity_run_endpoint(
    string $root,
    array $session,
    array $post
): void {
    $sessionId = 'integrity' . bin2hex(random_bytes(12));
    $runner = tempnam(sys_get_temp_dir(), 'yuva-admin-integrity-');
    if ($runner === false) {
        throw new RuntimeException('Unable to create isolated endpoint runner.');
    }

    $source = <<<'PHP'
<?php
declare(strict_types=1);

$root = (string) getenv('YUVA_INTEGRITY_TEST_ROOT');
$sessionId = (string) getenv('YUVA_INTEGRITY_TEST_SESSION_ID');
$session = json_decode((string) getenv('YUVA_INTEGRITY_TEST_SESSION'), true, 512, JSON_THROW_ON_ERROR);
$post = json_decode((string) getenv('YUVA_INTEGRITY_TEST_POST'), true, 512, JSON_THROW_ON_ERROR);

session_id($sessionId);
session_start();
$_SESSION = $session;
session_write_close();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['SCRIPT_NAME'] = '/admin-actions.php';
$_POST = $post;
require $root . '/admin-actions.php';
PHP;

    file_put_contents($runner, $source);
    $environment = array_merge($_ENV, [
        'APP_ENV' => 'test',
        'PORTAL_STORAGE_MODE' => 'filesystem',
        'SQL_APPROVAL_ENABLED' => 'false',
        'YUVA_INTEGRITY_TEST_ROOT' => $root,
        'YUVA_INTEGRITY_TEST_SESSION_ID' => $sessionId,
        'YUVA_INTEGRITY_TEST_SESSION' => json_encode(
            $session,
            JSON_THROW_ON_ERROR
        ),
        'YUVA_INTEGRITY_TEST_POST' => json_encode($post, JSON_THROW_ON_ERROR),
    ]);
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, $runner],
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
        $environment
    );
    if (!is_resource($process)) {
        unlink($runner);
        throw new RuntimeException('Unable to start isolated endpoint runner.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    unlink($runner);

    master_admin_integrity_assert(
        $exitCode === 0,
        'Guarded endpoint runner failed: ' . trim((string) $stderr)
    );
    master_admin_integrity_assert(
        !str_contains((string) $stdout, 'Fatal error'),
        'Guarded endpoint emitted a fatal error.'
    );
}

/** @return array<string, mixed> */
function master_admin_integrity_session(string $csrfToken): array
{
    return [
        'admin_logged_in' => true,
        'admin_email' => YUVA_PLATFORM_ADMIN_EMAIL,
        'admin_role' => YUVA_ROLE_MASTER_ADMIN,
        'admin_organization_id' => YUVA_PLATFORM_ORGANIZATION_ID,
        'admin_session_started_at' => time(),
        'csrf_token' => $csrfToken,
    ];
}

$studentId = 'YC2026997';
$csrfToken = bin2hex(random_bytes(32));
$session = master_admin_integrity_session($csrfToken);
$baselineRecord = [
    'approved' => 'Approved',
    'attendance' => '12',
    'presentations' => '4',
    'service_hours' => '8',
    'points' => '450',
    'tokens' => '35',
    'current_rank' => 'Speaker',
    'rank_status' => 'Approved',
    'certificate_status' => 'Ready',
    'student_session_status' => 'Open',
    'reward_status' => 'Silver Reward',
    'challenge_stage' => 'Community Showcase',
    'finalist_status' => 'Eligible',
    'award_status' => 'Badge Earned',
    'teacher_feedback' => 'Keep building evidence.',
    'admin_notes' => 'Baseline note.',
];
$baselineSelection = [
    'topic_title' => 'Synthetic topic',
    'status' => 'Approved',
];
$baselineResearch = [
    'research_notes' => 'Synthetic research',
    'status' => 'Approved',
];

$resetFixtures = static function () use (
    $studentId,
    $baselineRecord,
    $baselineSelection,
    $baselineResearch
): void {
    write_json_file(portal_records_file(), [$studentId => $baselineRecord]);
    write_json_file(topic_selections_file(), [$studentId => $baselineSelection]);
    write_json_file(research_file(), [$studentId => $baselineResearch]);
};

$fullPost = [
    'student_id' => $studentId,
    'csrf_token' => $csrfToken,
    'approved' => 'Approved',
    'topic_status' => 'Needs Changes',
    'research_status' => 'Needs Changes',
    'attendance' => '13',
    'presentations' => '5',
    'service_hours' => '9.5',
    'last_duration' => '6 minutes',
    'score' => '88',
    'teacher_feedback' => '',
    'certificate_status' => 'Issued',
    'admin_notes' => 'Full update.',
    'student_session_title' => 'Leadership presentation',
    'student_session_date' => '2026-08-01',
    'student_session_start' => '10:00',
    'student_session_end' => '10:30',
    'student_session_status' => 'Completed',
    'student_zoom_url' => '',
    'student_zoom_meeting_id' => '',
    'student_zoom_password' => '',
    'current_rank' => 'Leader',
    'rank_status' => 'Eligible for Review',
    'rank_recommendation' => 'Continue practicing.',
    'mentor_feedback' => 'Good progress.',
    'points' => '500',
    'tokens' => '40',
    'reward_status' => 'Gold Reward',
    'ai_feedback_summary' => 'Approved feedback.',
    'communication_skills' => 'Clear structure.',
    'leadership_milestones' => 'Completed showcase.',
    'challenge_stage' => 'Regional Challenge',
    'challenge_region' => 'Northeast',
    'challenge_month' => '2026-08',
    'finalist_status' => 'Finalist',
    'award_status' => 'Certificate Ready',
    'judge_feedback' => 'Specific evidence.',
];
foreach (array_keys(rubric_categories()) as $rubricKey) {
    $fullPost['rubric_' . $rubricKey] = '8';
}

$resetFixtures();
master_admin_integrity_run_endpoint($expectedRoot, $session, $fullPost);
$fullRecord = student_record($studentId);
master_admin_integrity_assert(
    ($fullRecord['attendance'] ?? null) === '13'
    && ($fullRecord['teacher_feedback'] ?? null) === ''
    && ($fullRecord['current_rank'] ?? null) === 'Leader'
    && ($fullRecord['points'] ?? null) === '500'
    && (read_json_file(topic_selections_file())[$studentId]['status'] ?? null)
        === 'Needs Changes'
    && (read_json_file(research_file())[$studentId]['status'] ?? null)
        === 'Needs Changes',
    'A valid full update must persist intentional values, including empty text.'
);

$resetFixtures();
master_admin_integrity_run_endpoint($expectedRoot, $session, [
    'student_id' => $studentId,
    'csrf_token' => $csrfToken,
    'admin_notes' => 'Only this field changed.',
]);
$partialRecord = student_record($studentId);
master_admin_integrity_assert(
    ($partialRecord['admin_notes'] ?? null) === 'Only this field changed.'
    && ($partialRecord['approved'] ?? null) === 'Approved'
    && ($partialRecord['attendance'] ?? null) === '12'
    && ($partialRecord['presentations'] ?? null) === '4'
    && ($partialRecord['service_hours'] ?? null) === '8'
    && ($partialRecord['points'] ?? null) === '450'
    && ($partialRecord['tokens'] ?? null) === '35'
    && ($partialRecord['current_rank'] ?? null) === 'Speaker'
    && (read_json_file(topic_selections_file())[$studentId]['status'] ?? null)
        === 'Approved'
    && (read_json_file(research_file())[$studentId]['status'] ?? null)
        === 'Approved',
    'A partial request must preserve every omitted student and submission value.'
);

$resetFixtures();
master_admin_integrity_run_endpoint($expectedRoot, $session, [
    'student_id' => $studentId,
    'csrf_token' => $csrfToken,
    'approved' => 'Unexpected Status',
    'admin_notes' => 'Must not be written.',
]);
master_admin_integrity_assert(
    read_json_file(portal_records_file())[$studentId] === $baselineRecord
    && read_json_file(topic_selections_file())[$studentId] === $baselineSelection
    && read_json_file(research_file())[$studentId] === $baselineResearch,
    'An invalid status must fail closed before any related or unrelated write.'
);

$resetFixtures();
master_admin_integrity_run_endpoint($expectedRoot, $session, [
    'student_id' => $studentId,
    'csrf_token' => $csrfToken,
    'topic_status' => 'Invalid Topic State',
    'points' => '0',
]);
master_admin_integrity_assert(
    read_json_file(portal_records_file())[$studentId] === $baselineRecord
    && read_json_file(topic_selections_file())[$studentId] === $baselineSelection,
    'An invalid topic status must not reset points or topic state.'
);

fwrite(STDOUT, "PASS valid full student update persists intentional values\n");
fwrite(STDOUT, "PASS partial student update preserves omitted values\n");
fwrite(STDOUT, "PASS invalid enumerated states fail closed before writes\n");
fwrite(STDOUT, "PASS AI-shaped partial requests cannot reset student data\n");
