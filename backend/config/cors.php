<?php

/*
|--------------------------------------------------------------------------
| CORS — single origin list (2026-09-12-002)
|--------------------------------------------------------------------------
| Consumed ONLY by App\Http\Middleware\Cors (the framework HandleCors is
| removed in bootstrap/app.php). Setting CORS_ALLOWED_ORIGINS in .env
| REPLACES the default list below (comma-separated) — production sets its
| exact origins there.
|
| Add one dev-port entry per app as apps/* are scaffolded (CLAUDE.md §2).
*/

$defaults = implode(',', [
    'http://localhost:5173', // first app — reserve the port when it lands
]);

return [
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', $defaults))
    ))),
];
