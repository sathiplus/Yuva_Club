<?php
declare(strict_types=1);

function env_value(string $name, string $default = ''): string {
    $value = getenv($name);
    if ($value === false || $value === '') {
        $value = $_SERVER[$name] ?? $default;
    }
    return is_string($value) ? trim($value) : $default;
}

function app_config(): array {
    return [
        'app_env' => env_value('APP_ENV', 'production'),
        'app_url' => rtrim(env_value('APP_URL', 'https://www.yuvaclub.app'), '/'),
        'database' => [
            'driver' => env_value('DB_DRIVER', 'mysql'),
            'host' => env_value('DB_HOST'),
            'port' => env_value('DB_PORT', env_value('DB_DRIVER', 'mysql') === 'sqlsrv' ? '1433' : '3306'),
            'name' => env_value('DB_DATABASE'),
            'user' => env_value('DB_USERNAME'),
            'password' => env_value('DB_PASSWORD'),
            'ssl_ca' => env_value('DB_SSL_CA'),
        ],
        'storage' => [
            'account' => env_value('AZURE_STORAGE_ACCOUNT'),
            'container' => env_value('AZURE_STORAGE_CONTAINER', 'yuva-uploads'),
            'connection_string' => env_value('AZURE_STORAGE_CONNECTION_STRING'),
        ],
        'mail' => [
            'from_email' => env_value('MAIL_FROM_EMAIL', 'noreply@yuvaclub.app'),
            'from_name' => env_value('MAIL_FROM_NAME', 'Yuva Club'),
            'provider' => env_value('MAIL_PROVIDER', 'azure'),
        ],
    ];
}
