# Phase 3 complete — pnpm workspace scaffolded (D-027 form-factor structure)

_2026-09-13. Implements [project-structure.md](../project-structure.md) as amended by D-025…D-029. Verified: `pnpm typecheck` green (7 packages), `pnpm build:all` green (both apps)._

## What exists now

| Piece | Detail |
|---|---|
| Root workspace | `pnpm-workspace.yaml` (`packages/*` + `apps/*/*`), root scripts (`dev:student:browser`, `build:all`, `typecheck`) |
| `apps/student/browser` | `@notebook/student-browser`, port **5171** — Vue 3.5 + Vite 6 + TS 5.7 + Pinia (auth-only store) + Router + Tailwind (shared preset) + Reka UI; aliases in BOTH `vite.config.ts` and `tsconfig.app.json`; `.env.example` committed |
| `apps/student/mobile` | `@notebook/student-mobile`, port **5174** — same kit, portrait phone UI shell |
| `apps/student/native` | THE Capacitor project (D-028): `capacitor.config.ts` (appId placeholder — **O-4**), `assemble-www.mjs` (copies `mobile/dist` → `www/`; grows the form-factor bootstrap when tablet joins). **`cap add android` NOT run — blocked on O-4 (appId)** |
| `apps/student/desktop` `tablet` | `.gitkeep` only — Electron post-Android (D-026), tablet deferred (D-029) |
| `packages/types` | envelope contract (`ApiResponse`, `PaginatedResponse`); domain files land WITH Phase 4 migrations so types mirror tables |
| `packages/services` | axios singleton (`http.ts`, bearer injection), `datasource.ts` (the ONE `VITE_USE_MOCK` reader), mock latency shim |
| `packages/utility` | `school-year.ts` (PH SY derivation, May cutover) |
| `packages/ui` | `brand/tailwind-preset.cjs` (paper/ink/margin/rule placeholder tokens); editor/paper components land with M1 implementation |
| `packages/sync` | `ids.ts` (UUIDv7, D-013), `platform.ts` (web/android/ios/electron seam), `storage/` adapter interface + memory adapter |

Same-commit conventions honored: CLAUDE.md §2 table updated; `/refresh-docs` run (`scripts/refresh-docs.mjs` taught the nested `apps/*/*` layout; env reference now lists both app templates).

## Decisions this phase leaned on
D-024 (plan), D-025 (platform matrix), D-026 (Android-first), D-027 (form-factor apps + anti-fork guardrail), D-028 (one native bundle), D-029 (M1 = browser + mobile).

## Still open before Phase 4
- **O-1** PSGC snapshot quarter (seeder asset)
- **O-3** trial start semantics
- **O-4** Capacitor `appId` — blocks `cap add android`, nothing else

Next: **Phase 4 — M1 migrations + models** per [database-schema.md](../database-schema.md) (M1-tagged tables only, fake-timestamp counter, PSGC + notebook_types seeders, `/refresh-docs` in the same commit).
