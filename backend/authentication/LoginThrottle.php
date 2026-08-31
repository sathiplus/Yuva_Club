<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use PDO;
use RuntimeException;

final class LoginThrottle
{
    private const MAX_BUCKETS = 5000;

    private ?string $storagePath;
    private ?PDO $pdo;
    private int $maxAttempts;
    private int $windowSeconds;
    private int $lockSeconds;

    /** @var callable(): int */
    private $clock;

    public function __construct(
        string|PDO $storage,
        int $maxAttempts = 5,
        int $windowSeconds = 900,
        int $lockSeconds = 900,
        ?callable $clock = null
    ) {
        if ($maxAttempts < 1 || $windowSeconds < 1 || $lockSeconds < 1) {
            throw new RuntimeException('Invalid login throttle configuration.');
        }

        $this->storagePath = is_string($storage) ? $storage : null;
        $this->pdo = $storage instanceof PDO ? $storage : null;
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
        if ($this->pdo instanceof PDO) {
            $hashes = $this->bucketHashes($scope, $identifier, $networkCategory);
            $statement = $this->pdo->prepare('SELECT CASE WHEN blocked_until > SYSUTCDATETIME() THEN 1 ELSE 0 END FROM dbo.authentication_attempts WHERE scope = :scope AND account_hash = CONVERT(BINARY(32), :account_hash, 2) AND network_hash = CONVERT(BINARY(32), :network_hash, 2)');
            $statement->execute($hashes);
            return (int) $statement->fetchColumn() === 1;
        }
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
        if ($this->pdo instanceof PDO) {
            $parameters = $this->bucketHashes($scope, $identifier, $networkCategory);
            $parameters['window_seconds_1'] = $this->windowSeconds;
            $parameters['window_seconds_2'] = $this->windowSeconds;
            $parameters['window_seconds_3'] = $this->windowSeconds;
            $parameters['max_attempts'] = $this->maxAttempts;
            $parameters['lock_seconds'] = $this->lockSeconds;
            $statement = $this->pdo->prepare(<<<'SQL'
MERGE dbo.authentication_attempts WITH (HOLDLOCK) AS target
USING (SELECT :scope AS scope, CONVERT(BINARY(32), :account_hash, 2) AS account_hash, CONVERT(BINARY(32), :network_hash, 2) AS network_hash) AS source
ON target.scope = source.scope AND target.account_hash = source.account_hash AND target.network_hash = source.network_hash
WHEN MATCHED THEN UPDATE SET
    attempt_count = CASE WHEN DATEDIFF(SECOND, target.window_started_at, SYSUTCDATETIME()) >= CONVERT(INT, :window_seconds_1) THEN 1 ELSE target.attempt_count + 1 END,
    window_started_at = CASE WHEN DATEDIFF(SECOND, target.window_started_at, SYSUTCDATETIME()) >= CONVERT(INT, :window_seconds_2) THEN SYSUTCDATETIME() ELSE target.window_started_at END,
    blocked_until = CASE WHEN (CASE WHEN DATEDIFF(SECOND, target.window_started_at, SYSUTCDATETIME()) >= CONVERT(INT, :window_seconds_3) THEN 1 ELSE target.attempt_count + 1 END) >= CONVERT(INT, :max_attempts) THEN DATEADD(SECOND, CONVERT(INT, :lock_seconds), SYSUTCDATETIME()) ELSE target.blocked_until END,
    updated_at = SYSUTCDATETIME()
WHEN NOT MATCHED THEN INSERT (scope, account_hash, network_hash, attempt_count, window_started_at, blocked_until)
VALUES (source.scope, source.account_hash, source.network_hash, 1, SYSUTCDATETIME(), NULL);
SQL);
            $statement->execute($parameters);
            return;
        }
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
        if ($this->pdo instanceof PDO) {
            $statement = $this->pdo->prepare('DELETE FROM dbo.authentication_attempts WHERE scope = :scope AND account_hash = CONVERT(BINARY(32), :account_hash, 2) AND network_hash = CONVERT(BINARY(32), :network_hash, 2)');
            $statement->execute($this->bucketHashes($scope, $identifier, $networkCategory));
            return;
        }
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

    /** @return array{scope:string,account_hash:string,network_hash:string} */
    private function bucketHashes(string $scope, string $identifier, string $networkCategory): array
    {
        return [
            'scope' => strtolower(trim($scope)),
            'account_hash' => hash('sha256', strtolower(trim($identifier))),
            'network_hash' => hash('sha256', strtolower(trim($networkCategory))),
        ];
    }

    private function withLockedState(callable $operation): mixed
    {
        if ($this->storagePath === null) {
            throw new RuntimeException('Login throttle storage is unavailable.');
        }
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
