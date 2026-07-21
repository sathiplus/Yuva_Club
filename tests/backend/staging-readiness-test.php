<?php
declare(strict_types=1);

require_once __DIR__ . '/../../portal-lib.php';

function staging_readiness_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function staging_readiness_set_environment(array $values): void {
    foreach ($values as $name => $value) {
        if ($value === null) {
            putenv($name);
            unset($_SERVER[$name]);
            continue;
        }

        putenv($name . '=' . $value);
        $_SERVER[$name] = $value;
    }
}

$settingNames = [
    'APP_ENV',
    'SQL_APPROVAL_ENABLED',
    'PORTAL_STORAGE_MODE',
    'STAGING_TEST_FIXTURES_ENABLED',
    'STAGING_TEST_APP_NAME',
    'STAGING_TEST_STUDENT_ID',
    'STAGING_TEST_STUDENT_DOB',
    'STAGING_TEST_ADMIN_EMAIL',
    'STAGING_TEST_ADMIN_PASSWORD_HASH',
    'WEBSITE_SITE_NAME',
];
$previousEnvironment = [];
$previousServer = [];
foreach ($settingNames as $settingName) {
    $previousEnvironment[$settingName] = getenv($settingName);
    $previousServer[$settingName] = $_SERVER[$settingName] ?? null;
}

$testSiteName = 'staging-' . bin2hex(random_bytes(4));
$testStudentId = 'YCSTAGE' . strtoupper(bin2hex(random_bytes(3)));
$testStudentDob = (new DateTimeImmutable('today'))
    ->modify('-16 years')
    ->format('Y-m-d');
$testAdminEmail = 'staging-' . bin2hex(random_bytes(4)) . '@example.invalid';
$testAdminPassword = bin2hex(random_bytes(24));
$testAdminPasswordHash = password_hash_for_admin($testAdminPassword);
$validEnvironment = [
    'APP_ENV' => 'staging',
    'SQL_APPROVAL_ENABLED' => 'false',
    'PORTAL_STORAGE_MODE' => 'filesystem',
    'STAGING_TEST_FIXTURES_ENABLED' => 'true',
    'STAGING_TEST_APP_NAME' => $testSiteName,
    'STAGING_TEST_STUDENT_ID' => $testStudentId,
    'STAGING_TEST_STUDENT_DOB' => $testStudentDob,
    'STAGING_TEST_ADMIN_EMAIL' => $testAdminEmail,
    'STAGING_TEST_ADMIN_PASSWORD_HASH' => $testAdminPasswordHash,
    'WEBSITE_SITE_NAME' => $testSiteName,
];

