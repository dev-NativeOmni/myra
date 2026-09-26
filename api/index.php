<?php

/**
 * Vercel serverless entry point.
 *
 * Applies the production defaults for a read-only, serverless filesystem, then
 * hands off to Laravel's regular front controller. Secrets (APP_KEY, APP_URL,
 * DB_URL, AWS_*) come from the Vercel project's environment variables, which
 * always take precedence over the defaults below.
 */
$serverlessDefaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_LOCALE' => 'id',
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/storage/framework/views',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'warning',
    'DB_CONNECTION' => 'pgsql',
    'DB_SSLMODE' => 'require',
    'DB_EMULATE_PREPARES' => 'true',
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_ENCRYPT' => 'true',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'UPLOADS_DISK' => 's3',
    'AWS_USE_PATH_STYLE_ENDPOINT' => 'true',
    'MAIL_MAILER' => 'log',
];

foreach ($serverlessDefaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

$storagePath = getenv('LARAVEL_STORAGE_PATH');

foreach (['app/private', 'app/public', 'fonts', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir("{$storagePath}/{$directory}")) {
        mkdir("{$storagePath}/{$directory}", 0755, true);
    }
}

require __DIR__.'/../public/index.php';
