<?php
declare(strict_types=1);

require_once __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/repositories.php';

if (isset($_GET['health'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $response = [
        'ok' => false,
        'app' => 'Yuva Club',
        'database_configured' => database_settings_present(),
        'database_connected' => false,
        'database_driver' => db_driver(),
        'checked_at' => gmdate('c'),
    ];

    if (!database_settings_present()) {
        $response['message'] = 'Database App Settings are not configured.';
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    try {
        $stmt = db()->query('SELECT COUNT(*) AS program_count FROM programs');
        $row = $stmt->fetch();
        $response['ok'] = true;
        $response['database_connected'] = true;
        $response['program_count'] = (int) ($row['program_count'] ?? 0);
        $response['message'] = 'Database connection is healthy.';
    } catch (Throwable $error) {
        http_response_code(500);
        $response['message'] = 'Database connection failed.';
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

$notificationEmail = 'support@yuvaclub.app';
$studentIdYear = '2026';

function clean_email(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
}

function checked_value(string $name): string {
    return isset($_POST[$name]) ? 'Yes' : 'No';
}

function checked_bool(string $name): bool {
    return isset($_POST[$name]);
}

function next_yuva_id_from_paths(array $csvPaths, string $year): string {
    $next = 1;

    foreach ($csvPaths as $csvPath) {
        if (!file_exists($csvPath) || ($handle = fopen($csvPath, 'rb')) === false) {
            continue;
        }

        $header = fgetcsv($handle);
        $idIndex = is_array($header) ? array_search('Yuva Club ID', $header, true) : false;

        while (($row = fgetcsv($handle)) !== false) {
            if ($idIndex !== false && isset($row[$idIndex]) && preg_match('/^YC-?' . preg_quote($year, '/') . '-?(\d+)$/', $row[$idIndex], $matches)) {
                $next = max($next, ((int) $matches[1]) + 1);
            } else {
                $next++;
            }
        }

        fclose($handle);
    }

    return sprintf('YC%s%03d', $year, $next);
}

function append_registration_row(string $csvPath, array $headers, array $row, string $year, array $idScanPaths): string {
    $dir = dirname($csvPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $handle = fopen($csvPath, 'c+');
    if ($handle === false) {
        return '';
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return '';
    }

    clearstatcache(true, $csvPath);
    $isNewFile = filesize($csvPath) === 0;
    if ($isNewFile) {
        fputcsv($handle, $headers);
    }

    $studentId = clean_text((string) ($row[1] ?? ''));
    if ($studentId === '') {
        $studentId = next_yuva_id_from_paths($idScanPaths, $year);
    }
    $row[1] = $studentId;
    fseek($handle, 0, SEEK_END);
    fputcsv($handle, $row);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $studentId;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registration.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: registration.php?status=security-error');
    exit;
}

$studentFirstName = clean_text($_POST['student_first_name'] ?? '');
$studentLastName = clean_text($_POST['student_last_name'] ?? '');
$membershipType = clean_text($_POST['membership_type'] ?? 'individual');
$organizationCode = strtoupper(clean_text($_POST['organization_code'] ?? ''));
$preferredName = clean_text($_POST['preferred_name'] ?? '');
$dateOfBirth = clean_text($_POST['date_of_birth'] ?? '');
$age = clean_text($_POST['age'] ?? '');
$programGroup = clean_text($_POST['program_group'] ?? '');
$grade = clean_text($_POST['grade'] ?? '');
$school = clean_text($_POST['school'] ?? '');
$cityState = clean_text($_POST['city_state'] ?? '');

$parentName = clean_text($_POST['parent_name'] ?? '');
$relationship = clean_text($_POST['relationship'] ?? '');
$parentEmail = clean_email($_POST['parent_email'] ?? '');
$parentPhone = clean_text($_POST['parent_phone'] ?? '');

$studentEmail = clean_email($_POST['student_email'] ?? '');
$studentPhone = clean_text($_POST['student_phone'] ?? '');
$whatsappContact = clean_text($_POST['whatsapp_contact'] ?? '');
$accountPassword = (string) ($_POST['account_password'] ?? '');
$accountPasswordConfirm = (string) ($_POST['account_password_confirm'] ?? '');
$passwordError = password_policy_error($accountPassword);

if ($passwordError !== '' || !hash_equals($accountPassword, $accountPasswordConfirm)) {
    header('Location: registration.php?status=password-error');
    exit;
}

if (!in_array($membershipType, ['individual', 'organization'], true) || ($membershipType === 'organization' && $organizationCode === '')) {
    header('Location: registration.php?status=error');
    exit;
}

$interestValues = $_POST['interests'] ?? [];
$interests = [];
if (is_array($interestValues)) {
    foreach ($interestValues as $interest) {
        $cleanInterest = clean_text((string) $interest);
        if ($cleanInterest !== '') {
            $interests[] = $cleanInterest;
        }
    }
}
$interestOther = clean_text($_POST['interest_other'] ?? '');
if ($interestOther !== '') {
    $interests[] = 'Other: ' . $interestOther;
}
$interestsText = implode(', ', $interests);

$joinReason = clean_text($_POST['join_reason'] ?? '');
$presentationExperience = clean_text($_POST['presentation_experience'] ?? '');
$presentationTopics = clean_text($_POST['presentation_topics'] ?? '');
$suggestions = clean_text($_POST['suggestions'] ?? '');

if ($programGroup === '') {
    $ageNumber = (int) $age;
    if ($ageNumber >= 18 && $ageNumber <= 21) {
        $programGroup = 'College Yuva (Ages 18-21)';
    } elseif ($ageNumber >= 13 && $ageNumber <= 17) {
        $programGroup = 'School Yuva (Ages 13-17)';
    } else {
        $programGroup = '';
    }
}

$agreeCode = checked_value('agree_code');
$agreeRecording = checked_value('agree_recording');
$agreeParentPermission = checked_value('agree_parent_permission');

$schedule = [];
for ($i = 1; $i <= 3; $i++) {
    $day = clean_text($_POST["preferred_day_$i"] ?? '');
    $time = clean_text($_POST["preferred_time_$i"] ?? '');

    if ($day !== '' && $time !== '') {
        $schedule[] = "Availability $i: $day at $time";
    } elseif ($day !== '' || $time !== '') {
        header('Location: registration.php?status=error');
        exit;
    }
}
$scheduleText = implode(' | ', $schedule);

$requiredFields = [
    $studentFirstName,
    $studentLastName,
    $dateOfBirth,
    $programGroup,
    $grade,
    $school,
    $cityState,
    $parentName,
    $relationship,
    $parentEmail,
    $parentPhone,
    $joinReason,
    $presentationExperience,
    $presentationTopics,
];

if (
    in_array('', $requiredFields, true)
    || (int) $age < 13
    || (int) $age > 21
    || $agreeCode !== 'Yes'
    || $agreeRecording !== 'Yes'
    || $agreeParentPermission !== 'Yes'
) {
    header('Location: registration.php?status=error');
    exit;
}

$submittedAt = date('Y-m-d H:i:s');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'submissions';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$headers = [
    'Submitted At',
    'Yuva Club ID',
    'Membership Type',
    'Organization Code',
    'Student First Name',
    'Student Last Name',
    'Preferred Name',
    'Date of Birth',
    'Age',
    'Program Group',
    'Grade',
    'School',
    'City/State',
    'Parent/Guardian Name',
    'Relationship',
    'Parent Email',
    'Parent Phone Number',
    'Student Email',
    'Student Phone Number',
    'WhatsApp Username / Number',
    'Interests',
    'Why Join',
    'Presentation Experience',
    'Presentation Topics',
    'Availability Preferences',
    'Suggestions',
    'Code of Conduct Agreement',
    'Recording Agreement',
    'Parent Permission',
    'IP Address',
];

$row = [
    $submittedAt,
    '',
    $membershipType === 'organization' ? 'Join Organization' : 'Individual Membership',
    $organizationCode,
    $studentFirstName,
    $studentLastName,
    $preferredName,
    $dateOfBirth,
    $age,
    $programGroup,
    $grade,
    $school,
    $cityState,
    $parentName,
    $relationship,
    $parentEmail,
    $parentPhone,
    $studentEmail,
    $studentPhone,
    $whatsappContact,
    $interestsText,
    $joinReason,
    $presentationExperience,
    $presentationTopics,
    $scheduleText,
    $suggestions,
    $agreeCode,
    $agreeRecording,
    $agreeParentPermission,
    $ipAddress,
];

$csvPath = $dataDir . DIRECTORY_SEPARATOR . 'registrations-current.csv';
$fullCsvPath = $dataDir . DIRECTORY_SEPARATOR . 'registrations-full.csv';
$legacyCsvPath = $dataDir . DIRECTORY_SEPARATOR . 'registrations.csv';
$idScanPaths = [$csvPath, $fullCsvPath, $legacyCsvPath];
$studentId = '';
$registrationId = null;
$storedInDatabase = false;

if (database_settings_present()) {
    try {
        $registrationId = create_registration([
            'student_first_name' => $studentFirstName,
            'student_last_name' => $studentLastName,
            'preferred_name' => $preferredName,
            'date_of_birth' => $dateOfBirth,
            'age' => $age,
            'grade' => $grade,
            'school' => $school,
            'city_state' => $cityState,
            'parent_name' => $parentName,
            'relationship' => $relationship,
            'parent_email' => $parentEmail,
            'parent_phone' => $parentPhone,
            'student_email' => $studentEmail,
            'student_phone' => $studentPhone,
            'whatsapp_contact' => $whatsappContact,
            'interests' => $interestsText,
            'why_join' => $joinReason,
            'presentation_experience' => $presentationExperience,
            'presentation_topics' => $presentationTopics,
            'preferred_schedule' => $scheduleText,
            'suggestions' => $suggestions,
            'code_of_conduct_agreed' => checked_bool('agree_code'),
            'recording_agreed' => checked_bool('agree_recording'),
            'parent_permission_granted' => checked_bool('agree_parent_permission'),
            'ip_address' => $ipAddress,
        ]);
        $storedInDatabase = true;
    } catch (Throwable $error) {
        error_log('Yuva Club database registration failed: ' . $error->getMessage());
        header('Location: registration.php?status=error');
        exit;
    }
} else {
    $studentId = append_registration_row($csvPath, $headers, $row, $studentIdYear, $idScanPaths);
}

if (!$storedInDatabase && $studentId === '') {
    header('Location: registration.php?status=error');
    exit;
}

if (!$storedInDatabase) {
    $row[1] = $studentId;
    append_registration_row($fullCsvPath, $headers, $row, $studentIdYear, $idScanPaths);
    create_student_account($studentId, $studentEmail, $parentEmail, $accountPassword);
}

if ($notificationEmail !== '') {
    $registrationReference = $storedInDatabase ? ('Registration #' . (string) $registrationId) : $studentId;
    $subject = "New Yuva Club Registration: $registrationReference";
    $message = "New Yuva Club registration:\n\n"
        . ($storedInDatabase ? "Registration ID: $registrationId\n" : "Yuva Club ID: $studentId\n")
        . "Submitted At: $submittedAt\n\n"
        . "Membership Type: " . ($membershipType === 'organization' ? 'Join Organization' : 'Individual Membership') . "\n"
        . "Organization Code: $organizationCode\n\n"
        . "Student: $studentFirstName $studentLastName\n"
        . "Preferred Name: $preferredName\n"
        . "Date of Birth: $dateOfBirth\n"
        . "Age: $age\n"
        . "Program Group: $programGroup\n"
        . "Grade: $grade\n"
        . "School: $school\n"
        . "City/State: $cityState\n\n"
        . "Parent/Guardian: $parentName\n"
        . "Relationship: $relationship\n"
        . "Parent Email: $parentEmail\n"
        . "Parent Phone: $parentPhone\n\n"
        . "Student Email: $studentEmail\n"
        . "Student Phone: $studentPhone\n"
        . "WhatsApp Username / Number: $whatsappContact\n\n"
        . "Interests: $interestsText\n"
        . "Why Join: $joinReason\n"
        . "Presentation Experience: $presentationExperience\n"
        . "Presentation Topics: $presentationTopics\n"
        . "Availability Preferences: $scheduleText\n"
        . "Suggestions: $suggestions\n\n"
        . "Code of Conduct Agreement: $agreeCode\n"
        . "Recording Agreement: $agreeRecording\n"
        . "Parent Permission: $agreeParentPermission\n";
    $headersText = "From: noreply@yuvaclub.app\r\n"
        . "Reply-To: $parentEmail\r\n";
    @mail($notificationEmail, $subject, $message, $headersText);
}

$query = $storedInDatabase
    ? 'status=success&registration=' . urlencode((string) $registrationId)
    : 'status=success&id=' . urlencode($studentId);
header('Location: registration.php?' . $query);
exit;
