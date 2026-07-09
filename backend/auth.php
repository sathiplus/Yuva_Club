<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function password_hash_secure(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function password_verify_secure(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function random_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

function token_hash(string $token): string {
    return hash('sha256', $token);
}

function find_user_by_email(string $email): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();
    return is_array($user) ? $user : null;
}

function create_user_account(string $email, string $role, string $displayName, ?string $password = null): int {
    $hash = $password !== null && $password !== '' ? password_hash_secure($password) : null;
    $verifyToken = random_token();

    $stmt = db()->prepare(
        'INSERT INTO users (
            email, password_hash, role, display_name, status,
            email_verification_token_hash, email_verification_expires_at
        ) VALUES (
            :email, :password_hash, :role, :display_name, :status,
            :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR)
        )'
    );
    $stmt->execute([
        'email' => strtolower(trim($email)),
        'password_hash' => $hash,
        'role' => $role,
        'display_name' => $displayName,
        'status' => 'pending',
        'token_hash' => token_hash($verifyToken),
    ]);

    return (int) db()->lastInsertId();
}

function login_user(string $email, string $password): ?array {
    $user = find_user_by_email($email);
    if ($user === null || ($user['password_hash'] ?? '') === '') {
        return null;
    }

    if (!password_verify_secure($password, (string) $user['password_hash'])) {
        return null;
    }

    if (!in_array($user['status'], ['active', 'pending'], true)) {
        return null;
    }

    $stmt = db()->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);

    return $user;
}

function require_role(array $user, array $allowedRoles): void {
    if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
