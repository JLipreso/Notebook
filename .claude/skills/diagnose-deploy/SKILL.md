---
name: diagnose-deploy
description: Use when a GitHub Actions run is red, a build fails in CI but works locally, a deployed site 404s, or someone says "the deploy failed" / "the pipeline is red" / "the site is broken". Decision tree of real failure modes for this stack. Diagnose BEFORE changing anything.
---

# diagnose-deploy — decision tree

> **Provenance:** Notebook has not deployed yet, so every entry below is carried over from **Exploria**, which runs this exact stack (Laravel 12 + Vue/pnpm → Hostinger VPS, CyberPanel/OpenLiteSpeed, GitHub Actions) and hit all of them for real. Treat them as the likely-first-suspects list. **When Notebook hits a failure of its own, add it here and to the learnings ledger in the same sitting** — that is how this file earns its keep.

Master references: the org guides at `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\VPS-Management\` (Vue §11 failure table, Laravel failure table). This file is the project fast path.

## Step 0 — read the failing STEP, not just the run

```
gh run list --workflow <name> --limit 3
gh run view <id> --log-failed
```

Everything before the failing step succeeded — don't redo it, and don't rebuild a theory the log already answers.

## Decision tree (observed-frequency order)

1. **Failed at a smoke test, but rsync/artisan steps were green?** The deploy itself landed; this is a serving problem. Probe the origin over SSH before touching anything.
   - SPA: unknown path 404s at origin but the homepage is 200 → **OpenLiteSpeed negative-cache on a freshly-created vhost.** Self-heals in minutes → *Re-run failed jobs*; instant fix `systemctl restart lsws` (root). A vhost created minutes before the deploy can outlast even a 2-minute retry window.
   - Laravel: `/` works, `/up` + `/api/*` 404, but `/index.php/up` returns 200 → same stale-vhost state → `systemctl restart lsws`.
   - Public DNS smoke warns but the origin passes → Cloudflare cache/bot detection. **Not a deploy failure.** Purge the CF cache for that hostname after its first deploy.
2. **`Setup SSH` failed with no `::error::` line** → transient `ssh-keyscan` blip; just re-run. If it persists, check the `*_SSH_HOST` secret — it must be the raw IP, never a CDN-proxied FQDN.
3. **An `.env` guard hard-failed** (`APP_ENV`/`APP_DEBUG`/`VITE_USE_MOCK`/`VITE_DEMO_MODE`/API base) → the corresponding `*_ENV_PROD` secret is wrong. Fix the secret and re-run. **These guards are intentional — never weaken one to make a pipeline green.**
4. **Seeder or file-not-found on the server** → something reads a path outside `backend/`; rsync ships only that directory, so monorepo siblings (`../packages/...`) do not exist on the VPS. Bundle the file into the release in the workflow and add a path fallback in code.
5. **`vue-tsc` build failure in CI but "works locally"** → run `pnpm install --frozen-lockfile && pnpm build:<app>` locally. Cause is either a stale local `node_modules` or a strict check dev mode skips.
6. **`rsync: Permission denied`** → root-owned files in the docroot (`chown -R <user>:<user>`), or `authorized_keys` perms drifted.
7. **Backend test job red** → a real test failure. Fix the code; do not touch the deploy job.
8. **`ERR_PNPM_BAD_PM_VERSION`** → `pnpm/action-setup@v4` reads `packageManager` from the root `package.json`; passing `version:` as well fails. Workspace installs always run at the repo **root**, never with an app as `working-directory`.

## Hard rules

- **NEVER "Re-run failed jobs" after editing the workflow file** — re-runs use the workflow from the original run. Push, or use `workflow_dispatch`.
- Never weaken the backend rsync exclude list — the site directory doubles as the SSH user's home, so `--delete` without excludes wipes `authorized_keys` and locks you out.
- Diagnose over SSH with an `scp`'d script, never complex inline PowerShell → ssh commands (quoting corrupts results silently).
- Before closing the incident: log any NEW failure mode in `document/0000-00-00-000-Memory/001-Learnings.md`, and if it's org-general, in the matching Foxcity guide too.
