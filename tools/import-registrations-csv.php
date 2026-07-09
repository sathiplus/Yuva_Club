<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/repositories.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!database_settings_present()) {
    fwrite(STDERR, "Database settings are not configured.\n");
    exit(1);
}

$path = $argv[1] ?? (__DIR__ . '/../submissions/registrations-current.csv');
if (!is_file($path)) {
    fwrite(STDERR, "CSV file not found: {$path}\n");
    exit(1);
}

$handle = fopen($path, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Could not open CSV file: {$path}\n");
    exit(1);
}

$headers = fgetcsv($handle);
if (!is_array($headers)) {
    fwrite(STDERR, "CSV file does not have headers.\n");
    exit(1);
}

$imported = 0;
$skipped = 0;

while (($row = fgetcsv($handle)) !== false) {
    $record = [];
    foreach ($headers as $index => $header) {
        $record[$header] = $row[$index] ?? '';
    }

    $parentEmail = strtolower(trim((string) ($record['Parent Email'] ?? '')));
    $submittedAt = trim((string) ($record['Submitted At'] ?? ''));
    $firstName = trim((string) ($record['Student First Name'] ?? ''));
    $lastName = trim((string) ($record['Student Last Name'] ?? ''));

    $exists = db()->prepare(
        'SELECT id FROM registrations
         WHERE parent_email = :parent_email
           AND student_first_name = :first_name
           AND student_last_name = :last_name
           AND submitted_at = :submitted_at
         LIMIT 1'
    );
    $exists->execute([
        'parent_email' => $parentEmail,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'submitted_at' => $submittedAt,
    ]);

    if ($exists->fetchColumn() !== false) {
        $skipped++;
        continue;
    }

    create_registration([
        'submitted_at' => $submittedAt,
        'student_first_name' => $firstName,
        'student_last_name' => $lastName,
        'preferred_name' => $record['Preferred Name'] ?? '',
        'date_of_birth' => $record['Date of Birth'] ?? '',
        'age' => $record['Age'] ?? '',
        'grade' => $record['Grade'] ?? '',
        'school' => $record['School'] ?? '',
        'city_state' => $record['City/State'] ?? '',
        'parent_name' => $record['Parent/Guardian Name'] ?? '',
        'relationship' => $record['Relationship'] ?? '',
        'parent_email' => $parentEmail,
        'parent_phone' => $record['Parent Phone Number'] ?? '',
        'student_email' => $record['Student Email'] ?? '',
        'student_phone' => $record['Student Phone Number'] ?? '',
        'whatsapp_contact' => $record['WhatsApp Username / Number'] ?? '',
        'interests' => $record['Interests'] ?? '',
        'why_join' => $record['Why Join'] ?? '',
        'presentation_experience' => $record['Presentation Experience'] ?? '',
        'presentation_topics' => $record['Presentation Topics'] ?? '',
        'preferred_schedule' => $record['Preferred Schedule'] ?? '',
        'suggestions' => $record['Suggestions'] ?? '',
        'code_of_conduct_agreed' => ($record['Code of Conduct Agreement'] ?? '') === 'Yes',
        'recording_agreed' => ($record['Recording Agreement'] ?? '') === 'Yes',
        'parent_permission_granted' => ($record['Parent Permission'] ?? '') === 'Yes',
        'ip_address' => $record['IP Address'] ?? '',
    ]);
    $imported++;
}

fclose($handle);

echo "Imported {$imported} registrations. Skipped {$skipped} duplicates.\n";
