<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function db_null_if_blank(mixed $value): mixed {
    if (is_string($value) && trim($value) === '') {
        return null;
    }
    return $value;
}

function find_program_id_by_code(string $code): ?int {
    $sql = db_is_sqlsrv()
        ? 'SELECT TOP (1) id FROM programs WHERE code = :code AND is_active = 1'
        : 'SELECT id FROM programs WHERE code = :code AND is_active = 1 LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute(['code' => $code]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function next_yuva_id(PDO $pdo, string $year): string {
    if (preg_match('/^\d{4}$/', $year) !== 1) {
        throw new InvalidArgumentException('YUVA ID year must contain exactly four digits.');
    }
    $numericYear = (int) $year;
    if ($numericYear < 2000 || $numericYear > 9999) {
        throw new InvalidArgumentException('YUVA ID year is outside the supported range.');
    }

    if (db_is_sqlsrv($pdo)) {
        $stmt = $pdo->prepare(
            'UPDATE dbo.yuva_id_counters WITH (UPDLOCK, HOLDLOCK)
             SET last_number = last_number + 1,
                 updated_at = SYSUTCDATETIME()
             OUTPUT INSERTED.last_number
             WHERE [year] = :year'
        );
        $stmt->bindValue(':year', $numericYear, PDO::PARAM_INT);
        $stmt->execute();
        $next = $stmt->fetchColumn();
        if ($next === false) {
            throw new RuntimeException(
                'YUVA ID counter is not initialized for the requested year.'
            );
        }
        if (!is_numeric($next) || (int) $next < 1) {
            throw new RuntimeException('YUVA ID counter returned an invalid value.');
        }
        return sprintf('YC%s%03d', $year, (int) $next);
    }

    $stmt = $pdo->prepare('SELECT yuva_id FROM students WHERE yuva_id LIKE :prefix ORDER BY yuva_id DESC LIMIT 1');
    $stmt->execute(['prefix' => 'YC' . $year . '%']);
    $latest = (string) ($stmt->fetchColumn() ?: '');
    $next = 1;
    if (preg_match('/^YC' . preg_quote($year, '/') . '(\d+)$/', $latest, $matches) === 1) {
        $next = ((int) $matches[1]) + 1;
    }
    return sprintf('YC%s%03d', $year, $next);
}

function create_registration(array $input): int {
    return Database::transaction(function (PDO $pdo) use ($input): int {
        $programCode = (int) ($input['age'] ?? 0) >= 18 ? 'college_yuva' : 'school_yuva';
        $programLimit = db_driver() === 'sqlsrv' ? 'SELECT TOP 1 id FROM programs WHERE code = :code AND is_active = 1' : 'SELECT id FROM programs WHERE code = :code AND is_active = 1 LIMIT 1';
        $programStmt = $pdo->prepare($programLimit);
        $programStmt->execute(['code' => $programCode]);
        $programId = $programStmt->fetchColumn();
        $programId = $programId === false ? null : (int) $programId;

        $insertSql = 'INSERT INTO registrations (
                submitted_at, student_first_name, student_last_name, preferred_name, date_of_birth, age,
                program_id, grade, school, city_state, parent_name, relationship, parent_email,
                parent_phone, student_email, student_phone, whatsapp_contact, interests, why_join,
                presentation_experience, presentation_topics, preferred_schedule, suggestions,
                code_of_conduct_agreed, recording_agreed, parent_permission_granted, ip_address
            )'
            . (db_is_sqlsrv($pdo) ? ' OUTPUT INSERTED.id' : '')
            . ' VALUES (
                COALESCE(:submitted_at, ' . db_now_sql() . '), :student_first_name, :student_last_name, :preferred_name, :date_of_birth, :age,
                :program_id, :grade, :school, :city_state, :parent_name, :relationship, :parent_email,
                :parent_phone, :student_email, :student_phone, :whatsapp_contact, :interests, :why_join,
                :presentation_experience, :presentation_topics, :preferred_schedule, :suggestions,
                :code_of_conduct_agreed, :recording_agreed, :parent_permission_granted, :ip_address
            )';
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            'submitted_at' => db_null_if_blank($input['submitted_at'] ?? null),
            'student_first_name' => $input['student_first_name'] ?? '',
            'student_last_name' => $input['student_last_name'] ?? '',
            'preferred_name' => db_null_if_blank($input['preferred_name'] ?? null),
            'date_of_birth' => db_null_if_blank($input['date_of_birth'] ?? null),
            'age' => db_null_if_blank($input['age'] ?? null),
            'program_id' => $programId,
            'grade' => db_null_if_blank($input['grade'] ?? null),
            'school' => db_null_if_blank($input['school'] ?? null),
            'city_state' => db_null_if_blank($input['city_state'] ?? null),
            'parent_name' => $input['parent_name'] ?? '',
            'relationship' => db_null_if_blank($input['relationship'] ?? null),
            'parent_email' => strtolower((string) ($input['parent_email'] ?? '')),
            'parent_phone' => db_null_if_blank($input['parent_phone'] ?? null),
            'student_email' => db_null_if_blank(strtolower((string) ($input['student_email'] ?? ''))),
            'student_phone' => db_null_if_blank($input['student_phone'] ?? null),
            'whatsapp_contact' => db_null_if_blank($input['whatsapp_contact'] ?? null),
            'interests' => db_null_if_blank($input['interests'] ?? null),
            'why_join' => db_null_if_blank($input['why_join'] ?? null),
            'presentation_experience' => db_null_if_blank($input['presentation_experience'] ?? null),
            'presentation_topics' => db_null_if_blank($input['presentation_topics'] ?? null),
            'preferred_schedule' => db_null_if_blank($input['preferred_schedule'] ?? null),
            'suggestions' => db_null_if_blank($input['suggestions'] ?? null),
            'code_of_conduct_agreed' => !empty($input['code_of_conduct_agreed']) ? 1 : 0,
            'recording_agreed' => !empty($input['recording_agreed']) ? 1 : 0,
            'parent_permission_granted' => !empty($input['parent_permission_granted']) ? 1 : 0,
            'ip_address' => $input['ip_address'] ?? null,
        ]);

        $registrationId = db_inserted_id($pdo, $stmt);
        log_activity_with_pdo(
            $pdo,
            null,
            'registration.created',
            'registration',
            $registrationId
        );
        return $registrationId;
    });
}

