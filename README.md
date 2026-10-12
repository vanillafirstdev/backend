# VanillaFirst Backend Kit

> A zero-dependency JSON API template in plain PHP and SQLite.

No `composer install`. No framework, no database server, no HTML. You need **PHP 8.1+** with `pdo_sqlite` enabled, which most PHP installs, XAMPP and shared hosts already have.

---

## Which one do I need?

The VanillaFirst Frontend, Backend and Full-Stack Kits and VanillaSaaS Core follow the same conventions, but they are different starting points. Choose one before you write any code.

Ask two questions: **does anything need saving on a server?** and **will anyone sign in?**

| Your project | Start from |
|---|---|
| Nothing is saved on a server: a portfolio, a static site, a front-end exercise, a page that reads someone else's API | **VanillaFirst Frontend Kit** |
| Data is saved on a server, but this project serves no pages: an API for a separate front end, a mobile app or another service | **VanillaFirst Backend Kit** |
| Data is saved on a server, the same project serves the pages, and nobody signs in: a content site with a contact form, docs, a calculator, an internal tool behind your host's password protection | **VanillaFirst Full-Stack Kit** |
| People sign in, and the public side is a few pages (home, pricing, terms) | **VanillaSaaS Core** on its own. A public page is a page that doesn't call `require_auth()`. |
| People sign in, and the public side is a real content site (docs, a blog, dozens of pages) | **Full-Stack Kit and Core, as two separate sites:** the kit at `example.com`, Core at `app.example.com`. The site's "Sign in" button links to the app. |

Moving between the kits is cheap, because they share files. The Frontend Kit's CSS, `validate.js` and `<bookmark-card>` are identical in the Full-Stack Kit; only its `localStorage` module is swapped for one that calls the server, and the Full-Stack Kit's `/client` page is exactly that swap. The Backend Kit's router, `Bookmark` rules and API controller are identical in the Full-Stack Kit, which serves the same JSON API. Moving between any kit and Core means rewriting every page, because the kits send every request through one front controller and a router, while Core uses one PHP file per address.

The kits double as a learning sequence: front end, back end, full stack. Core is not a fourth step in that sequence. It's where a real project with accounts starts, and its commented code is the reference to read when coursework asks you to build a login yourself.

Don't add registration, login or passwords to any kit. Accounts need password hashing, reset tokens that expire, rate limiting on sign-in, session timeouts, and every query scoped to the user who owns the row. Core has all of that written, commented and free under the MIT licence. Starting on a kit because it looks simpler, then bolting on a login in month two, is the mistake this section exists to prevent.

