<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/config.php';

$configuredAppUrl = app_url();
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || parse_url($configuredAppUrl, PHP_URL_SCHEME) === 'https';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();

const YUVA_ADMIN_SALT = 'yuva-club-admin-v1';
const YUVA_ADMIN_EMAIL = 'admin@karmabro.com';
const YUVA_ADMIN_PASSWORD_HASH = 'e5cdf3325de344337779c499e1d8b07b59bdba6b72fbbe7fa97bc0e00d978288';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function portal_path(string $name): string {
    return __DIR__ . DIRECTORY_SEPARATOR . $name;
}

function ensure_portal_dirs(): void {
    foreach (['portal-data', 'portal-uploads'] as $dir) {
        $path = portal_path($dir);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $htaccess = $path . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
}

function redirect_to(string $url): never {
    header('Location: ' . $url);
    exit;
}

function clean_text(string $value): string {
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    return preg_replace('/\s+/', ' ', $value) ?? '';
}

function normalize_yuva_id(string $value): string {
    $value = strtoupper(clean_text($value));
    if (preg_match('/^YC-?(\d{4})-?(\d+)$/', $value, $matches) === 1) {
        return sprintf('YC%s%03d', $matches[1], (int) $matches[2]);
    }
    return str_replace('-', '', $value);
}

function read_json_file(string $file, array $default = []): array {
    ensure_portal_dirs();
    if (!file_exists($file)) {
        return $default;
    }
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function write_json_file(string $file, array $data): void {
    ensure_portal_dirs();
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function portal_records_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'student-records.json';
}

function topic_selections_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'topic-selections.json';
}

function research_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'research-submissions.json';
}

function ai_reviews_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'ai-reviews.json';
}

function hub_settings_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'hub-settings.json';
}

function safety_reports_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'safety-reports.json';
}

function admin_credentials_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'admin-credentials.json';
}

function admin_credentials(): array {
    $stagingCredentials = staging_test_admin_credentials();
    if ($stagingCredentials !== null) {
        return $stagingCredentials;
    }

    return array_merge([
        'email' => YUVA_ADMIN_EMAIL,
        'password_hash' => YUVA_ADMIN_PASSWORD_HASH,
    ], read_json_file(admin_credentials_file(), []));
}

