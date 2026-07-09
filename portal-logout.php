<?php
require __DIR__ . '/portal-lib.php';
unset($_SESSION['student_id'], $_SESSION['parent_student_id'], $_SESSION['admin_logged_in']);
redirect_to('portal-login.php');