[Frontend Kit](https://github.com/vanillafirstdev/frontend) · [Backend Kit](https://github.com/vanillafirstdev/backend) · [Full-Stack Kit](https://github.com/vanillafirstdev/fullstack) · [VanillaSaaS Core](https://vanillasaas.dev)

---

## Quick start

```bash
git clone https://github.com/vanillafirstdev/backend.git my-api
cd my-api
php -S localhost:8000 -t public
```

On XAMPP, put the folder in `htdocs` and use `http://localhost/my-api/` as the base address instead.

There are no pages to open. Talk to the API with a tool:

- **`requests.http`**, included: every endpoint and every error case, ready to send. In VS Code install the REST Client extension and click "Send Request" above each one; PhpStorm runs the file natively.
- **curl**, from any terminal: `curl http://localhost:8000/api/bookmarks`
- **The browser**, for GET only: open `http://localhost:8000/api/bookmarks`.

The first request creates `storage/database/app.sqlite` from `database/schema.sql`.

---

## The API

| Method and path | Does | Success | Can fail with |
|---|---|---|---|
| `GET /api/bookmarks` | List all, newest first | 200 + array | |
| `GET /api/bookmarks/{id}` | One bookmark | 200 + object | 404 |
| `POST /api/bookmarks` | Create | 201 + object + `Location` header | 400, 401, 415, 422 |
| `PATCH /api/bookmarks/{id}` | Change only the fields sent | 200 + object | 400, 401, 404, 415, 422 |
| `DELETE /api/bookmarks/{id}` | Delete | 204, no body | 401, 404 |

A bookmark:

```json
{ "id": 1, "title": "MDN Web Docs", "url": "https://developer.mozilla.org/", "note": "", "created_at": "2026-10-12 09:30:00" }
```

Every error has one shape, so a client needs one piece of code to read them:

```json
{ "error": "Some fields are invalid.", "fields": { "url": "Enter a full web address starting with https:// or http://." } }
```

| Status | Means | Example cause |
|---|---|---|
| 400 | The body can't be read | A trailing comma in the JSON, or an array instead of an object |
| 401 | Missing or wrong API key | A write with no `Authorization` header, once a key is set |
| 404 | Nothing there | `/api/bookmarks/99999`, or a mistyped path |
| 405 | Address exists, method doesn't | `PUT /api/bookmarks/1`. The `Allow` header lists what works |
| 415 | Not JSON | A body sent as an HTML form would send it |
| 422 | Readable, but wrong | An empty title, a `javascript:` address, a number where a string belongs |
| 500 | The server's fault | Details in `storage/logs/app.log`; in `local` mode, also in the response |

`requests.http` has a request that triggers each of these.

---

## What's in the box

- **Routing:** `app/Router.php` matches exact paths and `{placeholders}`, which arrive as arguments to the controller method. 404s and 405s (with an `Allow` header) answer in the same JSON shape as every other error. The same router runs the Full-Stack Kit.
- **Input:** `json_body()` refuses anything that isn't `Content-Type: application/json` (415), isn't valid JSON (400) or isn't an object (400). Validation is an allowlist: unknown fields such as `id` are ignored, and every field's type, length and, for `url`, scheme is checked (422, with a message per field).
- **Database:** one PDO connection with foreign keys on, WAL mode and a 5-second busy timeout. Every value goes through a `?` placeholder.
- **Errors:** PHP's HTML error output is switched off because it would corrupt the JSON. Every uncaught error becomes a JSON 500, with the message in `local` mode and nothing but "Server error." in `production`.
- **CORS:** front ends on other addresses listed in `cors_origins` can call the API from a browser, including the preflight `OPTIONS` request before a JSON `POST`, `PATCH` or `DELETE`. Unlisted origins get no CORS headers, so the browser blocks them.
- **API key:** optional. Once set, writes need `Authorization: Bearer <key>`; reads stay open. The `.htaccess` passes the header through, which Apache otherwise drops.
- **Headers:** a CSP that lets a response run and load nothing, `nosniff`, no framing, no referrer, HSTS in production over HTTPS.
- **AI instructions:** `AGENTS.md` tells Copilot, Cursor, Codex and Claude Code the rules of this codebase and how to review against them.

---

## Directory structure

```text
my-api/
├── .htaccess                   # Routes requests into public/ when you can't set the web root
├── AGENTS.md / CLAUDE.md       # Instructions for AI coding assistants
├── requests.http               # Every endpoint and error case
├── public/
│   ├── index.php               # Front controller: preflight, routes, dispatch
│   └── .htaccess
├── app/
│   ├── bootstrap.php           # Config, JSON error handling, helpers, autoloader, headers
│   ├── config.php              # env, database path, cors_origins, api_key
│   ├── helpers.php             # json(), json_error(), json_body(), base_path(), request_path()
│   ├── Bookmark.php            # Validation rules and JSON shape (shared with the Full-Stack Kit)
│   ├── Router.php  Http.php  Database.php
│   └── Controllers/BookmarkController.php   # Shared with the Full-Stack Kit
├── database/
│   └── schema.sql              # Runs once on an empty database
└── storage/                    # Writable by PHP
    ├── database/               # app.sqlite (+ -wal, -shm)
    └── logs/                   # app.log
```

---

## Adding an endpoint

1. Add a method to a controller. Placeholders arrive as string arguments: `public function show(string $id)`.
2. Register it in `public/index.php`: `$router->get('/api/things/{id}', [$things, 'show']);`
3. Add requests to `requests.http`: one that works, and one for each way it can fail.

---

## Calling it from a front end

From the VanillaFirst Frontend Kit served by VS Code Live Server (`http://localhost:5500`), with this API on `http://localhost:8000`:

```js
const response = await fetch("http://localhost:8000/api/bookmarks", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ title: "MDN", url: "https://developer.mozilla.org/" }),
});
const body = await response.json();
if (!response.ok) console.log(body.error, body.fields);
```

That works because `http://localhost:5500` is in `cors_origins`. Serve the front end from any other address and the browser reports a CORS error while curl keeps working: add the exact origin (scheme, host and port) to the list.

`localhost` and `127.0.0.1` are different origins to a browser. Both are listed for that reason.

---

## Deployment

1. Copy the project to any host running PHP 8.1+ with `pdo_sqlite`. A subdomain such as `api.example.com` keeps it apart from your site.
2. Point the document root at `public/` if you can. If you can't, the root `.htaccess` handles it on Apache.
3. Make `storage/` writable by the PHP user.
4. In `app/config.php`: set `'env' => 'production'`, replace `cors_origins` with your real front-end address, and set an `api_key`.

---

## Limits you should know

- **Without an API key, anyone who finds the address can write.** Fine on `localhost`; not fine on the internet. Set the key before deploying.
- **The key isn't for browsers.** Anything in front-end JavaScript is readable by every visitor. A public front end that must write needs accounts, and accounts mean VanillaSaaS Core.
- **No rate limiting.** A client can send as many requests as it likes. Put the API behind your host's or Cloudflare's rate limits if it's public.
- **No pagination.** `GET /api/bookmarks` returns every row. Add `LIMIT` and `OFFSET` (and say so in the response) before a table grows past a few thousand rows.
- **One writer at a time, one server.** SQLite's limits: writes queue behind each other, and the database is a file on one machine.

---

## Where this fits

The Frontend Kit builds the bookmark list against `localStorage`. This kit builds the same data as an API. The Full-Stack Kit puts both in one project: its `/client` page is the Frontend Kit's code calling this kit's API, unchanged apart from the data module. `Router.php`, `Bookmark.php` and `BookmarkController.php` are identical in both kits. The one difference is `Http::writeAllowed()`: an API key here, a CSRF token there, because there the pages run on a session cookie.

---

## License

MIT. Free for personal, open-source and commercial use.
