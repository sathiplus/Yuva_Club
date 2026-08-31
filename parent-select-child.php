<?php
declare(strict_types=1);
require __DIR__ . '/portal-lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
$selected=portal_parent_login_workflow()->selectChild($_SESSION,normalize_yuva_id((string)($_POST['student_id']??'')),isset($_POST['csrf_token'])?(string)$_POST['csrf_token']:null,portal_network_category($_SERVER['REMOTE_ADDR']??null));
redirect_to($selected?'parent.php':'parent-login.php?status=error');
