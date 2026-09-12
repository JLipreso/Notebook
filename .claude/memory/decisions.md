# Locked Decisions

Decisions made by the Lead Developer (Jason Lipreso). **Final — do not re-litigate in code review or in a later session.**
Format: date · ID · decision · where it came from. IDs get quoted in route banners, docblocks, and commit messages so any code traces back to its *why*.

| Date | ID | Decision | Source |
|---|---|---|---|
| 2026-09-12 | D-001 | Stack mirrors the Exploria monorepo: Laravel 12 + PHP 8.2 at `backend/` (sibling dir, not a pnpm workspace member) + Vue 3.5 / Vite 6 / TS 5.7 / Pinia / Tailwind 3.4 pnpm workspace at `apps/*` + `packages/*` | Bootstrap session |
| 2026-09-12 | D-002 | Backend conventions follow the Rosterlink-EMR reference project — `ApiController` envelope, flat `Api/` controllers, no API Resource classes, Sanctum bearer tokens, `document/` task-folder convention | Bootstrap session (inherited via Exploria) |
| 2026-09-12 | D-003 | Git flow is three-tier: per-machine work branches → `staging` (integration, the PR base) → `main` (production/release). Documentation PRs follow the same flow | Bootstrap session |
| 2026-09-12 | D-004 | The repository is agentic per the org guide `Claude-AI-Guide/Project-Scaffold/agentic-repository.md`, sized at the small ("Exploria") tier: routing table + `.claude/` session state + learnings ledger + one generator + four skills. LPAI-scale machinery is deferred | Bootstrap session |
| 2026-09-12 | D-005 | Shared TypeScript types are the frozen API contract — the backend adapts to them, not the reverse | Bootstrap session (inherited via Exploria) |
| 2026-09-12 | D-006 | Claude has standing permission to create/edit/write anything under `.claude/` without prompting (`Edit(.claude/**)` + `Write(.claude/**)` in the checked-in `settings.json`). Grant is scoped to session state only — it does NOT extend to source code, `document/`, or `scripts/` | Lead Developer instruction |

## Open — waiting on the Lead Developer

These are **not** decisions yet. Nothing that depends on them should be implemented.

| ID | Question | Blocks |
|---|---|---|
| Q-001 | What is Notebook — product, users, business model? | CLAUDE.md §1, the entire data model |
| Q-002 | Is there any money/billing/entitlement math? If so, what are the exact rules? | Whether a canonical pricing module is needed at all |
| Q-003 | Single-tenant or multi-tenant? What are the roles? | Auth design, every query in the app |
| Q-004 | Where does this deploy, and is there an existing client environment? | CLAUDE.md §9, CI/CD, the `/whats-live` skill |
