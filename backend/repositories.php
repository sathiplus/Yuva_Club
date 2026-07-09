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
    $stmt = db()->prepare('SELECT id FROM programs WHERE code = :code AND is_active = 1 LIMIT 1');
    $stmt->execute(['code' => $code]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function next_yuva_id(PDO $pdo, string $year): string {
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

        $stmt = $pdo->prepare(
            'INSERT INTO registrations (
                submitted_at, student_first_name, student_last_name, preferred_name, date_of_birth, age,
                program_id, grade, school, city_state, parent_name, relationship, parent_email,
                parent_phone, student_email, student_phone, whatsapp_contact, interests, why_join,
                presentation_experience, presentation_topics, preferred_schedule, suggestions,
                code_of_conduct_agreed, recording_agreed, parent_permission_granted, ip_address
            ) VALUES (
                COALESCE(:submitted_at, ' . db_now_sql() . '), :student_first_name, :student_last_name, :preferred_name, :date_of_birth, :age,
                :program_id, :grade, :school, :city_state, :parent_name, :relationship, :parent_email,
                :parent_phone, :student_email, :student_phone, :whatsapp_contact, :interests, :why_join,
                :presentation_experience, :presentation_topics, :preferred_schedule, :suggestions,
                :code_of_conduct_agreed, :recording_agreed, :parent_permission_granted, :ip_address
            )'
        );
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

        $registrationId = (int) $pdo->query(db_identity_sql())->fetchColumn();
        log_activity(null, 'registration.created', 'registration', $registrationId, ['parent_email' => $input['parent_email'] ?? null]);
        return $registrationId;
    });
}

function approve_registration(int $registrationId, int $adminUserId): string {
    if (db_driver() !== 'mysql') {
        throw new RuntimeException('Approval workflow has not yet been ported to this database driver.');
    }

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

        log_activity($adminUserId, 'registration.approved', 'registration', $registrationId, ['yuva_id' => $yuvaId]);
        return $yuvaId;
    });
}

function log_activity(?int $actorUserId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void {
    $stmt = db()->prepare(
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
