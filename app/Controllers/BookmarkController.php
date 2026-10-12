<?php
// app/Controllers/BookmarkController.php — the bookmarks JSON API, one method per route.
//
//   GET    /api/bookmarks        index()    200 + list
//   GET    /api/bookmarks/{id}   show()     200 + one, or 404
//   POST   /api/bookmarks        store()    201 + the new one + Location header
//   PATCH  /api/bookmarks/{id}   update()   200 + the changed one
//   DELETE /api/bookmarks/{id}   destroy()  204, no body
//
// This file is identical in the Backend Kit and the Full-Stack Kit. What differs
// is Http::writeAllowed(): an optional API key in the Backend Kit, a CSRF token
// header in the Full-Stack Kit (whose pages use a session cookie).
declare(strict_types=1);

namespace Controllers;

use Bookmark;
use Database;
use Http;

class BookmarkController
{
    private const COLUMNS = 'id, title, url, note, created_at';

    public function index(): void
    {
        $rows = Database::connect()
            ->query('SELECT ' . self::COLUMNS . ' FROM bookmarks ORDER BY id DESC')
            ->fetchAll();

        json(array_map([Bookmark::class, 'shape'], $rows));
    }

    public function show(string $id): void
    {
        $row = self::find($id);
        if ($row === null) {
            json_error(404, "No bookmark with id {$id}.");
            return;
        }
        json(Bookmark::shape($row));
    }

    public function store(): void
    {
        if (!Http::writeAllowed() || ($data = json_body()) === null) {
            return;
        }

        [$clean, $errors] = Bookmark::validate($data);
        if ($errors) {
            json_error(422, 'Some fields are invalid.', $errors);
            return;
        }

        $pdo = Database::connect();
        // ? placeholders: the values never become part of the SQL.
        $pdo->prepare('INSERT INTO bookmarks (title, url, note) VALUES (?, ?, ?)')
            ->execute([$clean['title'], $clean['url'], $clean['note'] ?? '']);

        $id = $pdo->lastInsertId();
        // 201 Created, and where the new resource lives.
        header('Location: ' . base_path() . '/api/bookmarks/' . $id);
        json(Bookmark::shape(self::find($id)), 201);
    }

    public function update(string $id): void
    {
        if (!Http::writeAllowed()) {
            return;
        }
        if (self::find($id) === null) {
            json_error(404, "No bookmark with id {$id}.");
            return;
        }
        if (($data = json_body()) === null) {
            return;
        }

        // PATCH changes only the fields sent. Send {"note": "..."} to change the note alone.
        [$clean, $errors] = Bookmark::validate($data, partial: true);
        if ($errors) {
            json_error(422, 'Some fields are invalid.', $errors);
            return;
        }
        if (!$clean) {
            json_error(422, 'Send at least one of: title, url, note.');
            return;
        }

        // Column names come from Bookmark's fixed rules, never from the client,
        // so building the SET clause from them is safe. Values still use ?.
        $set = implode(', ', array_map(static fn (string $col): string => "{$col} = ?", array_keys($clean)));
        Database::connect()
            ->prepare("UPDATE bookmarks SET {$set} WHERE id = ?")
            ->execute([...array_values($clean), $id]);

        json(Bookmark::shape(self::find($id)));
    }

    public function destroy(string $id): void
    {
        if (!Http::writeAllowed()) {
            return;
        }
        $stmt = Database::connect()->prepare('DELETE FROM bookmarks WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            json_error(404, "No bookmark with id {$id}.");
            return;
        }
        // 204 No Content: it worked, and there is nothing to send back.
        http_response_code(204);
    }

    private static function find(string $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT ' . self::COLUMNS . ' FROM bookmarks WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
