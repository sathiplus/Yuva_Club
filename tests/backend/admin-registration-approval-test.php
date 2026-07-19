<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/repositories.php';

function admin_approval_test_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function admin_approval_function_source(string $name): string {
    $reflection = new ReflectionFunction($name);
    $lines = file($reflection->getFileName());
    if ($lines === false) {
        throw new RuntimeException('Unable to read function source: ' . $name);
    }
    return implode('', array_slice(
        $lines,
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1
    ));
}

$previousEnvironment = getenv('SQL_APPROVAL_ENABLED');
$previousServerValue = $_SERVER['SQL_APPROVAL_ENABLED'] ?? null;
try {
    putenv('SQL_APPROVAL_ENABLED');
    unset($_SERVER['SQL_APPROVAL_ENABLED']);
    admin_approval_test_assert(
        sql_approval_enabled() === false,
        'SQL approval must default to disabled.'
    );

    putenv('SQL_APPROVAL_ENABLED=not-a-boolean');
    admin_approval_test_assert(
        sql_approval_enabled() === false,
        'Invalid SQL approval values must behave as disabled.'
    );

    putenv('SQL_APPROVAL_ENABLED=true');
    admin_approval_test_assert(
        sql_approval_enabled() === true,
        'The explicit true value must enable the feature gate.'
    );
} finally {
    if ($previousEnvironment === false) {
        putenv('SQL_APPROVAL_ENABLED');
    } else {
        putenv('SQL_APPROVAL_ENABLED=' . $previousEnvironment);
    }
    if ($previousServerValue === null) {
        unset($_SERVER['SQL_APPROVAL_ENABLED']);
    } else {
        $_SERVER['SQL_APPROVAL_ENABLED'] = $previousServerValue;
    }
}

$endpointSource = file_get_contents(
    __DIR__ . '/../../admin-registration-approve.php'
);
$adminSource = file_get_contents(__DIR__ . '/../../admin.php');
$repositorySource = file_get_contents(__DIR__ . '/../../backend/repositories.php');
admin_approval_test_assert($endpointSource !== false, 'Approval endpoint is missing.');
admin_approval_test_assert($adminSource !== false, 'Admin UI is unreadable.');
admin_approval_test_assert($repositorySource !== false, 'Repository is unreadable.');

$authPosition = strpos($endpointSource, 'require_admin();');
$methodPosition = strpos(
    $endpointSource,
    "\$_SERVER['REQUEST_METHOD'] !== 'POST'"
);
$gatePosition = strpos($endpointSource, '!sql_approval_enabled()');
$csrfPosition = strpos($endpointSource, '!verify_csrf_token');
$idPosition = strpos($endpointSource, 'FILTER_VALIDATE_INT');
$sessionIdentityPosition = strpos(
    $endpointSource,
    "\$_SESSION['admin_email']"
);
$servicePosition = strpos($endpointSource, 'approve_registration(');
admin_approval_test_assert(
    $authPosition !== false
    && $methodPosition !== false
    && $gatePosition !== false
    && $csrfPosition !== false
    && $idPosition !== false
    && $sessionIdentityPosition !== false
    && $servicePosition !== false,
    'Approval endpoint is missing a required security control.'
);
admin_approval_test_assert(
    $authPosition < $methodPosition
    && $methodPosition < $gatePosition
    && $gatePosition < $csrfPosition
    && $csrfPosition < $idPosition
    && $idPosition < $sessionIdentityPosition
    && $sessionIdentityPosition < $servicePosition,
    'Approval endpoint security controls are in an unsafe order.'
);
admin_approval_test_assert(
    str_contains($endpointSource, "['options' => ['min_range' => 1]]"),
    'Registration ID must be validated as a positive integer.'
);
admin_approval_test_assert(
    str_contains($endpointSource, "unset(\$_SESSION['csrf_token'])"),
    'Successful approval must rotate the CSRF token.'
);
admin_approval_test_assert(
    !str_contains($endpointSource, '$error->getMessage()')
    && !str_contains($endpointSource, 'trace')
    && !str_contains($endpointSource, 'password'),
    'Endpoint errors must not expose internal details.'
);

