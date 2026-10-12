<?php
// app/bootstrap.php — every request starts here, via public/index.php.
// Order matters: config, then errors, then helpers and classes, then headers.
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This app requires PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '.');
}

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');

$config = require __DIR__ . '/config.php';
define('APP_ENV', $config['env']);
define('DB_PATH', $config['db_path']);
define('CORS_ORIGINS', $config['cors_origins']);
define('API_KEY', $config['api_key']);

// PHP's own error output is HTML, which would corrupt a JSON response.
// Errors are logged; the exception handler below sends JSON instead.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/app.log');

// json(), json_error(), json_body(), base_path(), request_path().
require __DIR__ . '/helpers.php';

// No Composer: classes load by name from app/.
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Warnings and notices become exceptions, so nothing half-works silently.
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Anything uncaught: log it, answer with a JSON 500. In production the client
// learns nothing about your code; locally you see the message straight away.
set_exception_handler(function (Throwable $ex): void {
    error_log((string) $ex);
    $message = APP_ENV === 'production' ? 'Server error.' : $ex->getMessage() . ' (' . basename($ex->getFile()) . ':' . $ex->getLine() . ')';
    json_error(500, $message);
});

if (PHP_SAPI !== 'cli') {
    Http::sendSecurityHeaders();
    Http::sendCorsHeaders();
}
