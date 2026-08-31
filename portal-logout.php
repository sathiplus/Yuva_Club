<?php
require __DIR__ . '/portal-lib.php';
$wasParent = ($_SESSION['portal_role'] ?? null) === 'parent';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
redirect_to($wasParent ? 'parent-login.php' : 'portal-login.php');
