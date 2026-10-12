-- database/schema.sql — runs once, automatically, when the database is empty.
-- The demo table is bookmarks: data anyone can add, with no owner and no login.
-- Need accounts? Use VanillaSaaS Core instead of adding a users table here.
CREATE TABLE IF NOT EXISTS bookmarks (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT NOT NULL,
    url        TEXT NOT NULL,
    note       TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO bookmarks (title, url, note) VALUES
    ('MDN Web Docs', 'https://developer.mozilla.org/', 'HTML, CSS and JavaScript reference'),
    ('PHP Manual', 'https://www.php.net/manual/en/', 'Every function, with user notes'),
    ('SQLite Documentation', 'https://www.sqlite.org/docs.html', 'Including how WAL mode works');
