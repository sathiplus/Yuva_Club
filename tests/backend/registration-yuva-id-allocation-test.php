<?php
declare(strict_types=1);

function allocation_check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$repositories = file_get_contents(__DIR__ . '/../../backend/repositories.php');
$submit = file_get_contents(__DIR__ . '/../../submit-registration.php');
$portal = file_get_contents(__DIR__ . '/../../portal-lib.php');

allocation_check(is_string($repositories), 'Registration repository is unavailable.');
allocation_check(is_string($submit), 'Registration handler is unavailable.');
allocation_check(is_string($portal), 'Portal library is unavailable.');

allocation_check(
    str_contains($repositories, 'function reserve_registration_yuva_id(')
    && str_contains($repositories, 'UPDATE dbo.yuva_id_counters WITH (UPDLOCK, HOLDLOCK)')
    && str_contains($repositories, 'FROM dbo.students WITH (UPDLOCK, HOLDLOCK)')
    && str_contains($repositories, 'FROM dbo.registrations WITH (UPDLOCK, HOLDLOCK)'),
    'SQL reservation must serialize the counter and check canonical ownership.'
);
allocation_check(
    str_contains($repositories, 'SET reserved_yuva_id = :yuva_id')
    && str_contains($repositories, "'reserved_yuva_id' => \$reservedYuvaId"),
    'The registration transaction must persist and return its reserved YUVA ID.'
);
allocation_check(
    str_contains($submit, 'create_registration_with_reserved_yuva_id($registrationInput)')
    && str_contains($submit, "\$studentId = \$registration['reserved_yuva_id']")
    && str_contains($submit, '$row[1] = $studentId;'),
    'SQL registration and filesystem mirrors must use the same reservation.'
);
allocation_check(
    str_contains($submit, 'if (db_is_sqlsrv())')
    && str_contains($submit, 'create_registration($registrationInput)')
    && str_contains($submit, 'next_yuva_id_from_paths($idScanPaths, $year)'),
    'CSV allocation must remain confined to explicit non-SQL fallback mode.'
);
allocation_check(
    str_contains($portal, 'YUVA ID is already assigned to another student account.')
    && str_contains($portal, '$existingEmail !== $requestedEmail'),
    'Filesystem account creation must reject cross-student YUVA ID overwrite.'
);
allocation_check(
    str_contains($repositories, "\$reservedYuvaId = trim((string) (\$registration['reserved_yuva_id'] ?? ''))")
    && str_contains($repositories, 'backend_validate_reserved_yuva_id($reservedYuvaId)')
    && str_contains($repositories, '$yuvaId = next_yuva_id($pdo, gmdate'),
    'Approval must reuse a reservation and allocate only when no reservation exists.'
);

fwrite(STDOUT, "Registration YUVA ID allocation contracts PASS\n");
