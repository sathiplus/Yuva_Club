<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/config.php';

function mutable_path_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function mutable_path_expect_failure(callable $callback, string $message): void {
    try {
        $callback();
    } catch (RuntimeException|InvalidArgumentException) {
        return;
    }

    throw new RuntimeException($message);
}

function mutable_path_set_environment(array $settings): void {
    foreach ($settings as $name => $value) {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function mutable_path_remove_tree(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($path);
}

$environmentNames = [
    'APP_ENV',
    'WEBSITE_SITE_NAME',
    'YUVA_PORTAL_DATA_PATH',
    'YUVA_PORTAL_UPLOADS_PATH',
    'YUVA_SUBMISSIONS_PATH',
];
$originalEnvironment = [];
foreach ($environmentNames as $name) {
    $value = getenv($name);
    $originalEnvironment[$name] = $value === false ? null : $value;
}

$testRoot = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'yuva-mutable-path-'
    . bin2hex(random_bytes(6));
$validPaths = [
    'portal-data' => $testRoot . DIRECTORY_SEPARATOR . 'portal-data',
    'portal-uploads' => $testRoot . DIRECTORY_SEPARATOR . 'portal-uploads',
    'submissions' => $testRoot . DIRECTORY_SEPARATOR . 'submissions',
];

try {
    foreach ($validPaths as $path) {
        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Could not create mutable-path fixture.');
        }
    }

    mutable_path_set_environment([
        'APP_ENV' => 'test',
        'WEBSITE_SITE_NAME' => null,
        'YUVA_PORTAL_DATA_PATH' => null,
        'YUVA_PORTAL_UPLOADS_PATH' => null,
        'YUVA_SUBMISSIONS_PATH' => null,
    ]);
    mutable_path_assert(
        mutable_runtime_path('portal-data')
            === dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'portal-data',
        'Test environment must retain the repository-local default.'
    );

    mutable_path_set_environment([
        'APP_ENV' => 'production',
        'WEBSITE_SITE_NAME' => 'yuva-mutable-path-test',
        'YUVA_PORTAL_DATA_PATH' => $validPaths['portal-data'],
        'YUVA_PORTAL_UPLOADS_PATH' => $validPaths['portal-uploads'],
        'YUVA_SUBMISSIONS_PATH' => $validPaths['submissions'],
    ]);
    foreach ($validPaths as $name => $path) {
        mutable_path_assert(
            mutable_runtime_path($name) === realpath($path),
            "Configured {$name} path must resolve to its canonical directory."
        );
    }
    $configured = app_config()['mutable_paths'] ?? [];
    mutable_path_assert(
        ($configured['portal_data'] ?? null) === $validPaths['portal-data']
            && ($configured['portal_uploads'] ?? null) === $validPaths['portal-uploads']
            && ($configured['submissions'] ?? null) === $validPaths['submissions'],
        'Application configuration must expose all three mutable path settings.'
    );
    mutable_path_assert(
        mutable_storage_is_healthy(),
        'Valid external mutable paths must be healthy.'
    );

    mutable_path_set_environment(['YUVA_SUBMISSIONS_PATH' => null]);
    mutable_path_expect_failure(
        static fn (): string => mutable_runtime_path('submissions'),
        'Missing production configuration must fail closed.'
    );
    mutable_path_assert(
        !mutable_storage_is_healthy(),
        'Missing production configuration must make storage unhealthy.'
    );

    mutable_path_set_environment([
        'YUVA_SUBMISSIONS_PATH' => $validPaths['submissions'],
    ]);
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            'relative/submissions',
            true
        ),
        'Relative paths must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            $testRoot . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . basename($testRoot),
            true
        ),
        'Traversal segments must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            $validPaths['submissions'] . "\0suffix",
            true
        ),
        'NUL bytes must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            DIRECTORY_SEPARATOR,
            true
        ),
        'The filesystem root must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            '/home',
            true
        ),
        '/home must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            '/home/site/wwwroot',
            true
        ),
        'wwwroot must be rejected.'
    );
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'submissions',
            $testRoot . DIRECTORY_SEPARATOR . 'missing',
            true
        ),
        'Nonexistent directories must be rejected.'
    );

    $applicationData = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'portal-data';
    mutable_path_expect_failure(
        static fn (): string => validate_configured_mutable_path(
            'portal-data',
            $applicationData,
            true
        ),
        'Repository-local production directories must be rejected.'
    );

    $permissionPath = $testRoot . DIRECTORY_SEPARATOR . 'permissions';
    mkdir($permissionPath, 0755);
    if (DIRECTORY_SEPARATOR !== '\\') {
        chmod($permissionPath, 0555);
        if (!is_writable($permissionPath)) {
            mutable_path_expect_failure(
                static fn (): string => validate_configured_mutable_path(
                    'submissions',
                    $permissionPath,
                    true
                ),
                'Unwritable configured directories must be rejected.'
            );
        }
        chmod($permissionPath, 0000);
        if (!is_readable($permissionPath)) {
            mutable_path_expect_failure(
                static fn (): string => validate_configured_mutable_path(
                    'submissions',
                    $permissionPath,
                    true
                ),
                'Unreadable configured directories must be rejected.'
            );
        }
        chmod($permissionPath, 0755);
    }

    $registrationSource = file_get_contents(
        dirname(__DIR__, 2) . '/submit-registration.php'
    );
    mutable_path_assert(
        is_string($registrationSource)
            && str_contains($registrationSource, "portal_path('submissions')"),
        'Registration writer must use the shared mutable-path helper.'
    );
    mutable_path_assert(
        !str_contains(
            (string) $registrationSource,
            "__DIR__ . DIRECTORY_SEPARATOR . 'submissions'"
        ),
        'Registration writer must not retain the direct submissions bypass.'
    );

    $importSource = file_get_contents(
        dirname(__DIR__, 2) . '/tools/import-registrations-csv.php'
    );
    mutable_path_assert(
        is_string($importSource)
            && str_contains($importSource, "mutable_runtime_path('submissions')"),
        'Import tool must use the shared mutable-path helper.'
    );
    mutable_path_assert(
        !str_contains(
            (string) $importSource,
            "__DIR__ . '/../submissions/registrations-current.csv'"
        ),
        'Import tool must not retain the direct submissions bypass.'
    );

    $portalLibrarySource = file_get_contents(
        dirname(__DIR__, 2) . '/portal-lib.php'
    );
    mutable_path_assert(
        is_string($portalLibrarySource)
            && str_contains(
                $portalLibrarySource,
                'return mutable_runtime_path($name);'
            ),
        'The central portal path helper must delegate mutable directories.'
    );

    $uploadSource = file_get_contents(
        dirname(__DIR__, 2) . '/portal-submit-research.php'
    );
    $downloadSource = file_get_contents(
        dirname(__DIR__, 2) . '/portal-download.php'
    );
    mutable_path_assert(
        is_string($uploadSource)
            && str_contains($uploadSource, "preg_replace('/[^A-Za-z0-9_-]/'"),
        'Upload student-ID sanitization must remain present.'
    );
    mutable_path_assert(
        is_string($downloadSource)
            && str_contains($downloadSource, "basename(\$research['file_stored'])"),
        'Download filename traversal protection must remain present.'
    );

    echo "Mutable path contract tests passed.\n";
} finally {
    mutable_path_set_environment($originalEnvironment);
    mutable_path_remove_tree($testRoot);
}
