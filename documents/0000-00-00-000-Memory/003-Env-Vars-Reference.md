# Environment Variables Reference

> **AUTO-GENERATED 2026-09-13** by `scripts/refresh-docs.mjs` from the committed `.env.example` files — do not edit by hand, rerun the script (or the `/refresh-docs` skill) instead.

Machine-local `.env` / `.env.prod` files are deliberately never parsed — this doc must be identical on every machine. Production values live in GitHub secrets; see [CLAUDE.md](../../CLAUDE.md) §9.

## Backend — development defaults

`backend/.env.example`

| Variable | Note (from file comments) |
|---|---|
| `APP_NAME` |  |
| `APP_ENV` |  |
| `APP_KEY` |  |
| `APP_DEBUG` |  |
| `APP_URL` |  |
| `APP_LOCALE` |  |
| `APP_FALLBACK_LOCALE` |  |
| `APP_FAKER_LOCALE` |  |
| `APP_MAINTENANCE_DRIVER` |  |
| `BCRYPT_ROUNDS` |  |
| `LOG_CHANNEL` |  |
| `LOG_STACK` |  |
| `LOG_DEPRECATIONS_CHANNEL` |  |
| `LOG_LEVEL` |  |
| `DB_CONNECTION` | when it lands, record it in .claude/memory/decisions.md and switch here. |
| `SESSION_DRIVER` |  |
| `SESSION_LIFETIME` |  |
| `SESSION_ENCRYPT` |  |
| `SESSION_PATH` |  |
| `SESSION_DOMAIN` |  |
| `BROADCAST_CONNECTION` |  |
| `FILESYSTEM_DISK` |  |
| `QUEUE_CONNECTION` |  |
| `CACHE_STORE` |  |
| `MEMCACHED_HOST` |  |
| `REDIS_CLIENT` |  |
| `REDIS_HOST` |  |
| `REDIS_PASSWORD` |  |
| `REDIS_PORT` |  |
| `MAIL_MAILER` |  |
| `MAIL_SCHEME` |  |
| `MAIL_HOST` |  |
| `MAIL_PORT` |  |
| `MAIL_USERNAME` |  |
| `MAIL_PASSWORD` |  |
| `MAIL_FROM_ADDRESS` |  |
| `MAIL_FROM_NAME` |  |
| `AWS_ACCESS_KEY_ID` |  |
| `AWS_SECRET_ACCESS_KEY` |  |
| `AWS_DEFAULT_REGION` |  |
| `AWS_BUCKET` |  |
| `AWS_USE_PATH_STYLE_ENDPOINT` |  |
| `VITE_APP_NAME` |  |
| `SANCTUM_STATEFUL_DOMAINS` | Deliberately blank — stateless bearer tokens only (CLAUDE.md §5). |

## Frontend — student/browser (⚠ these values ship to the browser — never put a secret in a `VITE_*` var)

`apps/student/browser/.env.example`

| Variable | Note (from file comments) |
|---|---|
| `VITE_API_URL` | Laravel API base URL (dev backend: php artisan serve) |
| `VITE_USE_MOCK` | Read ONLY by packages/services/datasource.ts. |
| `VITE_DEMO_MODE` | Demo/test-user affordances (CLAUDE.md §4) — must be 'false' against production auth. |
| `VITE_FIREBASE_API_KEY` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_AUTH_DOMAIN` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_PROJECT_ID` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_APP_ID` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_PUSHER_KEY` | Pusher Channels (D-007) |
| `VITE_PUSHER_CLUSTER` | Pusher Channels (D-007) |

## Frontend — student/mobile (⚠ these values ship to the browser — never put a secret in a `VITE_*` var)

`apps/student/mobile/.env.example`

| Variable | Note (from file comments) |
|---|---|
| `VITE_API_URL` | Laravel API base URL (dev backend: php artisan serve) |
| `VITE_USE_MOCK` | Read ONLY by packages/services/datasource.ts. |
| `VITE_DEMO_MODE` | Demo/test-user affordances (CLAUDE.md §4) — must be 'false' against production auth. |
| `VITE_FIREBASE_API_KEY` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_AUTH_DOMAIN` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_PROJECT_ID` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_FIREBASE_APP_ID` | Firebase web app config (public identifiers, safe to commit as placeholders) — D-015 |
| `VITE_PUSHER_KEY` | Pusher Channels (D-007) |
| `VITE_PUSHER_CLUSTER` | Pusher Channels (D-007) |