foreach ([
    'sql-registration-approved',
    'sql-registration-unavailable',
    'sql-registration-invalid',
    'sql-registration-error',
] as $safeStatus) {
    admin_approval_test_assert(
        str_contains($endpointSource, $safeStatus)
        && str_contains($adminSource, $safeStatus),
        'Safe endpoint status is not handled: ' . $safeStatus
    );
}
foreach ([
    'Registration approval is unavailable.',
    'Invalid request.',
    'Registration could not be approved.',
    'Registration approved successfully.',
] as $safeMessage) {
    admin_approval_test_assert(
        str_contains($adminSource, $safeMessage),
        'Safe user-facing message is missing: ' . $safeMessage
    );
}

preg_match_all(
    "/redirect_to\\(\\s*'([^']+)'/m",
    $endpointSource,
    $redirectMatches
);
foreach ($redirectMatches[1] ?? [] as $redirectUrl) {
    admin_approval_test_assert(
        !preg_match(
            '/(?:email|phone|name|birth|dob|registration_id|yuva_id)=/i',
            $redirectUrl
        ),
        'Redirect URL contains personal or record data.'
    );
}
admin_approval_test_assert(
    str_contains($endpointSource, 'correlation=')
    && str_contains($endpointSource, 'exception_type=')
    && !preg_match(
        '/error_log\\s*\\([^;]*(?:email|phone|name|birth|dob)/is',
        $endpointSource
    ),
    'Internal failure logging must use safe correlation metadata only.'
);

admin_approval_test_assert(
    str_contains($adminSource, 'if ($sqlApprovalEnabled)')
    && str_contains($adminSource, 'pending_sql_registrations()')
    && str_contains($adminSource, 'if (!$sqlApprovalEnabled')
    && str_contains($adminSource, 'admin-registration-approve.php')
    && str_contains($adminSource, 'method="post"')
    && str_contains($adminSource, 'csrf_field()'),
    'Admin UI gate, POST action, or CSRF field is missing.'
);

$pendingSource = admin_approval_function_source('pending_sql_registrations');
foreach ([
    'sql_approval_enabled()',
    'SELECT TOP (',
    "N'new'",
    "N'reviewing'",
    "N'waitlisted'",
    'ORDER BY registration.submitted_at, registration.id',
] as $pendingContract) {
    admin_approval_test_assert(
        str_contains($pendingSource, $pendingContract),
        'Pending registration query contract is missing: ' . $pendingContract
    );
}
foreach ([
    'password',
    'token',
    'date_of_birth',
    'parent_email',
    'student_email',
    'phone',
] as $sensitiveColumn) {
    admin_approval_test_assert(
        !str_contains($pendingSource, $sensitiveColumn),
        'Pending registration query exposes a sensitive column.'
    );
}

$publicApprovalSource = admin_approval_function_source('approve_registration');
admin_approval_test_assert(
    str_contains($publicApprovalSource, "!sql_approval_enabled()"),
    'Repository approval must independently enforce the feature gate.'
);
$sqlApprovalSource = admin_approval_function_source(
    'approve_registration_sqlsrv'
);
foreach (['new', 'reviewing', 'waitlisted'] as $actionableStatus) {
    admin_approval_test_assert(
        str_contains($pendingSource, "N'" . $actionableStatus . "'")
        && str_contains($sqlApprovalSource, "'" . $actionableStatus . "'"),
        'Actionable registration status is not accepted by the service: '
        . $actionableStatus
    );
}
admin_approval_test_assert(
    str_contains($endpointSource, 'approve_registration($registrationId, $adminUserId)')
    && !str_contains($endpointSource, 'yuva_id='),
    'Endpoint must use the idempotent service without exposing its response.'
);

fwrite(STDOUT, "PASS SQL approval feature gate defaults\n");
fwrite(STDOUT, "PASS POST-only and admin authentication contract\n");
fwrite(STDOUT, "PASS CSRF and positive registration ID contract\n");
fwrite(STDOUT, "PASS disabled endpoint rejection contract\n");
fwrite(STDOUT, "PASS safe response, redirect, and logging contract\n");
fwrite(STDOUT, "PASS authenticated SQL admin identity wiring\n");
fwrite(STDOUT, "PASS idempotent approval service wiring\n");
fwrite(STDOUT, "PASS actionable registration status alignment\n");
fwrite(STDOUT, "PASS deterministic pending registration query\n");
fwrite(STDOUT, "PASS disabled admin control behavior\n");
