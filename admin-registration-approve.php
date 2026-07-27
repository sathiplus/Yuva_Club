<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin.php?status=sql-registration-invalid#sql-registrations');
}
if (!sql_approval_enabled()) {
    redirect_to(
        'admin.php?status=sql-registration-unavailable#sql-registrations'
    );
}
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('admin.php?status=sql-registration-invalid#sql-registrations');
}

$registrationId = filter_var(
    $_POST['registration_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
if (!is_int($registrationId)) {
    redirect_to('admin.php?status=sql-registration-invalid#sql-registrations');
}

try {
    $adminUserId = find_sql_admin_user_id(
        (string) ($_SESSION['admin_email'] ?? '')
    );
    if ($adminUserId === null) {
        throw new RuntimeException('SQL admin identity is unavailable.');
    }

    approve_registration($registrationId, $adminUserId);
    unset($_SESSION['csrf_token']);
    redirect_to(
        'admin.php?status=sql-registration-approved#sql-registrations'
    );
} catch (Throwable $error) {
    $correlationId = bin2hex(random_bytes(12));
    error_log(
        'YUVA SQL registration approval failed'
        . ' correlation=' . $correlationId
        . ' exception_type=' . get_class($error)
    );
    redirect_to('admin.php?status=sql-registration-error#sql-registrations');
}
