<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use RuntimeException;

final class LoginThrottle
{
    private const MAX_BUCKETS = 5000;

    private string $storagePath;
    private int $maxAttempts;
    private int $windowSeconds;
    private int $lockSeconds;

    /** @var callable(): int */
    private $clock;

    public function __construct(
        string $storagePath,
        int $maxAttempts = 5,
        int $windowSeconds = 900,
        int $lockSeconds = 900,
        ?callable $clock = null
    ) {
        if ($maxAttempts < 1 || $windowSeconds < 1 || $lockSeconds < 1) {
            throw new RuntimeException('Invalid login throttle configuration.');
        }

        $this->storagePath = $storagePath;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->lockSeconds = $lockSeconds;
        $this->clock = $clock ?? static fn(): int => time();
    }

    public function isBlocked(
        string $scope,
        string $identifier,
        string $networkCategory
    ): bool {
        $key = $this->bucketKey($scope, $identifier, $networkCategory);
        return $this->withLockedState(
            function (array &$state, int $now) use ($key): bool {
                $bucket = $state[$key] ?? null;
                return is_array($bucket)
                    && (int) ($bucket['blocked_until'] ?? 0) > $now;
            }
        );
    }

    public function recordFailure(
        string $scope,
        string $identifier,
        string $networkCategory
    ): void {
        $key = $this->bucketKey($scope, $identifier, $networkCategory);
        $this->withLockedState(
            function (array &$state, int $now) use ($key): bool {
                $bucket = is_array($state[$key] ?? null) ? $state[$key] : [];
                $attempts = is_array($bucket['attempts'] ?? null)
                    ? $bucket['attempts']
                    : [];
                $minimum = $now - $this->windowSeconds;
                $attempts = array_values(array_filter(
                    $attempts,
                    static fn(mixed $attempt): bool =>
                        is_int($attempt) && $attempt >= $minimum
                ));
                $attempts[] = $now;

                $blockedUntil = (int) ($bucket['blocked_until'] ?? 0);
                if (count($attempts) >= $this->maxAttempts) {
                    $blockedUntil = max($blockedUntil, $now + $this->lockSeconds);
                }

                $state[$key] = [
                    'attempts' => $attempts,
                    'blocked_until' => $blockedUntil,
                    'last_seen' => $now,
                ];
                return true;
            }
        );
    }

    public function clear(
        string $scope,
        string $identifier,
        string $networkCategory
    ): void {
        $key = $this->bucketKey($scope, $identifier, $networkCategory);
        $this->withLockedState(
            static function (array &$state, int $now) use ($key): bool {
                unset($state[$key]);
                return true;
            }
        );
    }

    private function bucketKey(
        string $scope,
        string $identifier,
        string $networkCategory
    ): string {
        return hash(
            'sha256',
            strtolower(trim($scope))
            . "\0"
            . strtoupper(trim($identifier))
            . "\0"
            . strtolower(trim($networkCategory))
        );
    }

    private function withLockedState(callable $operation): mixed
    {
        $directory = dirname($this->storagePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Login throttle storage is unavailable.');
        }

        $handle = fopen($this->storagePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Login throttle storage is unavailable.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Login throttle lock is unavailable.');
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $decoded = is_string($raw) && $raw !== ''
                ? json_decode($raw, true)
                : [];
            $state = is_array($decoded) ? $decoded : [];
            $now = ($this->clock)();
            $this->prune($state, $now);
            $result = $operation($state, $now);
            $this->prune($state, $now);

            $encoded = json_encode($state, JSON_UNESCAPED_SLASHES);
            if (!is_string($encoded)) {
                throw new RuntimeException('Login throttle state is invalid.');
            }

            rewind($handle);
            if (!ftruncate($handle, 0) || fwrite($handle, $encoded) === false) {
                throw new RuntimeException('Login throttle storage write failed.');
            }
            fflush($handle);
            flock($handle, LOCK_UN);
            return $result;
        } finally {
            fclose($handle);
        }
    }

    /** @param array<string, mixed> $state */
    private function prune(array &$state, int $now): void
    {
        $retentionStart = $now - max(
            $this->windowSeconds,
            $this->lockSeconds
        ) * 2;

        foreach ($state as $key => $bucket) {
            if (
                !is_string($key)
                || preg_match('/^[a-f0-9]{64}$/', $key) !== 1
                || !is_array($bucket)
                || (
                    (int) ($bucket['last_seen'] ?? 0) < $retentionStart
                    && (int) ($bucket['blocked_until'] ?? 0) <= $now
                )
            ) {
                unset($state[$key]);
            }
        }

        if (count($state) <= self::MAX_BUCKETS) {
            return;
        }

        uasort(
            $state,
            static fn(mixed $left, mixed $right): int =>
                (int) (($right['last_seen'] ?? 0))
                <=> (int) (($left['last_seen'] ?? 0))
        );
        $state = array_slice($state, 0, self::MAX_BUCKETS, true);
    }
}
