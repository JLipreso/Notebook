---
name: whats-live
description: Use when someone asks "is the site up", "what's deployed", "check production", "is the API alive", or wants a status sweep of the deployed environments. READ-ONLY probes; reports status per site.
---

# whats-live — probe the deployed environments

## Step 0 — is anything deployed?

Check [CLAUDE.md §9](../../../CLAUDE.md) and `.claude/memory/current-status.md`.

**While §9 still says "Not wired yet", the correct answer is: nothing is deployed.** Say so plainly, name what's missing (no CI, no hosts, no client environment confirmed — Q-004 in `decisions.md`), and stop. Do not go hunting for URLs that don't exist, and do not probe Exploria's hosts by mistake.

## Once deployment lands — fill this in

Replace the placeholders below in the same commit that wires the first deploy, then delete this instruction.

### Public checks (through the CDN — informational)

```
https://<apex>/                    (expect 200)
https://<api-host>/up              (Laravel health, expect 200)
https://<api-host>/api/health      (JSON envelope, expect env=production)
```

Cloudflare bot-detection can 404 automated requests — **a public failure is a WARNING, not a verdict.**

### Authoritative origin checks (SSH, definitive)

Probe the origin directly with an explicit `Host:` header, using the per-site deploy key:

```
ssh -i ~/.ssh/<key> <site-user>@<vps-ip> "curl -s -o /dev/null -w '%{http_code}' -H 'Host: <hostname>' http://127.0.0.1/up"
```

Never build complex probe commands inline through PowerShell → ssh — quoting mangles `(`, `%{...}` and nested quotes **silently**, so you get wrong results rather than errors. `scp` a script and run `bash /tmp/probe.sh` instead.

For SPAs, also verify the fallback: an unknown path (`/_probe_<random>`) must return the app's `index.html`, not a 404.

## Report format

One row per site: name, public HTTP code, origin HTTP code (if checked), verdict. Call out anything known-degraded (pending DNS cutover, missing credentials, queue worker down) explicitly rather than letting a green table imply everything works.
