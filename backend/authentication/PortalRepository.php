<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use PDO;

/**
 * Read-only SQL access for portal authentication.
 *
 * This class deliberately contains no session handling or authentication
 * decisions. Callers must validate status, role, approval, and credentials.
 */
class PortalRepository
{
    /** @var callable(string, array<string, scalar|null>): ?array<string, mixed> */
    private $fetchOne;

    /** @var callable(string, array<string, scalar|null>): array<int, array<string, mixed>> */
    private $fetchAll;

    public function __construct(callable $fetchOne, callable $fetchAll)
    {
        $this->fetchOne = $fetchOne;
        $this->fetchAll = $fetchAll;
    }

    public static function fromPdo(PDO $pdo): self
    {
        return new self(
            static function (string $sql, array $parameters) use ($pdo): ?array {
                $statement = $pdo->prepare($sql);
                $statement->execute($parameters);
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return is_array($row) ? $row : null;
            },
            static function (string $sql, array $parameters) use ($pdo): array {
                $statement = $pdo->prepare($sql);
                $statement->execute($parameters);
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
                return is_array($rows) ? $rows : [];
            }
        );
    }

    /** @return array<string, mixed>|null */
    public function findStudentByYuvaId(string $yuvaId): ?array
    {
        return ($this->fetchOne)(
            <<<'SQL'
SELECT TOP (1)
    student.id AS student_id,
    student.user_id AS student_user_id,
    student.yuva_id,
    student.first_name AS student_first_name,
    student.last_name AS student_last_name,
    student.preferred_name,
    student.date_of_birth,
    student.grade,
    student.school,
    student.city_state,
    student.phone AS student_phone,
    student.whatsapp_contact,
    student.approval_status AS student_approval_status,
    student.approved_at,
    student_user.email AS student_email,
    student_user.password_hash,
    student_user.role AS user_role,
    student_user.status AS user_status,
    student_user.email_verified_at,
    program.code AS program_code,
    program.name AS program_name,
    registration.id AS registration_id,
    registration.status AS registration_status,
    registration.submitted_at AS registration_submitted_at,
    registration.age,
    registration.interests,
    registration.why_join,
    registration.presentation_experience,
    registration.presentation_topics,
    registration.preferred_schedule,
    registration.suggestions,
    registration.code_of_conduct_agreed,
    registration.recording_agreed,
    registration.parent_permission_granted,
    primary_parent.parent_id,
    primary_parent.parent_user_id,
    primary_parent.parent_name,
    primary_parent.parent_relationship,
    primary_parent.parent_email,
    primary_parent.parent_phone,
    primary_parent.parent_is_primary,
    primary_parent.parent_consent_status
FROM dbo.students AS student
INNER JOIN dbo.users AS student_user
    ON student_user.id = student.user_id
INNER JOIN dbo.programs AS program
    ON program.id = student.program_id
OUTER APPLY (
    SELECT TOP (1)
        registration_row.id,
        registration_row.status,
        registration_row.submitted_at,
        registration_row.age,
        registration_row.interests,
        registration_row.why_join,
        registration_row.presentation_experience,
        registration_row.presentation_topics,
        registration_row.preferred_schedule,
        registration_row.suggestions,
        registration_row.code_of_conduct_agreed,
        registration_row.recording_agreed,
        registration_row.parent_permission_granted
    FROM dbo.registrations AS registration_row
    WHERE registration_row.student_id = student.id
    ORDER BY
        CASE WHEN registration_row.status = N'approved' THEN 0 ELSE 1 END,
        registration_row.reviewed_at DESC,
        registration_row.submitted_at DESC,
        registration_row.id DESC
) AS registration
OUTER APPLY (
    SELECT TOP (1)
        parent.id AS parent_id,
        parent.user_id AS parent_user_id,
        LTRIM(RTRIM(CONCAT(parent.first_name, N' ', parent.last_name))) AS parent_name,
        parent.relationship AS parent_relationship,
        parent_user.email AS parent_email,
        parent.phone AS parent_phone,
        student_parent.is_primary AS parent_is_primary,
        student_parent.consent_status AS parent_consent_status
    FROM dbo.student_parents AS student_parent
    INNER JOIN dbo.parents AS parent
        ON parent.id = student_parent.parent_id
    INNER JOIN dbo.users AS parent_user
        ON parent_user.id = parent.user_id
    WHERE student_parent.student_id = student.id
    ORDER BY
        student_parent.is_primary DESC,
        student_parent.created_at,
        student_parent.parent_id
) AS primary_parent
WHERE student.yuva_id = :yuva_id
SQL,
            ['yuva_id' => $yuvaId]
        );
    }

