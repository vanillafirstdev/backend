<?php
// app/config.php — settings. A plain array: open it, change a value, save.
// It lives outside public/, so the web server never serves it.
return [
    // 'local'      → error details included in 500 responses.
    // 'production' → 500 responses say only "Server error"; details go to
    //                storage/logs/app.log. ALWAYS set this on a live server.
    'env' => 'local',

    // The SQLite file. Kept in storage/, never in public/.
    'db_path' => dirname(__DIR__) . '/storage/database/app.sqlite',

    // Front ends on OTHER addresses that may call this API from a browser (CORS).
    // A page served from the same address as the API needs nothing here.
    // Exact matches only: scheme, host and port. No trailing slash, no wildcards.
    'cors_origins' => [
        'http://localhost:5500',      // VS Code Live Server
        'http://127.0.0.1:5500',
        'http://localhost:8080',
    ],

    // Leave empty while developing. Set a long random string before this API
    // is reachable from the internet, and writes (POST, PATCH, DELETE) will
    // need the header:  Authorization: Bearer <api_key>
    // Generate one with:  php -r "echo bin2hex(random_bytes(32));"
    // It's for scripts and tools you control. Anything shipped to a browser is
    // public, so never put this key in front-end JavaScript.
    'api_key' => '',
];