function pending_sql_registrations(int $limit = 50): array {
    if (!sql_approval_enabled() || !db_is_sqlsrv()) {
        throw new RuntimeException('Registration approval is unavailable.');
    }
    $safeLimit = max(1, min(100, $limit));
    $stmt = db()->query(
        "SELECT TOP (" . $safeLimit . ")
             registration.id,
             registration.submitted_at,
             registration.status,
             registration.student_first_name,
             registration.student_last_name,
             registration.preferred_name,
             registration.age,
             registration.grade,
             registration.school,
             program.code AS program_code,
             program.name AS program_name
         FROM dbo.registrations AS registration
         LEFT JOIN dbo.programs AS program
             ON program.id = registration.program_id
         WHERE registration.status IN (N'new', N'reviewing', N'waitlisted')
         ORDER BY registration.submitted_at, registration.id"
    );
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function find_sql_admin_user_id(string $email): ?int {
    if (!sql_approval_enabled() || !db_is_sqlsrv()) {
        return null;
    }
    $normalized = backend_usable_email($email);
    if ($normalized === null) {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT TOP (1) id
         FROM dbo.users
         WHERE email = :email
           AND role = N'admin'
           AND status = N'active'"
    );
    $stmt->execute(['email' => $normalized]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function approve_registration(int $registrationId, int $adminUserId): string {
    if (db_driver() === 'sqlsrv') {
        if (!sql_approval_enabled()) {
            throw new RuntimeException('Registration approval is unavailable.');
        }
        return approve_registration_sqlsrv($registrationId, $adminUserId);
    }
    if (db_driver() === 'mysql') {
        return approve_registration_mysql($registrationId, $adminUserId);
    }
    throw new RuntimeException('Approval workflow does not support the configured database driver.');
}

function approve_registration_mysql(int $registrationId, int $adminUserId): string {
    return Database::transaction(function (PDO $pdo) use ($registrationId, $adminUserId): string {
        $stmt = $pdo->prepare('SELECT * FROM registrations WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $registrationId]);
        $registration = $stmt->fetch();
        if (!is_array($registration)) {
            throw new RuntimeException('Registration not found.');
        }

        $year = gmdate('Y');
        $yuvaId = next_yuva_id($pdo, $year);
        $levelId = (int) $pdo->query("SELECT id FROM levels WHERE code = 'explorer' LIMIT 1")->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO students (
                program_id, current_level_id, yuva_id, first_name, last_name, preferred_name,
                date_of_birth, grade, school, city_state, phone, whatsapp_contact,
                approval_status, approved_at
            ) VALUES (
                :program_id, :current_level_id, :yuva_id, :first_name, :last_name, :preferred_name,
                :date_of_birth, :grade, :school, :city_state, :phone, :whatsapp_contact,
                "approved", UTC_TIMESTAMP()
            )'
        );
        $stmt->execute([
            'program_id' => $registration['program_id'],
            'current_level_id' => $levelId,
            'yuva_id' => $yuvaId,
            'first_name' => $registration['student_first_name'],
            'last_name' => $registration['student_last_name'],
            'preferred_name' => $registration['preferred_name'],
            'date_of_birth' => $registration['date_of_birth'],
            'grade' => $registration['grade'],
            'school' => $registration['school'],
            'city_state' => $registration['city_state'],
            'phone' => $registration['student_phone'],
            'whatsapp_contact' => $registration['whatsapp_contact'],
        ]);
        $studentId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'UPDATE registrations
             SET student_id = :student_id, status = "approved", reviewed_by = :reviewed_by, reviewed_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'reviewed_by' => $adminUserId,
            'id' => $registrationId,
        ]);

        log_activity_with_pdo(
            $pdo,
            $adminUserId,
            'registration.approved',
            'registration',
            $registrationId,
            ['yuva_id' => $yuvaId]
        );
        return $yuvaId;
    });
}

function backend_normalize_email(string $email): string {
    return strtolower(trim($email));
}

function backend_usable_email(string $email): ?string {
    $normalized = backend_normalize_email($email);
    if ($normalized === '' || strlen($normalized) > 190) {
        return null;
    }
    if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
        return null;
    }
    if (str_ends_with($normalized, '.invalid')) {
        return null;
    }
    return $normalized;
}

