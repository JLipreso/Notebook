# Notebook

A W Labs client project. Monorepo: Laravel 12 API in `backend/`, Vue 3 + Vite apps in a pnpm workspace under `apps/` and `packages/`.

> **Status: day zero.** The repository is bootstrapped with its documentation and AI scaffold; no application code exists yet.

## Start here

**[CLAUDE.md](CLAUDE.md) is the entry point** for every developer and every AI session. It carries the Context-First routing table (what you're asking about → the doc to read first), the architecture rules, the git flow, and the conventions that keep all of it accurate.

| I want to… | Go to |
|---|---|
| Know where the project is right now | [.claude/memory/current-status.md](.claude/memory/current-status.md) |
| Know why something is the way it is | [.claude/memory/decisions.md](.claude/memory/decisions.md) |
| Avoid re-debugging a known gotcha | [documents/0000-00-00-000-Memory/001-Learnings.md](documents/0000-00-00-000-Memory/001-Learnings.md) |
| Find an endpoint or an env var | [002-Endpoints-Reference.md](documents/0000-00-00-000-Memory/002-Endpoints-Reference.md) · [003-Env-Vars-Reference.md](documents/0000-00-00-000-Memory/003-Env-Vars-Reference.md) (both auto-generated) |
| Understand the AI setup | [.claude/README.md](.claude/README.md) |

In a Claude Code session, just say **"help"**.

## Working in this repo

- **Session ritual:** read `.claude/memory/current-status.md` at the start; write a `.claude/worklog/` entry and update the status file at the end.
- **Branching:** work branch → `staging` → `main`. **PRs always base on `staging`** — documentation PRs included.
- **After changing routes or `.env.example`:** run `node scripts/refresh-docs.mjs` in the same commit.