function password_hash_for_admin(string $password): string {
    return hash('sha256', YUVA_ADMIN_SALT . $password);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function staging_test_admin_credentials(): ?array {
    $fixture = staging_test_fixture_config();
    if ($fixture === null) {
        return null;
    }

    return [
        'email' => $fixture['admin_email'],
        'password_hash' => $fixture['admin_password_hash'],
    ];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool {
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function default_hub_settings(): array {
    $zoom = app_config()['zoom'];
    return [
        'junior_session_date' => '',
        'junior_session_start' => '',
        'junior_session_end' => '',
        'junior_session_status' => 'Closed',
        'junior_zoom_url' => $zoom['default_url'],
        'junior_zoom_meeting_id' => $zoom['default_meeting_id'],
        'junior_zoom_password' => $zoom['default_password'],
        'junior_scheduler_embed' => $zoom['scheduler_url'],
        'junior_session_title' => 'School Yuva Session',
        'senior_session_date' => '',
        'senior_session_start' => '',
        'senior_session_end' => '',
        'senior_session_status' => 'Closed',
        'senior_zoom_url' => $zoom['default_url'],
        'senior_zoom_meeting_id' => $zoom['default_meeting_id'],
        'senior_zoom_password' => $zoom['default_password'],
        'senior_scheduler_embed' => $zoom['scheduler_url'],
        'senior_session_title' => 'College Yuva Session',
        'announcements' => '',
        'recordings' => '',
        'resources' => 'Stories Library|stories.html' . "\n" . 'Topics Library|curriculum.html' . "\n" . 'Reading Resources|resources.html',
    ];
}

function student_program_group(array $student): string {
    $group = $student['Program Group'] ?? '';
    if ($group !== '') {
        if (str_contains($group, 'School')) {
            return 'junior';
        }
        if (str_contains($group, 'College')) {
            return 'senior';
        }
    }
    $age = (int) ($student['Age'] ?? 0);
    return ($age >= 18 && $age <= 21) ? 'senior' : 'junior';
}

function membership_group_label(array $student): string {
    $group = trim((string) ($student['Program Group'] ?? ''));
    $age = (int) ($student['Age'] ?? 0);
    if ($age >= 18 && $age <= 21) {
        return 'College Yuva (Ages 18-21)';
    }
    if ($age >= 13 && $age <= 17) {
        return 'School Yuva (Ages 13-17)';
    }
    if (str_contains($group, 'College')) {
        return 'College Yuva (Ages 18-21)';
    }
    if (str_contains($group, 'School')) {
        return 'School Yuva (Ages 13-17)';
    }
    return 'Yuva Club Member';
}

function rank_definitions(): array {
    return [
        'Explorer' => [
            'certificate' => 'Yuva Explorer Certificate',
            'meaning' => 'Learn and participate',
            'requirements' => 'Complete onboarding, attend sessions, join discussions, and select topics.',
        ],
        'Speaker' => [
            'certificate' => 'Yuva Speaker Certificate',
            'meaning' => 'Research and present',
            'requirements' => 'Research topics, upload notes or slides, present, and answer questions.',
        ],
        'Leader' => [
            'certificate' => 'Yuva Leader Certificate',
            'meaning' => 'Lead and organize',
            'requirements' => 'Lead discussions, support sessions, participate consistently, and receive approval.',
        ],
        'Mentor' => [
            'certificate' => 'Yuva Mentor Certificate',
            'meaning' => 'Coach and represent',
            'requirements' => 'Coach newer members, provide constructive feedback, support events, and serve the community.',
        ],
    ];
}

function challenge_stages(): array {
    return [
        'Practice Session',
        'Monthly Club Challenge',
        'Regional Challenge',
        'State Challenge',
        'National Challenge',
        'International Yuva Championship',
    ];
}

function rubric_categories(): array {
    return [
        'confidence' => 'Confidence',
        'voice_clarity' => 'Voice Clarity',
        'research_quality' => 'Research Quality',
        'organization' => 'Organization',
        'creativity' => 'Creativity',
        'visual_presentation' => 'Visual Presentation',
        'audience_engagement' => 'Audience Engagement',
        'question_handling' => 'Question Handling',
        'leadership' => 'Leadership',
        'time_management' => 'Time Management',
    ];
}

function challenge_stage(array $record): string {
    $stage = (string) ($record['challenge_stage'] ?? '');
    return in_array($stage, challenge_stages(), true) ? $stage : 'Practice Session';
}

function next_rank_name(string $rank): string {
    $ranks = array_keys(rank_definitions());
    $index = rank_order($rank);
    return $ranks[min($index + 1, count($ranks) - 1)] ?? $rank;
}

function rubric_score(array $record): int {
    $total = 0;
    foreach (array_keys(rubric_categories()) as $key) {
        $value = $record['rubric_' . $key] ?? '';
        if ($value !== '' && is_numeric($value)) {
            $total += max(0, min(10, (int) $value));
        }
    }
    return $total;
}

function rubric_completed_count(array $record): int {
    $count = 0;
    foreach (array_keys(rubric_categories()) as $key) {
        if (($record['rubric_' . $key] ?? '') !== '') {
            $count++;
        }
    }
    return $count;
}

function challenge_badges(array $record): array {
    $badges = [];
    $stage = challenge_stage($record);
    if ($stage !== 'Practice Session') {
        $badges[] = $stage;
    }
    if (rubric_score($record) >= 80) {
        $badges[] = 'Strong Presentation Score';
    }
    if (($record['finalist_status'] ?? '') === 'Finalist') {
        $badges[] = 'Challenge Finalist';
    }
    if (($record['finalist_status'] ?? '') === 'Champion') {
        $badges[] = 'Challenge Champion';
    }
    return $badges;
}

function rank_order(string $rank): int {
    return array_search($rank, array_keys(rank_definitions()), true) ?: 0;
}

function approved_rank(array $record): string {
    $rank = (string) ($record['current_rank'] ?? '');
    return array_key_exists($rank, rank_definitions()) ? $rank : 'Explorer';
}

function rank_eligibility(array $record): string {
    $attendance = (int) ($record['attendance'] ?? 0);
    $presentations = (int) ($record['presentations'] ?? 0);
    $hours = (float) ($record['service_hours'] ?? 0);

    if ($presentations >= 5 && $hours >= 10 && $attendance >= 8) {
        return 'Mentor';
    }
    if ($presentations >= 3 && $hours >= 5 && $attendance >= 5) {
        return 'Leader';
    }
    if ($presentations >= 1) {
        return 'Speaker';
    }
    return 'Explorer';
}

function group_session(array $hub, string $group): array {
    $prefix = $group === 'junior' ? 'junior' : 'senior';
    $defaults = default_hub_settings();
    $zoomUrl = trim((string) ($hub[$prefix . '_zoom_url'] ?? ''));
    $zoomMeetingId = trim((string) ($hub[$prefix . '_zoom_meeting_id'] ?? ''));
    $zoomPassword = trim((string) ($hub[$prefix . '_zoom_password'] ?? ''));
    $schedulerEmbed = trim((string) ($hub[$prefix . '_scheduler_embed'] ?? ''));

    return [
        'group_label' => $prefix === 'junior' ? 'School Yuva (Ages 13-17)' : 'College Yuva (Ages 18-21)',
        'title' => $hub[$prefix . '_session_title'] ?? '',
        'date' => $hub[$prefix . '_session_date'] ?? '',
        'start' => $hub[$prefix . '_session_start'] ?? '',
        'end' => $hub[$prefix . '_session_end'] ?? '',
        'status' => $hub[$prefix . '_session_status'] ?? 'Closed',
        'zoom_url' => $zoomUrl !== '' ? $zoomUrl : ($defaults[$prefix . '_zoom_url'] ?? ''),
        'zoom_meeting_id' => $zoomMeetingId !== '' ? $zoomMeetingId : ($defaults[$prefix . '_zoom_meeting_id'] ?? ''),
        'zoom_password' => $zoomPassword !== '' ? $zoomPassword : ($defaults[$prefix . '_zoom_password'] ?? ''),
        'scheduler_embed' => $schedulerEmbed !== '' ? $schedulerEmbed : ($defaults[$prefix . '_scheduler_embed'] ?? ''),
    ];
}

function scheduler_embed_src(string $embedCode): string {
    $embedCode = trim($embedCode);
    if ($embedCode === '') {
        return '';
    }

    if (preg_match('/src=["\']([^"\']+)["\']/i', $embedCode, $matches) === 1) {
        $src = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $src = $embedCode;
    }

    if (!str_starts_with($src, 'https://scheduler.zoom.us/')) {
        return '';
    }

    if (!str_contains($src, 'embed=true')) {
        $src .= str_contains($src, '?') ? '&embed=true' : '?embed=true';
    }

    return $src;
}

function scheduler_page_url(string $schedulerSrc): string {
    $schedulerSrc = trim($schedulerSrc);
    if (!str_starts_with($schedulerSrc, 'https://scheduler.zoom.us/')) {
        return '';
    }

    $parts = parse_url($schedulerSrc);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host']) || empty($parts['path'])) {
        return '';
    }

    $params = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $params);
        unset($params['embed']);
    }

    $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    $query = http_build_query($params);
    return $query !== '' ? $url . '?' . $query : $url;
}

