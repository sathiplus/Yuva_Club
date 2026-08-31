<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use Throwable;

final class ParentLoginWorkflow
{
    private const LOGIN_SCOPE = 'parent-login';
    private const SELECTION_SCOPE = 'parent-child-selection';

    private AuthenticationService $authentication;
    private LoginThrottle $throttle;

    /** @var callable(?string): bool */
    private $csrfVerifier;

    /** @var callable(): void */
    private $sessionRegenerator;

    /** @var callable(array<string, mixed>&): void */
    private $csrfRotator;

    /** @var callable(string): void */
    private $auditLogger;

    public function __construct(
        AuthenticationService $authentication,
        LoginThrottle $throttle,
        callable $csrfVerifier,
        callable $sessionRegenerator,
        ?callable $auditLogger = null,
        ?callable $csrfRotator = null
    ) {
        $this->authentication = $authentication;
        $this->throttle = $throttle;
        $this->csrfVerifier = $csrfVerifier;
        $this->sessionRegenerator = $sessionRegenerator;
        $this->auditLogger = $auditLogger ?? static function (string $category): void {
        };
        $this->csrfRotator = $csrfRotator ?? static function (array &$session): void {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        };
    }

    /**
     * @param array<string, mixed> $session
     * @return array{authenticated: bool, requires_child_selection: bool}
     */
    public function attempt(
        array &$session,
        string $email,
        string $credential,
        ?string $legacyChildYuvaId,
        ?string $csrfToken,
        string $networkCategory
    ): array {
        $normalizedEmail = strtolower(trim($email));
        try {
            if ($this->throttle->isBlocked(
                self::LOGIN_SCOPE,
                $normalizedEmail,
                $networkCategory
            )) {
                ($this->auditLogger)('parent.login.failed');
                return $this->failure();
            }
            if (!(($this->csrfVerifier)($csrfToken))) {
                $this->recordFailure(
                    self::LOGIN_SCOPE,
                    $normalizedEmail,
                    $networkCategory,
                    'parent.login.failed'
                );
                return $this->failure();
            }

            $result = $this->authentication->authenticateParent(
                $normalizedEmail,
                $credential,
                $legacyChildYuvaId
            );
            if (($result['authenticated'] ?? false) !== true) {
                $this->recordFailure(
                    self::LOGIN_SCOPE,
                    $normalizedEmail,
                    $networkCategory,
                    'parent.login.failed'
                );
                return $this->failure();
            }

            $this->throttle->clear(
                self::LOGIN_SCOPE,
                $normalizedEmail,
                $networkCategory
            );
            ($this->sessionRegenerator)();
            $this->clearRoleSessions($session);
            ($this->csrfRotator)($session);

            if (($result['source'] ?? null) === 'sql') {
                $session['parent_auth_source'] = 'sql';
                $session['parent_user_id'] = (int) ($result['parent_user_id'] ?? 0);
                $session['parent_id'] = (int) ($result['parent_id'] ?? 0);
                $session['parent_credentials_version'] = (int) ($result['credentials_version'] ?? 1);
                $session['parent_authenticated_at'] = time();
                $session['portal_role'] = 'parent';
                ($this->auditLogger)('parent.login.succeeded');
                return [
                    'authenticated' => true,
                    'requires_child_selection' => true,
                ];
            }

            $session['parent_email'] = $normalizedEmail;
            $session['parent_session_started_at'] = time();
            $session['parent_student_id'] = (string) (
                $result['parent_student_id'] ?? ''
            );
            ($this->auditLogger)('parent.login.succeeded');
            return [
                'authenticated' => true,
                'requires_child_selection' => false,
            ];
        } catch (Throwable) {
            ($this->auditLogger)('parent.login.infrastructure_failure');
            return $this->failure();
        }
    }

    /**
     * @param array<string, mixed> $session
     * @return array<int, array<string, mixed>>|null
     */
    public function authorizedChildren(array &$session): ?array
    {
        if (!$this->revalidateParentIdentity($session)) {
            $this->clearParentSession($session);
            return null;
        }

        try {
            return $this->authentication->authorizedParentChildren(
                $session['parent_user_id']
            );
        } catch (Throwable) {
            $this->clearParentSession($session);
            return null;
        }
    }

