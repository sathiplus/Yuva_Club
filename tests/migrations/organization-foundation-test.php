<?php
declare(strict_types=1);

function organization_test_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$migrationPath = __DIR__
    . '/../../database/05-organization-admin-foundation.azure-sql.sql';
$documentationPath = __DIR__
    . '/../../docs/ORGANIZATION_ADMIN_FOUNDATION_V1.md';
$sql = file_get_contents($migrationPath);
$documentation = file_get_contents($documentationPath);

organization_test_assert($sql !== false, 'Organization foundation migration is missing.');
organization_test_assert(
    $documentation !== false,
    'Organization foundation documentation is missing.'
);
$sql = str_replace(["\r\n", "\r"], "\n", $sql);

foreach (
    [
        "OBJECT_ID(N'dbo.organizations', N'U') IS NULL",
        "OBJECT_ID(N'dbo.organization_memberships', N'U') IS NULL",
        "OBJECT_ID(N'dbo.organization_students', N'U') IS NULL",
        "OBJECT_ID(N'dbo.organization_settings', N'U') IS NULL",
        "OBJECT_ID(N'dbo.organization_announcements', N'U') IS NULL",
        "COL_LENGTH(N'dbo.registrations', N'organization_id') IS NULL",
        "COL_LENGTH(N'dbo.sessions', N'organization_id') IS NULL",
        "COL_LENGTH(N'dbo.activity_logs', N'organization_id') IS NULL",
    ] as $guard
) {
    organization_test_assert(
        str_contains($sql, $guard),
        'Organization migration is missing idempotent guard: ' . $guard
    );
}

foreach (
    [
        'organization_admin',
        'ck_users_role',
        'pk_organizations',
        'uq_organizations_code',
        'uq_organizations_slug',
        'uq_organization_memberships_org_user',
        'ck_organization_memberships_role',
        'ck_organization_memberships_status',
        'ck_organization_students_status',
        'fk_registrations_organization',
        'fk_sessions_organization',
        'fk_activity_logs_organization',
    ] as $requiredSchema
) {
    organization_test_assert(
        str_contains($sql, $requiredSchema),
        'Organization migration is missing schema contract: ' . $requiredSchema
    );
}

$expectedIndexes = [
    'idx_organization_memberships_user_status',
    'uq_organization_students_active_student',
    'idx_organization_students_org_status',
    'idx_organization_announcements_org_status',
    'idx_registrations_org_status',
    'idx_sessions_org_starts',
    'idx_activity_logs_org_created',
];
foreach ($expectedIndexes as $indexName) {
    organization_test_assert(
        substr_count($sql, "[name] = N'{$indexName}'") === 1,
        'Organization index must have one idempotent guard: ' . $indexName
    );
    organization_test_assert(
        substr_count(
            $sql,
            'CREATE '
                . ($indexName === 'uq_organization_students_active_student'
                    ? 'UNIQUE '
                    : '')
                . "INDEX {$indexName}"
        ) === 1,
        'Organization index must have one definition: ' . $indexName
    );
}

organization_test_assert(
    str_contains(
        $sql,
        "CREATE UNIQUE INDEX uq_organization_students_active_student\n"
            . '        ON dbo.organization_students (student_id)'
    ),
    'Active student assignment uniqueness is missing.'
);
organization_test_assert(
    str_contains($sql, "WHERE [status] = N'active'"),
    'Active student assignment uniqueness must be filtered.'
);

foreach (
    [
        '/\bLIMIT\b/i',
        '/\bUTC_TIMESTAMP\s*\(/i',
        '/\bAUTO_INCREMENT\b/i',
        '/`[A-Za-z_][A-Za-z0-9_]*`/',
        '/\bSELECT\b[\s\S]*\bFOR\s+UPDATE\b/i',
        '/\b(?:DROP|TRUNCATE)\s+(?:TABLE|VIEW)\b/i',
    ] as $forbiddenSqlPattern
) {
    organization_test_assert(
        preg_match($forbiddenSqlPattern, $sql) !== 1,
        'Organization migration contains forbidden SQL: '
            . $forbiddenSqlPattern
    );
}

foreach (
    [
        'Existing students, registrations, sessions, and activity logs remain unassigned',
        'Organization',
        'Student',
        'Tenant',
        'Program',
        'Coach / Mentor',
        'roadmap documentation only',
        'organization predicate',
        'Master Admin and Organization Admin sessions remain distinct',
    ] as $requiredDocumentation
) {
    organization_test_assert(
        str_contains($documentation, $requiredDocumentation),
        'Organization documentation is missing: ' . $requiredDocumentation
    );
}

organization_test_assert(
    !preg_match('/\bINSERT\s+INTO\s+dbo\.organizations\b/i', $sql),
    'Organization migration must not seed an organization.'
);
organization_test_assert(
    !preg_match('/\bINSERT\s+INTO\s+dbo\.organization_memberships\b/i', $sql),
    'Organization migration must not seed an administrator membership.'
);

fwrite(STDOUT, "PASS organization migration idempotent guards\n");
fwrite(STDOUT, "PASS organization role and relationship constraints\n");
fwrite(STDOUT, "PASS organization isolation indexes\n");
fwrite(STDOUT, "PASS organization Azure SQL compatibility\n");
fwrite(STDOUT, "PASS organization non-destructive SQL policy\n");
fwrite(STDOUT, "PASS organization migration has no seeded tenants\n");
fwrite(STDOUT, "PASS future tenant-program and coach roadmap\n");
