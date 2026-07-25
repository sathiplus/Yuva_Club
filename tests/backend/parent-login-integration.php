<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;

require_once __DIR__ . '/../../backend/database.php';
require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/ParentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/AuthenticationService.php';

if (getenv('YUVA_RUN_SQL_INTEGRATION') !== 'YES') {
    fwrite(STDERR, "Refusing to run without YUVA_RUN_SQL_INTEGRATION=YES.\n");
    exit(2);
}
if (db_driver() !== 'sqlsrv') {
    throw new RuntimeException('Parent authentication integration requires DB_DRIVER=sqlsrv.');
}

$pdo = Database::connection();
if ((string) $pdo->query('SELECT DB_NAME()')->fetchColumn() !== 'yuva_club_phasea_test') {
    throw new RuntimeException('Integration test must use yuva_club_phasea_test.');
}
if ((string) $pdo->query('SELECT USER_NAME()')->fetchColumn() !== 'yuva_phasea_test_runner') {
    throw new RuntimeException('Integration test must use yuva_phasea_test_runner.');
}

$suffix = strtolower(bin2hex(random_bytes(6)));
$parentEmail = 'synthetic.parent.auth.' . $suffix . '@example.test';
$parentPassword = 'Synthetic-Parent-' . bin2hex(random_bytes(10));
$parentHash = password_hash($parentPassword, PASSWORD_DEFAULT);
$studentHash = password_hash(
    'Synthetic-Student-' . bin2hex(random_bytes(10)),
    PASSWORD_DEFAULT
);
if (!is_string($parentHash) || !is_string($studentHash)) {
    throw new RuntimeException('Unable to create synthetic password hashes.');
}
$yuvaIds = [
    'YPT' . strtoupper($suffix) . '1',
    'YPT' . strtoupper($suffix) . '2',
];

