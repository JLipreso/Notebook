# Learnings Ledger

Hard-won, non-obvious lessons — one entry per lesson, newest first.
**Append here whenever something costs real debugging time**; the routing table in [CLAUDE.md](../../CLAUDE.md) §0 sends people here before they start debugging.

**Entry bar:** *cost real debugging time AND is non-obvious.* If it wouldn't save the next person an hour, it belongs in a `.claude/worklog/` entry, not here. The **rule is the payload** — write the generalized lesson; the incident is just the evidence.

**Two-tier logging:** project-specific lessons stay here. Org-general ones (deploy patterns, tooling, Windows quirks) go **also** to the matching guide in `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\`. Do both before closing the incident.

---

## Notebook's own lessons

_None yet — this repo is at day zero. First real entry goes here._

## Carried over from Exploria (same stack, not yet hit here)

These were paid for on `Monorepo-Exploria-Restart`, which runs the identical stack (Laravel 12 + PHP 8.2 / Vue 3 + pnpm workspace / GitHub Actions → Hostinger VPS with CyberPanel + OpenLiteSpeed). They are the likely-first-suspects list, not this project's history. **Promote one into the section above the day Notebook actually hits it**, with our own incident as the evidence.

| Date | Area | Rule | Why / incident |
|---|---|---|---|
| 2026-09-07 | Tests/.env | PHPUnit inherits any `.env` value not pinned in `phpunit.xml`. When a real credential lands in a developer's `.env`, pin it empty in `phpunit.xml` — otherwise tests silently change behavior, and a real API key makes "unconfigured" tests place **paid** third-party calls. | Adding real credentials to `.env` broke the 503-unconfigured tests and made the chat test hit the live API. |
| 2026-09-03 | Deploy/OLS | After the FIRST deploy to a freshly-created CyberPanel vhost, expect `.htaccess` rewrites to 404 for minutes — `/` still works because `index.php` is the default doc. Fix: wait and re-run, or `systemctl restart lsws` for an instant fix. The negative-cache clock runs from **vhost creation**, not from the deploy. | Every first deploy hit it; one failed even a 2-minute retry window. |
| 2026-09-03 | Deploy/CDN | After the first successful deploy of any Cloudflare-proxied hostname, **purge that hostname's cache**. A fully green pipeline can coexist with users seeing the old placeholder for the whole cache TTL. | Origin smoke tests never traverse the CDN, so they structurally cannot catch this. |
| 2026-09-03 | Deploy/paths | Anything the backend reads at runtime or seed time MUST live inside `backend/` — rsync ships only that directory, so monorepo siblings (`../packages/...`) do not exist on the server. Bundle the file in the workflow and add a path fallback in code. | A seeder crashed on first deploy reading `../packages/mock/data`. |
| 2026-09-03 | Windows/ssh | Never pass complex commands inline through PowerShell → ssh. Quoting mangles `(`, `%{...}` and nested quotes **silently** — you get wrong *results*, not errors. `scp` a script and run `bash /tmp/x.sh` remotely. | A mangled `Host:` header made a healthy server look broken for an hour during a 404 hunt. |
| 2026-09-03 | Windows/PHP | Never write PHP or `.env` files with PowerShell 5.1 `Set-Content -Encoding utf8` — it adds a BOM that breaks PHP and dotenv parsing. Use `[IO.File]::WriteAllText(..., UTF8Encoding($false))`, or the agent's Write tool. | Cost a debugging session on a config file that "looked identical". |
| 2026-09-03 | CI/builds | Run a real `pnpm build` (not just `pnpm dev`) before pushing app or shared-package changes: `vue-tsc -b` enforces strict checks that dev mode skips, and a stale local `node_modules` can fail builds that CI's fresh install passes — and vice versa. | A build broke on a dependency another machine had added to the lockfile. |
| 2026-09-03 | Workflows | `pnpm/action-setup@v4` reads `packageManager` from the root `package.json`; passing `version:` as well fails with `ERR_PNPM_BAD_PM_VERSION`. Workspace installs always run at the repo **root** — never set an app as `working-directory` for install. | Org-wide convention; see the Foxcity Vue guide §9. |
| 2026-09-03 | GH Actions | **"Re-run failed jobs" uses the workflow file from the ORIGINAL run.** After editing a workflow, push or `workflow_dispatch` — otherwise you are re-running the bug you just fixed. A silent `Setup SSH` failure with no `::error::` line is usually a transient `ssh-keyscan` blip: just re-run. | Confirmed repeatedly. |
| 2026-09-03 | Eloquent | Mass `update()` bypasses model casts, and MariaDB rejects ISO-8601 `'...T...Z'` strings. Always pass Carbon instances, and wrap multi-table state flips in a transaction. | Found live during a payout-run state flip. |
| 2026-09-03 | Tests | `:memory:` sqlite leaks state across test **classes** in a full run — wipe tables explicitly when asserting on an empty database. | A test that passed alone failed in the suite. |
