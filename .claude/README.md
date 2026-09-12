# .claude — Shared AI Work Sync

This folder is **committed to git** and shared between every machine, every developer, and every AI session. It exists so work done in one session is instantly visible to the next.

Personal preferences (editor habits, tone, machine-local paths) do NOT belong here — they live in each developer's own `~/.claude/`.

## Layout

```
.claude/
  README.md                 ← this file
  settings.json             ← shared harness settings (checked in; personal additions go in settings.local.json)
  memory/
    current-status.md       ← ALWAYS READ FIRST: where the project is right now, what's next
    decisions.md            ← locked decisions log (Lead Developer only adds entries)
  worklog/
    YYYY-MM-DD-NNN-topic.md ← one file per working session, append-only
  skills/
    <name>/SKILL.md         ← checked-in playbooks; every session loads them automatically
```

## Rules

1. **Start of every session:** read [memory/current-status.md](memory/current-status.md), then the [CLAUDE.md](../CLAUDE.md) at repo root.
2. **End of every session:** write a worklog entry (`worklog/YYYY-MM-DD-NNN-topic.md`) — what was done, files touched, what's next, any blockers. Update `memory/current-status.md` to match.
3. **Decisions:** when the Lead Developer (Jason Lipreso) answers an open question or locks a choice, record it in [memory/decisions.md](memory/decisions.md) with the date and an ID. Locked decisions are final — do not re-litigate them in code review or in a later session.
4. Worklog entries are append-only history — never rewrite an old one; add an addendum to the same day's file instead. `current-status.md` is a living document — always overwrite it to reflect reality.
5. Keep entries short and factual. Link to `document/` plans instead of duplicating them.
6. **Never put credentials or secrets in this folder.**

## settings.json

**Claude has standing permission to create, edit, and write any file under `.claude/` without asking** (D-006). Two rules carry it:

```json
"allow": ["Edit(.claude/**)", "Write(.claude/**)"]
```

`Edit(...)` rules already cover every file-modification tool (Edit, Write, NotebookEdit); `Write(.claude/**)` is stated explicitly so the intent survives anyone reading the file and doesn't depend on that one implication. `**` reaches every depth, so new files and new subdirectories — a fresh `worklog/` entry, a whole new `skills/<name>/SKILL.md` — are covered, not just edits to files that already exist.

**Why:** the session rituals above mean the agent writes here constantly. Prompting on every status update trains people to skip the ritual, and a skipped ritual is how the next session ends up flying blind.

**Scope is deliberately session state only.** Nothing outside `.claude/` is granted — source code, `document/`, and `scripts/` keep whatever prompting posture the developer runs. Widening this is a decision, not a convenience: record it in [memory/decisions.md](memory/decisions.md) if it ever changes.

This file is checked in, so the grant follows the repo to every machine. Personal additions go in `settings.local.json` (gitignored) — never edit this file for a machine-local preference.

Validate after editing (a malformed file silently disables the whole thing):

```
node -e "JSON.parse(require('fs').readFileSync('.claude/settings.json','utf8'))"
```

If a session still prompts on a `.claude/` write, its settings were loaded before this file existed — open `/hooks` once to reload, or restart the session.

## Adding a skill

A skill is a folder with a `SKILL.md`: YAML frontmatter (`name`, `description` — the description's trigger phrases decide when it fires) plus the playbook body. Write them **from real incidents, not speculation**: a diagnostic skill is the crystallized form of a debugging session you never want to repeat. When an incident teaches something new, update the skill in the same sitting.

Current catalog: `help`, `whats-live`, `diagnose-deploy`, `refresh-docs`. Rationale and the growth path: [CLAUDE.md §10](../CLAUDE.md).
