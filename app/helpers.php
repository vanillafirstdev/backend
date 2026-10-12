<?php
// app/helpers.php — small functions used everywhere.
// base_path() and request_path() are shared, word for word, with the
// Full-Stack Kit and VanillaSaaS Core.
declare(strict_types=1);

/** Send $data as JSON with a status code. */
function json(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

/**
 * Every error has the same shape, so a client needs one piece of code to read them:
 *   { "error": "What went wrong", "fields": { "url": "Why this field is wrong" } }
 * "fields" only appears on validation errors (422).
 */
function json_error(int $status, string $message, array $fields = []): void
{
    $body = ['error' => $message];
    if ($fields) {
        $body['fields'] = $fields;
    }
    json($body, $status);
}

/**
 * Read the request body as a JSON object.
 * Returns the decoded array, or null after sending the error response itself.
 *
 *   $data = json_body();
 *   if ($data === null) return;
 */
function json_body(): ?array
{
    // 415: the client sent something that isn't JSON (e.g. an HTML form).
    $type = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_starts_with($type, 'application/json')) {
        json_error(415, 'Send the body as JSON with the header Content-Type: application/json.');
        return null;
    }

    try {
        $data = json_decode((string) file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        json_error(400, 'The body is not valid JSON: ' . $ex->getMessage() . '.');
        return null;
    }

    // [1, 2, 3] and "text" are valid JSON but not an object with fields.
    if (!is_array($data) || array_is_list($data) && $data !== []) {
        json_error(400, 'The body must be a JSON object, like {"title": "..."}.');
        return null;
    }
    return $data;
}

/**
 * The folder the app is served from: '' at https://api.example.com/,
 * '/my-api' at http://localhost/my-api/ on XAMPP.
 */
function base_path(): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    $requested = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    $inside = static fn (string $dir): bool =>
        $dir === '' || $requested === $dir || str_starts_with($requested, $dir . '/');

    if (!$inside($scriptDir) && str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -strlen('/public'));
    }
    return $inside($scriptDir) ? $scriptDir : '';
}

/** The route being asked for, relative to the app: '/', '/api/bookmarks/7'. */
function request_path(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim(rawurldecode($path), '/');
}