function zoom_browser_join_url(string $zoomUrl): string {
    $zoomUrl = trim($zoomUrl);
    if ($zoomUrl === '') {
        return '';
    }

    if (preg_match('#/j/(\d+)#', $zoomUrl, $matches) !== 1) {
        return '';
    }

    $meetingId = $matches[1];
    $query = parse_url($zoomUrl, PHP_URL_QUERY);
    $params = [];
    if (is_string($query)) {
        parse_str($query, $params);
    }

    $browserUrl = 'https://zoom.us/wc/join/' . rawurlencode($meetingId);
    if (!empty($params['pwd'])) {
        $browserUrl .= '?pwd=' . rawurlencode((string) $params['pwd']);
    }

    return $browserUrl;
}

function hub_settings(): array {
    return array_merge(default_hub_settings(), read_json_file(hub_settings_file(), []));
}

function registration_headers(): array {
    return [
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
}

function registration_csv_path(): string {
    return portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations-current.csv';
}

function registration_csv_paths(): array {
    return [
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations-current.csv',
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations-full.csv',
        portal_path('submissions') . DIRECTORY_SEPARATOR . 'registrations.csv',
    ];
}

function repair_registration_csv_ids(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $handle = fopen($path, 'r+b');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return;
    }

    $headers = fgetcsv($handle);
    if (!is_array($headers)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return;
    }

    $idIndex = array_search('Yuva Club ID', $headers, true);
    if ($idIndex === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return;
    }

    $rows = [];
    $seen = [];
    $maxNumber = 0;
    $changed = false;

    while (($row = fgetcsv($handle)) !== false) {
        $id = trim((string) ($row[$idIndex] ?? ''));
        if (preg_match('/^YC-?(\d{4})-?(\d+)$/', $id, $matches) === 1) {
            $maxNumber = max($maxNumber, (int) $matches[2]);
        }
        $rows[] = $row;
    }

    foreach ($rows as &$row) {
        $id = normalize_yuva_id((string) ($row[$idIndex] ?? ''));
        if ($id === '' || isset($seen[$id])) {
            do {
                $maxNumber++;
                $id = sprintf('YC%s%03d', date('Y'), $maxNumber);
            } while (isset($seen[$id]));
            $changed = true;
        }
        if (($row[$idIndex] ?? '') !== $id) {
            $row[$idIndex] = $id;
            $changed = true;
        }
        $seen[$id] = true;
    }
    unset($row);

    if ($changed) {
        ftruncate($handle, 0);
        rewind($handle);
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fflush($handle);
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

function registration_rows(): array {
    $allHeaders = registration_headers();
    $allRows = [];

    foreach (registration_csv_paths() as $path) {
        repair_registration_csv_ids($path);

        if (!file_exists($path)) {
            continue;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            continue;
        }

        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            continue;
        }

        foreach ($headers as $header) {
            if (!in_array($header, $allHeaders, true)) {
                $allHeaders[] = $header;
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $record = ['__source_path' => $path];
            foreach ($headers as $index => $header) {
                $record[$header] = $row[$index] ?? '';
            }
            if (($record['WhatsApp Username / Number'] ?? '') === '') {
                $record['WhatsApp Username / Number'] = trim(($record['WhatsApp Username'] ?? '') . ' ' . ($record['WhatsApp Phone Number'] ?? ''));
            }
            $age = (int) ($record['Age'] ?? 0);
            if ($age >= 18 && $age <= 21) {
                $record['Program Group'] = 'College Yuva (Ages 18-21)';
            } elseif ($age >= 13 && $age <= 17) {
                $record['Program Group'] = 'School Yuva (Ages 13-17)';
            }
            $allRows[] = $record;
        }
        fclose($handle);
    }

    return ['path' => registration_csv_path(), 'headers' => $allHeaders, 'rows' => $allRows];
}

function find_registration_row(string $studentId): ?array {
    $studentId = normalize_yuva_id($studentId);
    $data = registration_rows();
    foreach ($data['rows'] as $row) {
        if (normalize_yuva_id($row['Yuva Club ID'] ?? '') === $studentId) {
            return $row;
        }
    }
    return null;
}

function editable_registration_fields(): array {
    return [
        'Student Information & Contact' => ['Student First Name', 'Student Last Name', 'Preferred Name', 'Date of Birth', 'Age', 'Program Group', 'Grade', 'School', 'City/State', 'Student Email', 'Student Phone Number', 'WhatsApp Username / Number'],
        'Parent/Guardian Information' => ['Parent/Guardian Name', 'Relationship', 'Parent Email', 'Parent Phone Number'],
        'Participation' => ['Interests', 'Why Join', 'Presentation Experience', 'Presentation Topics', 'Preferred Schedule', 'Suggestions'],
        'Agreements' => ['Code of Conduct Agreement', 'Recording Agreement', 'Parent Permission'],
    ];
}

function update_registration_row(string $studentId, array $updates): bool {
    $data = registration_rows();
    $target = find_registration_row($studentId);
    $path = $target['__source_path'] ?? $data['path'];
    if (!file_exists($path)) {
        return false;
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return false;
    }

    $headers = fgetcsv($handle);
    if (!is_array($headers)) {
        fclose($handle);
        return false;
    }

    $rows = [];
    while (($csvRow = fgetcsv($handle)) !== false) {
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $csvRow[$index] ?? '';
        }
        $rows[] = $row;
    }
    fclose($handle);
    $found = false;

    foreach (editable_registration_fields() as $fields) {
        foreach ($fields as $field) {
            if (!in_array($field, $headers, true)) {
                $headers[] = $field;
            }
        }
    }

    foreach ($rows as &$row) {
        if (normalize_yuva_id($row['Yuva Club ID'] ?? '') !== normalize_yuva_id($studentId)) {
            continue;
        }
        foreach ($updates as $field => $value) {
            if (in_array($field, $headers, true)) {
                $row[$field] = clean_text((string) $value);
            }
        }
        $found = true;
        break;
    }
    unset($row);

    if (!$found) {
        return false;
    }

    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $tmp = $path . '.tmp';
    $handle = fopen($tmp, 'wb');
    if ($handle === false) {
        return false;
    }

    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        $csvRow = [];
        foreach ($headers as $header) {
            $csvRow[] = $row[$header] ?? '';
        }
        fputcsv($handle, $csvRow);
    }
    fclose($handle);

    return rename($tmp, $path);
}