    /** @return array<string, mixed>|null */
    public function findParentByEmail(string $normalizedEmail): ?array
    {
        return ($this->fetchOne)(
            <<<'SQL'
SELECT TOP (1)
    parent.id AS parent_id,
    parent.user_id AS parent_user_id,
    parent.first_name,
    parent.last_name,
    parent.relationship,
    parent.phone,
    parent_user.email,
    parent_user.password_hash,
    parent_user.role AS user_role,
    parent_user.status AS user_status,
    parent_user.email_verified_at
FROM dbo.parents AS parent
INNER JOIN dbo.users AS parent_user
    ON parent_user.id = parent.user_id
WHERE LOWER(LTRIM(RTRIM(parent_user.email))) = :email
SQL,
            ['email' => $normalizedEmail]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findAuthorizedChildren(int $parentUserId): array
    {
        return ($this->fetchAll)(
            <<<'SQL'
SELECT
    student_parent.parent_id,
    student_parent.student_id,
    student_parent.is_primary,
    student_parent.consent_status,
    parent_user.role AS parent_user_role,
    parent_user.status AS parent_user_status,
    parent_user.email_verified_at AS parent_email_verified_at,
    parent_user.password_hash AS parent_password_hash,
    student.id,
    student.user_id AS student_user_id,
    student.yuva_id,
    student.first_name AS student_first_name,
    student.last_name AS student_last_name,
    student.preferred_name,
    student.approval_status AS student_approval_status,
    student.approved_at,
    student_user.role AS student_user_role,
    student_user.status AS student_user_status
FROM dbo.parents AS parent
INNER JOIN dbo.users AS parent_user
    ON parent_user.id = parent.user_id
INNER JOIN dbo.student_parents AS student_parent
    ON student_parent.parent_id = parent.id
INNER JOIN dbo.students AS student
    ON student.id = student_parent.student_id
INNER JOIN dbo.users AS student_user
    ON student_user.id = student.user_id
WHERE parent_user.id = :parent_user_id
ORDER BY
    student_parent.is_primary DESC,
    student.first_name,
    student.last_name,
    student.id
SQL,
            ['parent_user_id' => $parentUserId]
        );
    }

    /** @return array<string, mixed>|null */
    public function findParentChildLink(int $parentUserId, string $yuvaId): ?array
    {
        return ($this->fetchOne)(
            <<<'SQL'
SELECT TOP (1)
    parent_user.id AS parent_user_id,
    parent_user.role AS parent_user_role,
    parent_user.status AS parent_user_status,
    parent_user.email_verified_at AS parent_email_verified_at,
    parent_user.password_hash AS parent_password_hash,
    student_parent.parent_id,
    student_parent.student_id,
    student_parent.consent_status,
    student.yuva_id,
    student.approval_status AS student_approval_status,
    student.approved_at,
    student_user.role AS student_user_role,
    student_user.status AS student_user_status
FROM dbo.parents AS parent
INNER JOIN dbo.users AS parent_user
    ON parent_user.id = parent.user_id
INNER JOIN dbo.student_parents AS student_parent
    ON student_parent.parent_id = parent.id
INNER JOIN dbo.students AS student
    ON student.id = student_parent.student_id
INNER JOIN dbo.users AS student_user
    ON student_user.id = student.user_id
WHERE parent_user.id = :parent_user_id
  AND student.yuva_id = :yuva_id
SQL,
            [
                'parent_user_id' => $parentUserId,
                'yuva_id' => $yuvaId,
            ]
        );
    }

}
