<?php
declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');
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
const YUVA_PLATFORM_ADMIN_EMAIL = 'admin@yuvaclub.app';
const YUVA_ADMIN_EMAIL = YUVA_PLATFORM_ADMIN_EMAIL;
const YUVA_ADMIN_PASSWORD_HASH = '8028e3a1b67db0e8f715a09c54f460c6449f1f091f065a28be74e6879b5b78b3';
const YUVA_ROLE_MASTER_ADMIN = 'MasterAdmin';
const YUVA_ROLE_ORGANIZATION_ADMIN = 'OrganizationAdmin';
const YUVA_ROLE_PARENT = 'Parent';
const YUVA_ROLE_STUDENT = 'Student';
const YUVA_PLATFORM_ORGANIZATION_ID = 'platform';
const YUVA_PARENT_SESSION_TTL_SECONDS = 7200;
const YUVA_ADMIN_SESSION_TTL_SECONDS = 7200;

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

function student_accounts_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'student-accounts.json';
}

function login_attempts_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'login-attempts.json';
}

function safety_reports_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'safety-reports.json';
}

function admin_credentials_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'admin-credentials.json';
}

function organization_admin_accounts_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-accounts.json';
}

function organization_admin_invitation_tokens_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-invitation-tokens.json';
}

function organization_admin_invitation_delivery_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-admin-invitation-delivery.jsonl';
}

function organization_student_memberships_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-student-memberships.json';
}

function organization_student_invitation_delivery_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'organization-student-invitation-delivery.jsonl';
}

function parent_accounts_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-accounts.json';
}

function parent_student_links_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-student-links.json';
}

function parent_activation_tokens_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-activation-tokens.json';
}

function parent_activation_delivery_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'parent-activation-delivery.jsonl';
}

function security_audit_file(): string {
    return portal_path('portal-data') . DIRECTORY_SEPARATOR . 'security-audit-log.jsonl';
}

function admin_credentials(): array {
    return array_merge([
        'email' => YUVA_ADMIN_EMAIL,
        'password_hash' => YUVA_ADMIN_PASSWORD_HASH,
        'role' => YUVA_ROLE_MASTER_ADMIN,
        'organization_id' => YUVA_PLATFORM_ORGANIZATION_ID,
    ], read_json_file(admin_credentials_file(), []));
}

function password_hash_for_admin(string $password): string {
    return hash('sha256', YUVA_ADMIN_SALT . $password);
}

