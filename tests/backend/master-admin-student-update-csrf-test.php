<?php
declare(strict_types=1);

$expectedRoot = realpath(__DIR__ . '/../..');
$isolatedRoot = realpath((string) getenv('YUVA_TEST_ISOLATED_ROOT'));
if ($expectedRoot === false || $isolatedRoot === false || $expectedRoot !== $isolatedRoot) {
    throw new RuntimeException(
        'Master Admin CSRF regression must run through the isolated validation runner.'
    );
}

require_once $expectedRoot . '/portal-lib.php';

function master_admin_csrf_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param array<string, mixed> $session
 * @param array<string, mixed> $post
 */
function master_admin_csrf_run_endpoint(
    string $root,
    array $session,
    array $post
): void {
    $sessionId = 'csrf' . bin2hex(random_bytes(12));
    $runner = tempnam(sys_get_temp_dir(), 'yuva-admin-csrf-');
    if ($runner === false) {
        throw new RuntimeException('Unable to create isolated endpoint runner.');
    }

    $source = <<<'PHP'
<?php
declare(strict_types=1);

$root = (string) getenv('YUVA_CSRF_TEST_ROOT');
$sessionId = (string) getenv('YUVA_CSRF_TEST_SESSION_ID');
$session = json_decode((string) getenv('YUVA_CSRF_TEST_SESSION'), true, 512, JSON_THROW_ON_ERROR);
$post = json_decode((string) getenv('YUVA_CSRF_TEST_POST'), true, 512, JSON_THROW_ON_ERROR);

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
        'YUVA_CSRF_TEST_ROOT' => $root,
        'YUVA_CSRF_TEST_SESSION_ID' => $sessionId,
        'YUVA_CSRF_TEST_SESSION' => json_encode(
            $session,
            JSON_THROW_ON_ERROR
        ),
        'YUVA_CSRF_TEST_POST' => json_encode($post, JSON_THROW_ON_ERROR),
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

    master_admin_csrf_assert(
        $exitCode === 0,
        'Guarded endpoint runner failed: ' . trim((string) $stderr)
    );
    master_admin_csrf_assert(
        !str_contains((string) $stdout, 'Fatal error'),
        'Guarded endpoint emitted a fatal error.'
    );
}

/** @return array<string, mixed> */
function master_admin_csrf_session(string $csrfToken): array
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

function master_admin_csrf_reset_record(string $studentId): void
{
    write_json_file(portal_records_file(), [
        $studentId => [
            'approved' => 'Pending',
            'attendance' => '0',
        ],
    ]);
}

$adminSource = file_get_contents($expectedRoot . '/admin.php');
$actionsSource = file_get_contents($expectedRoot . '/admin-actions.php');
$portalLibSource = file_get_contents($expectedRoot . '/portal-lib.php');
master_admin_csrf_assert(is_string($adminSource), 'Master Admin source is unreadable.');
master_admin_csrf_assert(is_string($actionsSource), 'Admin action source is unreadable.');
master_admin_csrf_assert(is_string($portalLibSource), 'Portal library source is unreadable.');

$studentUpdateFormPosition = strpos(
    $adminSource,
    'action="admin-actions.php"'
);
$studentIdFieldPosition = strpos(
    $adminSource,
    '<input type="hidden" name="student_id"',
    $studentUpdateFormPosition === false ? 0 : $studentUpdateFormPosition
);
$studentUpdateCsrfPosition = strpos(
    $adminSource,
    '<?php echo csrf_field(); ?>',
    $studentUpdateFormPosition === false ? 0 : $studentUpdateFormPosition
);
master_admin_csrf_assert(
    $studentUpdateFormPosition !== false
    && $studentIdFieldPosition !== false
    && $studentUpdateCsrfPosition !== false
    && $studentUpdateCsrfPosition < $studentIdFieldPosition,
    'The student-update form must emit the established CSRF field.'
);
master_admin_csrf_assert(
    str_contains(
        $portalLibSource,
        'name="csrf_token" value="'
    )
    && str_contains($portalLibSource, 'function verify_csrf_token')
    && str_contains($actionsSource, 'require_admin_post([YUVA_ROLE_MASTER_ADMIN])'),
    'The expected CSRF field, verifier, or Master Admin guard is missing.'
);

foreach ([
    'admin-registration-approve.php',
    'admin-ai-review.php',
    'admin-ai-apply.php',
] as $existingAction) {
    master_admin_csrf_assert(
        preg_match(
            '/<form[^>]+action="' . preg_quote($existingAction, '/') . '"'
            . '[^>]*>\s*<\?php echo csrf_field\(\); \?>/s',
            $adminSource
        ) === 1,
        'Existing Admin CSRF form changed: ' . $existingAction
    );
}

$studentId = 'YC2026998';
$validToken = bin2hex(random_bytes(32));
$validSession = master_admin_csrf_session($validToken);
$validPost = [
    'student_id' => $studentId,
    'approved' => 'Approved',
    'csrf_token' => $validToken,
];

master_admin_csrf_reset_record($studentId);
master_admin_csrf_run_endpoint($expectedRoot, $validSession, $validPost);
master_admin_csrf_assert(
    (student_record($studentId)['approved'] ?? null) === 'Approved',
    'A valid guarded POST must update the approval state.'
);

master_admin_csrf_reset_record($studentId);
$missingTokenPost = $validPost;
unset($missingTokenPost['csrf_token']);
master_admin_csrf_run_endpoint($expectedRoot, $validSession, $missingTokenPost);
master_admin_csrf_assert(
    (student_record($studentId)['approved'] ?? null) === 'Pending',
    'A missing CSRF token must not change approval state.'
);

master_admin_csrf_reset_record($studentId);
$invalidTokenPost = $validPost;
$invalidTokenPost['csrf_token'] = bin2hex(random_bytes(32));
master_admin_csrf_run_endpoint($expectedRoot, $validSession, $invalidTokenPost);
master_admin_csrf_assert(
    (student_record($studentId)['approved'] ?? null) === 'Pending',
    'An invalid CSRF token must not change approval state.'
);

master_admin_csrf_reset_record($studentId);
master_admin_csrf_run_endpoint($expectedRoot, [], $validPost);
master_admin_csrf_assert(
    (student_record($studentId)['approved'] ?? null) === 'Pending',
    'An unauthenticated request must not change approval state.'
);

master_admin_csrf_reset_record($studentId);
$nonAdminSession = $validSession;
$nonAdminSession['admin_role'] = YUVA_ROLE_PARENT;
master_admin_csrf_run_endpoint($expectedRoot, $nonAdminSession, $validPost);
master_admin_csrf_assert(
    (student_record($studentId)['approved'] ?? null) === 'Pending',
    'A non-admin request must not change approval state.'
);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION = $validSession;
$_POST = ['csrf_token' => $validToken];
$identity = require_admin_post([YUVA_ROLE_MASTER_ADMIN]);
master_admin_csrf_assert(
    ($identity['role'] ?? null) === YUVA_ROLE_MASTER_ADMIN
    && ($identity['email'] ?? null) === YUVA_PLATFORM_ADMIN_EMAIL,
    'Valid CSRF must preserve existing Master Admin authentication.'
);

fwrite(STDOUT, "PASS student-update form emits established CSRF field\n");
fwrite(STDOUT, "PASS valid guarded POST updates approval state\n");
fwrite(STDOUT, "PASS missing and invalid CSRF tokens fail closed\n");
fwrite(STDOUT, "PASS unauthenticated and non-admin requests fail closed\n");
fwrite(STDOUT, "PASS existing Admin POST CSRF contracts remain present\n");
