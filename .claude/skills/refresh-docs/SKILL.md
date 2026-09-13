---
name: refresh-docs
description: Use when routes, controllers, or env templates changed, or someone asks to "update the reference docs" / "regenerate the endpoints list" / notices the endpoint or env-var reference is stale. Regenerates the auto-generated references under documents/0000-00-00-000-Memory/.
---

# refresh-docs — regenerate the auto-generated references

1. From the repo root run: `node scripts/refresh-docs.mjs`
   - **Endpoints** prefer `php artisan route:list --json` (exact, includes middleware); falls back to statically parsing `backend/routes/api.php` when PHP or `vendor/` isn't available. The banner line in the output file says which path was used.
   - **Env vars** parse the committed `.env.example` templates (`backend/` and every `apps/*`). Machine-local `.env` / `.env.prod` files are deliberately never read — the generated doc must be identical on every machine.
   - Missing sources are not an error: the script writes an honest "not present yet" placeholder so the file is never silently stale.
2. Regenerates:
   - `documents/0000-00-00-000-Memory/002-Endpoints-Reference.md`
   - `documents/0000-00-00-000-Memory/003-Env-Vars-Reference.md`
3. `git diff` the two files and summarize what actually changed (new/removed endpoints or vars) for the user — the diff is the review.
4. **Do NOT commit automatically.** Ask the user, then commit alongside the change that made them stale.

## Rules

- Never hand-edit the generated files — the banner says so and the next run silently discards your edits. Change the source (`routes/api.php`, `.env.example`) and rerun.
- Keep `routes/api.php` parser-friendly: one `Route::` call per line, banner comments in `// ==== SECTION (task-id) ====` form. The static fallback depends on it.
- Good hygiene: run this in the same commit as any `routes/api.php` or `.env.example` change (CLAUDE.md §6).
