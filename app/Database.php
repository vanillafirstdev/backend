<?php
// app/Database.php — one PDO connection to one SQLite file.
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Foreign keys ship OFF in SQLite for backwards compatibility.
            $pdo->exec('PRAGMA foreign_keys = ON');
            // WAL: readers don't block the writer. There is still only ONE writer at a time.
            $pdo->exec('PRAGMA journal_mode = WAL');
            // Wait up to 5s for a write lock instead of failing with "database is locked".
            $pdo->exec('PRAGMA busy_timeout = 5000');

            self::installIfNeeded($pdo);
            self::$instance = $pdo;
        }
        return self::$instance;
    }

    // First request: an empty database gets database/schema.sql.
    private static function installIfNeeded(PDO $pdo): void
    {
        $exists = $pdo->query(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'bookmarks'"
        )->fetchColumn();

        if (!$exists) {
            $pdo->exec((string) file_get_contents(APP_ROOT . '/database/schema.sql'));
        }
    }
}
