---
name: help
description: Use when a developer asks "help", "what can you do", "what do you know about this project", "where do I find X", "how do I get started", "what's in this repo", or any discovery question about this repo's knowledge and tooling. READ-ONLY — presents the project's knowledge map and available skills.
---

# help — Notebook repo knowledge map

Present the following, adapted to what the developer asked. Keep it short; link, don't dump. **Read `.claude/memory/current-status.md` first** so the "where we are" line you give is current rather than what this file says.

## Say this first if it's still true

Notebook is at **day zero** — the knowledge scaffold exists, the application does not. `apps/` and `backend/` are empty, and **CLAUDE.md §1 (what the product is) is still TBD**. If someone asks a product question, the honest answer is "the brief hasn't landed — ask the Lead Developer", not a guess.

## Ask-me-anything routing

The repo is self-documenting. [CLAUDE.md](../../../CLAUDE.md) carries a **Context-First routing table** (topic → the doc to read before answering). Headline entry points:

- **Where the project is right now**: `.claude/memory/current-status.md` — read at the start of every session.
- **Why things are the way they are**: `.claude/memory/decisions.md` (D-001…, locked, never re-litigate). It also lists the **open questions Q-001…** that block real work.
- **What happened in past sessions**: `.claude/worklog/`.
- **Gotchas that cost real time**: `documents/0000-00-00-000-Memory/001-Learnings.md` — check BEFORE debugging, append after.
- **Every API endpoint**: `documents/0000-00-00-000-Memory/002-Endpoints-Reference.md` (auto-generated).
- **Every env var**: `documents/0000-00-00-000-Memory/003-Env-Vars-Reference.md` (auto-generated).
- **How the code is meant to be structured**: CLAUDE.md §2 (repo shape), §3 (data flow), §4 (frontend rules), §5 (Laravel conventions).
- **How work is documented**: CLAUDE.md §7 — `documents/<YYYY-MM-DD>-<NNN>-<Kebab-Title>/`.
- **The working example of this whole layout**: the Exploria monorepo at `D:\Software-Dev-Projects\Jazer\Monorepo-Exploria-Restart`.

## Available skills

- `/help` — this map.
- `/whats-live` — probe deployed environments and report status (says "nothing deployed yet" until CLAUDE.md §9 is filled in).
- `/diagnose-deploy` — decision tree for red deploys, seeded from the same-stack failures Exploria hit.
- `/refresh-docs` — regenerate the auto-generated endpoint + env-var references.

## House rules worth repeating

- **Session ritual**: status file at the start, worklog entry + status update at the end.
- **A new subsystem doc without a routing-table row doesn't exist** — add the row in the same commit.
- **Routes or `.env.example` changed? Run `/refresh-docs` in the same commit.**
- **PRs always base on `staging`, never `main`** — documentation PRs included.
- Shared TS types are the API contract; Laravel adapts to them.
- Run `pnpm typecheck` and a real `pnpm build` before pushing shared-package changes.
- Log a real gotcha in the learnings ledger before closing the incident; if it's org-general (deploy, tooling, Windows), also add it to the matching guide in `D:\Software-Dev-Projects\Foxcity-4-Project-Notes\Claude-AI-Guide\`.
