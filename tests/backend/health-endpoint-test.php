<?php
declare(strict_types=1);

function health_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function health_remove_tree(string $path): void {
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

function health_directory_snapshot(string $root): array {
    $snapshot = [];
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($items as $item) {
        $relative = substr($item->getPathname(), strlen($root) + 1);
        $snapshot[$relative] = [
            'size' => $item->isFile() ? $item->getSize() : null,
            'mtime' => $item->getMTime(),
        ];
    }
    ksort($snapshot);
    return $snapshot;
}

function health_request(string $documentRoot, array $environment): array {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($socket === false) {
        throw new RuntimeException('Could not allocate health-test port.');
    }
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $port = (int) substr((string) strrchr((string) $address, ':'), 1);

    $command = [
        PHP_BINARY,
        '-S',
        '127.0.0.1:' . $port,
        '-t',
        $documentRoot,
    ];
    $pipes = [];
    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $documentRoot,
        array_merge($_ENV, $environment)
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start health-test server.');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    try {
        $body = false;
        $statusLine = '';
        $deadline = microtime(true) + 5.0;
        do {
            $context = stream_context_create([
                'http' => [
                    'ignore_errors' => true,
                    'timeout' => 1,
                ],
            ]);
            $body = @file_get_contents(
                'http://127.0.0.1:' . $port . '/health.php',
                false,
                $context
            );
            $statusLine = $http_response_header[0] ?? '';
            if ($body !== false && $statusLine !== '') {
                break;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        if ($body === false || $statusLine === '') {
            $stderr = stream_get_contents($pipes[2]);
            throw new RuntimeException(
                'Health endpoint did not respond.'
                . ($stderr === '' ? '' : ' Server startup failed.')
            );
        }

        return [
            'status' => $statusLine,
            'body' => $body,
        ];
    } finally {
        proc_terminate($process);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}

$root = dirname(__DIR__, 2);
$fixtureRoot = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'yuva-health-secret-marker-'
    . bin2hex(random_bytes(6));
$paths = [
    'portal-data' => $fixtureRoot . DIRECTORY_SEPARATOR . 'portal-data',
    'portal-uploads' => $fixtureRoot . DIRECTORY_SEPARATOR . 'portal-uploads',
    'submissions' => $fixtureRoot . DIRECTORY_SEPARATOR . 'submissions',
];

try {
    foreach ($paths as $path) {
        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Could not create health fixture.');
        }
    }

    $environment = [
        'APP_ENV' => 'production',
        'WEBSITE_SITE_NAME' => 'yuva-health-test',
        'YUVA_PORTAL_DATA_PATH' => $paths['portal-data'],
        'YUVA_PORTAL_UPLOADS_PATH' => $paths['portal-uploads'],
        'YUVA_SUBMISSIONS_PATH' => $paths['submissions'],
    ];
    $before = health_directory_snapshot($fixtureRoot);
    $healthy = health_request($root, $environment);
    $after = health_directory_snapshot($fixtureRoot);

    health_assert(
        str_contains($healthy['status'], ' 200 '),
        'Valid mounted paths must return HTTP 200.'
    );
    health_assert(
        $healthy['body'] === '{"status":"ok"}',
        'Healthy response must remain minimal and machine-readable.'
    );
    health_assert(
        !str_contains($healthy['body'], $fixtureRoot)
            && !str_contains($healthy['body'], 'secret-marker'),
        'Health response must not expose absolute paths or marker values.'
    );
    health_assert(
        $before === $after,
        'Health check must not create or modify files.'
    );

    $missing = $environment;
    $missing['YUVA_SUBMISSIONS_PATH'] = '';
    $unhealthy = health_request($root, $missing);
    health_assert(
        str_contains($unhealthy['status'], ' 503 '),
        'Missing production mount configuration must return HTTP 503.'
    );
    health_assert(
        $unhealthy['body'] === '{"status":"unavailable"}',
        'Unhealthy response must remain generic.'
    );

    $unsafe = $environment;
    $unsafe['YUVA_SUBMISSIONS_PATH'] = DIRECTORY_SEPARATOR;
    $unsafeResponse = health_request($root, $unsafe);
    health_assert(
        str_contains($unsafeResponse['status'], ' 503 '),
        'Unsafe production mount configuration must return HTTP 503.'
    );
    health_assert(
        !str_contains($unsafeResponse['body'], $fixtureRoot),
        'Unhealthy response must not expose configured paths.'
    );

    echo "Health endpoint tests passed.\n";
} finally {
    health_remove_tree($fixtureRoot);
}
