<?php
declare(strict_types=1);

$notificationEmail = 'yuvaclub@karmabro.com';
$studentIdYear = '2026';

function clean_text(string $value): string {
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    return preg_replace('/\s+/', ' ', $value) ?? '';
}

function clean_email(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
}

function checked_value(string $name): string {
    return isset($_POST[$name]) ? 'Yes' : 'No';
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

$studentFirstName = clean_text($_POST['student_first_name'] ?? '');
$studentLastName = clean_text($_POST['student_last_name'] ?? '');
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
        $programGroup = 'College Yuva Leader (Ages 18-21)';
    } elseif ($ageNumber >= 13 && $ageNumber <= 17) {
        $programGroup = 'School Yuva Leader (Ages 13-17)';
    } elseif ($ageNumber >= 8 && $ageNumber <= 12) {
        $programGroup = 'Junior Yuva Learner (Ages 8-12)';
    } else {
        $programGroup = 'Outside standard age groups';
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
        $schedule[] = "Choice $i: $day at $time";
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
    || count($schedule) === 0
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
    'Preferred Schedule',
    'Suggestions',
    'Code of Conduct Agreement',
    'Recording Agreement',
    'Parent Permission',
    'IP Address',
];

$row = [
    $submittedAt,
    '',
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
$studentId = append_registration_row($csvPath, $headers, $row, $studentIdYear, $idScanPaths);

if ($studentId === '') {
    header('Location: registration.php?status=error');
    exit;
}

$row[1] = $studentId;
append_registration_row($fullCsvPath, $headers, $row, $studentIdYear, $idScanPaths);

if ($notificationEmail !== '') {
    $subject = "New Yuva Club Registration: $studentId";
    $message = "New Yuva Club registration:\n\n"
        . "Yuva Club ID: $studentId\n"
        . "Submitted At: $submittedAt\n\n"
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
        . "Preferred Schedule: $scheduleText\n"
        . "Suggestions: $suggestions\n\n"
        . "Code of Conduct Agreement: $agreeCode\n"
        . "Recording Agreement: $agreeRecording\n"
        . "Parent Permission: $agreeParentPermission\n";
    $headersText = "From: no-reply@yuvaclub.karmabro.com\r\n"
        . "Reply-To: $parentEmail\r\n";
    @mail($notificationEmail, $subject, $message, $headersText);
}

header('Location: registration.php?status=success&id=' . urlencode($studentId));
exit;
