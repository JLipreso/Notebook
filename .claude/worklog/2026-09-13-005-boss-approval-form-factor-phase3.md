# 2026-09-13 (session 005) — boss approval, form-factor decisions, Phase 3 scaffold

Task: 2026-09-13-004 (M1 Foundation), Phase 3 + the platform decisions preceding it.

## What happened

1. **Boss approved both client documents** — no content changes, praised the structure. Platform inputs followed, all recorded:
   - D-025 (platform matrix: teacher matches student), D-026 (Android-first; Electron after Android, iOS last) — via AskUserQuestion with the Lead Developer.
   - **First Phase-3 run FALSE START:** on "start", a single-app `apps/student` scaffold was built (pre-form-factor). Lead Developer halted mid-`pnpm install` — "stop coding, we need to continue planning" — and had it deleted. Lesson below.
   - **Boss structural request** (relayed with sketch dirs): per-form-factor apps. Feasibility assessed (Play Store one-binary-per-listing constraint surfaced), recorded as **D-027** with the anti-fork guardrail; Lead Developer closed the follow-ups as **D-028** (one `native/` Capacitor bundle per role) and **D-029** (M1 = browser + mobile; tablet later).
   - Plan docs + concept-final.md updated; both client artifacts regenerated twice to their same URLs (once for D-025/026, once for D-027/028/029).
2. **Committed the planning batch** (`6555d93`), then **re-ran Phase 3 under D-027**:
   - Root workspace (`apps/*/*` globs), `apps/student/{browser,mobile,native}` + `.gitkeep`d `desktop/`/`tablet/`, five package skeletons (types/services/utility/ui/sync).
   - `scripts/refresh-docs.mjs` taught the nested layout; `/refresh-docs` run in the same commit (2 new `.env.example`).
   - CLAUDE.md banner/§1/§2 reality-checked in the same commit.
   - Verified: `pnpm typecheck` green (7 projects), `pnpm build:all` green (both apps). `cap add android` NOT run — blocked on O-4 (appId).

## Decisions
D-025…D-029 (see decisions.md). Open: Q-004, O-1, O-3, O-4.

## Gotchas / lessons

- **"Approved, start" ≠ start coding when the approval carries new inputs.** The boss's platform notes looked like confirmations but contained a structural change (Electron, and later the form-factor split). Should have planned the inputs through before scaffolding; cost one thrown-away scaffold + a re-run. Process note: treat any approval-with-comments as a planning trigger first.
- Tailwind `content` globs reaching into `packages/ui/**` also match its `node_modules` — list source dirs explicitly (`{brand,editor,paper}/**`) or builds slow down (warning seen, fixed).
- Google Play: one binary per listing (multi-APK deprecated) — separate phone/tablet apps under one listing are impossible; hence D-028's bundled bootstrap. Worth remembering for any future per-form-factor product.
