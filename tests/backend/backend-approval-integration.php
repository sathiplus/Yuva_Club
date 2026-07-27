<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/repositories.php';

function approval_integration_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function approval_integration_guard(): void {
    if (app_environment() !== 'test') {
        throw new RuntimeException('Integration test requires APP_ENV=test.');
    }
    if (env_value('YUVA_RUN_SQL_INTEGRATION') !== 'YES') {
        throw new RuntimeException(
            'Integration test requires YUVA_RUN_SQL_INTEGRATION=YES.'
        );
    }
    if (db_driver() !== 'sqlsrv') {
        throw new RuntimeException('Integration test requires DB_DRIVER=sqlsrv.');
    }
    if (!sql_approval_enabled()) {
        throw new RuntimeException(
            'Integration test requires SQL_APPROVAL_ENABLED=true.'
        );
    }
    $databaseName = strtolower(env_value('DB_DATABASE'));
    if (
        $databaseName === ''
        || preg_match('/(?:test|ci|scratch|temp)/', $databaseName) !== 1
    ) {
        throw new RuntimeException(
            'Database name must identify a disposable test database.'
        );
    }
}

function approval_test_insert_user(
    PDO $pdo,
    string $email,
    string $role,
    string $name
): int {
    $stmt = $pdo->prepare(
        "INSERT INTO dbo.users (email, role, display_name, status)
         OUTPUT INSERTED.id
         VALUES (:email, :role, :display_name, N'active')"
    );
    $stmt->execute([
        'email' => $email,
        'role' => $role,
        'display_name' => $name,
    ]);
    return db_inserted_id($pdo, $stmt);
}