function portal_students(): array {
    $data = registration_rows();
    $students = [];
    foreach ($data['rows'] as $student) {
        if (($student['Yuva Club ID'] ?? '') !== '') {
            $student['Yuva Club ID'] = normalize_yuva_id($student['Yuva Club ID']);
            $students[$student['Yuva Club ID']] = $student;
        }
    }
    return merge_staging_test_student($students);
}

function staging_test_student_fixture(): ?array {
    $fixture = staging_test_fixture_config();
    if ($fixture === null) {
        return null;
    }

    $student = array_fill_keys(registration_headers(), '');
    $student['Yuva Club ID'] = $fixture['student_id'];
    $student['Student First Name'] = 'Staging';
    $student['Student Last Name'] = 'Test Student';
    $student['Preferred Name'] = 'Staging';
    $student['Date of Birth'] = $fixture['student_dob'];
    $student['Age'] = (string) $fixture['student_age'];
    $student['Program Group'] = $fixture['student_program_group'];

    return $student;
}

function merge_staging_test_student(array $students): array {
    $fixture = staging_test_student_fixture();
    if ($fixture === null) {
        return $students;
    }

    $studentId = (string) $fixture['Yuva Club ID'];
    if (!array_key_exists($studentId, $students)) {
        $students[$studentId] = $fixture;
    }

    return $students;
}

function find_student(string $studentId): ?array {
    $studentId = normalize_yuva_id($studentId);
    $students = portal_students();
    return $students[$studentId] ?? null;
}

function student_display_name(array $student): string {
    $preferred = $student['Preferred Name'] ?? '';
    if ($preferred !== '') {
        return $preferred;
    }
    return trim(($student['Student First Name'] ?? '') . ' ' . ($student['Student Last Name'] ?? '')) ?: 'Student';
}

function student_certificate_name(array $student): string {
    return trim(($student['Student First Name'] ?? '') . ' ' . ($student['Student Last Name'] ?? '')) ?: student_display_name($student);
}

function logged_in_student_id(): ?string {
    return $_SESSION['student_id'] ?? null;
}

function require_student(): array {
    $studentId = logged_in_student_id();
    if ($studentId === null) {
        redirect_to('portal-login.php');
    }

    $student = find_student($studentId);
    if ($student === null) {
        unset($_SESSION['student_id']);
        redirect_to('portal-login.php?status=missing');
    }

    return $student;
}

