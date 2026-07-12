<?php
declare(strict_types=1);

require __DIR__ . '/../portal-lib.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$registrationRows = registration_rows()['rows'];
$parentAccounts = parent_accounts();
$parentLinks = parent_student_links();
$students = portal_students();
$registrationParentEmails = [];
$studentsRequiringGuardian = 0;
$studentsWithGuardian = [];

foreach ($registrationRows as $row) {
    $studentId = normalize_yuva_id((string) ($row['Yuva Club ID'] ?? ''));
    $parentEmail = normalize_email((string) ($row['Parent Email'] ?? ''));
    $age = (int) ($row['Age'] ?? 0);
    if ($parentEmail !== '') {
        $registrationParentEmails[$parentEmail] ??= 0;
        $registrationParentEmails[$parentEmail]++;
    }
    if ($studentId !== '' && $age > 0 && $age < 18) {
        $studentsRequiringGuardian++;
        if ($parentEmail !== '') {
            $studentsWithGuardian[$studentId] = true;
        }
    }
}

$linkCount = 0;
$orphanedLinks = 0;
$linkedStudents = [];
foreach ($parentLinks as $email => $links) {
    if (!is_array($links)) {
        continue;
    }
    foreach ($links as $studentId => $link) {
        $linkCount++;
        $normalizedStudentId = normalize_yuva_id((string) $studentId);
        if (!isset($students[$normalizedStudentId])) {
            $orphanedLinks++;
            continue;
        }
        if (($link['status'] ?? '') === 'active') {
            $linkedStudents[$normalizedStudentId] = true;
        }
    }
}

$missingLinkHashes = [];
foreach (array_keys($registrationParentEmails) as $email) {
    if (parent_linked_students($email) === []) {
        $missingLinkHashes[] = hash('sha256', $email);
    }
}

$duplicateEmailHashes = [];
foreach ($registrationParentEmails as $email => $count) {
    if ($count > 1) {
        $duplicateEmailHashes[] = [
            'email_hash' => hash('sha256', $email),
            'registration_count' => $count,
        ];
    }
}

$report = [
    'generated_at' => gmdate('c'),
    'registration_rows' => count($registrationRows),
    'distinct_parent_emails_in_registrations' => count($registrationParentEmails),
    'parent_user_accounts_file_backed' => count($parentAccounts),
    'parent_student_links_file_backed' => $linkCount,
    'orphaned_parent_student_links' => $orphanedLinks,
    'students_requiring_guardian' => $studentsRequiringGuardian,
    'students_requiring_guardian_with_parent_email' => count($studentsWithGuardian),
    'students_with_active_parent_link' => count($linkedStudents),
    'parent_emails_missing_active_links_count' => count($missingLinkHashes),
    'parent_emails_missing_active_links_hashes' => $missingLinkHashes,
    'duplicate_parent_email_count' => count($duplicateEmailHashes),
    'duplicate_parent_email_hashes' => $duplicateEmailHashes,
];

echo json_encode($report, JSON_PRETTY_PRINT) . PHP_EOL;