function approval_test_independent_connection(): PDO {
    $config = app_config()['database'];
    $dsn = sprintf(
        'sqlsrv:Server=tcp:%s,%s;Database=%s;Encrypt=yes;'
        . 'TrustServerCertificate=no;ConnectionPooling=0',
        $config['host'],
        $config['port'] ?: '1433',
        $config['name']
    );
    return new PDO(
        $dsn,
        $config['user'],
        $config['password'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function approval_test_insert_registration(
    PDO $pdo,
    string $nameSuffix,
    string $parentEmail,
    ?string $studentEmail,
    ?string $reservedYuvaId = null
): int {
    $programId = $pdo->query(
        "SELECT TOP (1) id FROM dbo.programs WHERE code = N'school_yuva'"
    )->fetchColumn();
    approval_integration_assert($programId !== false, 'Test program is missing.');

    $stmt = $pdo->prepare(
        "INSERT INTO dbo.registrations (
            status, student_first_name, student_last_name, date_of_birth, age,
            program_id, parent_name, relationship, parent_email, student_email,
            code_of_conduct_agreed, recording_agreed, parent_permission_granted,
            reserved_yuva_id
        )
        OUTPUT INSERTED.id
        VALUES (
            N'new', :first_name, N'Student', '2012-01-01', 14,
            :program_id, N'Integration Parent', N'Guardian', :parent_email,
            :student_email, 1, 0, 1, :reserved_yuva_id
        )"
    );
    $stmt->execute([
        'first_name' => 'Integration' . $nameSuffix,
        'program_id' => $programId,
        'parent_email' => $parentEmail,
        'student_email' => $studentEmail,
        'reserved_yuva_id' => $reservedYuvaId,
    ]);
    return db_inserted_id($pdo, $stmt);
}

approval_integration_guard();
$pdo = db();
$schemaReady = $pdo->query(
    "SELECT CASE
        WHEN OBJECT_ID(N'dbo.registrations', N'U') IS NOT NULL
         AND OBJECT_ID(N'dbo.yuva_id_counters', N'U') IS NOT NULL
        THEN 1 ELSE 0 END"
)->fetchColumn();
approval_integration_assert(
    (int) $schemaReady === 1,
    'Disposable test database must have the reviewed Phase A migrations applied.'
);
$run = strtolower(bin2hex(random_bytes(6)));
$year = (int) gmdate('Y');
$parentEmail = "parent-{$run}@example.test";
$registrationIds = [];
$userIds = [];

$counterStmt = $pdo->prepare(
    'SELECT last_number FROM dbo.yuva_id_counters WHERE [year] = :year'
);
$counterStmt->execute(['year' => $year]);
$counterValue = $counterStmt->fetchColumn();
$originalCounter = $counterValue === false ? null : (int) $counterValue;

try {
    if ($originalCounter === null) {
        $pdo->prepare(
            'INSERT INTO dbo.yuva_id_counters ([year], last_number) VALUES (:year, 0)'
        )->execute(['year' => $year]);
    }
    $workingCounter = $originalCounter ?? 0;
    $pdo->prepare(
        'DELETE FROM dbo.yuva_id_counters WHERE [year] = :year'
    )->execute(['year' => $year]);
    try {
        Database::transaction(
            static fn(PDO $transactionPdo) => next_yuva_id(
                $transactionPdo,
                (string) $year
            ),
            'SERIALIZABLE',
            true
        );
        throw new RuntimeException('Missing counter unexpectedly allocated an ID.');
    } catch (RuntimeException $error) {
        approval_integration_assert(
            str_contains($error->getMessage(), 'not initialized'),
            'Missing counter returned the wrong failure.'
        );
    }
    $pdo->prepare(
        'INSERT INTO dbo.yuva_id_counters ([year], last_number)
         VALUES (:year, :last_number)'
    )->execute([
        'year' => $year,
        'last_number' => $workingCounter,
    ]);
    $adminId = approval_test_insert_user(
        $pdo,
        "admin-{$run}@example.test",
        'admin',
        'Integration Admin'
    );
    $userIds[] = $adminId;

    $firstId = approval_test_insert_registration($pdo, 'One', $parentEmail, null);
    $registrationIds[] = $firstId;
    $firstIdentityResources = backend_registration_identity_lock_resources([
        'student_email' => null,
        'parent_email' => $parentEmail,
        'reserved_yuva_id' => null,
    ]);
    $counterBeforeLockFailure = (int) $pdo->query(
        "SELECT last_number FROM dbo.yuva_id_counters WHERE [year] = {$year}"
    )->fetchColumn();
    $blocker = approval_test_independent_connection();
    $blocker->beginTransaction();
    try {
        db_acquire_application_lock(
            $blocker,
            $firstIdentityResources[0],
            0,
            'Transaction'
        );
        try {
            approve_registration($firstId, $adminId);
            throw new RuntimeException(
                'Identity lock contention unexpectedly approved.'
            );
        } catch (RuntimeException $error) {
            approval_integration_assert(
                str_contains($error->getMessage(), 'already in progress'),
                'Identity lock contention returned the wrong failure.'
            );
        }
    } finally {
        db_safe_rollback($blocker);
    }
    $lockFailureRegistration = $pdo->prepare(
        'SELECT status, student_id
         FROM dbo.registrations
         WHERE id = :id'
    );
    $lockFailureRegistration->execute(['id' => $firstId]);
    $lockFailureState = $lockFailureRegistration->fetch();
    approval_integration_assert(
        is_array($lockFailureState)
        && $lockFailureState['status'] === 'new'
        && $lockFailureState['student_id'] === null,
        'Identity lock failure did not leave registration state unchanged.'
    );
    $counterAfterLockFailure = (int) $pdo->query(
        "SELECT last_number FROM dbo.yuva_id_counters WHERE [year] = {$year}"
    )->fetchColumn();
    approval_integration_assert(
        $counterBeforeLockFailure === $counterAfterLockFailure,
        'Identity lock failure did not roll back before counter allocation.'
    );

    $firstYuvaId = approve_registration($firstId, $adminId);
    approval_integration_assert(
        approve_registration($firstId, $adminId) === $firstYuvaId,
        'Repeated approval changed the YUVA ID.'
    );
    $identityStmt = $pdo->prepare(
        'SELECT student.id AS student_id, student_user.email, parent.id AS parent_id
         FROM dbo.registrations AS registration
         INNER JOIN dbo.students AS student ON student.id = registration.student_id
         INNER JOIN dbo.users AS student_user ON student_user.id = student.user_id
         INNER JOIN dbo.student_parents AS student_parent
             ON student_parent.student_id = student.id
         INNER JOIN dbo.parents AS parent ON parent.id = student_parent.parent_id
         WHERE registration.id = :id'
    );
    $identityStmt->execute(['id' => $firstId]);
    $identity = $identityStmt->fetch();
    approval_integration_assert(
        is_array($identity)
        && $identity['email'] === backend_internal_student_email($firstYuvaId),
        'Missing student email did not create the deterministic internal identity.'
    );
    sqlsrv_link_student_parent(
        $pdo,
        (int) $identity['student_id'],
        (int) $identity['parent_id'],
        true
    );
    $relationshipStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM dbo.student_parents
         WHERE student_id = :student_id AND parent_id = :parent_id'
    );
    $relationshipStmt->execute([
        'student_id' => $identity['student_id'],
        'parent_id' => $identity['parent_id'],
    ]);
    approval_integration_assert(
        (int) $relationshipStmt->fetchColumn() === 1,
        'Repeated relationship creation was not idempotent.'
    );
    $activity = $pdo->prepare(
        "SELECT COUNT(*) FROM dbo.activity_logs
         WHERE action = N'registration.approved'
           AND entity_type = N'registration' AND entity_id = :id"
    );
    $activity->execute(['id' => $firstId]);
    approval_integration_assert(
        (int) $activity->fetchColumn() === 1,
        'Repeated approval duplicated its activity record.'
    );

    $siblingId = approval_test_insert_registration(
        $pdo,
        'Sibling',
        $parentEmail,
        "sibling-{$run}@example.test"
    );
    $registrationIds[] = $siblingId;
    approve_registration($siblingId, $adminId);
    $parents = $pdo->prepare(
        'SELECT COUNT(*) FROM dbo.parents AS parent
         INNER JOIN dbo.users AS parent_user ON parent_user.id = parent.user_id
         WHERE parent_user.email = :email'
    );
    $parents->execute(['email' => $parentEmail]);
    approval_integration_assert(
        (int) $parents->fetchColumn() === 1,
        'Sibling approval duplicated its parent.'
    );

    $reserved = sprintf('YC%d9%05d', $year, random_int(1, 99999));
    $reservedId = approval_test_insert_registration(
        $pdo,
        'Reserved',
        "reserved-parent-{$run}@example.test",
        "reserved-student-{$run}@example.test",
        $reserved
    );
    $registrationIds[] = $reservedId;
    approval_integration_assert(
        approve_registration($reservedId, $adminId) === $reserved,
        'Reserved YUVA ID was not preserved.'
    );

    $conflictEmail = "conflict-{$run}@example.test";
    $userIds[] = approval_test_insert_user(
        $pdo,
        $conflictEmail,
        'admin',
        'Role Conflict'
    );
    $conflictId = approval_test_insert_registration(
        $pdo,
        'Conflict',
        "conflict-parent-{$run}@example.test",
        $conflictEmail
    );
    $registrationIds[] = $conflictId;
    $before = (int) $pdo->query(
        "SELECT last_number FROM dbo.yuva_id_counters WHERE [year] = {$year}"
    )->fetchColumn();
    try {
        approve_registration($conflictId, $adminId);
        throw new RuntimeException('Role conflict unexpectedly approved.');
    } catch (RuntimeException $error) {
        approval_integration_assert(
            str_contains($error->getMessage(), 'incompatible account role'),
            'Role conflict returned the wrong failure.'
        );
    }
    $after = (int) $pdo->query(
        "SELECT last_number FROM dbo.yuva_id_counters WHERE [year] = {$year}"
    )->fetchColumn();
    approval_integration_assert($before === $after, 'Failure did not roll back.');

    fwrite(STDOUT, "PASS transactional approval and retry idempotency\n");
    fwrite(STDOUT, "PASS identity lock contention rollback\n");
    fwrite(STDOUT, "PASS missing counter safe failure\n");
    fwrite(STDOUT, "PASS parent reuse and relationship idempotency\n");
    fwrite(STDOUT, "PASS reserved YUVA ID and internal email behavior\n");
    fwrite(STDOUT, "PASS incompatible-role rollback\n");
} finally {
    foreach (array_reverse($registrationIds) as $registrationId) {
        $stmt = $pdo->prepare(
            'SELECT student_id FROM dbo.registrations WHERE id = :id'
        );
        $stmt->execute(['id' => $registrationId]);
        $studentId = $stmt->fetchColumn();
        $pdo->prepare(
            "DELETE FROM dbo.activity_logs
             WHERE entity_type = N'registration' AND entity_id = :id"
        )->execute(['id' => $registrationId]);
        $pdo->prepare('DELETE FROM dbo.registrations WHERE id = :id')
            ->execute(['id' => $registrationId]);
        if ($studentId !== false && $studentId !== null) {
            $userStmt = $pdo->prepare(
                'SELECT user_id FROM dbo.students WHERE id = :id'
            );
            $userStmt->execute(['id' => $studentId]);
            $studentUserId = $userStmt->fetchColumn();
            $pdo->prepare(
                'DELETE FROM dbo.student_parents WHERE student_id = :id'
            )->execute(['id' => $studentId]);
            $pdo->prepare('DELETE FROM dbo.students WHERE id = :id')
                ->execute(['id' => $studentId]);
            if ($studentUserId !== false && $studentUserId !== null) {
                $userIds[] = (int) $studentUserId;
            }
        }
    }
    $parentRows = $pdo->prepare(
        'SELECT parent.id AS parent_id, parent_user.id AS user_id
         FROM dbo.parents AS parent
         INNER JOIN dbo.users AS parent_user ON parent_user.id = parent.user_id
         WHERE parent_user.email LIKE :pattern'
    );
    $parentRows->execute(['pattern' => "%{$run}%"]);
    foreach ($parentRows->fetchAll() as $row) {
        $pdo->prepare('DELETE FROM dbo.parents WHERE id = :id')
            ->execute(['id' => $row['parent_id']]);
        $userIds[] = (int) $row['user_id'];
    }
    foreach (array_unique($userIds) as $userId) {
        $pdo->prepare('DELETE FROM dbo.users WHERE id = :id')
            ->execute(['id' => $userId]);
    }
    if ($originalCounter === null) {
        $pdo->prepare('DELETE FROM dbo.yuva_id_counters WHERE [year] = :year')
            ->execute(['year' => $year]);
    } else {
        $pdo->prepare(
            'UPDATE dbo.yuva_id_counters
             SET last_number = :last_number WHERE [year] = :year'
        )->execute([
            'last_number' => $originalCounter,
            'year' => $year,
        ]);
    }
}