function admin_password_matches(string $email, string $password): bool {
    $credentials = admin_credentials();
    return strtolower($email) === strtolower((string) ($credentials['email'] ?? ''))
        && hash_equals((string) ($credentials['password_hash'] ?? ''), password_hash_for_admin($password));
}

function require_admin(): void {
    if (($_SESSION['admin_logged_in'] ?? false) !== true) {
        redirect_to('admin-login.php');
    }
}

function yuva_topic_categories(): array {
    return [
        'Leadership & Inspiration' => ['Great Leaders', 'Young Changemakers', 'Nobel Prize Winners', 'Humanitarian Leaders', 'Social Reformers', 'Women Who Changed the World', 'Nelson Mandela', 'A.P.J. Abdul Kalam', 'Abraham Lincoln', 'Malala Yousafzai', 'Wangari Maathai', 'Mother Teresa'],
        'Science & Technology' => ['Space Exploration', 'Artificial Intelligence', 'Robotics', 'Medical Discoveries', 'Renewable Energy', 'Future Technologies', 'Famous Scientists', 'Great Inventors', 'Marie Curie', 'Albert Einstein', 'Ada Lovelace', 'Katherine Johnson', 'Tu Youyou', 'Sunita Williams'],
        'Business & Entrepreneurship' => ['Famous Entrepreneurs', 'Startup Stories', 'Brands That Changed the World', 'Financial Literacy', 'Marketing', 'Innovation', 'Steve Jobs', 'Elon Musk', 'Sundar Pichai', 'Walt Disney', 'Ratan Tata'],
        'History & Civilization' => ['Ancient Civilizations', 'World History', 'Historical Events', 'Great Empires', 'Archaeology', 'Ancient Wonders', 'Indus Valley Civilization', 'Ancient Egypt', 'Ancient Greece', 'Ancient Rome', 'Maya Civilization'],
        'Geography & Cultures' => ['Countries of the World', 'World Cultures', 'Languages', 'Traditions', 'Festivals', 'UNESCO World Heritage Sites', 'Yoga', 'Ayurveda', 'Sanskrit', 'Classical Music', 'Classical Dance'],
        'Architecture & Engineering' => ['Famous Buildings', 'Bridges', 'Skyscrapers', 'Ancient Architecture', 'Modern Engineering Marvels', 'Smart Cities', 'Great Wall of China', 'Taj Mahal', 'Pyramids of Giza', 'Machu Picchu', 'Angkor Wat'],
        'Environment' => ['Climate Change', 'Wildlife', 'Oceans', 'National Parks', 'Sustainability', 'Recycling', 'Biodiversity', 'Conservation'],
        'Health & Wellness' => ['Nutrition', 'Exercise', 'Mental Well-being', 'Yoga', 'Meditation', 'Healthy Habits', 'Sleep Science'],
        'Books & Literature' => ['Famous Authors', 'Classic Books', 'Children\'s Literature', 'Poetry', 'Book Reviews', 'Storytelling', 'Ramayana: Rama Accepts Exile', 'Ramayana: Sita\'s Strength', 'Ramayana: Hanuman\'s Leap to Lanka', 'Mahabharata: Bhishma\'s Great Vow', 'Mahabharata: Arjuna and the Eye of the Bird', 'Mahabharata: Krishna Guides Arjuna', 'Panchatantra: The Lion and the Clever Hare', 'Jataka Tales', 'Aesop\'s Fables'],
        'Arts & Creativity' => ['Painting', 'Music', 'Dance', 'Photography', 'Film', 'Theatre', 'Design'],
        'Sports' => ['Olympic Games', 'World Cup', 'Great Athletes', 'Teamwork', 'Sportsmanship', 'Sports Science'],
        'Digital Skills' => ['Coding', 'Cybersecurity', 'Internet Safety', 'Digital Citizenship', 'Graphic Design', 'Video Editing', 'Responsible Use of AI'],
        'Communication' => ['Public Speaking', 'Debate', 'Persuasion', 'Interview Skills', 'Storytelling', 'Body Language'],
        'Character Development' => ['Kindness', 'Integrity', 'Leadership', 'Teamwork', 'Time Management', 'Goal Setting', 'Emotional Intelligence', 'Problem Solving'],
        'Community & Service' => ['Volunteering', 'Community Projects', 'Charity', 'Civic Responsibility', 'Environmental Action', 'Doctors', 'Teachers', 'Firefighters', 'Volunteers', 'Nonprofit Leaders', 'Everyday Leaders in My Community'],
        'STEM Challenges' => ['DIY Science', 'Engineering Challenges', 'Math Puzzles', 'Coding Challenges', 'Robotics Projects'],
        'Career Exploration' => ['Doctors', 'Engineers', 'Scientists', 'Artists', 'Lawyers', 'Teachers', 'Pilots', 'Entrepreneurs', 'AI Professionals', 'Environmental Scientists'],
    ];
}

