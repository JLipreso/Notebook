# Memory — evergreen project knowledge

The `0000-00-00-000` prefix sorts this folder above every dated task folder, because it is the one folder that is never "done". Everything here describes how the project **is**, not what a particular task **did**.

| File | What it is | Maintained by |
|---|---|---|
| [001-Learnings.md](001-Learnings.md) | The learnings ledger — non-obvious gotchas that cost real debugging time | Hand-written, append-only. Add an entry **before closing an incident** |
| [002-Endpoints-Reference.md](002-Endpoints-Reference.md) | Every API route | **Auto-generated** — `node scripts/refresh-docs.mjs` (`/refresh-docs`). Never hand-edit |
| [003-Env-Vars-Reference.md](003-Env-Vars-Reference.md) | Every environment variable, from the committed `.env.example` templates | **Auto-generated** — same script. Never hand-edit |
| `004+` | Reserved: one behavior doc per app once apps exist (`004-<App-Name>.md`), plus infra notes | Hand-written; keep current when behavior changes |

## Rules

- **Anything enumerable gets generated.** If a script can produce the list, a hand-written version of it is already wrong. Add it to `scripts/refresh-docs.mjs` rather than typing it out.
- **Every file added here needs a routing-table row in [CLAUDE.md](../../CLAUDE.md) §0** — a subsystem doc with no row doesn't exist, because nothing will ever route a session to it.
- Task-specific narrative (what was built, why, verification evidence) belongs in the dated task folder's `plan/completion/` notes, not here. This folder points at those notes; it doesn't duplicate them.
