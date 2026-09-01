<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;

require_once __DIR__ . '/sqlsrv-integration-environment.php';
$sqlIntegrationConfig = yuva_configure_sqlsrv_integration_environment();
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
    throw new RuntimeException('Student authentication integration requires DB_DRIVER=sqlsrv.');
}

$pdo = Database::connection();
yuva_assert_sqlsrv_integration_identity($pdo, $sqlIntegrationConfig);

$suffix = strtolower(bin2hex(random_bytes(6)));
$email = 'synthetic.auth.' . $suffix . '@example.test';
$yuvaId = 'YCT' . strtoupper($suffix);
$password = 'Synthetic-' . bin2hex(random_bytes(12));
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if (!is_string($passwordHash)) {
    throw new RuntimeException('Unable to create synthetic password hash.');
}

$pdo->beginTransaction();
try {
    $programId = $pdo->query(
        'SELECT TOP (1) id FROM dbo.programs WHERE is_active = 1 ORDER BY id'
    )->fetchColumn();
    if ($programId === false) {
        throw new RuntimeException('No active synthetic-test program is available.');
    }

    $statement = $pdo->prepare(
        <<<'SQL'
INSERT INTO dbo.users (
    email, password_hash, role, display_name, email_verified_at, status
)
OUTPUT INSERTED.id
VALUES (
    :email, :password_hash, N'student', N'Synthetic Auth Student',
    SYSUTCDATETIME(), N'active'
)
SQL
    );
    $statement->execute([
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);
    $userId = (int) $statement->fetchColumn();

    $statement = $pdo->prepare(
        <<<'SQL'
INSERT INTO dbo.students (
    user_id, program_id, yuva_id, first_name, last_name,
    date_of_birth, approval_status, approved_at
)
OUTPUT INSERTED.id
VALUES (
    :user_id, :program_id, :yuva_id, N'Synthetic', N'Auth Student',
    '2010-01-02', N'approved', SYSUTCDATETIME()
)
SQL
    );
    $statement->execute([
        'user_id' => $userId,
        'program_id' => (int) $programId,
        'yuva_id' => $yuvaId,
    ]);
    $studentId = (int) $statement->fetchColumn();

    $statement = $pdo->prepare(
        <<<'SQL'
INSERT INTO dbo.registrations (
    student_id, status, student_first_name, student_last_name,
    date_of_birth, program_id, parent_name, parent_email,
    code_of_conduct_agreed, recording_agreed, parent_permission_granted
)
VALUES (
    :student_id, N'approved', N'Synthetic', N'Auth Student',
    '2010-01-02', :program_id, N'Synthetic Parent', :parent_email,
    1, 1, 1
)
SQL
    );
    $statement->execute([
        'student_id' => $studentId,
        'program_id' => (int) $programId,
        'parent_email' => 'synthetic.parent.' . $suffix . '@example.test',
    ]);

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
        static fn(string $parentEmail): array => [],
        static fn(array $record, string $credential): bool => false
    );
    $service = new AuthenticationService('sql', $students, $parents);

    $success = $service->authenticateStudent($yuvaId, $password);
    $credentialsVersion = (int) ($success['credentials_version'] ?? 0);
    if (
        ($success['authenticated'] ?? false) !== true
        || ($success['source'] ?? null) !== 'sql'
        || ($success['student_id'] ?? null) !== $yuvaId
        || (int) ($success['user_id'] ?? 0) !== $userId
    ) {
        throw new RuntimeException('Synthetic activated SQL student login failed.');
    }
    if ($service->authenticateStudent($yuvaId, 'wrong-password')['authenticated'] !== false) {
        throw new RuntimeException('Wrong synthetic SQL password was accepted.');
    }
    if ($service->revalidateSqlStudentSession($yuvaId, $userId, $credentialsVersion) === null) {
        throw new RuntimeException('Synthetic SQL session did not revalidate.');
    }

    $setUserState = $pdo->prepare(
        <<<'SQL'
UPDATE dbo.users
SET role = :role,
    status = :status,
    password_hash = :password_hash
WHERE id = :id
SQL
    );
    $setUserState->execute([
        'role' => 'student',
        'status' => 'disabled',
        'password_hash' => $passwordHash,
        'id' => $userId,
    ]);
    if ($service->authenticateStudent($yuvaId, $password)['authenticated'] !== false) {
        throw new RuntimeException('Disabled synthetic SQL student was accepted.');
    }

    $setUserState->execute([
        'role' => 'student',
        'status' => 'suspended',
        'password_hash' => $passwordHash,
        'id' => $userId,
    ]);
    if ($service->authenticateStudent($yuvaId, $password)['authenticated'] !== false) {
        throw new RuntimeException('Suspended synthetic SQL student was accepted.');
    }

    $setUserState->execute([
        'role' => 'parent',
        'status' => 'active',
        'password_hash' => $passwordHash,
        'id' => $userId,
    ]);
    if ($service->authenticateStudent($yuvaId, $password)['authenticated'] !== false) {
        throw new RuntimeException('Wrong-role synthetic SQL identity was accepted.');
    }

    $setUserState->execute([
        'role' => 'student',
        'status' => 'active',
        'password_hash' => null,
        'id' => $userId,
    ]);
    if ($service->authenticateStudent($yuvaId, $password)['authenticated'] !== false) {
        throw new RuntimeException('Null-hash synthetic SQL student was accepted.');
    }

    $setUserState->execute([
        'role' => 'student',
        'status' => 'active',
        'password_hash' => $passwordHash,
        'id' => $userId,
    ]);
    if ($service->revalidateSqlStudentSession($yuvaId, $userId, $credentialsVersion) === null) {
        throw new RuntimeException('Restored synthetic SQL session did not revalidate.');
    }

    $pdo->prepare(
        "UPDATE dbo.users SET status = N'suspended' WHERE id = :id"
    )->execute(['id' => $userId]);
    if ($service->revalidateSqlStudentSession($yuvaId, $userId, $credentialsVersion) !== null) {
        throw new RuntimeException('Suspended synthetic SQL session remained authorized.');
    }

    fwrite(STDOUT, "PASS synthetic SQL student authentication\n");
    fwrite(STDOUT, "PASS wrong password and ineligible SQL identity rejection\n");
    fwrite(STDOUT, "PASS protected-session revalidation and suspension rejection\n");
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

$cleanupStatement = $pdo->prepare(
    <<<'SQL'
SELECT
    (SELECT COUNT(*) FROM dbo.users WHERE email = :email) AS user_count,
    (SELECT COUNT(*) FROM dbo.students WHERE yuva_id = :yuva_id) AS student_count
SQL
);
$cleanupStatement->execute([
    'email' => $email,
    'yuva_id' => $yuvaId,
]);
$cleanup = $cleanupStatement->fetch(PDO::FETCH_ASSOC);
if (
    !is_array($cleanup)
    || (int) ($cleanup['user_count'] ?? -1) !== 0
    || (int) ($cleanup['student_count'] ?? -1) !== 0
) {
    throw new RuntimeException('Synthetic SQL student cleanup verification failed.');
}

fwrite(STDOUT, "PASS SQL database and contained-user identity verification\n");
fwrite(STDOUT, "PASS synthetic SQL student cleanup verification\n");