function topic_is_taken(string $title, string $studentId): bool {
    $selections = read_json_file(topic_selections_file());
    foreach ($selections as $id => $selection) {
        if ($id !== $studentId && strcasecmp($selection['topic_title'] ?? '', $title) === 0) {
            return true;
        }
    }
    return false;
}

function student_record(string $studentId): array {
    $records = read_json_file(portal_records_file());
    $defaults = [
        'approved' => 'Pending',
        'attendance' => '0',
        'presentations' => '0',
        'service_hours' => '0',
        'last_duration' => '',
        'score' => '',
        'teacher_feedback' => '',
        'certificate_status' => 'Not Ready',
        'admin_notes' => '',
        'student_session_title' => '',
        'student_session_date' => '',
        'student_session_start' => '',
        'student_session_end' => '',
        'student_session_status' => 'Closed',
        'student_zoom_url' => '',
        'student_zoom_meeting_id' => '',
        'student_zoom_password' => '',
        'current_rank' => 'Explorer',
        'rank_status' => 'Approved',
        'rank_recommendation' => '',
        'mentor_feedback' => '',
        'points' => '',
        'tokens' => '',
        'reward_status' => 'Not Yet',
        'ai_feedback_summary' => '',
        'communication_skills' => '',
        'leadership_milestones' => '',
        'challenge_stage' => 'Practice Session',
        'challenge_region' => '',
        'challenge_month' => date('Y-m'),
        'finalist_status' => 'Not Qualified',
        'award_status' => 'None',
        'judge_feedback' => '',
    ];
    foreach (array_keys(rubric_categories()) as $key) {
        $defaults['rubric_' . $key] = '';
    }
    return array_merge($defaults, $records[$studentId] ?? []);
}

function student_points(array $record): int {
    if (($record['points'] ?? '') !== '') {
        return max(0, (int) $record['points']);
    }

    $attendance = (int) ($record['attendance'] ?? 0);
    $presentations = (int) ($record['presentations'] ?? 0);
    $hours = (float) ($record['service_hours'] ?? 0);
    $score = is_numeric($record['score'] ?? '') ? (int) $record['score'] : 0;

    return max(0, ($attendance * 5) + ($presentations * 25) + ((int) round($hours * 10)) + $score + rubric_score($record));
}

function student_tokens(array $record): int {
    if (($record['tokens'] ?? '') !== '') {
        return max(0, (int) $record['tokens']);
    }

    return intdiv(student_points($record), 25);
}

function reward_level(array $record): string {
    $tokens = student_tokens($record);
    if ($tokens >= 100) {
        return 'Gold Reward';
    }
    if ($tokens >= 50) {
        return 'Silver Reward';
    }
    if ($tokens >= 20) {
        return 'Bronze Reward';
    }
    return 'Keep Growing';
}

function safety_reports(): array {
    return read_json_file(safety_reports_file(), []);
}

function ai_reviews(): array {
    return read_json_file(ai_reviews_file(), []);
}

function ai_review_identifier(string $studentId, array $reviewRecord): string {
    $reviewId = trim((string) ($reviewRecord['review_id'] ?? ''));
    if ($reviewId !== '') {
        return $reviewId;
    }

    return hash('sha256', json_encode([
        'student_id' => normalize_yuva_id($studentId),
        'reviewed_at' => $reviewRecord['reviewed_at'] ?? '',
        'review' => $reviewRecord['review'] ?? [],
    ], JSON_UNESCAPED_SLASHES) ?: '');
}

function mark_ai_review_stale(string $studentId, string $reason): void {
    $reviews = ai_reviews();
    if (!isset($reviews[$studentId]) || !is_array($reviews[$studentId])) {
        return;
    }

    $currentStatus = (string) ($reviews[$studentId]['status'] ?? '');
    if (str_starts_with($currentStatus, 'Stale - ')) {
        return;
    }

    $reviews[$studentId]['previous_status'] = $currentStatus;
    $reviews[$studentId]['status'] = 'Stale - ' . $reason;
    $reviews[$studentId]['stale_at'] = date('Y-m-d H:i:s');
    $reviews[$studentId]['stale_reason'] = $reason;
    write_json_file(ai_reviews_file(), $reviews);
}

