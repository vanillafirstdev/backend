<?php
// app/Router.php — a small router. Exact paths, plus {name} placeholders.
//
//   $router->get('/about', $handler);              matches /about only
//   $router->get('/api/bookmarks/{id}', $handler); matches /api/bookmarks/7 and
//                                                  calls $handler('7')
//
// A placeholder matches one path segment of digits, letters, - or _.
// Anything else (a slash, a dot, an empty segment) doesn't match, so
// /api/bookmarks/../config never reaches a handler.
//
// This file is identical in the Backend Kit and the Full-Stack Kit. Each kit
// tells the router how to show errors with onError(): JSON in one, a page in the other.
declare(strict_types=1);

class Router
{
    private array $routes = [];

    /** @var callable(int $status, string $message, string[] $allowed): void */
    private $onError;

    public function __construct()
    {
        $this->onError = static function (int $status, string $message): void {
            http_response_code($status);
            echo $message;
        };
    }

    public function get(string $path, callable $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, callable $handler): void    { $this->add('PUT', $path, $handler); }
    public function patch(string $path, callable $handler): void  { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable $handler): void { $this->add('DELETE', $path, $handler); }

    /** How 404 and 405 responses are shown. */
    public function onError(callable $handler): void
    {
        $this->onError = $handler;
    }

    private function add(string $method, string $path, callable $handler): void
    {
        // '/api/bookmarks/{id}' becomes the pattern #^/api/bookmarks/([A-Za-z0-9_-]+)$#
        $pattern = preg_replace('#\\\{[a-z_]+\\\}#', '([A-Za-z0-9_-]+)', preg_quote($path, '#'));
        $this->routes[] = [$method, '#^' . $pattern . '$#', $handler];
    }

    /** $path is relative to the app (see request_path()), so subfolders just work. */
    public function dispatch(string $path, string $method): void
    {
        // HEAD is GET without a body; PHP drops the body for us.
        $lookup = $method === 'HEAD' ? 'GET' : $method;

        $allowed = [];
        foreach ($this->routes as [$verb, $pattern, $handler]) {
            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }
            if ($verb === $lookup) {
                array_shift($matches);              // drop the full match, keep the {placeholders}
                call_user_func_array($handler, $matches);
                return;
            }
            $allowed[] = $verb;
        }

        // The path exists under another method: that's a 405, not a 404.
        if ($allowed) {
            if (in_array('GET', $allowed, true)) {
                $allowed[] = 'HEAD';
            }
            header('Allow: ' . implode(', ', array_unique($allowed)));
            ($this->onError)(405, 'This address doesn\'t accept that kind of request.', $allowed);
            return;
        }

        ($this->onError)(404, 'No route matches this address.', []);
    }
}
