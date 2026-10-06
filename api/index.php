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
    'APP_NAME' => 'Taqreer',
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:vuXAcS0xGgbqO+IeN9GxVzf0W/lYnYWnE1pdqluhOvM=',
    'APP_DEBUG' => 'true',
    'APP_LOCALE' => 'id',
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
    'VIEW_COMPILED_PATH' => '/tmp/storage/framework/views',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'debug',
    'DB_CONNECTION' => 'pgsql',
    'DB_SSLMODE' => 'require',
    'DB_EMULATE_PREPARES' => 'true',
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_ENCRYPT' => 'false',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'UPLOADS_DISK' => 's3',
    'AWS_USE_PATH_STYLE_ENDPOINT' => 'true',
    'MAIL_MAILER' => 'log',
];

// Map Supabase / Vercel DATABASE_URL or POSTGRES_URL to DB_URL if not explicitly set
if (getenv('DB_URL') === false) {
    if (getenv('DATABASE_URL') !== false) {
        putenv('DB_URL='.getenv('DATABASE_URL'));
        $_ENV['DB_URL'] = $_SERVER['DB_URL'] = getenv('DATABASE_URL');
    } elseif (getenv('POSTGRES_URL') !== false) {
        putenv('DB_URL='.getenv('POSTGRES_URL'));
        $_ENV['DB_URL'] = $_SERVER['DB_URL'] = getenv('POSTGRES_URL');
    }
}

foreach ($serverlessDefaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

$storagePath = getenv('LARAVEL_STORAGE_PATH') ?: '/tmp/storage';

foreach (['app/private', 'app/public', 'fonts', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir("{$storagePath}/{$directory}")) {
        @mkdir("{$storagePath}/{$directory}", 0755, true);
    }
}

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $e) {
    header('Content-Type: text/html; charset=utf-8', true, 500);
    echo '<h1>Serverless Fatal Error</h1>';
    echo '<p><strong>'.htmlspecialchars($e->getMessage()).'</strong></p>';
    echo '<p>'.htmlspecialchars($e->getFile()).':'.$e->getLine().'</p>';
    echo '<pre>'.htmlspecialchars($e->getTraceAsString()).'</pre>';
}