function with_ai_apply_lock(callable $callback): mixed {
    ensure_portal_dirs();
    $lockPath = portal_path('portal-data') . DIRECTORY_SEPARATOR . 'ai-apply.lock';
    $handle = fopen($lockPath, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        throw new RuntimeException('Could not secure the AI review operation.');
    }

    try {
        return $callback();
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function openai_api_key(): string {
    $key = trim((string) (getenv('OPENAI_API_KEY') ?: ($_SERVER['OPENAI_API_KEY'] ?? '')));
    if ($key !== '') {
        return $key;
    }

    if (app_is_azure()) {
        return '';
    }

    $privateConfig = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'yuva-env.php';
    if (is_readable($privateConfig)) {
        $config = require $privateConfig;
        if (is_array($config) && !empty($config['OPENAI_API_KEY'])) {
            return trim((string) $config['OPENAI_API_KEY']);
        }
    }

    return '';
}

function openai_model_name(): string {
    $model = trim((string) (getenv('OPENAI_MODEL') ?: ($_SERVER['OPENAI_MODEL'] ?? '')));
    if ($model !== '') {
        return $model;
    }

    if (app_is_azure()) {
        return 'gpt-4.1-mini';
    }

    $privateConfig = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'yuva-env.php';
    if (is_readable($privateConfig)) {
        $config = require $privateConfig;
        if (is_array($config) && !empty($config['OPENAI_MODEL'])) {
            return trim((string) $config['OPENAI_MODEL']);
        }
    }

    return 'gpt-4.1-mini';
}

function ai_review_prompt(array $student, array $selection, array $research): string {
    $studentName = student_display_name($student);
    $category = $selection['topic_category'] ?? 'Not selected';
    $title = $selection['topic_title'] ?? 'Not selected';
    $notes = $research['research_notes'] ?? '';
    $sources = $research['sources_used'] ?? '';
    $outline = $research['presentation_outline'] ?? '';
    $questions = $research['prepared_questions'] ?? '';

    return <<<PROMPT
You are the Yuva Club AI Coach. Review this student's research submission for a youth presentation program.

Audience: students ages 8-18. Be encouraging, specific, and safe. Do not compare the student to other students. Do not shame the student. Do not infer sensitive traits.

Student: {$studentName}
Topic category: {$category}
Topic title: {$title}

Research notes:
{$notes}

Sources used:
{$sources}

Presentation outline:
{$outline}

Questions prepared:
{$questions}

Return only valid JSON with these keys:
{
  "research_quality": 0-20,
  "presentation_structure": 0-20,
  "topic_understanding": 0-20,
  "discussion_questions": 0-15,
  "leadership_lesson": 0-15,
  "effort_and_readiness": 0-10,
  "total_points": 0-100,
  "summary": "2-3 sentence encouraging summary",
  "strengths": ["strength 1", "strength 2", "strength 3"],
  "improvements": ["improvement 1", "improvement 2", "improvement 3"],
  "communication_skills": "short note about clarity, organization, and speaking preparation",
  "leadership_milestones": "short milestone-style note",
  "suggested_tokens": 0-4,
  "admin_notes": "short note for adult reviewer"
}
PROMPT;
}

function extract_response_text(array $response): string {
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }

    $parts = [];
    foreach (($response['output'] ?? []) as $output) {
        foreach (($output['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $parts[] = (string) $content['text'];
            }
        }
    }

    return trim(implode("\n", $parts));
}

function ai_review_research_submission(array $student, array $selection, array $research): array {
    $apiKey = openai_api_key();
    if ($apiKey === '') {
        return [
            'ok' => false,
            'error' => 'OPENAI_API_KEY is not configured on the server.',
        ];
    }

    $payload = [
        'model' => openai_model_name(),
        'input' => [
            [
                'role' => 'system',
                'content' => 'You are a child-safe educational coach. Return only valid JSON.',
            ],
            [
                'role' => 'user',
                'content' => ai_review_prompt($student, $selection, $research),
            ],
        ],
        'text' => [
            'format' => [
                'type' => 'json_object',
            ],
        ],
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not initialize cURL.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $curlError !== '') {
        return ['ok' => false, 'error' => 'OpenAI request failed: ' . $curlError];
    }

    $response = json_decode((string) $raw, true);
    if (!is_array($response)) {
        return ['ok' => false, 'error' => 'OpenAI returned an unreadable response.'];
    }

    if ($status < 200 || $status >= 300) {
        $message = $response['error']['message'] ?? ('OpenAI returned HTTP ' . $status);
        return ['ok' => false, 'error' => (string) $message];
    }

    $text = extract_response_text($response);
    $review = json_decode($text, true);
    if (!is_array($review)) {
        return ['ok' => false, 'error' => 'AI response was not valid JSON.'];
    }

    $review['total_points'] = max(0, min(100, (int) ($review['total_points'] ?? 0)));
    $review['suggested_tokens'] = max(0, min(4, (int) ($review['suggested_tokens'] ?? intdiv($review['total_points'], 25))));

    return [
        'ok' => true,
        'review' => $review,
    ];
}

function leadership_level(array $record): string {
    return approved_rank($record);
}

function earned_badges(array $record): array {
    $badges = [];
    $presentations = (int) ($record['presentations'] ?? 0);
    $attendance = (int) ($record['attendance'] ?? 0);
    $hours = (float) ($record['service_hours'] ?? 0);

    if ($presentations >= 1) {
        $badges[] = 'First Presentation';
    }
    if ($presentations >= 5) {
        $badges[] = 'Five Presentations';
    }
    if ($presentations >= 10) {
        $badges[] = 'Master Presenter';
    }
    if ($hours >= 10) {
        $badges[] = 'Leadership Hours';
    }
    if ($attendance >= 8) {
        $badges[] = 'Consistent Attendance';
    }
    if (($record['teacher_feedback'] ?? '') !== '') {
        $badges[] = 'Feedback Reviewed';
    }

    return array_values(array_unique(array_merge($badges, challenge_badges($record))));
}

function text_lines(string $value): array {
    $lines = preg_split('/\r\n|\r|\n/', trim($value));
    return array_values(array_filter(array_map('trim', $lines ?: []), fn($line) => $line !== ''));
}

function parse_link_lines(string $value): array {
    $links = [];
    foreach (text_lines($value) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        $links[] = [
            'title' => $parts[0],
            'url' => $parts[1] ?? '',
        ];
    }
    return $links;
}

function student_app_icon(string $name): string {
    $icons = [
        'home' => '<path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10.5V20h5v-6h4v6h5v-9.5"></path>',
        'practice' => '<circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="4"></circle><path d="m15 9 6-6"></path><path d="M18 3h3v3"></path>',
        'present' => '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><path d="M12 19v3"></path>',
        'progress' => '<path d="M4 19V9"></path><path d="M10 19V5"></path><path d="M16 19v-7"></path><path d="M22 19V2"></path>',
        'profile' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 22a8 8 0 0 1 16 0"></path>',
    ];
    $paths = $icons[$name] ?? $icons['home'];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
}

function student_app_navigation(string $className, string $label): string {
    $items = [
        ['home', 'Home', 'portal.php#app-home'],
        ['practice', 'Practice', 'portal.php#app-practice'],
        ['present', 'Present', 'portal.php#app-present'],
        ['progress', 'Progress', 'portal.php#app-progress'],
        ['profile', 'Profile', 'portal.php#app-profile'],
    ];
    $html = '<nav class="' . e($className) . '" aria-label="' . e($label) . '">';
    foreach ($items as [$key, $text, $href]) {
        $html .= '<a href="' . e($href) . '" data-app-nav="' . e($key) . '">' . student_app_icon($key) . '<span>' . e($text) . '</span></a>';
    }
    return $html . '</nav>';
}

function portal_header(string $title, bool $studentApp = false): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | Yuva Club</title>';
    echo '<meta name="description" content="Yuva Club student leadership portal.">';
    echo '<link rel="icon" href="assets/logo.png" type="image/png">';
    echo '<link rel="apple-touch-icon" href="assets/app-icon-180.png">';
    echo '<link rel="manifest" href="manifest.webmanifest">';
    echo '<meta name="theme-color" content="#062856">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-title" content="YUVA Club">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    echo '<link rel="stylesheet" href="assets/site.css?v=20260614-large-photos">';
    if ($studentApp) {
        echo '<link rel="stylesheet" href="assets/student-app.css?v=1">';
    }
    echo '<script src="assets/app.js" defer></script>';
    if ($studentApp) {
        echo '<script src="assets/student-app.js?v=1" defer></script>';
    }
    echo '</head><body' . ($studentApp ? ' class="student-app is-loading"' : '') . '>';
    if ($studentApp) {
        echo '<a class="app-skip-link" href="#app-main">Skip to content</a>';
        echo '<div class="app-loading" role="status" aria-live="polite"><img src="assets/logo.png" alt=""><span>Loading your YUVA Club app&hellip;</span></div>';
        echo '<header class="student-app-header"><a class="student-app-brand" href="portal.php#app-home"><img src="assets/logo.png" alt="YUVA Club"><span><strong>YUVA</strong> Club</span></a><div class="student-app-header-actions"><span class="student-app-page-title">' . e($title) . '</span><a class="student-app-profile-link" href="portal.php#app-profile" aria-label="Open profile">' . student_app_icon('profile') . '</a></div></header>';
        echo '<aside class="student-app-rail"><a class="student-app-rail-brand" href="portal.php#app-home"><img src="assets/logo.png" alt="YUVA Club"><span>YUVA <strong>Club</strong></span></a>' . student_app_navigation('student-app-rail-nav', 'Student app navigation') . '<a class="student-app-logout" href="portal-logout.php">Log out</a></aside>';
        echo '<div class="student-app-frame">';
        return;
    }
    echo '<header class="site-header"><a class="brand" href="index.html" aria-label="Yuva Club home"><img src="assets/logo.png" alt="Yuva Club logo" width="78" height="78"><span>Yuva Club</span></a>';
    echo '<nav class="nav" aria-label="Main navigation"><a href="index.html">Home</a><a href="programs.html">Programs</a><a href="challenges.html">Challenges</a><a href="curriculum.html">Topics</a><a href="stories.html">Stories</a><a href="leaderboard.php">Leaderboard</a><a href="app.html">App</a><a href="safety.html">Safety</a><a href="registration.php">Register</a><a href="portal-login.php">Student Portal</a><a href="parent-login.php">Parent</a><a href="admin-login.php">Admin</a></nav></header>';
}

function portal_footer(bool $studentApp = false): void {
    if ($studentApp) {
        echo '</div>' . student_app_navigation('student-app-bottom-nav', 'Student app navigation') . '</body></html>';
        return;
    }
    echo '<footer class="site-footer"><div><strong>Yuva Club</strong><p>A youth leadership development platform that empowers students through research, presentations, discussion, critical thinking, and peer learning.</p><p><a href="https://www.karmabro.com/">www.karmabro.com</a></p><p>&copy; 2026 KarmaBro. All rights reserved.</p></div></footer></body></html>';
}