$pdo->beginTransaction();
try {
    $programId = $pdo->query(
        'SELECT TOP (1) id FROM dbo.programs WHERE is_active = 1 ORDER BY id'
    )->fetchColumn();
    if ($programId === false) {
        throw new RuntimeException('No active synthetic-test program is available.');
    }

    $insertUser = $pdo->prepare(
        <<<'SQL'
INSERT INTO dbo.users (
    email, password_hash, role, display_name, email_verified_at, status
)
OUTPUT INSERTED.id
VALUES (
    :email, :password_hash, :role, :display_name,
    SYSUTCDATETIME(), N'active'
)
SQL
    );
    $insertUser->execute([
        'email' => $parentEmail,
        'password_hash' => $parentHash,
        'role' => 'parent',
        'display_name' => 'Synthetic Parent Auth',
    ]);
    $parentUserId = (int) $insertUser->fetchColumn();

    $insertParent = $pdo->prepare(
        <<<'SQL'
INSERT INTO dbo.parents (user_id, first_name, last_name, relationship)
OUTPUT INSERTED.id
VALUES (:user_id, N'Synthetic', N'Parent Auth', N'Guardian')
SQL
    );
    $insertParent->execute(['user_id' => $parentUserId]);
    $parentId = (int) $insertParent->fetchColumn();

    $studentIds = [];
    foreach ($yuvaIds as $index => $yuvaId) {
        $studentEmail = sprintf(
            'synthetic.child.%s.%d@example.test',
            $suffix,
            $index + 1
        );
        $insertUser->execute([
            'email' => $studentEmail,
            'password_hash' => $studentHash,
            'role' => 'student',
            'display_name' => 'Synthetic Child ' . ($index + 1),
        ]);
        $studentUserId = (int) $insertUser->fetchColumn();

        $insertStudent = $pdo->prepare(
            <<<'SQL'
INSERT INTO dbo.students (
    user_id, program_id, yuva_id, first_name, last_name,
    date_of_birth, approval_status, approved_at
)
OUTPUT INSERTED.id
VALUES (
    :user_id, :program_id, :yuva_id, N'Synthetic', :last_name,
    '2010-01-02', N'approved', SYSUTCDATETIME()
)
SQL
        );
        $insertStudent->execute([
            'user_id' => $studentUserId,
            'program_id' => (int) $programId,
            'yuva_id' => $yuvaId,
            'last_name' => 'Child ' . ($index + 1),
        ]);
        $studentId = (int) $insertStudent->fetchColumn();
        $studentIds[] = $studentId;

        $insertRegistration = $pdo->prepare(
            <<<'SQL'
INSERT INTO dbo.registrations (
    student_id, status, student_first_name, student_last_name,
    date_of_birth, program_id, parent_name, parent_email,
    code_of_conduct_agreed, recording_agreed, parent_permission_granted
)
VALUES (
    :student_id, N'approved', N'Synthetic', :student_last_name,
    '2010-01-02', :program_id, N'Synthetic Parent Auth', :parent_email,
    1, 1, 1
)
SQL
        );
        $insertRegistration->execute([
            'student_id' => $studentId,
            'student_last_name' => 'Child ' . ($index + 1),
            'program_id' => (int) $programId,
            'parent_email' => $parentEmail,
        ]);

        $insertLink = $pdo->prepare(
            <<<'SQL'
INSERT INTO dbo.student_parents (
    student_id, parent_id, is_primary, consent_status, consent_at
)
VALUES (
    :student_id, :parent_id, :is_primary, N'granted', SYSUTCDATETIME()
)
SQL
        );
        $insertLink->execute([
            'student_id' => $studentId,
            'parent_id' => $parentId,
            'is_primary' => $index === 0 ? 1 : 0,
        ]);
    }

    $repository = PortalRepository::fromPdo($pdo);
    $students = new StudentAuthentication(
        $repository,
        new PortalCompatibilityAdapter(),
        static fn(string $id): ?array => null,
        static fn(array $record, string $credential): bool => false
    );
    $parents = new ParentAuthentication(
        $repository,
        static fn(string $id): ?array => null,
        static fn(string $email): array => [],
        static fn(array $record, string $credential): bool => false
    );
    $service = new AuthenticationService('sql', $students, $parents);

    $success = $service->authenticateParent($parentEmail, $parentPassword);
    if (
        ($success['authenticated'] ?? false) !== true
        || ($success['source'] ?? null) !== 'sql'
        || (int) ($success['parent_user_id'] ?? 0) !== $parentUserId
        || (int) ($success['parent_id'] ?? 0) !== $parentId
        || !array_key_exists('parent_student_id', $success)
        || $success['parent_student_id'] !== null
        || count($success['children'] ?? []) !== 2
    ) {
        throw new RuntimeException('Synthetic SQL parent login or reuse failed.');
    }
    if ($service->authenticateParent($parentEmail, 'wrong-password')['authenticated'] !== false) {
        throw new RuntimeException('Wrong synthetic SQL parent password was accepted.');
    }
    if (!$service->revalidateSqlParentSession($parentUserId, $parentId)) {
        throw new RuntimeException('Synthetic SQL parent identity did not revalidate.');
    }
    foreach ($yuvaIds as $yuvaId) {
        if (
            !$service->parentCanAccessChild($parentUserId, $yuvaId)
            || $service->authorizedSqlParentChildRecord(
                $parentUserId,
                $yuvaId
            ) === null
        ) {
            throw new RuntimeException('Authorized synthetic child was unavailable.');
        }
    }
    if ($service->parentCanAccessChild($parentUserId, 'YC-NOT-LINKED')) {
        throw new RuntimeException('Forged synthetic child access was accepted.');
    }

    $setParentState = $pdo->prepare(
        <<<'SQL'
UPDATE dbo.users
SET role = :role,
    status = :status,
    email_verified_at = :email_verified_at,
    password_hash = :password_hash
WHERE id = :id
SQL
    );
    foreach ([
        ['parent', 'disabled', 'verified', $parentHash],
        ['parent', 'suspended', 'verified', $parentHash],
        ['student', 'active', 'verified', $parentHash],
        ['parent', 'active', null, $parentHash],
        ['parent', 'active', 'verified', null],
    ] as [$role, $status, $verified, $hash]) {
        $setParentState->execute([
            'role' => $role,
            'status' => $status,
            'email_verified_at' => $verified === null ? null : date('Y-m-d H:i:s'),
            'password_hash' => $hash,
            'id' => $parentUserId,
        ]);
        if ($service->authenticateParent($parentEmail, $parentPassword)['authenticated'] !== false) {
            throw new RuntimeException('Ineligible synthetic SQL parent was accepted.');
        }
    }
    $setParentState->execute([
        'role' => 'parent',
        'status' => 'active',
        'email_verified_at' => date('Y-m-d H:i:s'),
        'password_hash' => $parentHash,
        'id' => $parentUserId,
    ]);

    $pdo->prepare(
        <<<'SQL'
UPDATE dbo.student_parents
SET consent_status = N'revoked'
WHERE parent_id = :parent_id AND student_id = :student_id
SQL
    )->execute([
        'parent_id' => $parentId,
        'student_id' => $studentIds[0],
    ]);
    if ($service->parentCanAccessChild($parentUserId, $yuvaIds[0])) {
        throw new RuntimeException('Revoked synthetic child link was accepted.');
    }
    $pdo->prepare(
        <<<'SQL'
UPDATE dbo.student_parents
SET consent_status = N'granted'
WHERE parent_id = :parent_id AND student_id = :student_id
SQL
    )->execute([
        'parent_id' => $parentId,
        'student_id' => $studentIds[0],
    ]);

    $pdo->prepare(
        <<<'SQL'
UPDATE dbo.users
SET status = N'disabled'
WHERE id = (SELECT user_id FROM dbo.students WHERE id = :student_id)
SQL
    )->execute(['student_id' => $studentIds[0]]);
    if ($service->parentCanAccessChild($parentUserId, $yuvaIds[0])) {
        throw new RuntimeException('Disabled synthetic child was accepted.');
    }

    fwrite(STDOUT, "PASS synthetic SQL parent authentication and reuse\n");
    fwrite(STDOUT, "PASS SQL parent status, role, verification, and password gates\n");
    fwrite(STDOUT, "PASS linked-child authorization and revocation gates\n");
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

$cleanup = $pdo->prepare(
    <<<'SQL'
SELECT
    (
        SELECT COUNT(*)
        FROM dbo.users
        WHERE email = :parent_email OR email LIKE :child_email_pattern
    ) AS user_count,
    (
        SELECT COUNT(*)
        FROM dbo.parents AS p
        INNER JOIN dbo.users AS u ON u.id = p.user_id
        WHERE u.email = :parent_email_for_parent
    ) AS parent_count,
    (
        SELECT COUNT(*)
        FROM dbo.students
        WHERE yuva_id IN (:yuva_id_1, :yuva_id_2)
    ) AS student_count,
    (
        SELECT COUNT(*)
        FROM dbo.registrations
        WHERE parent_email = :parent_email_for_registration
    ) AS registration_count,
    (
        SELECT COUNT(*)
        FROM dbo.student_parents AS sp
        INNER JOIN dbo.students AS s ON s.id = sp.student_id
        WHERE s.yuva_id IN (:yuva_id_1_for_link, :yuva_id_2_for_link)
    ) AS link_count
SQL
);
$cleanup->execute([
    'parent_email' => $parentEmail,
    'child_email_pattern' => 'synthetic.child.' . $suffix . '.%@example.test',
    'parent_email_for_parent' => $parentEmail,
    'yuva_id_1' => $yuvaIds[0],
    'yuva_id_2' => $yuvaIds[1],
    'parent_email_for_registration' => $parentEmail,
    'yuva_id_1_for_link' => $yuvaIds[0],
    'yuva_id_2_for_link' => $yuvaIds[1],
]);
$counts = $cleanup->fetch(PDO::FETCH_ASSOC);
if (
    !is_array($counts)
    || (int) ($counts['user_count'] ?? -1) !== 0
    || (int) ($counts['parent_count'] ?? -1) !== 0
    || (int) ($counts['student_count'] ?? -1) !== 0
    || (int) ($counts['registration_count'] ?? -1) !== 0
    || (int) ($counts['link_count'] ?? -1) !== 0
) {
    throw new RuntimeException('Synthetic SQL parent cleanup verification failed.');
}

fwrite(STDOUT, "PASS SQL database and contained-user identity verification\n");
fwrite(STDOUT, "PASS synthetic SQL parent and child cleanup verification\n");
