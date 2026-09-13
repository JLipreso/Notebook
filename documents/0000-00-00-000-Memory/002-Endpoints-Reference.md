# Endpoints Reference

> **AUTO-GENERATED 2026-09-13** by `scripts/refresh-docs.mjs` from `php artisan route:list --json` — do not edit by hand, rerun the script (or the `/refresh-docs` skill) instead.

| Method | URI | Action | Middleware |
|---|---|---|---|
| POST | `/api/auth/device` | Api\AuthController@device | auth:sanctum |
| POST | `/api/auth/firebase` | Api\AuthController@firebase | throttle:auth |
| POST | `/api/auth/logout` | Api\AuthController@logout | auth:sanctum |
| GET | `/api/health` | Closure |  |
| GET | `/api/user` | Api\AuthController@me | auth:sanctum |
| GET | `/up` | Closure |  |
