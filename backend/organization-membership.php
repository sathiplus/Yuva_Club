<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

final class OrganizationMembershipService
{
    public const TOKEN_TTL_HOURS = 72;
    public const OPEN_STATUSES = ['Invited', 'StudentAccepted', 'ParentApprovalPending'];

    public function __construct(private readonly PDO $pdo)
    {
        if (!db_is_sqlsrv($pdo)) {
            throw new RuntimeException('Organization membership requires Azure SQL.');
        }
    }

    public function createRequest(array $admin, array $input): array
    {
        $organizationCode = $this->organizationCode((string) ($admin['organization_id'] ?? ''));
        $adminEmail = $this->email((string) ($admin['email'] ?? ''));
        if (($admin['role'] ?? '') !== YUVA_ROLE_ORGANIZATION_ADMIN || $organizationCode === '' || $adminEmail === '') {
            throw new RuntimeException('Organization administrator scope is unavailable.');
        }

        $requestType = (string) ($input['request_type'] ?? '');
        if (!in_array($requestType, ['InviteNew', 'LinkExisting'], true)) {
            throw new InvalidArgumentException('Unsupported membership request type.');
        }

        $student = null;
        $studentEmail = '';
        if ($requestType === 'InviteNew') {
            $studentEmail = $this->email((string) ($input['student_email'] ?? ''));
            if ($studentEmail === '') {
                throw new InvalidArgumentException('Enter a valid student email.');
            }
        } else {
            $identifier = trim((string) ($input['student_identifier'] ?? ''));
            $student = $this->findStudentByIdentifier($identifier);
            if ($student === null) {
                $this->audit(null, $organizationCode, 'OrganizationAdmin', $adminEmail, 'LinkRequestNeutral', false, ['reason' => 'unresolved']);
                return ['ok' => true, 'neutral' => true, 'email_sent' => false];
            }
            $studentEmail = $this->email((string) ($student['student_email'] ?? ''));
            if ($studentEmail === '') {
                $this->audit(null, $organizationCode, 'OrganizationAdmin', $adminEmail, 'LinkRequestNeutral', false, ['reason' => 'no_contact']);
                return ['ok' => true, 'neutral' => true, 'email_sent' => false];
            }
        }

        $firstName = $this->shortText((string) ($input['student_first_name'] ?? ''), 120);
        $lastName = $this->shortText((string) ($input['student_last_name'] ?? ''), 120);
        $parentEmail = $this->optionalEmail((string) ($input['parent_email'] ?? ''));
        $cohort = $this->shortText((string) ($input['cohort_label'] ?? ''), 120);
        $purpose = $this->shortText((string) ($input['invitation_purpose'] ?? ''), 220);
        $message = $this->shortText((string) ($input['invitation_message'] ?? ''), 1000);
        if ($purpose === '') {
            throw new InvalidArgumentException('Invitation purpose is required.');
        }

        return Database::transaction(function (PDO $pdo) use (
            $adminEmail, $organizationCode, $requestType, $student, $studentEmail,
            $firstName, $lastName, $parentEmail, $cohort, $purpose, $message
        ): array {
            db_acquire_application_lock($pdo, 'organization-membership:' . hash('sha256', strtolower($studentEmail)), 5000);

            if ($student !== null && $this->hasConflictingActiveMembership((int) $student['student_id'], $organizationCode)) {
                $this->audit(null, $organizationCode, 'OrganizationAdmin', $adminEmail, 'RequestRejected', false, ['reason' => 'active_conflict']);
                return ['ok' => true, 'neutral' => true, 'email_sent' => false];
            }
            $duplicate = $pdo->prepare(
                "SELECT TOP (1) id
                 FROM dbo.organization_student_membership_requests WITH (UPDLOCK, HOLDLOCK)
                 WHERE organization_code = :organization_code
                   AND student_email_normalized = :student_email
                   AND [status] IN (N'Invited', N'StudentAccepted', N'ParentApprovalPending', N'Active')"
            );
            $duplicate->execute(['organization_code' => $organizationCode, 'student_email' => strtolower($studentEmail)]);
            if ($duplicate->fetchColumn() !== false) {
                $this->audit(null, $organizationCode, 'OrganizationAdmin', $adminEmail, 'RequestDuplicate', false);
                return ['ok' => true, 'neutral' => true, 'email_sent' => false];
            }

            $sql = "INSERT INTO dbo.organization_student_membership_requests(
                        organization_code, request_type, student_id,
                        student_first_name, student_last_name,
                        student_email_snapshot, student_email_normalized,
                        parent_email_snapshot, cohort_label, invitation_purpose,
                        invitation_message, invited_by_email, expires_at
                    )
                    OUTPUT INSERTED.id, CONVERT(NVARCHAR(36), INSERTED.membership_guid)
                    VALUES(
                        :organization_code, :request_type, :student_id,
                        :first_name, :last_name, :student_email, :student_email_normalized,
                        :parent_email, :cohort, :purpose, :message, :invited_by,
                        DATEADD(HOUR, " . self::TOKEN_TTL_HOURS . ", SYSUTCDATETIME())
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'organization_code' => $organizationCode,
                'request_type' => $requestType,
                'student_id' => $student['student_id'] ?? null,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'student_email' => $studentEmail,
                'student_email_normalized' => strtolower($studentEmail),
                'parent_email' => $parentEmail !== '' ? $parentEmail : ($student['parent_email'] ?? null),
                'cohort' => $cohort !== '' ? $cohort : null,
                'purpose' => $purpose,
                'message' => $message !== '' ? $message : null,
                'invited_by' => $adminEmail,
            ]);
            $created = $stmt->fetch();
            if (!is_array($created)) {
                throw new RuntimeException('Membership request was not created.');
            }
            $membershipId = (int) $created['id'];
            $token = $this->createToken($membershipId, 'StudentAccept');
            $this->audit($membershipId, $organizationCode, 'OrganizationAdmin', $adminEmail, 'RequestCreated', true, ['request_type' => $requestType]);
            return [
                'ok' => true,
                'neutral' => $requestType === 'LinkExisting',
                'email_sent' => false,
                'membership_id' => $membershipId,
                'membership_guid' => (string) $created['membership_guid'],
                'student_email' => $studentEmail,
                'token' => $token,
                'organization_code' => $organizationCode,
                'request_type' => $requestType,
            ];
        }, 'SERIALIZABLE', true);
    }

    public function tokenRecord(string $rawToken, string $tokenType = 'StudentAccept'): ?array
    {
        if (!$this->validRawToken($rawToken)) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT TOP (1)
                 token.id AS token_id, token.membership_id, token.token_type,
                 request.membership_guid, request.organization_code,
                 request.request_type, request.student_id, request.registration_id,
                 request.student_email_snapshot, request.parent_email_snapshot,
                 request.[status], request.expires_at
             FROM dbo.organization_membership_tokens AS token
             INNER JOIN dbo.organization_student_membership_requests AS request
                ON request.id = token.membership_id
             WHERE token.token_hash = CONVERT(BINARY(32), :token_hash, 2)
               AND token.token_type = :token_type
               AND token.used_at IS NULL
               AND token.revoked_at IS NULL
               AND token.expires_at > SYSUTCDATETIME()
               AND request.expires_at > SYSUTCDATETIME()"
        );
        $stmt->bindValue(':token_hash', hash('sha256', $rawToken), PDO::PARAM_STR);
        $stmt->bindValue(':token_type', $tokenType);
        $stmt->execute();
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function attachRegistration(string $rawToken, int $registrationId, string $studentEmail): bool
    {
        $record = $this->tokenRecord($rawToken, 'StudentAccept');
        $email = $this->email($studentEmail);
        if ($record === null || $email === '' || ($record['request_type'] ?? '') !== 'InviteNew'
            || !hash_equals(strtolower((string) $record['student_email_snapshot']), strtolower($email))) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_student_membership_requests
             SET registration_id = :registration_id, updated_at = SYSUTCDATETIME()
             WHERE id = :id AND registration_id IS NULL AND [status] = N'Invited'"
        );
        $stmt->execute(['registration_id' => $registrationId, 'id' => (int) $record['membership_id']]);
        $ok = $stmt->rowCount() === 1;
        $this->audit((int) $record['membership_id'], (string) $record['organization_code'], 'System', null, 'RegistrationAttached', $ok, ['registration_id' => $registrationId]);
        return $ok;
    }

    public function requestsForOrganization(string $organizationCode): array
    {
        $organizationCode = $this->organizationCode($organizationCode);
        $this->expireOutstandingRequests();
        $stmt = $this->pdo->prepare(
            "SELECT request.membership_guid, request.organization_code, request.request_type,
                    request.student_id, student.yuva_id,
                    request.student_first_name, request.student_last_name,
                    request.student_email_snapshot, request.parent_email_snapshot,
                    request.cohort_label, request.invitation_purpose, request.[status],
                    request.expires_at, request.created_at, request.activated_at
             FROM dbo.organization_student_membership_requests AS request
             LEFT JOIN dbo.students AS student ON student.id = request.student_id
             WHERE request.organization_code = :organization_code
             ORDER BY request.created_at DESC, request.id DESC"
        );
        $stmt->execute(['organization_code' => $organizationCode]);
        return $stmt->fetchAll() ?: [];
    }

    public function requestsForStudent(string $yuvaId): array
    {
        $this->expireOutstandingRequests();
        $stmt = $this->pdo->prepare(
            "SELECT request.membership_guid, request.organization_code,
                    request.request_type, request.invitation_purpose,
                    request.invitation_message, request.cohort_label,
                    request.[status], request.expires_at, request.created_at
             FROM dbo.organization_student_membership_requests AS request
             INNER JOIN dbo.students AS student
                ON student.yuva_id = :yuva_id
             LEFT JOIN dbo.registrations AS registration
                ON registration.id = request.registration_id
             WHERE (request.student_id = student.id OR registration.student_id = student.id)
               AND request.[status] IN (
                   N'Invited', N'StudentAccepted', N'ParentApprovalPending',
                   N'Active', N'Withdrawn'
               )
             ORDER BY request.created_at DESC, request.id DESC"
        );
        $stmt->execute(['yuva_id' => strtoupper(trim($yuvaId))]);
        return $stmt->fetchAll() ?: [];
    }

    public function studentDecision(string $yuvaId, string $membershipGuid, string $decision): array
    {
        if (!in_array($decision, ['accept', 'decline'], true)) {
            throw new InvalidArgumentException('Unsupported student decision.');
        }
        return Database::transaction(function (PDO $pdo) use ($yuvaId, $membershipGuid, $decision): array {
            $request = $this->lockRequestForStudent($yuvaId, $membershipGuid);
            if ($request === null || ($request['status'] ?? '') !== 'Invited') {
                throw new RuntimeException('Membership request is unavailable.');
            }
            $membershipId = (int) $request['id'];
            $organizationCode = (string) $request['organization_code'];
            if ($decision === 'decline') {
                $this->setStatus($membershipId, 'Declined', 'declined_at');
                $this->revokeTokens($membershipId);
                $this->audit($membershipId, $organizationCode, 'Student', strtoupper(trim($yuvaId)), 'StudentDeclined', true);
                return [
                    'status' => 'Declined',
                    'parent_required' => false,
                    'student_email' => (string) ($request['student_email_snapshot'] ?? ''),
                    'organization_code' => $organizationCode,
                ];
            }

            $studentId = (int) $request['resolved_student_id'];
            if ($this->hasConflictingActiveMembership($studentId, $organizationCode)) {
                throw new RuntimeException('Student already has an active organization membership.');
            }
            $parentRequired = $this->parentRequired((string) ($request['date_of_birth'] ?? ''));
            $status = $parentRequired ? 'ParentApprovalPending' : 'Active';
            $sql = "UPDATE dbo.organization_student_membership_requests
                    SET student_id = :student_id, [status] = :status,
                        student_accepted_at = SYSUTCDATETIME(),
                        activated_at = " . ($parentRequired ? 'NULL' : 'SYSUTCDATETIME()') . ",
                        updated_at = SYSUTCDATETIME()
                    WHERE id = :id AND [status] = N'Invited'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId, 'status' => $status, 'id' => $membershipId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Membership request changed before it could be accepted.');
            }
            $this->consumeTokens($membershipId, 'StudentAccept');
            $parentToken = $parentRequired ? $this->createToken($membershipId, 'ParentApprove') : '';
            $this->audit($membershipId, $organizationCode, 'Student', strtoupper(trim($yuvaId)), 'StudentAccepted', true, ['parent_required' => $parentRequired]);
            return [
                'status' => $status,
                'parent_required' => $parentRequired,
                'parent_token' => $parentToken,
                'parent_email' => (string) ($request['resolved_parent_email'] ?? $request['parent_email_snapshot'] ?? ''),
                'student_email' => (string) ($request['student_email_snapshot'] ?? ''),
                'organization_code' => $organizationCode,
            ];
        }, 'SERIALIZABLE', true);
    }

    public function parentDecision(string $parentEmail, string $yuvaId, string $membershipGuid, string $decision): array
    {
        if (!in_array($decision, ['approve', 'decline', 'withdraw'], true)) {
            throw new InvalidArgumentException('Unsupported parent decision.');
        }
        $parentEmail = $this->email($parentEmail);
        if ($parentEmail === '') {
            throw new RuntimeException('Parent identity is unavailable.');
        }
        return Database::transaction(function (PDO $pdo) use ($parentEmail, $yuvaId, $membershipGuid, $decision): array {
            $request = $this->lockRequestForStudent($yuvaId, $membershipGuid);
            if ($request === null) {
                throw new RuntimeException('Membership request is unavailable.');
            }
            $membershipId = (int) $request['id'];
            $organizationCode = (string) $request['organization_code'];
            $current = (string) $request['status'];
            if ($decision === 'withdraw') {
                if ($current !== 'Active') {
                    throw new RuntimeException('Only an active membership can be withdrawn.');
                }
                $this->setStatus($membershipId, 'Withdrawn', 'withdrawn_at');
                $this->audit($membershipId, $organizationCode, 'Parent', $parentEmail, 'ParentWithdrew', true);
                return ['status' => 'Withdrawn', 'organization_code' => $organizationCode, 'student_email' => (string) $request['student_email_snapshot']];
            }
            if ($current !== 'ParentApprovalPending') {
                throw new RuntimeException('Parent approval is not pending.');
            }
            if ($decision === 'decline') {
                $this->setStatus($membershipId, 'Declined', 'declined_at');
                $this->revokeTokens($membershipId);
                $this->audit($membershipId, $organizationCode, 'Parent', $parentEmail, 'ParentDeclined', true);
                return ['status' => 'Declined', 'organization_code' => $organizationCode, 'student_email' => (string) $request['student_email_snapshot']];
            }
            if ($this->hasConflictingActiveMembership((int) $request['resolved_student_id'], $organizationCode)) {
                throw new RuntimeException('Student already has an active organization membership.');
            }
            $stmt = $pdo->prepare(
                "UPDATE dbo.organization_student_membership_requests
                 SET [status] = N'Active', parent_approved_at = SYSUTCDATETIME(),
                     activated_at = SYSUTCDATETIME(), updated_at = SYSUTCDATETIME()
                 WHERE id = :id AND [status] = N'ParentApprovalPending'"
            );
            $stmt->execute(['id' => $membershipId]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Membership request changed before approval.');
            }
            $this->consumeTokens($membershipId, 'ParentApprove');
            $this->audit($membershipId, $organizationCode, 'Parent', $parentEmail, 'ParentApproved', true);
            return ['status' => 'Active', 'organization_code' => $organizationCode, 'student_email' => (string) $request['student_email_snapshot']];
        }, 'SERIALIZABLE', true);
    }

    public function organizationArchive(array $admin, string $membershipGuid): bool
    {
        $organizationCode = $this->organizationCode((string) ($admin['organization_id'] ?? ''));
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_student_membership_requests
             SET [status] = CASE WHEN [status] = N'Active' THEN N'Removed' ELSE N'Archived' END,
                 removed_at = CASE WHEN [status] = N'Active' THEN SYSUTCDATETIME() ELSE removed_at END,
                 archived_at = CASE WHEN [status] <> N'Active' THEN SYSUTCDATETIME() ELSE archived_at END,
                 updated_at = SYSUTCDATETIME()
             WHERE membership_guid = :guid AND organization_code = :organization_code
               AND [status] NOT IN (N'Removed', N'Archived')"
        );
        $stmt->execute(['guid' => $membershipGuid, 'organization_code' => $organizationCode]);
        $ok = $stmt->rowCount() === 1;
        $this->audit(null, $organizationCode, 'OrganizationAdmin', (string) ($admin['email'] ?? ''), 'MembershipArchived', $ok, ['membership_guid' => $membershipGuid]);
        return $ok;
    }

    private function lockRequestForStudent(string $yuvaId, string $membershipGuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT TOP (1) request.*,
                    student.id AS resolved_student_id,
                    student.date_of_birth,
                    parent_user.email AS resolved_parent_email
             FROM dbo.organization_student_membership_requests AS request WITH (UPDLOCK, HOLDLOCK)
             INNER JOIN dbo.students AS student
                ON student.yuva_id = :yuva_id
             LEFT JOIN dbo.registrations AS registration
                ON registration.id = request.registration_id
             LEFT JOIN dbo.student_parents AS link ON link.student_id = student.id AND link.is_primary = 1
             LEFT JOIN dbo.parents AS parent ON parent.id = link.parent_id
             LEFT JOIN dbo.users AS parent_user ON parent_user.id = parent.user_id
             WHERE request.membership_guid = :guid
               AND (request.student_id = student.id OR registration.student_id = student.id)"
        );
        $stmt->execute(['yuva_id' => strtoupper(trim($yuvaId)), 'guid' => $membershipGuid]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function findStudentByIdentifier(string $identifier): ?array
    {
        $normalized = strtolower(trim($identifier));
        if ($normalized === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT TOP (1) student.id AS student_id, student.yuva_id,
                    student.date_of_birth, student_user.email AS student_email,
                    parent_user.email AS parent_email
             FROM dbo.students AS student
             LEFT JOIN dbo.users AS student_user ON student_user.id = student.user_id
             LEFT JOIN dbo.student_parents AS link ON link.student_id = student.id AND link.is_primary = 1
             LEFT JOIN dbo.parents AS parent ON parent.id = link.parent_id
             LEFT JOIN dbo.users AS parent_user ON parent_user.id = parent.user_id
             WHERE student.approval_status = N'approved'
               AND (UPPER(student.yuva_id) = UPPER(:identifier)
                    OR LOWER(student_user.email) = :normalized_email)"
        );
        $stmt->execute(['identifier' => trim($identifier), 'normalized_email' => $normalized]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function hasConflictingActiveMembership(int $studentId, string $organizationCode): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT TOP (1) id
             FROM dbo.organization_student_membership_requests
             WHERE student_id = :student_id AND [status] = N'Active'
               AND organization_code <> :organization_code"
        );
        $stmt->execute(['student_id' => $studentId, 'organization_code' => $organizationCode]);
        return $stmt->fetchColumn() !== false;
    }

    private function createToken(int $membershipId, string $type): string
    {
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $stmt = $this->pdo->prepare(
            "INSERT INTO dbo.organization_membership_tokens(
                membership_id, token_hash, token_type, expires_at
             ) VALUES(:membership_id, CONVERT(BINARY(32), :token_hash, 2), :token_type,
                      DATEADD(HOUR, " . self::TOKEN_TTL_HOURS . ", SYSUTCDATETIME()))"
        );
        $stmt->bindValue(':membership_id', $membershipId, PDO::PARAM_INT);
        $stmt->bindValue(':token_hash', hash('sha256', $raw), PDO::PARAM_STR);
        $stmt->bindValue(':token_type', $type);
        $stmt->execute();
        return $raw;
    }

    private function consumeTokens(int $membershipId, string $type): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_membership_tokens
             SET used_at = COALESCE(used_at, SYSUTCDATETIME())
             WHERE membership_id = :membership_id AND token_type = :token_type
               AND used_at IS NULL AND revoked_at IS NULL"
        );
        $stmt->execute(['membership_id' => $membershipId, 'token_type' => $type]);
    }

    private function revokeTokens(int $membershipId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_membership_tokens
             SET revoked_at = COALESCE(revoked_at, SYSUTCDATETIME())
             WHERE membership_id = :membership_id AND used_at IS NULL AND revoked_at IS NULL"
        );
        $stmt->execute(['membership_id' => $membershipId]);
    }

    private function expireOutstandingRequests(): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_student_membership_requests
             SET [status] = N'Expired', updated_at = SYSUTCDATETIME()
             WHERE [status] IN (N'Invited', N'StudentAccepted', N'ParentApprovalPending')
               AND expires_at <= SYSUTCDATETIME()"
        );
        $stmt->execute();
    }

    private function setStatus(int $membershipId, string $status, string $timestampColumn): void
    {
        $allowed = ['declined_at', 'withdrawn_at'];
        if (!in_array($timestampColumn, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported membership timestamp.');
        }
        $stmt = $this->pdo->prepare(
            "UPDATE dbo.organization_student_membership_requests
             SET [status] = :status, {$timestampColumn} = SYSUTCDATETIME(),
                 updated_at = SYSUTCDATETIME()
             WHERE id = :id"
        );
        $stmt->execute(['status' => $status, 'id' => $membershipId]);
    }

    private function audit(?int $membershipId, ?string $organizationCode, string $actorType, ?string $actor, string $action, bool $success, array $detail = []): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO dbo.organization_membership_audit(
                membership_id, organization_code, actor_type, actor_identifier,
                action_name, succeeded, detail_json
             ) VALUES(:membership_id, :organization_code, :actor_type, :actor,
                      :action_name, :succeeded, :detail_json)"
        );
        $stmt->execute([
            'membership_id' => $membershipId,
            'organization_code' => $organizationCode,
            'actor_type' => $actorType,
            'actor' => $actor !== null && trim($actor) !== '' ? trim($actor) : null,
            'action_name' => $action,
            'succeeded' => $success ? 1 : 0,
            'detail_json' => $detail !== [] ? json_encode($detail, JSON_THROW_ON_ERROR) : null,
        ]);
    }

    private function parentRequired(string $dateOfBirth): bool
    {
        if ($dateOfBirth === '') {
            return true;
        }
        try {
            $dob = new DateTimeImmutable($dateOfBirth);
            return $dob->diff(new DateTimeImmutable('today'))->y < 18;
        } catch (Throwable) {
            return true;
        }
    }

    private function validRawToken(string $token): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{43}$/', $token) === 1;
    }

    private function organizationCode(string $value): string
    {
        $value = strtoupper(trim($value));
        return preg_match('/^[A-Z0-9][A-Z0-9_-]{1,59}$/', $value) === 1 ? $value : '';
    }

    private function email(string $value): string
    {
        $value = strtolower(trim($value));
        return strlen($value) <= 190 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : '';
    }

    private function optionalEmail(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }
        $email = $this->email($value);
        if ($email === '') {
            throw new InvalidArgumentException('Enter a valid parent or guardian email.');
        }
        return $email;
    }

    private function shortText(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        return mb_substr($value, 0, $max);
    }
}