function request_ip(): string {
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

function request_user_agent(): string {
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
}

function audit_log_event(
    ?string $actorUserId,
    string $role,
    ?string $organizationId,
    string $action,
    string $targetType,
    ?string $targetId,
    bool $success,
    array $metadata = []
): void {
    ensure_portal_dirs();
    $entry = [
        'timestamp' => gmdate('c'),
        'actor_user_id' => $actorUserId,
        'role' => $role,
        'organization_id' => $organizationId,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'success' => $success,
        'ip' => request_ip(),
        'user_agent' => request_user_agent(),
        'metadata' => $metadata,
    ];
    file_put_contents(security_audit_file(), json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function parent_accounts(): array {
    return read_json_file(parent_accounts_file(), []);
}

function write_parent_accounts(array $accounts): void {
    write_json_file(parent_accounts_file(), $accounts);
}

function parent_student_links(): array {
    return read_json_file(parent_student_links_file(), []);
}

function write_parent_student_links(array $links): void {
    write_json_file(parent_student_links_file(), $links);
}

function parent_activation_tokens(): array {
    return read_json_file(parent_activation_tokens_file(), []);
}

function write_parent_activation_tokens(array $tokens): void {
    write_json_file(parent_activation_tokens_file(), $tokens);
}

function normalize_email(string $email): string {
    return strtolower(trim($email));
}

function parent_actor_id(string $email): string {
    return 'parent:' . hash('sha256', normalize_email($email));
}

function admin_actor_id(string $email): string {
    return 'admin:' . hash('sha256', normalize_email($email));
}

function normalize_organization_id(string $value): string {
    $value = strtoupper(clean_text($value));
    $value = preg_replace('/[^A-Z0-9_-]/', '', $value) ?? '';
    return $value !== '' ? $value : YUVA_PLATFORM_ORGANIZATION_ID;
}

function student_organization_id(array $student): string {
    $code = clean_text((string) ($student['Organization Code'] ?? ''));
    return $code !== '' ? normalize_organization_id($code) : YUVA_PLATFORM_ORGANIZATION_ID;
}

function create_parent_account(string $parentEmail, string $password, string $studentId): void {
    $email = normalize_email($parentEmail);
    $studentId = normalize_yuva_id($studentId);
    if ($email === '' || $studentId === '' || password_policy_error($password) !== '') {
        return;
    }

    $accounts = parent_accounts();
    $existing = $accounts[$email] ?? [];
    if (($existing['password_hash'] ?? '') === '') {
        $accounts[$email] = [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
            'email_verified' => true,
            'role' => YUVA_ROLE_PARENT,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
        ];
        write_parent_accounts($accounts);
    }

    link_parent_to_student($email, $studentId, student_organization_id(find_student($studentId) ?? []));
}

function ensure_parent_account_placeholder(string $parentEmail): void {
    $email = normalize_email($parentEmail);
    if ($email === '') {
        return;
    }

    $accounts = parent_accounts();
    if (!isset($accounts[$email]) || !is_array($accounts[$email])) {
        $accounts[$email] = [
            'email' => $email,
            'password_hash' => '',
            'status' => 'activation_pending',
            'email_verified' => false,
            'role' => YUVA_ROLE_PARENT,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
        ];
        write_parent_accounts($accounts);
    }
}

function link_parent_to_student(string $parentEmail, string $studentId, string $organizationId = YUVA_PLATFORM_ORGANIZATION_ID): void {
    $email = normalize_email($parentEmail);
    $studentId = normalize_yuva_id($studentId);
    if ($email === '' || $studentId === '') {
        return;
    }

    $links = parent_student_links();
    $links[$email] ??= [];
    $links[$email][$studentId] = [
        'student_id' => $studentId,
        'organization_id' => $organizationId !== '' ? $organizationId : YUVA_PLATFORM_ORGANIZATION_ID,
        'status' => 'active',
        'linked_at' => gmdate('c'),
    ];
    write_parent_student_links($links);
}

function sync_parent_links_from_registrations(string $parentEmail): int {
    $email = normalize_email($parentEmail);
    if ($email === '') {
        return 0;
    }

    $linked = 0;
    foreach (registration_rows()['rows'] as $row) {
        if (normalize_email((string) ($row['Parent Email'] ?? '')) !== $email) {
            continue;
        }
        $studentId = normalize_yuva_id((string) ($row['Yuva Club ID'] ?? ''));
        if ($studentId === '') {
            continue;
        }
        $student = find_student($studentId);
        if ($student === null) {
            continue;
        }
        link_parent_to_student($email, $studentId, student_organization_id($student));
        $linked++;
    }
    return $linked;
}

function parent_has_existing_relationship(string $parentEmail): bool {
    $email = normalize_email($parentEmail);
    if ($email === '') {
        return false;
    }
    if (parent_linked_students($email) !== []) {
        return true;
    }
    return sync_parent_links_from_registrations($email) > 0;
}

function parent_account_by_email(string $email): ?array {
    $accounts = parent_accounts();
    $account = $accounts[normalize_email($email)] ?? null;
    return is_array($account) ? $account : null;
}

function parent_password_matches(string $email, string $password): bool {
    $account = parent_account_by_email($email);
    $hash = (string) ($account['password_hash'] ?? '');
    return $account !== null
        && ($account['status'] ?? '') === 'active'
        && !empty($account['email_verified'])
        && $hash !== ''
        && password_verify($password, $hash);
}

function create_parent_activation_token(string $parentEmail): ?string {
    $email = normalize_email($parentEmail);
    if ($email === '' || !parent_has_existing_relationship($email)) {
        return null;
    }

    ensure_parent_account_placeholder($email);

    $token = bin2hex(random_bytes(32));
    $tokenId = bin2hex(random_bytes(16));
    $tokens = parent_activation_tokens();
    $tokens[$tokenId] = [
        'token_hash' => hash('sha256', $token),
        'parent_email' => $email,
        'purpose' => 'parent_password_setup',
        'created_at' => gmdate('c'),
        'expires_at' => gmdate('c', time() + 3600),
        'used_at' => null,
    ];
    write_parent_activation_tokens($tokens);

    audit_log_event(parent_actor_id($email), YUVA_ROLE_PARENT, null, 'parent.activation.requested', 'parent', $email, true);
    return $tokenId . '.' . $token;
}

function parent_activation_record(string $activationToken): ?array {
    $parts = explode('.', $activationToken, 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$tokenId, $token] = $parts;
    $tokens = parent_activation_tokens();
    $record = $tokens[$tokenId] ?? null;
    if (!is_array($record)) {
        return null;
    }
    if (($record['used_at'] ?? null) !== null) {
        return null;
    }
    if (strtotime((string) ($record['expires_at'] ?? '')) < time()) {
        return null;
    }
    if (!hash_equals((string) ($record['token_hash'] ?? ''), hash('sha256', $token))) {
        return null;
    }

    $record['token_id'] = $tokenId;
    return $record;
}

function complete_parent_activation(string $activationToken, string $password): bool {
    $record = parent_activation_record($activationToken);
    $email = normalize_email((string) ($record['parent_email'] ?? ''));
    if ($record === null || $email === '' || password_policy_error($password) !== '' || !parent_has_existing_relationship($email)) {
        audit_log_event($email !== '' ? parent_actor_id($email) : null, YUVA_ROLE_PARENT, null, 'parent.activation.completed', 'parent', $email !== '' ? $email : null, false);
        return false;
    }

    $accounts = parent_accounts();
    $existing = is_array($accounts[$email] ?? null) ? $accounts[$email] : [];
    $accounts[$email] = array_merge($existing, [
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'status' => 'active',
        'email_verified' => true,
        'role' => YUVA_ROLE_PARENT,
        'activated_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ]);
    if (empty($accounts[$email]['created_at'])) {
        $accounts[$email]['created_at'] = gmdate('c');
    }
    write_parent_accounts($accounts);

    $tokens = parent_activation_tokens();
    $tokenId = (string) ($record['token_id'] ?? '');
    if ($tokenId !== '' && isset($tokens[$tokenId])) {
        $tokens[$tokenId]['used_at'] = gmdate('c');
        write_parent_activation_tokens($tokens);
    }

    audit_log_event(parent_actor_id($email), YUVA_ROLE_PARENT, null, 'parent.activation.completed', 'parent', $email, true);
    return true;
}

function public_base_url(): string {
    $host = clean_text((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return 'https://www.yuvaclub.app';
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    return ($isHttps ? 'https://' : 'http://') . $host;
}

function parent_activation_url(string $activationToken): string {
    return public_base_url() . '/parent-activate.php?token=' . rawurlencode($activationToken);
}

function yuva_email_from_address(): string {
    $from = trim((string) (getenv('YUVA_EMAIL_FROM') ?: 'DoNotReply@yuvaclub.app'));
    return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : 'DoNotReply@yuvaclub.app';
}

function yuva_email_reply_to_address(): string {
    $replyTo = trim((string) (getenv('YUVA_EMAIL_REPLY_TO') ?: 'support@yuvaclub.app'));
    return filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : 'support@yuvaclub.app';
}

function yuva_email_connection_parts(string $connectionString): array {
    $parts = [];
    foreach (explode(';', $connectionString) as $part) {
        if (strpos($part, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $part, 2);
        $parts[strtolower(trim($key))] = trim($value);
    }
    return $parts;
}

function send_yuva_email_via_azure(string $to, string $subject, string $plainText, string $html = ''): bool {
    $connectionString = trim((string) (getenv('YUVA_EMAIL_CONNECTION_STRING') ?: ''));
    if ($connectionString === '' || !function_exists('curl_init')) {
        return false;
    }

    $parts = yuva_email_connection_parts($connectionString);
    $endpoint = rtrim((string) ($parts['endpoint'] ?? ''), '/');
    $accessKey = (string) ($parts['accesskey'] ?? '');
    if ($endpoint === '' || $accessKey === '') {
        error_log('Yuva Club Azure email is not configured correctly.');
        return false;
    }

    $url = $endpoint . '/emails:send?api-version=2023-03-31';
    $payload = [
        'senderAddress' => yuva_email_from_address(),
        'recipients' => [
            'to' => [
                ['address' => $to],
            ],
        ],
        'content' => [
            'subject' => $subject,
            'plainText' => $plainText,
        ],
    ];
    if ($html !== '') {
        $payload['content']['html'] = $html;
    }

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($body === false) {
        return false;
    }

    $urlParts = parse_url($url);
    $host = (string) ($urlParts['host'] ?? '');
    $path = (string) ($urlParts['path'] ?? '');
    $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
    $pathAndQuery = $path . $query;
    $date = gmdate('D, d M Y H:i:s') . ' GMT';
    $contentHash = base64_encode(hash('sha256', $body, true));
    $decodedKey = base64_decode($accessKey, true);
    if ($host === '' || $pathAndQuery === '' || $decodedKey === false) {
        return false;
    }
    $stringToSign = "POST\n{$pathAndQuery}\n{$date};{$host};{$contentHash}";
    $signature = base64_encode(hash_hmac('sha256', $stringToSign, $decodedKey, true));
    $authorization = 'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature=' . $signature;

    $curl = curl_init($url);
    if ($curl === false) {
        return false;
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-ms-date: ' . $date,
            'x-ms-content-sha256: ' . $contentHash,
            'Authorization: ' . $authorization,
        ],
    ]);
    curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($status >= 200 && $status < 300) {
        return true;
    }
    error_log('Yuva Club Azure email send failed. HTTP status: ' . $status . ($error !== '' ? ' Error: ' . $error : ''));
    return false;
}

function send_yuva_email(string $to, string $subject, string $plainText, string $replyTo = '', string $html = ''): bool {
    $to = normalize_email($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $provider = strtolower(trim((string) (getenv('YUVA_EMAIL_PROVIDER') ?: '')));
    if ($provider === 'azure_communication_services' || $provider === 'azure') {
        return send_yuva_email_via_azure($to, $subject, $plainText, $html);
    }

    $from = yuva_email_from_address();
    $replyToAddress = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : yuva_email_reply_to_address();
    $headers = "From: {$from}\r\n"
        . "Reply-To: {$replyToAddress}\r\n";
    return @mail($to, $subject, $plainText, $headers);
}

function send_parent_activation_email(string $parentEmail, string $activationUrl): bool {
    $email = normalize_email($parentEmail);
    if ($email === '') {
        return false;
    }

    $subject = 'Set up your YUVA Club parent account';
    $message = "Hello,\n\n"
        . "Use this secure link to set up your YUVA Club parent account password:\n\n"
        . $activationUrl . "\n\n"
        . "This link expires in 60 minutes. If you did not request this, you can ignore this email.\n\n"
        . "YUVA Club";

    $sent = send_yuva_email($email, $subject, $message);
    if ((getenv('YUVA_CAPTURE_PARENT_ACTIVATION_LINKS') ?: '') === '1') {
        file_put_contents(parent_activation_delivery_file(), json_encode([
            'created_at' => gmdate('c'),
            'parent_email_hash' => hash('sha256', $email),
            'delivery' => $sent ? 'mail_and_development_file' : 'development_file',
            'activation_url' => $activationUrl,
        ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    return $sent;
}

function organization_admin_accounts(): array {
    return read_json_file(organization_admin_accounts_file(), []);
}

function write_organization_admin_accounts(array $accounts): void {
    write_json_file(organization_admin_accounts_file(), $accounts);
}

function organization_admin_invitation_tokens(): array {
    return read_json_file(organization_admin_invitation_tokens_file(), []);
}

function write_organization_admin_invitation_tokens(array $tokens): void {
    write_json_file(organization_admin_invitation_tokens_file(), $tokens);
}

function organization_admin_by_email(string $email): ?array {
    $account = organization_admin_accounts()[normalize_email($email)] ?? null;
    return is_array($account) ? $account : null;
}

function organization_admin_public_view(array $account): array {
    unset($account['password_hash']);
    return $account;
}

function organization_options(): array {
    $options = [];
    foreach (portal_students() as $student) {
        $orgId = student_organization_id($student);
        if ($orgId !== YUVA_PLATFORM_ORGANIZATION_ID) {
            $options[$orgId] = $orgId;
        }
    }
    foreach (organization_admin_accounts() as $account) {
        $orgId = normalize_organization_id((string) ($account['organization_id'] ?? ''));
        if ($orgId !== YUVA_PLATFORM_ORGANIZATION_ID) {
            $options[$orgId] = $orgId;
        }
    }
    ksort($options);
    return $options;
}

function organization_admin_invitation_url(string $token): string {
    return public_base_url() . '/organization-admin-activate.php?token=' . rawurlencode($token);
}

function send_organization_admin_invitation_email(string $email, string $fullName, string $invitationUrl, string $purpose = 'invitation'): bool {
    $email = normalize_email($email);
    if ($email === '') {
        return false;
    }

    $subject = $purpose === 'password_reset' ? 'Reset your YUVA Club organization admin password' : 'Activate your YUVA Club organization admin account';
    $greeting = $fullName !== '' ? "Hello {$fullName}," : 'Hello,';
    $message = $greeting . "\n\n"
        . "Use this secure link to set up your YUVA Club organization administrator password:\n\n"
        . $invitationUrl . "\n\n"
        . "This link is single-use and expires in 72 hours. If you did not expect this invitation, please contact YUVA Club support.\n\n"
        . "YUVA Club";

    $sent = send_yuva_email($email, $subject, $message);
    if ((getenv('YUVA_CAPTURE_ADMIN_INVITATION_LINKS') ?: '') === '1') {
        file_put_contents(organization_admin_invitation_delivery_file(), json_encode([
            'created_at' => gmdate('c'),
            'admin_email_hash' => hash('sha256', $email),
            'delivery' => $sent ? 'mail_and_development_file' : 'development_file',
            'purpose' => $purpose,
            'activation_url' => $invitationUrl,
        ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        return true;
    }

    return $sent;
}

function create_organization_admin_token(string $email, string $purpose = 'invitation'): ?string {
    $email = normalize_email($email);
    $account = organization_admin_by_email($email);
    if ($email === '' || $account === null || ($account['role'] ?? '') !== YUVA_ROLE_ORGANIZATION_ADMIN) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $tokenId = bin2hex(random_bytes(16));
    $tokens = organization_admin_invitation_tokens();
    $tokens[$tokenId] = [
        'token_hash' => hash('sha256', $token),
        'admin_email' => $email,
        'purpose' => $purpose,
        'created_at' => gmdate('c'),
        'expires_at' => gmdate('c', time() + 259200),
        'used_at' => null,
    ];
    write_organization_admin_invitation_tokens($tokens);
    return $tokenId . '.' . $token;
}

function organization_admin_token_record(string $token): ?array {
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return null;
    }
    [$tokenId, $secret] = $parts;
    $tokens = organization_admin_invitation_tokens();
    $record = $tokens[$tokenId] ?? null;
    if (!is_array($record) || !empty($record['used_at'])) {
        return null;
    }
    if (strtotime((string) ($record['expires_at'] ?? '')) < time()) {
        return null;
    }
    if (!hash_equals((string) ($record['token_hash'] ?? ''), hash('sha256', $secret))) {
        return null;
    }
    $record['token_id'] = $tokenId;
    return $record;
}

function provision_organization_admin_invitation(array $admin, string $organizationId, string $fullName, string $email, string $role, string $status): bool {
    $email = normalize_email($email);
    $organizationId = normalize_organization_id($organizationId);
    $fullName = clean_text($fullName);
    $status = in_array($status, ['pending_invitation', 'suspended'], true) ? $status : 'pending_invitation';
    if ($email === '' || $organizationId === YUVA_PLATFORM_ORGANIZATION_ID || $fullName === '' || $role !== YUVA_ROLE_ORGANIZATION_ADMIN) {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.invitation.create', 'organization_admin', $email, false, ['reason' => 'invalid_input']);
        return false;
    }
    if ($email === YUVA_PLATFORM_ADMIN_EMAIL) {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.invitation.create', 'organization_admin', $email, false, ['reason' => 'master_admin_reserved']);
        return false;
    }

    $accounts = organization_admin_accounts();
    $existing = is_array($accounts[$email] ?? null) ? $accounts[$email] : [];
    $accounts[$email] = array_merge($existing, [
        'email' => $email,
        'full_name' => $fullName,
        'role' => YUVA_ROLE_ORGANIZATION_ADMIN,
        'organization_id' => $organizationId,
        'status' => $status,
        'email_verified' => false,
        'invitation_status' => $status === 'suspended' ? 'not_sent_suspended' : 'pending',
        'invited_at' => $existing['invited_at'] ?? gmdate('c'),
        'updated_at' => gmdate('c'),
        'last_login_at' => $existing['last_login_at'] ?? '',
    ]);
    if (!array_key_exists('password_hash', $accounts[$email])) {
        $accounts[$email]['password_hash'] = '';
    }
    write_organization_admin_accounts($accounts);

    if ($status === 'suspended') {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.invitation.create', 'organization_admin', $email, true, ['organization_id' => $organizationId, 'status' => $status]);
        return true;
    }

    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.invitation.create', 'organization_admin', $email, true, ['organization_id' => $organizationId, 'status' => $status]);
    return send_organization_admin_invitation($admin, $email, 'invitation');
}

function send_organization_admin_invitation(array $admin, string $email, string $purpose = 'invitation'): bool {
    $email = normalize_email($email);
    $accounts = organization_admin_accounts();
    $account = is_array($accounts[$email] ?? null) ? $accounts[$email] : null;
    if ($account === null || ($account['role'] ?? '') !== YUVA_ROLE_ORGANIZATION_ADMIN || ($account['status'] ?? '') === 'suspended') {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.invitation.send', 'organization_admin', $email, false, ['reason' => 'not_invitable']);
        return false;
    }

    $token = create_organization_admin_token($email, $purpose);
    if ($token === null) {
        return false;
    }
    $url = organization_admin_invitation_url($token);
    $sent = send_organization_admin_invitation_email($email, (string) ($account['full_name'] ?? ''), $url, $purpose);

    $accounts[$email]['invitation_status'] = $sent ? 'sent' : 'delivery_failed';
    $accounts[$email]['invitation_sent_at'] = gmdate('c');
    $accounts[$email]['updated_at'] = gmdate('c');
    write_organization_admin_accounts($accounts);

    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], $purpose === 'password_reset' ? 'organization_admin.password_reset.send' : 'organization_admin.invitation.send', 'organization_admin', $email, true, [
        'organization_id' => $account['organization_id'] ?? null,
        'email_delivery' => $sent ? 'sent' : 'failed',
    ]);
    return true;
}

function complete_organization_admin_invitation(string $token, string $password): bool {
    $record = organization_admin_token_record($token);
    $email = normalize_email((string) ($record['admin_email'] ?? ''));
    if ($record === null || $email === '' || password_policy_error($password) !== '') {
        audit_log_event($email !== '' ? admin_actor_id($email) : null, YUVA_ROLE_ORGANIZATION_ADMIN, null, 'organization_admin.invitation.complete', 'organization_admin', $email !== '' ? $email : null, false);
        return false;
    }

    $accounts = organization_admin_accounts();
    $account = is_array($accounts[$email] ?? null) ? $accounts[$email] : null;
    if ($account === null || ($account['role'] ?? '') !== YUVA_ROLE_ORGANIZATION_ADMIN || ($account['status'] ?? '') === 'suspended') {
        audit_log_event(admin_actor_id($email), YUVA_ROLE_ORGANIZATION_ADMIN, null, 'organization_admin.invitation.complete', 'organization_admin', $email, false, ['reason' => 'invalid_account']);
        return false;
    }

    $accounts[$email] = array_merge($account, [
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'status' => 'active',
        'email_verified' => true,
        'invitation_status' => 'accepted',
        'activated_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ]);
    write_organization_admin_accounts($accounts);

    $tokens = organization_admin_invitation_tokens();
    $tokenId = (string) ($record['token_id'] ?? '');
    if ($tokenId !== '' && isset($tokens[$tokenId])) {
        $tokens[$tokenId]['used_at'] = gmdate('c');
        write_organization_admin_invitation_tokens($tokens);
    }

    audit_log_event(admin_actor_id($email), YUVA_ROLE_ORGANIZATION_ADMIN, (string) ($account['organization_id'] ?? ''), 'organization_admin.invitation.complete', 'organization_admin', $email, true);
    return true;
}

function update_organization_admin_status(array $admin, string $email, string $status): bool {
    $email = normalize_email($email);
    $status = in_array($status, ['active', 'suspended'], true) ? $status : '';
    $accounts = organization_admin_accounts();
    if ($email === '' || $status === '' || !is_array($accounts[$email] ?? null)) {
        return false;
    }
    $accounts[$email]['status'] = $status;
    $accounts[$email]['updated_at'] = gmdate('c');
    write_organization_admin_accounts($accounts);
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.status.update', 'organization_admin', $email, true, ['status' => $status]);
    return true;
}

function update_organization_admin_assignment(array $admin, string $email, string $organizationId): bool {
    $email = normalize_email($email);
    $organizationId = normalize_organization_id($organizationId);
    $accounts = organization_admin_accounts();
    if ($email === '' || $organizationId === YUVA_PLATFORM_ORGANIZATION_ID || !is_array($accounts[$email] ?? null)) {
        return false;
    }
    $accounts[$email]['organization_id'] = $organizationId;
    $accounts[$email]['updated_at'] = gmdate('c');
    write_organization_admin_accounts($accounts);
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'organization_admin.assignment.update', 'organization_admin', $email, true, ['organization_id' => $organizationId]);
    return true;
}

function organization_admin_password_matches(string $email, string $password): ?array {
    $email = normalize_email($email);
    $account = organization_admin_by_email($email);
    $hash = (string) ($account['password_hash'] ?? '');
    if (
        $account === null
        || ($account['role'] ?? '') !== YUVA_ROLE_ORGANIZATION_ADMIN
        || ($account['status'] ?? '') !== 'active'
        || empty($account['email_verified'])
        || $hash === ''
        || !password_verify($password, $hash)
    ) {
        return null;
    }
    return $account;
}

function record_organization_admin_login(string $email): void {
    $email = normalize_email($email);
    $accounts = organization_admin_accounts();
    if (!is_array($accounts[$email] ?? null)) {
        return;
    }
    $accounts[$email]['last_login_at'] = gmdate('c');
    $accounts[$email]['updated_at'] = gmdate('c');
    write_organization_admin_accounts($accounts);
}

function organization_membership_statuses(): array {
    return ['Invited', 'Active', 'Inactive', 'Transferred', 'Archived'];
}

function normalize_membership_status(string $status): string {
    $status = clean_text($status);
    foreach (organization_membership_statuses() as $allowed) {
        if (strcasecmp($status, $allowed) === 0) {
            return $allowed;
        }
    }
    return 'Invited';
}

function organization_student_memberships(): array {
    return read_json_file(organization_student_memberships_file(), []);
}

function write_organization_student_memberships(array $memberships): void {
    write_json_file(organization_student_memberships_file(), $memberships);
}

function organization_student_membership_key(string $organizationId, string $studentId = '', string $studentEmail = ''): string {
    $organizationId = normalize_organization_id($organizationId);
    $studentId = normalize_yuva_id($studentId);
    $studentEmail = normalize_email($studentEmail);
    $target = $studentId !== '' ? $studentId : ('EMAIL-' . hash('sha256', $studentEmail));
    return $organizationId . ':' . $target;
}

function organization_student_membership_defaults(string $organizationId, string $studentId = '', string $studentEmail = ''): array {
    return [
        'organization_id' => normalize_organization_id($organizationId),
        'student_id' => normalize_yuva_id($studentId),
        'student_email' => normalize_email($studentEmail),
        'status' => 'Invited',
        'group' => '',
        'coach' => '',
        'teacher' => '',
        'moderator' => '',
        'source' => 'organization',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'invited_at' => '',
        'activated_at' => '',
        'archived_at' => '',
        'transferred_to_organization_id' => '',
        'notes' => '',
    ];
}

function activate_organization_student_membership_from_registration(string $organizationId, string $studentId, string $studentEmail): bool {
    $organizationId = normalize_organization_id($organizationId);
    $studentId = normalize_yuva_id($studentId);
    $studentEmail = normalize_email($studentEmail);
    if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID || $studentId === '' || $studentEmail === '') {
        return false;
    }

    $memberships = organization_student_memberships();
    $studentKey = organization_student_membership_key($organizationId, $studentId, $studentEmail);
    $emailKey = organization_student_membership_key($organizationId, '', $studentEmail);
    $emailInvite = is_array($memberships[$emailKey] ?? null) ? $memberships[$emailKey] : [];
    $studentMembership = is_array($memberships[$studentKey] ?? null) ? $memberships[$studentKey] : [];
    $base = $emailInvite !== [] ? $emailInvite : organization_student_membership_defaults($organizationId, $studentId, $studentEmail);

    $memberships[$studentKey] = array_merge($base, $studentMembership, [
        'organization_id' => $organizationId,
        'student_id' => $studentId,
        'student_email' => $studentEmail,
        'status' => 'Active',
        'source' => ($emailInvite !== [] ? 'organization_invitation' : 'registration'),
        'activated_at' => (string) (($studentMembership['activated_at'] ?? '') ?: gmdate('c')),
        'updated_at' => gmdate('c'),
    ]);

    if ($emailKey !== $studentKey && isset($memberships[$emailKey])) {
        unset($memberships[$emailKey]);
    }

    write_organization_student_memberships($memberships);
    audit_log_event(null, YUVA_ROLE_STUDENT, $organizationId, 'organization_student.invitation.accepted', 'student_membership', $studentKey, true, [
        'student_id' => $studentId,
        'student_email_hash' => hash('sha256', $studentEmail),
        'matched_invitation' => $emailInvite !== [],
    ]);
    return true;
}

function organization_student_memberships_for_org(string $organizationId): array {
    $organizationId = normalize_organization_id($organizationId);
    if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
        return [];
    }

    $memberships = [];
    foreach (organization_student_memberships() as $key => $membership) {
        if (!is_array($membership) || normalize_organization_id((string) ($membership['organization_id'] ?? '')) !== $organizationId) {
            continue;
        }
        $studentId = normalize_yuva_id((string) ($membership['student_id'] ?? ''));
        $studentEmail = normalize_email((string) ($membership['student_email'] ?? ''));
        if ($studentId === '' && $studentEmail !== '') {
            foreach (portal_students() as $registeredStudentId => $student) {
                if (
                    student_organization_id($student) === $organizationId
                    && normalize_email((string) ($student['Student Email'] ?? '')) === $studentEmail
                    && normalize_yuva_id((string) $registeredStudentId) !== ''
                ) {
                    continue 2;
                }
            }
        }
        $memberships[$key] = array_merge(organization_student_membership_defaults($organizationId), $membership);
    }

    foreach (portal_students() as $studentId => $student) {
        if (student_organization_id($student) !== $organizationId) {
            continue;
        }
        $key = organization_student_membership_key($organizationId, (string) $studentId);
        if (!isset($memberships[$key])) {
            $memberships[$key] = array_merge(organization_student_membership_defaults($organizationId, (string) $studentId, (string) ($student['Student Email'] ?? '')), [
                'status' => 'Active',
                'source' => 'registration',
                'activated_at' => (string) ($student['Submitted At'] ?? ''),
            ]);
        }
    }

    uasort($memberships, static function (array $a, array $b): int {
        return strcmp((string) ($a['student_id'] ?: $a['student_email']), (string) ($b['student_id'] ?: $b['student_email']));
    });
    return $memberships;
}

function organization_student_membership(string $organizationId, string $membershipKey): ?array {
    $organizationId = normalize_organization_id($organizationId);
    $memberships = organization_student_memberships_for_org($organizationId);
    $membership = $memberships[$membershipKey] ?? null;
    return is_array($membership) ? $membership : null;
}

function organization_student_can_access(string $organizationId, string $studentId): bool {
    $organizationId = normalize_organization_id($organizationId);
    $studentId = normalize_yuva_id($studentId);
    if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID || $studentId === '') {
        return false;
    }
    foreach (organization_student_memberships_for_org($organizationId) as $membership) {
        if (($membership['student_id'] ?? '') === $studentId && ($membership['status'] ?? '') !== 'Archived') {
            return true;
        }
    }
    return false;
}

function send_organization_student_invitation_email(string $studentEmail, string $organizationId): bool {
    $studentEmail = normalize_email($studentEmail);
    $organizationId = normalize_organization_id($organizationId);
    if ($studentEmail === '' || $organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
        return false;
    }

    $subject = 'You are invited to join YUVA Club';
    $message = "Hello,\n\n"
        . "You have been invited to join YUVA Club through organization {$organizationId}.\n\n"
        . "Visit " . public_base_url() . "/registration.php to create or connect your student account. Keep this organization code: {$organizationId}.\n\n"
        . "YUVA Club";

    $sent = send_yuva_email($studentEmail, $subject, $message);
    if ((getenv('YUVA_CAPTURE_STUDENT_INVITATION_LINKS') ?: '') === '1') {
        file_put_contents(organization_student_invitation_delivery_file(), json_encode([
            'created_at' => gmdate('c'),
            'student_email_hash' => hash('sha256', $studentEmail),
            'organization_id' => $organizationId,
            'delivery' => $sent ? 'mail_and_development_file' : 'development_file',
            'registration_url' => public_base_url() . '/registration.php',
        ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        return true;
    }
    return $sent;
}

function upsert_organization_student_membership(array $admin, array $input): array {
    $organizationId = normalize_organization_id((string) ($admin['organization_id'] ?? ''));
    $studentId = normalize_yuva_id((string) ($input['student_id'] ?? ''));
    $studentEmail = normalize_email((string) ($input['student_email'] ?? ''));
    $status = normalize_membership_status((string) ($input['status'] ?? 'Invited'));
    if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID || ($studentId === '' && $studentEmail === '')) {
        return ['ok' => false, 'error' => 'Invalid organization membership request.'];
    }
    if ($studentId !== '' && find_student($studentId) === null) {
        return ['ok' => false, 'error' => 'YUVA ID was not found. Invite the student by email first.'];
    }
    if ($studentId !== '') {
        $student = find_student($studentId);
        $studentEmail = $studentEmail !== '' ? $studentEmail : normalize_email((string) ($student['Student Email'] ?? ''));
    }

    $memberships = organization_student_memberships();
    $key = organization_student_membership_key($organizationId, $studentId, $studentEmail);
    $existing = is_array($memberships[$key] ?? null) ? $memberships[$key] : organization_student_membership_defaults($organizationId, $studentId, $studentEmail);
    $memberships[$key] = array_merge($existing, [
        'organization_id' => $organizationId,
        'student_id' => $studentId,
        'student_email' => $studentEmail,
        'status' => $status,
        'group' => clean_text((string) ($input['group'] ?? ($existing['group'] ?? ''))),
        'coach' => clean_text((string) ($input['coach'] ?? ($existing['coach'] ?? ''))),
        'teacher' => clean_text((string) ($input['teacher'] ?? ($existing['teacher'] ?? ''))),
        'moderator' => clean_text((string) ($input['moderator'] ?? ($existing['moderator'] ?? ''))),
        'notes' => clean_text((string) ($input['notes'] ?? ($existing['notes'] ?? ''))),
        'source' => clean_text((string) ($input['source'] ?? ($existing['source'] ?? 'organization'))),
        'updated_at' => gmdate('c'),
    ]);
    if ($status === 'Invited' && empty($memberships[$key]['invited_at'])) {
        $memberships[$key]['invited_at'] = gmdate('c');
    }
    if ($status === 'Active' && empty($memberships[$key]['activated_at'])) {
        $memberships[$key]['activated_at'] = gmdate('c');
    }
    if ($status === 'Archived' && empty($memberships[$key]['archived_at'])) {
        $memberships[$key]['archived_at'] = gmdate('c');
    }
    if ($status === 'Transferred') {
        $memberships[$key]['transferred_to_organization_id'] = normalize_organization_id((string) ($input['transferred_to_organization_id'] ?? ''));
    }

    write_organization_student_memberships($memberships);
    if (($input['send_invite'] ?? false) && $studentEmail !== '') {
        send_organization_student_invitation_email($studentEmail, $organizationId);
    }
    audit_log_event($admin['id'], $admin['role'], $organizationId, 'organization_student.membership.upsert', 'student_membership', $key, true, [
        'student_id' => $studentId,
        'student_email_hash' => $studentEmail !== '' ? hash('sha256', $studentEmail) : '',
        'status' => $status,
    ]);
    return ['ok' => true, 'key' => $key, 'membership' => $memberships[$key]];
}

function update_organization_student_membership(array $admin, string $membershipKey, array $updates): bool {
    $organizationId = normalize_organization_id((string) ($admin['organization_id'] ?? ''));
    $membership = organization_student_membership($organizationId, $membershipKey);
    if ($membership === null) {
        audit_log_event($admin['id'], $admin['role'], $organizationId, 'organization_student.membership.update', 'student_membership', $membershipKey, false, ['reason' => 'cross_org_or_missing']);
        return false;
    }
    $memberships = organization_student_memberships();
    $existing = is_array($memberships[$membershipKey] ?? null) ? $memberships[$membershipKey] : $membership;
    $status = array_key_exists('status', $updates) ? normalize_membership_status((string) $updates['status']) : (string) ($existing['status'] ?? 'Active');
    $memberships[$membershipKey] = array_merge($existing, [
        'status' => $status,
        'group' => clean_text((string) ($updates['group'] ?? ($existing['group'] ?? ''))),
        'coach' => clean_text((string) ($updates['coach'] ?? ($existing['coach'] ?? ''))),
        'teacher' => clean_text((string) ($updates['teacher'] ?? ($existing['teacher'] ?? ''))),
        'moderator' => clean_text((string) ($updates['moderator'] ?? ($existing['moderator'] ?? ''))),
        'notes' => clean_text((string) ($updates['notes'] ?? ($existing['notes'] ?? ''))),
        'updated_at' => gmdate('c'),
    ]);
    if ($status === 'Archived' && empty($memberships[$membershipKey]['archived_at'])) {
        $memberships[$membershipKey]['archived_at'] = gmdate('c');
    }
    if ($status === 'Transferred') {
        $memberships[$membershipKey]['transferred_to_organization_id'] = normalize_organization_id((string) ($updates['transferred_to_organization_id'] ?? ($existing['transferred_to_organization_id'] ?? '')));
    }
    write_organization_student_memberships($memberships);
    audit_log_event($admin['id'], $admin['role'], $organizationId, 'organization_student.membership.update', 'student_membership', $membershipKey, true, ['status' => $status]);
    return true;
}

function organization_student_progress_summary(string $studentId): array {
    $studentId = normalize_yuva_id($studentId);
    $record = student_record($studentId);
    $selection = read_json_file(topic_selections_file())[$studentId] ?? [];
    $research = read_json_file(research_file())[$studentId] ?? [];
    $aiReview = read_json_file(ai_reviews_file())[$studentId] ?? [];
    return [
        'approved' => (string) ($record['approved'] ?? 'Pending'),
        'presentations' => (string) ($record['presentations'] ?? '0'),
        'certificates' => (string) ($record['certificate_status'] ?? 'Not Ready'),
        'volunteer_hours' => (string) ($record['service_hours'] ?? '0'),
        'portfolio' => implode(', ', earned_badges($record)) ?: 'No badges yet',
        'assigned_activities' => (string) ($record['student_session_title'] ?? ''),
        'topic' => (string) ($selection['topic_title'] ?? 'No topic selected'),
        'research' => (string) ($research['status'] ?? 'No research submitted'),
        'ai_review' => (string) ($aiReview['summary'] ?? ($record['ai_feedback_summary'] ?? '')),
    ];
}

function parent_linked_students(string $parentEmail): array {
    $email = normalize_email($parentEmail);
    $links = parent_student_links()[$email] ?? [];
    $students = [];
    if (!is_array($links)) {
        return $students;
    }
    foreach ($links as $studentId => $link) {
        if (($link['status'] ?? '') !== 'active') {
            continue;
        }
        $student = find_student((string) $studentId);
        if ($student !== null) {
            $students[normalize_yuva_id((string) $studentId)] = $student;
        }
    }
    return $students;
}

function parent_can_access_student(string $parentEmail, string $studentId): bool {
    $email = normalize_email($parentEmail);
    $studentId = normalize_yuva_id($studentId);
    $link = parent_student_links()[$email][$studentId] ?? null;
    return is_array($link) && ($link['status'] ?? '') === 'active';
}

function require_parent_for_student(?string $requestedStudentId = null): array {
    $email = normalize_email((string) ($_SESSION['parent_email'] ?? ''));
    $startedAt = (int) ($_SESSION['parent_session_started_at'] ?? 0);
    if ($email === '' || $startedAt <= 0 || (time() - $startedAt) > YUVA_PARENT_SESSION_TTL_SECONDS) {
        audit_log_event(null, YUVA_ROLE_PARENT, null, 'parent.session.rejected', 'student', $requestedStudentId, false, ['reason' => 'missing_or_expired']);
        unset($_SESSION['parent_email'], $_SESSION['parent_session_started_at']);
        redirect_to('parent-login.php?status=expired');
    }

    $students = parent_linked_students($email);
    $studentId = normalize_yuva_id($requestedStudentId ?? ($_GET['id'] ?? ''));
    if ($studentId === '') {
        $studentId = (string) array_key_first($students);
    }

    if ($studentId === '' || !isset($students[$studentId]) || !parent_can_access_student($email, $studentId)) {
        audit_log_event(parent_actor_id($email), YUVA_ROLE_PARENT, null, 'parent.student_access', 'student', $studentId, false);
        http_response_code(403);
        exit('Access denied.');
    }

    $_SESSION['parent_session_started_at'] = time();
    audit_log_event(parent_actor_id($email), YUVA_ROLE_PARENT, student_organization_id($students[$studentId]), 'parent.student_access', 'student', $studentId, true);
    return ['email' => $email, 'student_id' => $studentId, 'student' => $students[$studentId], 'students' => $students];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
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

function password_policy_error(string $password): string {
    if (strlen($password) < 12) {
        return 'Password must be at least 12 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must include an uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must include a lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must include a number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must include a special character.';
    }
    return '';
}

function normalize_login_identifier(string $value): string {
    $value = clean_text($value);
    if (str_contains($value, '@')) {
        return strtolower($value);
    }
    return normalize_yuva_id($value);
}

function student_accounts(): array {
    return read_json_file(student_accounts_file(), []);
}

function write_student_accounts(array $accounts): void {
    write_json_file(student_accounts_file(), $accounts);
}

function create_student_account(string $yuvaId, string $studentEmail, string $parentEmail, string $password): void {
    $yuvaId = normalize_yuva_id($yuvaId);
    if ($yuvaId === '' || password_policy_error($password) !== '') {
        return;
    }

    $accounts = student_accounts();
    $accounts[$yuvaId] = [
        'yuva_id' => $yuvaId,
        'student_email' => strtolower(trim($studentEmail)),
        'parent_email' => strtolower(trim($parentEmail)),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'status' => 'active',
        'email_verified' => false,
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ];
    write_student_accounts($accounts);
}

function find_student_account_by_identifier(string $identifier): ?array {
    $identifier = normalize_login_identifier($identifier);
    foreach (student_accounts() as $account) {
        $yuvaId = normalize_yuva_id((string) ($account['yuva_id'] ?? ''));
        $studentEmail = strtolower((string) ($account['student_email'] ?? ''));
        if ($identifier === $yuvaId || $identifier === $studentEmail) {
            return $account;
        }
    }
    return null;
}

function login_rate_limited(string $identifier): bool {
    $identifier = normalize_login_identifier($identifier);
    $attempts = read_json_file(login_attempts_file(), []);
    $record = $attempts[$identifier] ?? [];
    $count = (int) ($record['count'] ?? 0);
    $lastAt = (int) ($record['last_at'] ?? 0);
    return $count >= 5 && (time() - $lastAt) < 900;
}

function record_login_attempt(string $identifier, bool $success): void {
    $identifier = normalize_login_identifier($identifier);
    $attempts = read_json_file(login_attempts_file(), []);
    if ($success) {
        unset($attempts[$identifier]);
        write_json_file(login_attempts_file(), $attempts);
        return;
    }
    $record = $attempts[$identifier] ?? ['count' => 0, 'last_at' => 0];
    $lastAt = (int) ($record['last_at'] ?? 0);
    $count = (time() - $lastAt) > 900 ? 0 : (int) ($record['count'] ?? 0);
    $attempts[$identifier] = [
        'count' => $count + 1,
        'last_at' => time(),
    ];
    write_json_file(login_attempts_file(), $attempts);
}

function default_hub_settings(): array {
    $defaultSchedulerEmbed = '<iframe src="https://scheduler.zoom.us/rakesh-nair-ora63i/yuva-club-1?embed=true" frameborder="0" style="width: 750px; height: 560px;"></iframe>';
    $defaultZoomUrl = 'https://us06web.zoom.us/s/82094865538#success';
    return [
        'junior_session_date' => '',
        'junior_session_start' => '',
        'junior_session_end' => '',
        'junior_session_status' => 'Closed',
        'junior_zoom_url' => $defaultZoomUrl,
        'junior_zoom_meeting_id' => '820 9486 5538',
        'junior_zoom_password' => 'Yuva2026',
        'junior_scheduler_embed' => $defaultSchedulerEmbed,
        'junior_session_title' => 'School Yuva Session',
        'senior_session_date' => '',
        'senior_session_start' => '',
        'senior_session_end' => '',
        'senior_session_status' => 'Closed',
        'senior_zoom_url' => $defaultZoomUrl,
        'senior_zoom_meeting_id' => '820 9486 5538',
        'senior_zoom_password' => 'Yuva2026',
        'senior_scheduler_embed' => $defaultSchedulerEmbed,
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
        'Membership' => ['Membership Type', 'Organization Code'],
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
    $email = normalize_email($email);
    return $email === YUVA_PLATFORM_ADMIN_EMAIL
        && $email === normalize_email((string) ($credentials['email'] ?? ''))
        && hash_equals((string) ($credentials['password_hash'] ?? ''), password_hash_for_admin($password));
}

function authenticate_admin_account(string $email, string $password): ?array {
    $email = normalize_email($email);
    if (admin_password_matches($email, $password)) {
        return [
            'id' => admin_actor_id(YUVA_PLATFORM_ADMIN_EMAIL),
            'email' => YUVA_PLATFORM_ADMIN_EMAIL,
            'role' => YUVA_ROLE_MASTER_ADMIN,
            'organization_id' => YUVA_PLATFORM_ORGANIZATION_ID,
            'redirect' => 'admin.php',
        ];
    }

    $organizationAdmin = organization_admin_password_matches($email, $password);
    if ($organizationAdmin !== null) {
        return [
            'id' => admin_actor_id($email),
            'email' => $email,
            'role' => YUVA_ROLE_ORGANIZATION_ADMIN,
            'organization_id' => normalize_organization_id((string) ($organizationAdmin['organization_id'] ?? '')),
            'redirect' => 'organization-admin.php',
        ];
    }

    return null;
}

function current_admin_identity(): ?array {
    if (($_SESSION['admin_logged_in'] ?? false) !== true) {
        return null;
    }

    $email = normalize_email((string) ($_SESSION['admin_email'] ?? ''));
    $role = (string) ($_SESSION['admin_role'] ?? '');
    $organizationId = (string) ($_SESSION['admin_organization_id'] ?? YUVA_PLATFORM_ORGANIZATION_ID);
    $startedAt = (int) ($_SESSION['admin_session_started_at'] ?? 0);
    if ($email === '' || $role === '' || $startedAt <= 0 || (time() - $startedAt) > YUVA_ADMIN_SESSION_TTL_SECONDS) {
        return null;
    }

    if ($role === YUVA_ROLE_ORGANIZATION_ADMIN) {
        $account = organization_admin_by_email($email);
        if ($account === null || ($account['status'] ?? '') !== 'active' || empty($account['email_verified'])) {
            return null;
        }
        $organizationId = normalize_organization_id((string) ($account['organization_id'] ?? $organizationId));
    }

    return [
        'id' => admin_actor_id($email),
        'email' => $email,
        'role' => $role,
        'organization_id' => $organizationId,
    ];
}

function require_admin(array $allowedRoles = [YUVA_ROLE_MASTER_ADMIN]): array {
    $admin = current_admin_identity();
    if ($admin === null) {
        audit_log_event(null, 'Unknown', null, 'admin.access', basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'admin')), null, false, ['reason' => 'missing_or_expired_session']);
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_email'], $_SESSION['admin_role'], $_SESSION['admin_organization_id'], $_SESSION['admin_session_started_at']);
        redirect_to('admin-login.php');
    }

    if (!in_array($admin['role'], $allowedRoles, true)) {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.access', basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'admin')), null, false, ['reason' => 'role_denied']);
        http_response_code(403);
        exit('Access denied.');
    }

    if ($admin['role'] === YUVA_ROLE_MASTER_ADMIN && $admin['email'] !== YUVA_PLATFORM_ADMIN_EMAIL) {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.access', 'master_admin', $admin['email'], false, ['reason' => 'invalid_master_admin_email']);
        http_response_code(403);
        exit('Access denied.');
    }

    $_SESSION['admin_session_started_at'] = time();
    audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.access', basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'admin')), null, true);
    return $admin;
}

function require_admin_post(array $allowedRoles = [YUVA_ROLE_MASTER_ADMIN]): array {
    $admin = require_admin($allowedRoles);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
        audit_log_event($admin['id'], $admin['role'], $admin['organization_id'], 'admin.post.rejected', basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'admin')), null, false, ['reason' => 'csrf_or_method']);
        redirect_to('admin.php?status=security-error');
    }
    return $admin;
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

function openai_api_key(): string {
    $key = trim((string) (getenv('OPENAI_API_KEY') ?: ($_SERVER['OPENAI_API_KEY'] ?? '')));
    if ($key !== '') {
        return $key;
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

function portal_header(string $title): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | Yuva Club</title>';
    echo '<meta name="description" content="Yuva Club student leadership portal.">';
    echo '<link rel="canonical" href="https://www.yuvaclub.app">';
    echo '<meta property="og:site_name" content="YUVA Club">';
    echo '<meta property="og:url" content="https://www.yuvaclub.app">';
    echo '<meta property="og:image" content="https://www.yuvaclub.app/assets/logo.png">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:title" content="YUVA Club">';
    echo '<meta name="twitter:description" content="Empowering Young Minds to Learn, Lead and Inspire.">';
    echo '<meta name="twitter:image" content="https://www.yuvaclub.app/assets/logo.png">';
    echo '<script type="application/ld+json">{"@context":"https://schema.org","@type":"EducationalOrganization","name":"YUVA Club","url":"https://www.yuvaclub.app","description":"Empowering Young Minds to Learn, Lead and Inspire."}</script>';
    echo '<link rel="icon" href="assets/logo.png" type="image/png">';
    echo '<link rel="apple-touch-icon" href="assets/app-icon-180.png">';
    echo '<link rel="manifest" href="manifest.webmanifest">';
    echo '<meta name="theme-color" content="#062856">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-title" content="YUVA Club">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    echo '<link rel="stylesheet" href="assets/site.css?v=20260614-large-photos">';
    echo '<script src="assets/app.js" defer></script>';
    echo '</head><body>';
    echo '<header class="site-header"><a class="brand" href="index.html" aria-label="Yuva Club home"><img src="assets/logo.png" alt="Yuva Club logo" width="78" height="78"><span>Yuva Club</span></a>';
    echo '<nav class="nav" aria-label="Main navigation"><a href="index.html">Home</a><a href="programs.html">Programs</a><a href="challenges.html">Challenges</a><a href="curriculum.html">Topics</a><a href="stories.html">Stories</a><a href="leaderboard.php">Leaderboard</a><a href="app.html">App</a><a href="safety.html">Safety</a><a href="registration.php">Register</a><a href="portal-login.php">Student Portal</a><a href="parent-login.php">Parent</a><a href="admin-login.php">Admin</a></nav></header>';
}

function portal_footer(): void {
    echo '
  <footer class="site-footer">
    <div>
      <strong>YUVA Club</strong>
      <p>Empowering Young Minds to Learn, Lead and Inspire.</p>
      <p>&copy; 2026 YUVA Club. All Rights Reserved.</p>
      <p><a href="privacy.html">Privacy Policy</a> <a href="terms.html">Terms of Service</a> <a href="consent.html">Consent Policy</a> <a href="safety.html">Safety Guidelines</a> <a href="contact.html">Contact</a></p>
    </div>
  </footer></body></html>';
}
