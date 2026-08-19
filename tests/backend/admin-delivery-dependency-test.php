<?php
declare(strict_types=1);

function delivery_dependency_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    fwrite(STDOUT, "PASS {$message}\n");
}

$root = dirname(__DIR__, 2);
$saveHandler = file_get_contents($root . '/admin-delivery-save-draft.php');
$applyHandler = file_get_contents($root . '/admin-delivery-apply.php');
$repository = file_get_contents($root . '/backend/delivery/SqlDeliveryReviewRepository.php');

delivery_dependency_assert(is_string($saveHandler), 'Save Draft handler is readable');
delivery_dependency_assert(is_string($applyHandler), 'Apply handler is readable');
delivery_dependency_assert(is_string($repository), 'delivery SQL repository is readable');

$dependency = "require_once __DIR__ . '/backend/repositories.php';";
foreach (['Save Draft' => $saveHandler, 'Apply' => $applyHandler] as $name => $handler) {
    delivery_dependency_assert(substr_count($handler, $dependency) === 1, "{$name} loads the SQL Admin dependency exactly once");
    delivery_dependency_assert(
        strpos($handler, $dependency) < strpos($handler, 'find_sql_admin_user_id('),
        "{$name} loads the SQL Admin dependency before use"
    );
    delivery_dependency_assert(
        strpos($handler, 'require_admin_post(') < strpos($handler, 'find_sql_admin_user_id('),
        "{$name} preserves authorization and CSRF before SQL Admin lookup"
    );
}

delivery_dependency_assert(str_contains($saveHandler, "(string)(\$_POST['version']??'')"), 'Save Draft preserves rowversion concurrency input');
delivery_dependency_assert(str_contains($repository, "'SERIALIZABLE'"), 'Apply preserves serializable transaction behavior');

require_once $root . '/portal-lib.php';
delivery_dependency_assert(!function_exists('find_sql_admin_user_id'), 'portal bootstrap alone does not define SQL Admin lookup');
require_once $root . '/backend/repositories.php';
delivery_dependency_assert(function_exists('find_sql_admin_user_id'), 'SQL Admin lookup is available after handler bootstrap');
