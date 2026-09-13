# Claude Artifacts — master list

Every published Claude Artifact for Project Notebook, in one place. All links are stable across updates (artifacts are revised in place, never re-created — a new URL would break the boss's bookmarks). All are **private until shared** from each page's share menu.

| # | Artifact | Link | What it is | Audience / notes |
|---|---|---|---|---|
| 1 | **Notebook System Blueprint** 📐 | https://claude.ai/code/artifact/4d9c29a4-0ec5-49af-af38-4db06094d972 | Project structure + full ER design: repo layout, per-form-factor apps, backend architecture, all 4 domain ER diagrams, sync contract. Generated from the locked M1 Foundation plan (D-024, updated through D-031) | Boss + client review doc (task 2026-09-13-004, Phase 2). Source md: [2026-09-13-004-M1-Foundation/plan/](2026-09-13-004-M1-Foundation/plan/README.md) — edit sources, regenerate to the SAME URL |
| 2 | **Notebook Requirements & Features** 📘 | https://claude.ai/code/artifact/fcd28a9f-c1fc-4a64-a973-e4bdcaa68399 | Product requirements summary: identity, feature list per milestone, business model, payment workflow, technology decisions | Boss + client review doc (task 2026-09-13-004, Phase 2). Source: [concept-final.md](2026-09-12-001-Project-Details/concept-final.md) + plan docs |
| 3 | **Notebook Brand Directions** 🎨 | https://claude.ai/code/artifact/81763d78-b4a8-4e57-9bc7-444a827fcb96 | The three color-direction pitch (Komposisyon / Silid-Aralan / Kislap), same 3 mobile screens each | Boss + client picked **Option A → D-032** (task 2026-09-13-006). Kept as the decision record; superseded for design detail by the canvases in [2026-09-13-006-Brand-Colors/](2026-09-13-006-Brand-Colors/README.md) |
| 4 | **Notebook M1 Build Tracker** 🛠️ | https://claude.ai/code/artifact/74514cdd-95cb-44b5-8170-b7277b5fa991 | **Live** phase-by-phase M1 progress: 12 phases, statuses from the artifact's shared database, updates in real time without republishing | Boss watches (view = read-only); junior/Lead Dev advance statuses (needs edit access, or a Claude session updates `phases/phase-NNN`). Org-internal — viewers must be signed in. Wired into the [implementation plan](2026-09-13-005-Implementation-Plan/README.md) completion ritual |

## Conventions

- **Updating:** never publish a new artifact for a revision — edit the md/canvas sources, then regenerate to the same URL (give Claude the link).
- **New artifact →** add a row here in the same session, so this list stays the single index.
- The Claude Design canvases (mobile 22-screen, browser 12-screen) are **HTML files in the repo**, not artifacts — they live in [2026-09-13-006-Brand-Colors/](2026-09-13-006-Brand-Colors/README.md).
