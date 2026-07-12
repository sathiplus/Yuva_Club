<?php
require __DIR__ . '/portal-lib.php';
unset(
    $_SESSION['student_id'],
    $_SESSION['parent_student_id'],
    $_SESSION['parent_email'],
    $_SESSION['parent_session_started_at'],
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_email'],
    $_SESSION['admin_role'],
    $_SESSION['admin_organization_id'],
    $_SESSION['admin_session_started_at']
);
session_regenerate_id(true);
redirect_to('portal-login.php');
