<?php
declare(strict_types=1);

use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentApprovalResolver;
use YuvaClub\Authentication\StudentAuthentication;

require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentApprovalResolver.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';

function approval_resolver_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, string> */
function approval_resolver_filesystem_student(): array
{
    return [
        'Yuva Club ID' => 'YC2026003',
        'Date of Birth' => '2010-05-15',
        'Student Email' => 'release2.student@example.test',
        'Parent Email' => 'release2.parent@example.test',
        'password_hash' => 'registered-password-hash',
    ];
}

/** @param array<string, mixed>|null $sqlApproval */
function approval_resolver_service(
    StudentApprovalResolver $resolver,
    ?array $sqlApproval = null
): StudentAuthentication {
    $filesystemStudent = approval_resolver_filesystem_student();
    return new StudentAuthentication(
        new PortalRepository(
            static fn(string $sql, array $parameters): ?array => $sqlApproval,
            static fn(string $sql, array $parameters): array => []
        ),
        new PortalCompatibilityAdapter(),
        static fn(string $yuvaId): ?array =>
            $yuvaId === 'YC2026003' ? $filesystemStudent : null,
        static fn(array $record, string $credential): bool =>
            $credential === 'CorrectPass!2026'
            && ($record['password_hash'] ?? '') === 'registered-password-hash',
        null,
        null,
        static fn(array $record): bool => $resolver->isApproved(
            (string) ($record['Yuva Club ID'] ?? ''),
            (string) ($record['Student Email'] ?? '')
        )
    );
}

$sqlRow = ['registration_id' => 6, 'registration_status' => 'approved'];
$filesystemStatus = 'Pending';
$lookupIdentity = [];
$sqlResolver = new StudentApprovalResolver(
    true,
    static function (string $yuvaId, string $studentEmail) use (
        &$sqlRow,
        &$lookupIdentity
    ): ?array {
        $lookupIdentity = [$yuvaId, $studentEmail];
        return $sqlRow;
    },
    static function (string $yuvaId) use (&$filesystemStatus): string {
        return $filesystemStatus;
    }
);

$approvedService = approval_resolver_service($sqlResolver);
approval_resolver_assert(
    $approvedService->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === true,
    'SQL Approved must authorize a valid filesystem identity even when its file says Pending.'
);
approval_resolver_assert(
    $sqlResolver->resolve('YC2026003', 'RELEASE2.STUDENT@EXAMPLE.TEST')
        === StudentApprovalResolver::APPROVED
    && $lookupIdentity === ['YC2026003', 'release2.student@example.test'],
    'Parent-facing approval resolution must report the same SQL Approved state by normalized identity.'
);

$filesystemStatus = 'Approved';
$sqlRow = ['registration_id' => 6, 'registration_status' => 'pending'];
approval_resolver_assert(
    approval_resolver_service($sqlResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === false
    && $sqlResolver->resolve('YC2026003', 'release2.student@example.test')
        === StudentApprovalResolver::PENDING,
    'SQL Pending must override a stale filesystem Approved value.'
);

$sqlRow = ['registration_id' => 6, 'registration_status' => 'rejected'];
approval_resolver_assert(
    approval_resolver_service($sqlResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === false
    && $sqlResolver->resolve('YC2026003', 'release2.student@example.test')
        === StudentApprovalResolver::REJECTED,
    'SQL Rejected must remain rejected for authentication and display.'
);

$sqlRow = null;
approval_resolver_assert(
    approval_resolver_service($sqlResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === false,
    'A configured SQL source with no matching registration must fail closed.'
);

$unavailableResolver = new StudentApprovalResolver(
    true,
    static function (string $yuvaId, string $studentEmail): ?array {
        throw new RuntimeException('Synthetic database outage.');
    },
    static fn(string $yuvaId): string => 'Approved'
);
approval_resolver_assert(
    $unavailableResolver->resolve('YC2026003', 'release2.student@example.test')
        === StudentApprovalResolver::UNAVAILABLE
    && approval_resolver_service($unavailableResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === false,
    'Configured but unavailable SQL must fail closed without filesystem downgrade.'
);

$filesystemStatus = 'Approved';
$filesystemResolver = new StudentApprovalResolver(
    false,
    static function (string $yuvaId, string $studentEmail): ?array {
        throw new RuntimeException('Filesystem-only mode must not query SQL.');
    },
    static function (string $yuvaId) use (&$filesystemStatus): string {
        return $filesystemStatus;
    }
);
approval_resolver_assert(
    approval_resolver_service($filesystemResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === true,
    'Filesystem-only mode must preserve exact Approved behavior.'
);
$filesystemStatus = 'Pending';
approval_resolver_assert(
    approval_resolver_service($filesystemResolver)->authenticate(
        'filesystem',
        'YC2026003',
        'CorrectPass!2026'
    )['authenticated'] === false,
    'Filesystem-only Pending must remain blocked.'
);

$capturedSql = '';
$capturedParameters = [];
$repository = new PortalRepository(
    static function (string $sql, array $parameters) use (
        &$capturedSql,
        &$capturedParameters
    ): ?array {
        $capturedSql = $sql;
        $capturedParameters = $parameters;
        return ['registration_id' => 6, 'registration_status' => 'approved'];
    },
    static fn(string $sql, array $parameters): array => []
);
$repository->findRegistrationApprovalByStudentIdentity(
    'YC2026003',
    'release2.student@example.test'
);
approval_resolver_assert(
    str_contains($capturedSql, 'student.yuva_id = :student_yuva_id')
    && str_contains($capturedSql, 'registration.reserved_yuva_id = :reserved_yuva_id')
    && str_contains($capturedSql, 'registration.student_email')
    && $capturedParameters['student_yuva_id'] === 'YC2026003'
    && $capturedParameters['student_email_lookup'] === 'release2.student@example.test',
    'Repository lookup must map approval by student YUVA ID, reserved YUVA ID, or normalized email.'
);

$portalSource = file_get_contents(__DIR__ . '/../../portal-lib.php');
$parentSource = file_get_contents(__DIR__ . '/../../parent.php');
approval_resolver_assert(
    is_string($portalSource)
    && str_contains($portalSource, 'function student_approval_status(')
    && str_contains($portalSource, 'student_approval_status($studentId, $student)')
    && is_string($parentSource)
    && str_contains($parentSource, '$approvalStatus = student_approval_status($studentId, $student);')
    && str_contains($parentSource, 'e($approvalStatus)'),
    'Student login revalidation and parent display must share the centralized resolver.'
);

fwrite(STDOUT, "PASS SQL approval is authoritative for filesystem authentication\n");
fwrite(STDOUT, "PASS parent display uses the shared approval resolver\n");
fwrite(STDOUT, "PASS SQL unavailable fails closed without filesystem downgrade\n");
fwrite(STDOUT, "PASS filesystem-only approval fallback remains supported\n");
