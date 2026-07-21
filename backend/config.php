<?php
declare(strict_types=1);

function env_value(string $name, string $default = ''): string {
    $value = getenv($name);
    if ($value === false || $value === '') {
        $value = $_SERVER[$name] ?? $default;
    }
    return is_string($value) ? trim($value) : $default;
}

function env_bool(string $name, bool $default = false): bool {
    $value = env_value($name);
    if ($value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function app_config(): array {
    $appEnv = strtolower(env_value('APP_ENV', 'production'));

    return [
        'app_env' => $appEnv,
        'app_url' => rtrim(env_value('APP_URL', 'https://yuvaclub-dja9ckadbagedja4.eastus-01.azurewebsites.net'), '/'),
        'database' => [
            'driver' => env_value('DB_DRIVER', 'mysql'),
            'host' => env_value('DB_HOST'),
            'port' => env_value('DB_PORT', env_value('DB_DRIVER', 'mysql') === 'sqlsrv' ? '1433' : '3306'),
            'name' => env_value('DB_DATABASE'),
            'user' => env_value('DB_USERNAME'),
            'password' => env_value('DB_PASSWORD'),
            'ssl_ca' => env_value('DB_SSL_CA'),
        ],
        'storage' => [
            'account' => env_value('AZURE_STORAGE_ACCOUNT'),
            'container' => env_value('AZURE_STORAGE_CONTAINER', 'yuva-uploads'),
            'connection_string' => env_value('AZURE_STORAGE_CONNECTION_STRING'),
        ],
        'mail' => [
            'enabled' => env_bool('MAIL_ENABLED', $appEnv !== 'staging'),
            'to_email' => env_value('MAIL_TO_EMAIL'),
            'from_email' => env_value('MAIL_FROM_EMAIL'),
            'from_name' => env_value('MAIL_FROM_NAME', 'Yuva Club'),
            'provider' => strtolower(env_value('MAIL_PROVIDER', 'php')),
        ],
        'zoom' => [
            'default_url' => env_value('ZOOM_DEFAULT_URL'),
            'default_meeting_id' => env_value('ZOOM_DEFAULT_MEETING_ID'),
            'default_password' => env_value('ZOOM_DEFAULT_PASSWORD'),
            'scheduler_url' => env_value('ZOOM_SCHEDULER_URL'),
        ],
        'features' => [
            'sql_approval_enabled' => env_bool(
                'SQL_APPROVAL_ENABLED',
                false
            ),
            'staging_test_fixtures_enabled' => env_bool(
                'STAGING_TEST_FIXTURES_ENABLED',
                false
            ),
        ],
    ];
}

function app_environment(): string {
    return app_config()['app_env'];
}

function app_is_staging(): bool {
    return app_environment() === 'staging';
}

function app_url(): string {
    return app_config()['app_url'];
}

function app_is_azure(): bool {
    return env_value('WEBSITE_INSTANCE_ID') !== '' || env_value('WEBSITE_SITE_NAME') !== '';
}

function sql_approval_enabled(): bool {
    return (app_config()['features']['sql_approval_enabled'] ?? false) === true;
}

function staging_test_fixture_config(): ?array {
    if (
        !app_is_staging()
        || !((app_config()['features']['staging_test_fixtures_enabled'] ?? false) === true)
        || sql_approval_enabled()
    ) {
        return null;
    }

    $expectedSiteName = env_value('STAGING_TEST_APP_NAME');
    $actualSiteName = env_value('WEBSITE_SITE_NAME');
    $studentId = strtoupper(env_value('STAGING_TEST_STUDENT_ID'));
    $studentDob = env_value('STAGING_TEST_STUDENT_DOB');
    $adminEmail = strtolower(env_value('STAGING_TEST_ADMIN_EMAIL'));
    $adminPasswordHash = strtolower(env_value('STAGING_TEST_ADMIN_PASSWORD_HASH'));

    if (
        $expectedSiteName === ''
        || $actualSiteName === ''
        || !hash_equals($expectedSiteName, $actualSiteName)
        || preg_match('/^YC[A-Z0-9]{5,38}$/', $studentId) !== 1
        || filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false
        || preg_match('/^[a-f0-9]{64}$/', $adminPasswordHash) !== 1
    ) {
        return null;
    }

    $dobParts = explode('-', $studentDob);
    if (
        count($dobParts) !== 3
        || !ctype_digit($dobParts[0])
        || !ctype_digit($dobParts[1])
        || !ctype_digit($dobParts[2])
        || !checkdate((int) $dobParts[1], (int) $dobParts[2], (int) $dobParts[0])
    ) {
        return null;
    }

    $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $studentDob);
    if (!$dob instanceof DateTimeImmutable || $dob->format('Y-m-d') !== $studentDob) {
        return null;
    }

    $today = new DateTimeImmutable('today');
    if ($dob > $today) {
        return null;
    }

    $age = $dob->diff($today)->y;
    if ($age < 13 || $age > 21) {
        return null;
    }

    return [
        'student_id' => $studentId,
        'student_dob' => $studentDob,
        'student_age' => $age,
        'student_program_group' => $age >= 18
            ? 'College Yuva (Ages 18-21)'
            : 'School Yuva (Ages 13-17)',
        'admin_email' => $adminEmail,
        'admin_password_hash' => $adminPasswordHash,
    ];
}
