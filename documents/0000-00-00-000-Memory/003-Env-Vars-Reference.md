# Environment Variables Reference

> **AUTO-GENERATED 2026-09-12** by `scripts/refresh-docs.mjs` from the committed `.env.example` files — do not edit by hand, rerun the script (or the `/refresh-docs` skill) instead.

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