function backend_internal_student_email(string $yuvaId): string {
    $localPart = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $yuvaId));
    if ($localPart === '') {
        throw new RuntimeException('YUVA ID cannot produce an internal student identity.');
    }
    return $localPart . '@students.invalid';
}

function backend_validate_reserved_yuva_id(string $yuvaId): string {
    if (preg_match('/^YC(\d{4})(\d{3,})$/', $yuvaId, $matches) !== 1) {
        throw new RuntimeException('Reserved YUVA ID is not in canonical stored format.');
    }
    if ((int) $matches[1] < 2000 || (int) $matches[2] < 1) {
        throw new RuntimeException('Reserved YUVA ID contains an invalid year or numeric suffix.');
    }
    return $yuvaId;
}

function backend_identity_lock_resource(string $email): string {
    $normalized = backend_normalize_email($email);
    if ($normalized === '') {
        throw new InvalidArgumentException(
            'Identity lock requires a normalized email value.'
        );
    }
    return 'yuva-identity-email:' . hash('sha256', $normalized);
}

function backend_identity_lock_resources(array $emails): array {
    $resources = [];
    foreach ($emails as $email) {
        if (!is_string($email)) {
            continue;
        }
        $normalized = backend_normalize_email($email);
        if ($normalized === '') {
            continue;
        }
        $resource = backend_identity_lock_resource($normalized);
        $resources[$resource] = true;
    }
    $sorted = array_keys($resources);
    sort($sorted, SORT_STRING);
    return $sorted;
}

function backend_registration_identity_lock_resources(array $registration): array {
    $emails = [];
    $studentEmail = backend_usable_email(
        (string) ($registration['student_email'] ?? '')
    );
    if ($studentEmail !== null) {
        $emails[] = $studentEmail;
    } else {
        $reservedYuvaId = trim(
            (string) ($registration['reserved_yuva_id'] ?? '')
        );
        if ($reservedYuvaId !== '') {
            $emails[] = backend_internal_student_email(
                backend_validate_reserved_yuva_id($reservedYuvaId)
            );
        }
    }

    $parentEmail = backend_usable_email(
        (string) ($registration['parent_email'] ?? '')
    );
    if ($parentEmail === null) {
        throw new RuntimeException(
            'A valid parent or guardian email is required for approval.'
        );
    }
    $emails[] = $parentEmail;

    return backend_identity_lock_resources($emails);
}

function backend_acquire_lock_resources(
    array $resources,
    callable $acquireLock
): void {
    foreach ($resources as $resource) {
        $acquireLock($resource);
    }
}

function backend_split_name(string $name): array {
    $normalized = trim((string) preg_replace('/\s+/', ' ', $name));
    if ($normalized === '') {
        throw new RuntimeException('Parent or guardian name is required for approval.');
    }
    $parts = explode(' ', $normalized, 2);
    return [$parts[0], $parts[1] ?? null];
}

