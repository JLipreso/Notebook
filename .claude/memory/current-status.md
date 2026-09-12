# Current Status

_Last updated: 2026-09-12 (repository bootstrap)_

## Where we are

- **Day zero.** The repo exists with the agentic knowledge scaffold in place — [CLAUDE.md](../../CLAUDE.md) with the Context-First routing table, this `.claude/` session state, the learnings ledger, the reference generator, and four skills.
- **No application code.** `apps/` and `backend/` are empty placeholders; there is no pnpm workspace, no Laravel install, no CI, no deployment.
- ⚠ **The product brief has not landed.** [CLAUDE.md §1](../../CLAUDE.md) is deliberately **TBD**. Until the client brief arrives, do not invent product behavior, data model, or money rules — ask the Lead Developer and record the answers as decisions.
- Remote: `https://github.com/JLipreso/Notebook.git`. `main`, `staging` and `Workstation-PC` all at `67a2c2e` (the bootstrap commit).
- **Work happens on `Workstation-PC`** — the desktop work branch, checked out by default. PRs from it go to `staging`.

## What's next (in order)

### Immediate
1. ~~**Land the bootstrap.** Create `staging` off the initial commit so the §8 three-tier flow is real.~~ **DONE 2026-09-12** — `67a2c2e` pushed to `main`, `staging` branched from it; both tracked locally. ⚠ Still open: GitHub's default branch is `main`, so new PRs default to the wrong base. Switch the repo's default branch to `staging` (Settings → General → Default branch, or `gh repo edit --default-branch staging`) — until then, always pass `--base staging` explicitly.
2. **Get the client brief** and write it into [document/2026-09-12-001-Project-Details/](../../document/2026-09-12-001-Project-Details/) — the folder exists but `about.md` is an empty stub. Then replace CLAUDE.md §1's TBD with a one-paragraph identity + a pointer to it. (The routing row already points there.)

### Once the brief is in
3. **Scaffold the pnpm workspace** — root `package.json`, `pnpm-workspace.yaml`, the first app under `apps/`, the shared `packages/` (types / services / utility). Update CLAUDE.md §2's table in the same commit.
4. **Install Laravel 12 into `backend/`** following CLAUDE.md §5. Add `backend/.env.example` and each `apps/*/.env.example`, then run `/refresh-docs` — that is the first run that produces real content.
5. **Lock the domain rules** (tenancy, permissions, and anything money-shaped) into `decisions.md` *before* implementing them, and give each a canonical module + a routing row.

### Later
6. **Deployment** — confirm the client environment, wire GitHub Actions, write the runbook, then fill in CLAUDE.md §9 and put the real hosts into the `/whats-live` and `/diagnose-deploy` skills.

## Standing facts

- **Lead Developer:** Jason Lipreso. Solo repo today — the scaffold is sized for that (guide's "Exploria tier"), see CLAUDE.md §10 for the growth path.
- **Stack is locked** (D-001): Laravel 12 + PHP 8.2 in `backend/`, Vue 3.5 + Vite 6 + TS + Pinia + Tailwind pnpm workspace in `apps/` + `packages/`. Exploria (`D:\Software-Dev-Projects\Jazer\Monorepo-Exploria-Restart`) is the working reference for every convention here.
- **Git flow is locked** (D-002): work branches → `staging` → `main`. PRs — documentation PRs included — always base on `staging`.
- The learnings ledger is **seeded with same-stack lessons carried over from Exploria**, not lessons this repo has hit. They are marked as such; they are still worth reading before debugging.