function organization_membership_service(): OrganizationMembershipService
{
    static $service = null;
    if (!$service instanceof OrganizationMembershipService) {
        $service = new OrganizationMembershipService(Database::connection());
    }
    return $service;
}

function organization_membership_student_url(string $token): string
{
    return public_base_url() . '/student-organization-membership.php?token=' . rawurlencode($token);
}

function organization_membership_parent_url(string $token): string
{
    return public_base_url() . '/parent-organization-membership.php?token=' . rawurlencode($token);
}

function send_organization_membership_student_email(array $request): bool
{
    $email = (string) ($request['student_email'] ?? '');
    $organization = (string) ($request['organization_code'] ?? '');
    $url = organization_membership_student_url((string) ($request['token'] ?? ''));
    $subject = 'YUVA Club organization invitation';
    $message = "{$organization} invited you to connect your YUVA Club student account.\n\n"
        . "Review this request using the secure, single-use link below. It expires in 72 hours.\n{$url}\n\n"
        . "No membership becomes active until you accept and, when required, your parent or guardian approves. YUVA Club never sends or asks an organization administrator to create your password.";
    return send_yuva_email($email, $subject, $message);
}

function send_organization_membership_parent_email(string $email, string $organization, string $token): bool
{
    $email = strtolower(trim($email));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $token === '') {
        return false;
    }
    $url = organization_membership_parent_url($token);
    $subject = 'Parent approval requested for a YUVA Club organization';
    $message = "Your child accepted a request to connect with {$organization} in YUVA Club.\n\n"
        . "Please sign in to the Parent Portal and review the request. This secure link expires in 72 hours.\n{$url}\n\n"
        . "The membership will not become active until you approve it.";
    return send_yuva_email($email, $subject, $message);
}