    /** @param array<string, mixed> $session */
    public function selectChild(
        array &$session,
        string $yuvaId,
        ?string $csrfToken,
        string $networkCategory
    ): bool {
        $parentUserId = is_int($session['parent_user_id'] ?? null)
            ? $session['parent_user_id']
            : 0;
        $identifier = (string) $parentUserId;

        try {
            if (
                $this->throttle->isBlocked(
                    self::SELECTION_SCOPE,
                    $identifier,
                    $networkCategory
                )
                || !(($this->csrfVerifier)($csrfToken))
                || !$this->revalidateParentIdentity($session)
                || !$this->authentication->parentCanAccessChild(
                    $parentUserId,
                    $yuvaId
                )
            ) {
                $this->recordFailure(
                    self::SELECTION_SCOPE,
                    $identifier,
                    $networkCategory,
                    'parent.child_selection.failed'
                );
                $this->clearParentSession($session);
                return false;
            }

            $this->throttle->clear(
                self::SELECTION_SCOPE,
                $identifier,
                $networkCategory
            );
            ($this->sessionRegenerator)();
            unset($session['parent_student_id']);
            $session['parent_student_id'] = $this->normalizeYuvaId($yuvaId);
            ($this->auditLogger)('parent.child_selection.succeeded');
            return true;
        } catch (Throwable) {
            $this->clearParentSession($session);
            ($this->auditLogger)('parent.child_selection.infrastructure_failure');
            return false;
        }
    }

    /**
     * @param array<string, mixed> $session
     * @return array<string, mixed>|null
     */
    public function revalidateSqlChildAccess(array &$session): ?array
    {
        if (
            !$this->revalidateParentIdentity($session)
            || !is_string($session['parent_student_id'] ?? null)
        ) {
            $this->clearParentSession($session);
            return null;
        }

        try {
            $student = $this->authentication->authorizedSqlParentChildRecord(
                $session['parent_user_id'],
                $session['parent_student_id']
            );
        } catch (Throwable) {
            $student = null;
        }
        if ($student === null) {
            $this->clearParentSession($session);
        }
        return $student;
    }

    /** @param array<string, mixed> $session */
    private function revalidateParentIdentity(array $session): bool
    {
        if (
            ($session['parent_auth_source'] ?? null) !== 'sql'
            || ($session['portal_role'] ?? null) !== 'parent'
            || !is_int($session['parent_user_id'] ?? null)
            || !is_int($session['parent_id'] ?? null)
            || !is_int($session['parent_credentials_version'] ?? null)
            || !is_int($session['parent_authenticated_at'] ?? null)
            || (time() - $session['parent_authenticated_at']) > 7200
        ) {
            return false;
        }

        try {
            return $this->authentication->revalidateSqlParentSession(
                $session['parent_user_id'],
                $session['parent_id'],
                $session['parent_credentials_version']
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function recordFailure(
        string $scope,
        string $identifier,
        string $networkCategory,
        string $category
    ): void {
        $this->throttle->recordFailure($scope, $identifier, $networkCategory);
        ($this->auditLogger)($category);
    }

    /** @param array<string, mixed> $session */
    private function clearRoleSessions(array &$session): void
    {
        unset(
            $session['student_id'],
            $session['student_auth_source'],
            $session['student_user_id'],
            $session['parent_student_id'],
            $session['parent_auth_source'],
            $session['parent_user_id'],
            $session['parent_id'],
            $session['parent_credentials_version'],
            $session['parent_authenticated_at'],
            $session['parent_email'],
            $session['parent_session_started_at'],
            $session['portal_role'],
            $session['admin_logged_in'],
            $session['admin_email']
        );
    }

    /** @param array<string, mixed> $session */
    private function clearParentSession(array &$session): void
    {
        unset(
            $session['parent_student_id'],
            $session['parent_auth_source'],
            $session['parent_user_id'],
            $session['parent_id'],
            $session['parent_credentials_version'],
            $session['parent_authenticated_at'],
            $session['portal_role']
        );
    }

    /** @return array{authenticated: false, requires_child_selection: false} */
    private function failure(): array
    {
        return [
            'authenticated' => false,
            'requires_child_selection' => false,
        ];
    }

    private function normalizeYuvaId(string $value): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^YC-?(\d{4})-?(\d+)$/', $value, $matches) === 1) {
            return sprintf('YC%s%03d', $matches[1], (int) $matches[2]);
        }
        return str_replace('-', '', $value);
    }
}