function sqlsrv_find_user_for_approval(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare(
        'SELECT TOP (1) id, email, role, status, display_name
         FROM dbo.users WITH (UPDLOCK, HOLDLOCK)
         WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    return is_array($user) ? $user : null;
}

function sqlsrv_resolve_user(
    PDO $pdo,
    string $email,
    string $requiredRole,
    string $displayName
): int {
    $existing = sqlsrv_find_user_for_approval($pdo, $email);
    if ($existing !== null) {
        if (($existing['role'] ?? '') !== $requiredRole) {
            throw new RuntimeException(
                'The submitted email is already assigned to an incompatible account role.'
            );
        }
        $stmt = $pdo->prepare(
            "UPDATE dbo.users
             SET status = N'active',
                 updated_at = SYSUTCDATETIME()
             WHERE id = :id"
        );
        $stmt->execute(['id' => $existing['id']]);
        return (int) $existing['id'];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO dbo.users (
            email, password_hash, role, display_name, status,
            email_verification_token_hash, email_verification_expires_at
        )
        OUTPUT INSERTED.id
        VALUES (
            :email, NULL, :role, :display_name, N'active', NULL, NULL
        )"
    );
    $stmt->execute([
        'email' => $email,
        'role' => $requiredRole,
        'display_name' => $displayName,
    ]);
    return db_inserted_id($pdo, $stmt);
}

function sqlsrv_resolve_active_program(PDO $pdo, array $registration): int {
    if (!empty($registration['program_id'])) {
        $stmt = $pdo->prepare(
            'SELECT TOP (1) id
             FROM dbo.programs WITH (HOLDLOCK)
             WHERE id = :id AND is_active = 1'
        );
        $stmt->execute(['id' => $registration['program_id']]);
    } else {
        $programCode = (int) ($registration['age'] ?? 0) >= 18
            ? 'college_yuva'
            : 'school_yuva';
        $stmt = $pdo->prepare(
            'SELECT TOP (1) id
             FROM dbo.programs WITH (HOLDLOCK)
             WHERE code = :code AND is_active = 1'
        );
        $stmt->execute(['code' => $programCode]);
    }
    $programId = $stmt->fetchColumn();
    if ($programId === false) {
        throw new RuntimeException('No active program is available for this registration.');
    }
    return (int) $programId;
}

function sqlsrv_explorer_level_id(PDO $pdo): int {
    $stmt = $pdo->query(
        "SELECT TOP (1) id
         FROM dbo.levels WITH (HOLDLOCK)
         WHERE code = N'explorer' AND is_active = 1"
    );
    $levelId = $stmt->fetchColumn();
    if ($levelId === false) {
        throw new RuntimeException('The Explorer leadership level is unavailable.');
    }
    return (int) $levelId;
}

function sqlsrv_create_student(
    PDO $pdo,
    array $registration,
    int $studentUserId,
    int $programId,
    int $levelId,
    string $yuvaId
): int {
    $collision = $pdo->prepare(
        'SELECT TOP (1) id
         FROM dbo.students WITH (UPDLOCK, HOLDLOCK)
         WHERE yuva_id = :yuva_id'
    );
    $collision->execute(['yuva_id' => $yuvaId]);
    if ($collision->fetchColumn() !== false) {
        throw new RuntimeException('Reserved YUVA ID is already assigned to another student.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO dbo.students (
            user_id, program_id, current_level_id, yuva_id,
            first_name, last_name, preferred_name, date_of_birth,
            grade, school, city_state, phone, whatsapp_contact,
            approval_status, approved_at
        )
        OUTPUT INSERTED.id
        VALUES (
            :user_id, :program_id, :current_level_id, :yuva_id,
            :first_name, :last_name, :preferred_name, :date_of_birth,
            :grade, :school, :city_state, :phone, :whatsapp_contact,
            N'approved', SYSUTCDATETIME()
        )"
    );
    $stmt->execute([
        'user_id' => $studentUserId,
        'program_id' => $programId,
        'current_level_id' => $levelId,
        'yuva_id' => $yuvaId,
        'first_name' => $registration['student_first_name'],
        'last_name' => $registration['student_last_name'],
        'preferred_name' => $registration['preferred_name'],
        'date_of_birth' => $registration['date_of_birth'],
        'grade' => $registration['grade'],
        'school' => $registration['school'],
        'city_state' => $registration['city_state'],
        'phone' => $registration['student_phone'],
        'whatsapp_contact' => $registration['whatsapp_contact'],
    ]);
    return db_inserted_id($pdo, $stmt);
}

function sqlsrv_resolve_parent(PDO $pdo, array $registration): int {
    $parentEmail = backend_usable_email((string) ($registration['parent_email'] ?? ''));
    if ($parentEmail === null) {
        throw new RuntimeException('A valid parent or guardian email is required for approval.');
    }
    [$firstName, $lastName] = backend_split_name((string) $registration['parent_name']);
    $parentUserId = sqlsrv_resolve_user(
        $pdo,
        $parentEmail,
        'parent',
        trim((string) $registration['parent_name'])
    );

    $stmt = $pdo->prepare(
        'SELECT TOP (1) id
         FROM dbo.parents WITH (UPDLOCK, HOLDLOCK)
         WHERE user_id = :user_id'
    );
    $stmt->execute(['user_id' => $parentUserId]);
    $parentId = $stmt->fetchColumn();
    if ($parentId !== false) {
        return (int) $parentId;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dbo.parents (
            user_id, first_name, last_name, relationship, phone
        )
        OUTPUT INSERTED.id
        VALUES (
            :user_id, :first_name, :last_name, :relationship, :phone
        )'
    );
    $stmt->execute([
        'user_id' => $parentUserId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'relationship' => db_null_if_blank($registration['relationship'] ?? null),
        'phone' => db_null_if_blank($registration['parent_phone'] ?? null),
    ]);
    return db_inserted_id($pdo, $stmt);
}

function sqlsrv_link_student_parent(
    PDO $pdo,
    int $studentId,
    int $parentId,
    bool $consentGranted
): void {
    $stmt = $pdo->prepare(
        'SELECT TOP (1) student_id
         FROM dbo.student_parents WITH (UPDLOCK, HOLDLOCK)
         WHERE student_id = :student_id AND parent_id = :parent_id'
    );
    $stmt->execute([
        'student_id' => $studentId,
        'parent_id' => $parentId,
    ]);
    if ($stmt->fetchColumn() !== false) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dbo.student_parents (
            student_id, parent_id, is_primary, consent_status, consent_at
        )
        VALUES (
            :student_id,
            :parent_id,
            CASE WHEN EXISTS (
                SELECT 1 FROM dbo.student_parents WHERE student_id = :existing_student_id
            ) THEN 0 ELSE 1 END,
            :consent_status,
            CASE WHEN :consent_at_enabled = 1 THEN SYSUTCDATETIME() ELSE NULL END
        )'
    );
    $stmt->execute([
        'student_id' => $studentId,
        'parent_id' => $parentId,
        'existing_student_id' => $studentId,
        'consent_status' => $consentGranted ? 'granted' : 'pending',
        'consent_at_enabled' => $consentGranted ? 1 : 0,
    ]);
}