function send_organization_membership_status_emails(string $status, string $organization, string $studentEmail, string $parentEmail = ''): void
{
    $safeStatus = in_array($status, ['Active', 'Declined', 'Withdrawn'], true) ? $status : '';
    if ($safeStatus === '' || $organization === '') {
        return;
    }
    $subject = 'YUVA Club organization membership ' . strtolower($safeStatus);
    $message = "The YUVA Club membership request for {$organization} is now {$safeStatus}.\n\nNo password or authentication credential was shared with the organization.";
    foreach (array_unique([$studentEmail, $parentEmail]) as $recipient) {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false && !send_yuva_email($recipient, $subject, $message)) {
            error_log('YUVA organization membership confirmation delivery failed correlation=' . bin2hex(random_bytes(12)));
        }
    }
    foreach (organization_admin_accounts() as $account) {
        if (normalize_organization_id((string) ($account['organization_id'] ?? '')) !== normalize_organization_id($organization)
            || ($account['status'] ?? '') !== 'active') {
            continue;
        }
        $adminEmail = normalize_email((string) ($account['email'] ?? ''));
        if ($adminEmail !== '' && !send_yuva_email($adminEmail, $subject, $message)) {
            error_log('YUVA organization admin membership status delivery failed correlation=' . bin2hex(random_bytes(12)));
        }
    }
}
