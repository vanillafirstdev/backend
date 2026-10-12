<?php
// public/index.php — the front controller. Every request lands here.
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// CORS preflight: answer and stop before routing (see app/Http.php).
if ($method === 'OPTIONS') {
    Http::answerPreflight();
    return;
}

$router = new Router();

// 404 and 405 answer in the same JSON shape as every other error.
$router->onError(function (int $status, string $message): void {
    json_error($status, $message);
});

$bookmarks = new Controllers\BookmarkController();

// A map of the API, so GET / tells a newcomer what's here.
$router->get('/', function (): void {
    json([
        'name'      => 'VanillaFirst Backend Kit',
        'endpoints' => [
            'GET /api/bookmarks',
            'GET /api/bookmarks/{id}',
            'POST /api/bookmarks',
            'PATCH /api/bookmarks/{id}',
            'DELETE /api/bookmarks/{id}',
        ],
    ]);
});

$router->get('/api/bookmarks', [$bookmarks, 'index']);
$router->post('/api/bookmarks', [$bookmarks, 'store']);
$router->get('/api/bookmarks/{id}', [$bookmarks, 'show']);
$router->patch('/api/bookmarks/{id}', [$bookmarks, 'update']);
$router->delete('/api/bookmarks/{id}', [$bookmarks, 'destroy']);

$router->dispatch(request_path(), $method);
