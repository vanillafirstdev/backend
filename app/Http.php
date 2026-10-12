<?php
// app/Http.php — headers sent with every response, CORS, and the API key check.
declare(strict_types=1);

class Http
{
    public static function sendSecurityHeaders(): void
    {
        // An API returns data, never pages, so the browser is told to run nothing
        // and load nothing from a response, and never to frame it.
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Cross-Origin-Opener-Policy: same-origin');
        header_remove('X-Powered-By');

        if (APP_ENV === 'production' && self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    /**
     * CORS: browsers block a page at http://localhost:5500 from reading a
     * response from http://localhost:8000 unless the response says that origin
     * is allowed. Tools like curl ignore CORS entirely: it protects visitors'
     * browsers, not your API. Access control is the API key's job.
     */
    public static function sendCorsHeaders(): void
    {
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

        // Responses differ by Origin, so caches must store them separately.
        header('Vary: Origin');

        if ($origin !== '' && in_array($origin, CORS_ORIGINS, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
    }

    /**
     * Preflight. Before a cross-origin POST with JSON, PATCH or DELETE, the
     * browser sends OPTIONS to ask permission. We answer, then stop.
     */
    public static function answerPreflight(): void
    {
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '' && in_array($origin, CORS_ORIGINS, true)) {
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 600');   // the browser may skip asking again for 10 minutes
        }
        // Not allowed: answer anyway, without the headers. The browser then blocks the real request.
        http_response_code(204);
    }

    /**
     * Writes need the API key once one is configured. Returns true if the request
     * may continue; otherwise sends 401 and returns false.
     */
    public static function writeAllowed(): bool
    {
        if (API_KEY === '') {
            return true;
        }
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        $sent = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';

        // hash_equals compares in constant time, so response timing can't leak the key.
        if ($sent !== '' && hash_equals(API_KEY, $sent)) {
            return true;
        }
        header('WWW-Authenticate: Bearer');
        json_error(401, 'This API needs a key for writes. Send the header Authorization: Bearer <key>.');
        return false;
    }
}