function approve_registration_sqlsrv(int $registrationId, int $adminUserId): string {
    if ($registrationId < 1 || $adminUserId < 1) {
        throw new InvalidArgumentException('Registration and administrator IDs are required.');
    }

    return Database::transaction(
        function (PDO $pdo) use ($registrationId, $adminUserId): string {
            db_acquire_application_lock(
                $pdo,
                'yuva-registration-approval:' . $registrationId,
                0,
                'Transaction'
            );

            $identityStmt = $pdo->prepare(
                'SELECT TOP (1)
                     student_email,
                     parent_email,
                     reserved_yuva_id
                 FROM dbo.registrations
                 WHERE id = :id'
            );
            $identityStmt->execute(['id' => $registrationId]);
            $identityRegistration = $identityStmt->fetch();
            if (!is_array($identityRegistration)) {
                throw new RuntimeException('Registration not found.');
            }
            $identityLockResources =
                backend_registration_identity_lock_resources(
                    $identityRegistration
                );
            backend_acquire_lock_resources(
                $identityLockResources,
                static function (string $resource) use ($pdo): void {
                    db_acquire_application_lock(
                        $pdo,
                        $resource,
                        0,
                        'Transaction'
                    );
                }
            );

            $stmt = $pdo->prepare(
                'SELECT TOP (1) *
                 FROM dbo.registrations WITH (UPDLOCK, HOLDLOCK)
                 WHERE id = :id'
            );
            $stmt->execute(['id' => $registrationId]);
            $registration = $stmt->fetch();
            if (!is_array($registration)) {
                throw new RuntimeException('Registration not found.');
            }
            if (
                backend_registration_identity_lock_resources($registration)
                !== $identityLockResources
            ) {
                throw new RuntimeException(
                    'Registration identity changed during approval.'
                );
            }

            if (($registration['status'] ?? '') === 'approved') {
                if (empty($registration['student_id'])) {
                    throw new RuntimeException(
                        'Approved registration is missing its linked student.'
                    );
                }
                $studentStmt = $pdo->prepare(
                    'SELECT TOP (1) yuva_id
                     FROM dbo.students WITH (HOLDLOCK)
                     WHERE id = :id'
                );
                $studentStmt->execute(['id' => $registration['student_id']]);
                $existingYuvaId = $studentStmt->fetchColumn();
                if (!is_string($existingYuvaId) || $existingYuvaId === '') {
                    throw new RuntimeException(
                        'Approved registration has an invalid linked student.'
                    );
                }
                return $existingYuvaId;
            }

            if (!in_array(
                $registration['status'] ?? '',
                ['new', 'reviewing', 'waitlisted'],
                true
            )) {
                throw new RuntimeException(
                    'Registration is not in a state that can be approved.'
                );
            }

            $programId = sqlsrv_resolve_active_program($pdo, $registration);
            $levelId = sqlsrv_explorer_level_id($pdo);
            $reservedYuvaId = trim((string) ($registration['reserved_yuva_id'] ?? ''));
            if ($reservedYuvaId !== '') {
                $yuvaId = backend_validate_reserved_yuva_id($reservedYuvaId);
            } else {
                $yuvaId = next_yuva_id($pdo, gmdate('Y'));
                $reserveStmt = $pdo->prepare(
                    'UPDATE dbo.registrations
                     SET reserved_yuva_id = :yuva_id,
                         approval_attempted_at = SYSUTCDATETIME(),
                         approval_error_code = NULL,
                         updated_at = SYSUTCDATETIME()
                     WHERE id = :id'
                );
                $reserveStmt->execute([
                    'yuva_id' => $yuvaId,
                    'id' => $registrationId,
                ]);
            }

            $studentName = trim(
                (string) $registration['student_first_name']
                . ' '
                . (string) $registration['student_last_name']
            );
            $studentEmail = backend_usable_email(
                (string) ($registration['student_email'] ?? '')
            ) ?? backend_internal_student_email($yuvaId);
            $studentUserId = sqlsrv_resolve_user(
                $pdo,
                $studentEmail,
                'student',
                $studentName
            );
            $studentId = sqlsrv_create_student(
                $pdo,
                $registration,
                $studentUserId,
                $programId,
                $levelId,
                $yuvaId
            );
            $parentId = sqlsrv_resolve_parent($pdo, $registration);
            sqlsrv_link_student_parent(
                $pdo,
                $studentId,
                $parentId,
                !empty($registration['parent_permission_granted'])
            );

            $stmt = $pdo->prepare(
                "UPDATE dbo.registrations
                 SET student_id = :student_id,
                     program_id = :program_id,
                     reserved_yuva_id = :yuva_id,
                     status = N'approved',
                     reviewed_by = :reviewed_by,
                     reviewed_at = SYSUTCDATETIME(),
                     approval_attempted_at = SYSUTCDATETIME(),
                     approval_error_code = NULL,
                     updated_at = SYSUTCDATETIME()
                 WHERE id = :id"
            );
            $stmt->execute([
                'student_id' => $studentId,
                'program_id' => $programId,
                'yuva_id' => $yuvaId,
                'reviewed_by' => $adminUserId,
                'id' => $registrationId,
            ]);

            log_activity_with_pdo(
                $pdo,
                $adminUserId,
                'registration.approved',
                'registration',
                $registrationId,
                ['yuva_id' => $yuvaId]
            );
            return $yuvaId;
        },
        'SERIALIZABLE',
        true
    );
}

function log_activity_with_pdo(
    PDO $pdo,
    ?int $actorUserId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    array $metadata = []
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (actor_user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
         VALUES (:actor_user_id, :action, :entity_type, :entity_id, :metadata, :ip_address, :user_agent)'
    );
    $stmt->execute([
        'actor_user_id' => $actorUserId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'metadata' => $metadata === [] ? null : json_encode($metadata),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);
}

function log_activity(?int $actorUserId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void {
    log_activity_with_pdo(
        db(),
        $actorUserId,
        $action,
        $entityType,
        $entityId,
        $metadata
    );
}
