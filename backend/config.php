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

function mutable_path_definitions(): array {
    return [
        'portal-data' => 'YUVA_PORTAL_DATA_PATH',
        'portal-uploads' => 'YUVA_PORTAL_UPLOADS_PATH',
        'submissions' => 'YUVA_SUBMISSIONS_PATH',
    ];
}

function app_requires_external_mutable_storage(): bool {
    if (!app_is_azure()) {
        return false;
    }

    $appEnv = strtolower(env_value('APP_ENV', 'production'));
    $slotName = strtolower(env_value('WEBSITE_SLOT_NAME'));

    return $appEnv === 'production' || $slotName === 'release';
}

function path_uses_absolute_syntax(string $path): bool {
    return str_starts_with($path, '/')
        || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
        || str_starts_with($path, '\\\\');
}

function normalized_path_for_comparison(string $path): string {
    $normalized = str_replace('\\', '/', rtrim($path, "\\/"));
    if ($normalized === '') {
        return '/';
    }

    if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
        return strtolower($normalized);
    }

    return $normalized;
}

function path_is_within(string $candidate, string $root): bool {
    $candidate = normalized_path_for_comparison($candidate);
    $root = normalized_path_for_comparison($root);
    return $candidate === $root || str_starts_with($candidate, $root . '/');
}

function validate_configured_mutable_path(
    string $name,
    string $configuredPath,
    bool $externalStorageRequired
): string {
    if (!array_key_exists($name, mutable_path_definitions())) {
        throw new InvalidArgumentException('Unsupported mutable path type.');
    }

    if (
        $configuredPath === ''
        || str_contains($configuredPath, "\0")
        || !path_uses_absolute_syntax($configuredPath)
    ) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    $segments = preg_split('#[\\\\/]+#', $configuredPath) ?: [];
    if (in_array('..', $segments, true)) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    $resolved = realpath($configuredPath);
    if ($resolved === false || !is_dir($resolved)) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    $normalized = normalized_path_for_comparison($resolved);
    if (
        $normalized === '/'
        || $normalized === '/home'
        || path_is_within($normalized, '/home/site/wwwroot')
    ) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    $applicationRoot = realpath(dirname(__DIR__));
    if (
        $externalStorageRequired
        && is_string($applicationRoot)
        && path_is_within($resolved, $applicationRoot)
    ) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    if (!is_readable($resolved) || !is_writable($resolved)) {
        throw new RuntimeException('Required mutable storage path is unavailable.');
    }

    return $resolved;
}

function mutable_runtime_path(string $name): string {
    $definitions = mutable_path_definitions();
    if (!isset($definitions[$name])) {
        throw new InvalidArgumentException('Unsupported mutable path type.');
    }

    $externalStorageRequired = app_requires_external_mutable_storage();
    $configuredPath = env_value($definitions[$name]);

    if ($configuredPath === '') {
        if ($externalStorageRequired) {
            throw new RuntimeException('Required mutable storage path is unavailable.');
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . $name;
    }

    return validate_configured_mutable_path(
        $name,
        $configuredPath,
        $externalStorageRequired
    );
}

function mutable_storage_is_healthy(): bool {
    try {
        foreach (array_keys(mutable_path_definitions()) as $name) {
            $path = mutable_runtime_path($name);
            if (!is_dir($path) || !is_readable($path) || !is_writable($path)) {
                return false;
            }
        }
    } catch (Throwable) {
        return false;
    }

    return true;
}

function app_config(): array {
    $appEnv = strtolower(env_value('APP_ENV', 'production'));

    return [
        'app_env' => $appEnv,
        'app_url' => rtrim(env_value('APP_URL', 'https://www.yuvaclub.app'), '/'),
        'mutable_paths' => [
            'portal_data' => env_value('YUVA_PORTAL_DATA_PATH'),
            'portal_uploads' => env_value('YUVA_PORTAL_UPLOADS_PATH'),
            'submissions' => env_value('YUVA_SUBMISSIONS_PATH'),
        ],
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
            'from_email' => env_value('MAIL_FROM_EMAIL', 'noreply@yuvaclub.app'),
            'from_name' => env_value('MAIL_FROM_NAME', 'Yuva Club'),
            'provider' => strtolower(env_value('MAIL_PROVIDER', 'azure')),
        ],
        'zoom' => [
            'default_url' => env_value('ZOOM_DEFAULT_URL'),
            'default_meeting_id' => env_value('ZOOM_DEFAULT_MEETING_ID'),
            'default_password' => env_value('ZOOM_DEFAULT_PASSWORD'),
            'scheduler_url' => env_value('ZOOM_SCHEDULER_URL'),
        ],
        'features' => [
            'portal_auth_mode' => strtolower(env_value(
                'PORTAL_AUTH_MODE',
                'filesystem'
            )),
            'ai_mentor' => [
                'foundation_enabled' => env_bool(
                    'AI_MENTOR_FOUNDATION_ENABLED',
                    true
                ),
                'coach_me_enabled' => env_bool(
                    'AI_MENTOR_COACH_ME_ENABLED',
                    false
                ),
                'media_analysis_enabled' => env_bool(
                    'AI_MENTOR_MEDIA_ANALYSIS_ENABLED',
                    false
                ),
                'transcription_model' => env_value(
                    'OPENAI_TRANSCRIBE_MODEL',
                    'whisper-1'
                ),
                'visual_model' => env_value(
                    'OPENAI_VISUAL_MODEL',
                    env_value('OPENAI_MODEL', 'gpt-4.1-mini')
                ),
                'weekly_reports_enabled' => env_bool(
                    'AI_MENTOR_WEEKLY_REPORTS_ENABLED',
                    false
                ),
                'guided_mentor_enabled' => env_bool(
                    'AI_MENTOR_GUIDED_MENTOR_ENABLED',
                    false
                ),
                'premium_entitlement_enabled' => env_bool(
                    'AI_MENTOR_PREMIUM_ENTITLEMENT_ENABLED',
                    false
                ),
            ],
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

function portal_auth_mode(): string {
    $mode = (string) (app_config()['features']['portal_auth_mode'] ?? 'filesystem');
    if (!in_array($mode, ['filesystem', 'sql', 'hybrid'], true)) {
        throw new RuntimeException('Unsupported PORTAL_AUTH_MODE value.');
    }
    return $mode;
}

function ai_mentor_feature_enabled(string $capability): bool {
    $features = app_config()['features']['ai_mentor'] ?? [];
    return is_array($features) && ($features[$capability] ?? false) === true;
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
