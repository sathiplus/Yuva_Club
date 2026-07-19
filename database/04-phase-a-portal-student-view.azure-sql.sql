-- Phase A read-only compatibility projection for the Student V1 portal.
-- One row is returned per student. Stored YUVA IDs are not transformed.

SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

CREATE OR ALTER VIEW dbo.vw_portal_students
AS
SELECT
    student.id AS student_id,
    student.user_id AS student_user_id,
    student.yuva_id AS yuva_id,
    student.first_name AS student_first_name,
    student.last_name AS student_last_name,
    COALESCE(student.preferred_name, registration.preferred_name) AS preferred_name,
    COALESCE(
        NULLIF(student.preferred_name, N''),
        NULLIF(registration.preferred_name, N''),
        NULLIF(student_user.display_name, N''),
        student.first_name
    ) AS display_name,
    COALESCE(student.date_of_birth, registration.date_of_birth) AS date_of_birth,
    student.approval_status AS student_approval_status,
    program.id AS program_id,
    program.code AS program_code,
    program.name AS program_name,
    COALESCE(student.grade, registration.grade) AS grade,
    COALESCE(student.school, registration.school) AS school,
    COALESCE(student.city_state, registration.city_state) AS city_state,
    COALESCE(registration.student_email, student_user.email) AS student_email,
    COALESCE(student.phone, registration.student_phone) AS student_phone,
    COALESCE(student.whatsapp_contact, registration.whatsapp_contact) AS whatsapp_contact,
    primary_parent.parent_id AS parent_id,
    primary_parent.parent_user_id AS parent_user_id,
    COALESCE(primary_parent.parent_name, registration.parent_name) AS parent_name,
    COALESCE(primary_parent.relationship, registration.relationship) AS parent_relationship,
    COALESCE(primary_parent.parent_email, registration.parent_email) AS parent_email,
    COALESCE(primary_parent.parent_phone, registration.parent_phone) AS parent_phone,
    primary_parent.is_primary AS parent_is_primary,
    primary_parent.consent_status AS parent_consent_status,
    registration.registration_id AS registration_id,
    registration.registration_status AS registration_status,
    registration.submitted_at AS registration_submitted_at,
    registration.interests AS interests,
    registration.why_join AS why_join,
    registration.presentation_experience AS presentation_experience,
    registration.presentation_topics AS presentation_topics,
    registration.preferred_schedule AS preferred_schedule,
    registration.suggestions AS suggestions,
    registration.code_of_conduct_agreed AS code_of_conduct_agreed,
    registration.recording_agreed AS recording_agreed,
    registration.parent_permission_granted AS parent_permission_granted
FROM dbo.students AS student
INNER JOIN dbo.programs AS program
    ON program.id = student.program_id
LEFT JOIN dbo.users AS student_user
    ON student_user.id = student.user_id
OUTER APPLY (
    SELECT TOP (1)
        registration_row.id AS registration_id,
        registration_row.status AS registration_status,
        registration_row.submitted_at,
        registration_row.preferred_name,
        registration_row.date_of_birth,
        registration_row.grade,
        registration_row.school,
        registration_row.city_state,
        registration_row.student_email,
        registration_row.student_phone,
        registration_row.whatsapp_contact,
        registration_row.parent_name,
        registration_row.relationship,
        registration_row.parent_email,
        registration_row.parent_phone,
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
        NULLIF(
            LTRIM(RTRIM(CONCAT(parent.first_name, N' ', parent.last_name))),
            N''
        ) AS parent_name,
        parent.relationship,
        parent_user.email AS parent_email,
        parent.phone AS parent_phone,
        student_parent.is_primary,
        student_parent.consent_status
    FROM dbo.student_parents AS student_parent
    INNER JOIN dbo.parents AS parent
        ON parent.id = student_parent.parent_id
    LEFT JOIN dbo.users AS parent_user
        ON parent_user.id = parent.user_id
    WHERE student_parent.student_id = student.id
    ORDER BY
        student_parent.is_primary DESC,
        student_parent.created_at,
        student_parent.parent_id
) AS primary_parent;
GO
