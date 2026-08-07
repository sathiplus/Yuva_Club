<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use Throwable;

final class StudentLoginWorkflow
{
    private const THROTTLE_SCOPE = 'student-login';

    private AuthenticationService $authentication;
    private LoginThrottle $throttle;

    /** @var callable(?string): bool */
    private $csrfVerifier;

    /** @var callable(): void */
    private $sessionRegenerator;

    /** @var callable(string): void */
    private $auditLogger;

    public function __construct(
        AuthenticationService $authentication,
        LoginThrottle $throttle,
        callable $csrfVerifier,
        callable $sessionRegenerator,
        ?callable $auditLogger = null
    ) {
        $this->authentication = $authentication;
        $this->throttle = $throttle;
        $this->csrfVerifier = $csrfVerifier;
        $this->sessionRegenerator = $sessionRegenerator;
        $this->auditLogger = $auditLogger ?? static function (string $category): void {
        };
    }

    /**
     * @param array<string, mixed> $session
     * @return array{authenticated: bool}
     */
    public function attempt(
        array &$session,
        string $identifier,
        string $credential,
        ?string $csrfToken,
        string $networkCategory
    ): array {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);

        try {
            if ($this->throttle->isBlocked(
                self::THROTTLE_SCOPE,
                $normalizedIdentifier,
                $networkCategory
            )) {
                ($this->auditLogger)('student.login.failed');
                return ['authenticated' => false];
            }

            if (!(($this->csrfVerifier)($csrfToken))) {
                $this->throttle->recordFailure(
                    self::THROTTLE_SCOPE,
                    $normalizedIdentifier,
                    $networkCategory
                );
                ($this->auditLogger)('student.login.failed');
                return ['authenticated' => false];
            }

            $result = $this->authentication->authenticateStudent(
                $normalizedIdentifier,
                $credential
            );
            if (($result['authenticated'] ?? false) !== true) {
                $this->throttle->recordFailure(
                    self::THROTTLE_SCOPE,
                    $normalizedIdentifier,
                    $networkCategory
                );
                ($this->auditLogger)('student.login.failed');
                return ['authenticated' => false];
            }

            $this->throttle->clear(
                self::THROTTLE_SCOPE,
                $normalizedIdentifier,
                $networkCategory
            );
            ($this->sessionRegenerator)();
            $this->clearStudentSession($session);
            $session['student_id'] = (string) ($result['student_id'] ?? '');

            if (($result['source'] ?? null) === 'sql') {
                $session['student_auth_source'] = 'sql';
                $session['student_user_id'] = (int) ($result['user_id'] ?? 0);
                $session['portal_role'] = 'student';
            }

            ($this->auditLogger)('student.login.succeeded');
            return ['authenticated' => true];
        } catch (Throwable) {
            ($this->auditLogger)('student.login.infrastructure_failure');
            return ['authenticated' => false];
        }
    }

    /**
     * @param array<string, mixed> $session
     * @return array<string, mixed>|null
     */
    public function revalidateSqlSession(array &$session): ?array
    {
        if (
            ($session['student_auth_source'] ?? null) !== 'sql'
            || ($session['portal_role'] ?? null) !== 'student'
            || !is_string($session['student_id'] ?? null)
            || !is_int($session['student_user_id'] ?? null)
        ) {
            $this->clearStudentSession($session);
            return null;
        }

        try {
            $student = $this->authentication->revalidateSqlStudentSession(
                $session['student_id'],
                $session['student_user_id']
            );
        } catch (Throwable) {
            $student = null;
        }

        if ($student === null) {
            $this->clearStudentSession($session);
        }
        return $student;
    }

    /** @param array<string, mixed> $session */
    private function clearStudentSession(array &$session): void
    {
        unset(
            $session['student_id'],
            $session['student_auth_source'],
            $session['student_user_id'],
            $session['portal_role']
        );
    }

    private function normalizeIdentifier(string $value): string
    {
        $value = trim($value);
        if (str_contains($value, '@')) {
            return strtolower($value);
        }

        $value = strtoupper($value);
        if (preg_match('/^YC-?(\d{4})-?(\d+)$/', $value, $matches) === 1) {
            return sprintf('YC%s%03d', $matches[1], (int) $matches[2]);
        }
        return str_replace('-', '', $value);
    }
}