try {
    staging_readiness_set_environment(array_fill_keys($settingNames, null));
    staging_readiness_assert(
        staging_test_fixture_config() === null,
        'Staging fixtures must be disabled by default.'
    );

    staging_readiness_set_environment($validEnvironment);
    staging_readiness_set_environment(['APP_ENV' => 'production']);
    staging_readiness_assert(
        staging_test_fixture_config() === null,
        'Production must ignore staging fixture settings.'
    );

    staging_readiness_set_environment($validEnvironment);
    staging_readiness_set_environment(['WEBSITE_SITE_NAME' => $testSiteName . '-other']);
    staging_readiness_assert(
        staging_test_fixture_config() === null,
        'A mismatched Azure App Service name must disable fixtures.'
    );

    foreach ([
        'STAGING_TEST_APP_NAME',
        'STAGING_TEST_STUDENT_ID',
        'STAGING_TEST_STUDENT_DOB',
        'STAGING_TEST_ADMIN_EMAIL',
        'STAGING_TEST_ADMIN_PASSWORD_HASH',
        'WEBSITE_SITE_NAME',
    ] as $requiredSetting) {
        staging_readiness_set_environment($validEnvironment);
        staging_readiness_set_environment([$requiredSetting => null]);
        staging_readiness_assert(
            staging_test_fixture_config() === null,
            'Missing fixture setting must disable fixtures: ' . $requiredSetting
        );
    }

    foreach ([
        'STAGING_TEST_FIXTURES_ENABLED' => 'not-a-boolean',
        'STAGING_TEST_STUDENT_ID' => 'invalid fixture id',
        'STAGING_TEST_STUDENT_DOB' => 'not-a-date',
        'STAGING_TEST_ADMIN_EMAIL' => 'not-an-email',
        'STAGING_TEST_ADMIN_PASSWORD_HASH' => 'not-a-hash',
    ] as $invalidSetting => $invalidValue) {
        staging_readiness_set_environment($validEnvironment);
        staging_readiness_set_environment([$invalidSetting => $invalidValue]);
        staging_readiness_assert(
            staging_test_fixture_config() === null,
            'Invalid fixture setting must disable fixtures: ' . $invalidSetting
        );
    }

    staging_readiness_set_environment($validEnvironment);
    staging_readiness_set_environment([
        'STAGING_TEST_STUDENT_DOB' => (new DateTimeImmutable('today'))
            ->modify('+1 day')
            ->format('Y-m-d'),
    ]);
    staging_readiness_assert(
        staging_test_fixture_config() === null,
        'A future synthetic date of birth must disable fixtures.'
    );

    staging_readiness_set_environment($validEnvironment);
    $fixtureConfig = staging_test_fixture_config();
    staging_readiness_assert(
        is_array($fixtureConfig)
        && $fixtureConfig['student_id'] === $testStudentId
        && $fixtureConfig['student_dob'] === $testStudentDob
        && $fixtureConfig['admin_email'] === $testAdminEmail
        && $fixtureConfig['admin_password_hash'] === $testAdminPasswordHash,
        'Valid staging gates must expose the configured fixture values.'
    );

    $existingStudent = [
        'Yuva Club ID' => 'EXISTING',
        'Student First Name' => 'Existing',
    ];
    $students = merge_staging_test_student(['EXISTING' => $existingStudent]);
    staging_readiness_assert(
        count($students) === 2
        && $students['EXISTING'] === $existingStudent
        && isset($students[$testStudentId]),
        'The synthetic student must be added without changing filesystem students.'
    );
    staging_readiness_assert(
        merge_staging_test_student($students) === $students,
        'Repeated fixture merging must not duplicate or change student records.'
    );
    $syntheticStudent = $students[$testStudentId];
    staging_readiness_assert(
        $syntheticStudent['Student First Name'] === 'Staging'
        && $syntheticStudent['Student Last Name'] === 'Test Student'
        && $syntheticStudent['School'] === ''
        && $syntheticStudent['City/State'] === ''
        && $syntheticStudent['Student Email'] === ''
        && $syntheticStudent['Student Phone Number'] === ''
        && $syntheticStudent['Parent/Guardian Name'] === ''
        && $syntheticStudent['Parent Email'] === ''
        && $syntheticStudent['Parent Phone Number'] === '',
        'The staging student must contain no real contact, school, location, or parent data.'
    );

    staging_readiness_assert(
        staging_test_admin_credentials() === [
            'email' => $testAdminEmail,
            'password_hash' => $testAdminPasswordHash,
        ],
        'The staging admin override must use only the configured email and hash.'
    );
    staging_readiness_assert(
        admin_password_matches($testAdminEmail, $testAdminPassword)
        && !admin_password_matches($testAdminEmail, $testAdminPassword . '-invalid'),
        'The staging admin override must authenticate only the generated test password.'
    );

    staging_readiness_set_environment(['SQL_APPROVAL_ENABLED' => 'true']);
    staging_readiness_assert(
        staging_test_fixture_config() === null
        && staging_test_admin_credentials() === null
        && staging_test_student_fixture() === null,
        'Fixtures must remain disabled while SQL approval is enabled.'
    );

    staging_readiness_set_environment($validEnvironment);
    staging_readiness_assert(
        sql_approval_enabled() === false,
        'The readiness fixture must not enable SQL approval.'
    );
    $storageModeBefore = getenv('PORTAL_STORAGE_MODE');
    staging_test_fixture_config();
    staging_readiness_assert(
        getenv('PORTAL_STORAGE_MODE') === $storageModeBefore,
        'The readiness fixture must not change the portal storage mode.'
    );

    $adminLoginSource = file_get_contents(__DIR__ . '/../../admin-login.php');
    staging_readiness_assert($adminLoginSource !== false, 'Admin login source is unreadable.');
    $regeneratePosition = strpos($adminLoginSource, 'session_regenerate_id(true)');
    $sessionPosition = strpos($adminLoginSource, "\$_SESSION['admin_logged_in'] = true");
    staging_readiness_assert(
        $regeneratePosition !== false
        && $sessionPosition !== false
        && $regeneratePosition < $sessionPosition,
        'Successful admin login must regenerate the session before authentication state is set.'
    );

    foreach ([
        'staging_test_fixture_config',
        'staging_test_admin_credentials',
        'staging_test_student_fixture',
        'merge_staging_test_student',
    ] as $functionName) {
        $reflection = new ReflectionFunction($functionName);
        $lines = file($reflection->getFileName());
        staging_readiness_assert($lines !== false, 'Fixture source is unreadable.');
        $source = implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
        staging_readiness_assert(
            !preg_match(
                '/(?:PDO|sqlsrv|migration|fputcsv|write_json_file|file_put_contents)/i',
                $source
            ),
            'Fixture helper must not access SQL, migrations, CSV, or JSON: ' . $functionName
        );
    }

    fwrite(STDOUT, "PASS staging fixtures default disabled\n");
    fwrite(STDOUT, "PASS production and App Service identity gates\n");
    fwrite(STDOUT, "PASS required fixture setting validation\n");
    fwrite(STDOUT, "PASS synthetic student in-memory overlay\n");
    fwrite(STDOUT, "PASS staging-only admin override\n");
    fwrite(STDOUT, "PASS admin session regeneration contract\n");
    fwrite(STDOUT, "PASS no SQL, migration, CSV, or JSON fixture access\n");
    fwrite(STDOUT, "PASS SQL approval and storage mode remain unchanged\n");
} finally {
    foreach ($settingNames as $settingName) {
        if ($previousEnvironment[$settingName] === false) {
            putenv($settingName);
        } else {
            putenv($settingName . '=' . $previousEnvironment[$settingName]);
        }

        if ($previousServer[$settingName] === null) {
            unset($_SERVER[$settingName]);
        } else {
            $_SERVER[$settingName] = $previousServer[$settingName];
        }
    }
}
