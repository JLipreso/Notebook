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

## Addendum (same session) — task 2026-09-13-005: M1 implementation plan

- Boss requested a phase-by-phase implementation plan executable by a junior developer on their own workstation. Before writing it, closed EVERY open implementation question with the Lead Developer: O-1 (PSGC = latest snapshot at implementation), O-3 (trial at registration), O-4 (appId `com.notebook.student`, `com.notebook.teacher` reserved) → **D-030**.
- **D-031 (Lead Developer instruction): no contractor branding anywhere** — outsourced project, source ships to the client. Scrubbed CLAUDE.md, README.md, root package.json, concept-validation README, both client artifacts (republished, same URLs), and `capacitor.config.ts` (appId updated to D-030 value).
- Wrote [documents/2026-09-13-005-Implementation-Plan/](../../documents/2026-09-13-005-Implementation-Plan/README.md): README (ground rules, phase index, decision digest) + Phase-001…Phase-012, each with goal/prereqs/decision refs/steps/acceptance checklist, one PR per phase to staging. Routing-table row added to CLAUDE.md.
- Grep proof: `grep -ri "w labs"` clean across repo and artifacts.

## Addendum 2 (same session) — task 2026-09-13-006: brand colors

- Pitched three color directions as an artifact (same 3 mobile screens, identical content, only color varies): A "Komposisyon" (ink navy/cream/margin red), B "Silid-Aralan" (chalkboard/manila/gold), C "Kislap" (teal/coral). Boss + client chose **Option A** → **D-032**.
- Task folder documents/2026-09-13-006-Brand-Colors/ records all three options + the artifact link; CLAUDE.md routing row added ("brand colors/palette/theme"); `packages/ui/brand/tailwind-preset.cjs` synced to the exact chosen hexes (token names unchanged; `pnpm build:all` green).
- Next: Claude Design screen pass on this palette (Lead Developer drives).

## Addendum 3 (session close) — design surface, tracker, release zero, HANDOVER

- **Design passes 1–3 completed in Claude Design** (Lead Developer drove; briefs + completion notes in the 006 task folder). Canonical references: `Notebook-Mobile-22-screens.html` + `Notebook-Browser-12-screens.html`. Typefaces (Fraunces + Figtree) folded back into the brand preset, bundled offline-safe via `@fontsource`. Design thread CLOSED.
- **M1 Build Tracker artifact published** (live db-backed phase statuses, boss-facing) and wired into the plan's completion ritual; master artifact index at `documents/claude-artifact-list.md` with a CLAUDE.md routing row.
- **PRs #3–#8 all merged.** #6/#8 were `staging` → `main` promotions — **release zero (the M1 foundation) is on `main`**; `main` = `staging` = `Workstation-PC`, tree clean.
- **Q&A at close:** encrypted-secrets-in-repo tooling assessed (Laravel `env:encrypt`, SOPS, git-crypt) — deliberately NOT adopted: the source ships to the client (D-031), so out-of-repo secrets stay the rule. Teacher app folders confirmed intentionally absent (teacher = M2, D-025; glob `apps/*/*` picks them up when they land).
- **Recommendation given to the boss (pending his decision): junior implements M1 first**; M2/M3 get milestone-level plans now and file-level detail when M1 nears completion (already the 005 README's stated intent). If accepted → a `M2-M3-Milestone-Plan` task folder is the follow-up.
- **REPO HANDED OVER to the junior developer.** Their entry point: clone, branch off `staging`, start [Phase-001](../../documents/2026-09-13-005-Implementation-Plan/Phase-001.md). Lead Dev to-dos outstanding: tracker access (boss view / junior edit), Firebase credentials before Phase-004.
