# Locked Decisions

Decisions made by the Lead Developer (Jason Lipreso). **Final — do not re-litigate in code review or in a later session.**
Format: date · ID · decision · where it came from. IDs get quoted in route banners, docblocks, and commit messages so any code traces back to its *why*.

| Date | ID | Decision | Source |
|---|---|---|---|
| 2026-09-12 | D-001 | Stack mirrors the Exploria monorepo: Laravel 12 + PHP 8.2 at `backend/` (sibling dir, not a pnpm workspace member) + Vue 3.5 / Vite 6 / TS 5.7 / Pinia / Tailwind 3.4 pnpm workspace at `apps/*` + `packages/*` | Bootstrap session |
| 2026-09-12 | D-002 | Backend conventions follow the Rosterlink-EMR reference project — `ApiController` envelope, flat `Api/` controllers, no API Resource classes, Sanctum bearer tokens, `documents/` task-folder convention | Bootstrap session (inherited via Exploria) |
| 2026-09-12 | D-003 | Git flow is three-tier: per-machine work branches → `staging` (integration, the PR base) → `main` (production/release). Documentation PRs follow the same flow. Work branches are named for the machine; the first is `Workstation-PC` (Lead Developer's desktop) | Bootstrap session |
| 2026-09-12 | D-004 | The repository is agentic per the org guide `Claude-AI-Guide/Project-Scaffold/agentic-repository.md`, sized at the small ("Exploria") tier: routing table + `.claude/` session state + learnings ledger + one generator + four skills. LPAI-scale machinery is deferred | Bootstrap session |
| 2026-09-12 | D-005 | Shared TypeScript types are the frozen API contract — the backend adapts to them, not the reverse | Bootstrap session (inherited via Exploria) |
| 2026-09-12 | D-006 | Claude has standing permission to create/edit/write anything under `.claude/` without prompting (`Edit(.claude/**)` + `Write(.claude/**)` in the checked-in `settings.json`). Grant is scoped to session state only — it does NOT extend to source code, `documents/`, or `scripts/` | Lead Developer instruction |
| 2026-09-13 | D-007 | Realtime uses a **paid Pusher Channels account** (pusher-js + laravel-echo), NOT Laravel Reverb. MIIT's channel-auth pattern ports verbatim | Lead Developer instruction (concept-validation session) |
| 2026-09-13 | D-008 | **MVP collects raw scores only — no final-grade computation.** Final grades differ per year level and per school (elementary/HS/college); grade computation ships later as free per-level "Grade Calculator" add-on tools (e.g. "Elementary Grade Calculator", "College Grade Calculator") reading the score data | Lead Developer instruction (concept-validation session) |
| 2026-09-13 | D-009 | **Lessons must be downloadable as PDF and directly printable** (web + device print dialog) by both teachers and students — serves students who have no device | Lead Developer instruction (concept-validation session) |
| 2026-09-13 | D-010 | Notebook types replicate the real Philippine paper formats as page templates: **Composition** = single-ruled + red margin (HS/college); **Writing** = alternating blue/red penmanship guide lines with `Date:` header and Teacher's/Parent's Signature footers (preschool). Paper fidelity is product identity | Lead Developer clarification with reference photos (concept-validation session) |
| 2026-09-13 | D-011 | **MVP is notebook-first** (closes Q-005): M1 = notebook core (library, editor, sharing, offline); M2 = classroom layer WITH minimal teacher portal (lessons, quizzes, invitations, PDF/print, scores); M3 = payments + admin. Full cut: [2026-09-13-003 README §3](../../documents/2026-09-13-003-Concept-Validation/README.md) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-012 | **Notebook pages are typed block documents** — Tiptap/ProseMirror JSON rendered on paper-styled templates; the schema reserves a future drawing/ink block (closes Q-006). The **Writing** type is visual style only in MVP — typed content on penmanship guide lines; handwriting/tracing input arrives with the ink block later (closes Q-006b) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-013 | **UUIDv7 primary keys everywhere** on all synced tables, client-generated on device, server inserts as-is (closes Q-009). Set in the first migration; no auto-increment/temp-ID mapping | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-014 | **One `users` table + `role` column**, single Sanctum guard; role-specific fields in `student_profiles`/`teacher_profiles` satellites (closes Q-007). No per-role tables/guards — MIIT's duplication lesson | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-015 | **Auth = Firebase → Sanctum exchange** (closes Q-008): client signs in with Firebase (email/password, Google, phone OTP); backend verifies the ID token via kreait and issues a Sanctum bearer; every API call uses Sanctum | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-016 | **Offline scope** (closes Q-010): offline-WRITABLE = notebooks, pages, attachments (queued upload); offline READ cache = enrolled courses, lessons, own scores, profile; ONLINE-ONLY = quiz taking, chat, payments, sharing changes, invitations | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-017 | **Mobile/tablet shell = Capacitor** wrapping the same Vue apps, `@capacitor-community/sqlite` for the local DB (closes Q-011) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-018 | **Rich-text editor = Tiptap 2** (ProseMirror JSON) for notebook pages AND lesson authoring (closes Q-012) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-019 | **Production DB = MySQL 8** (closes Q-014). SQLite stays for dev and on-device | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-020 | **Geolocation dropped from MVP** (closes Q-015). PSGC address dropdowns provide regional demographics; no location permission requested | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-021 | **Chat = course-room chat first** (one room per course, teacher always present; MIIT pattern on Pusher). Private/group DMs deferred until a moderation plan (reporting, blocking) exists (closes Q-016) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-022 | **Growth loop is teacher-led** (closes Q-013a): a paying teacher's students get course access regardless of the student's own tier; students pay for notebook-side premium (more notebooks, storage, fonts, AI) | Lead Developer (AskUserQuestion, concept-validation session) |
| 2026-09-13 | D-023 | **Free is a permanent limited tier after the 14-day trial** (closes Q-013b): e.g. 2 active notebooks + read-only archives + limited course features. User data is NEVER deleted or fully locked away | Lead Developer (AskUserQuestion, concept-validation session) |

## Open — waiting on the Lead Developer

These are **not** decisions yet. Nothing that depends on them should be implemented.

| ID | Question | Blocks |
|---|---|---|
| Q-004 | Where does this deploy, and is there an existing client environment? | CLAUDE.md §9, CI/CD, the `/whats-live` skill |

## Closed questions (answer trail)

| ID | Resolution |
|---|---|
| Q-001 | **Closed 2026-09-13** — product identity: the brief ([about.md](../../documents/2026-09-12-001-Project-Details/about.md)) + finalized concept ([concept-final.md](../../documents/2026-09-12-001-Project-Details/concept-final.md)) + [validation](../../documents/2026-09-13-003-Concept-Validation/README.md); shaped by D-010…D-012, D-022, D-023 |
| Q-002 | **Closed 2026-09-13** — yes, there is money math: tiers/prices/payment statuses per the brief; entitlement shape via D-008 (scores only), D-022 (teacher-led), D-023 (permanent free floor). Exact ₱ figures stay Admin-editable config, one canonical entitlements module |
| Q-003 | **Closed 2026-09-13** — single-tenant B2C platform (no per-school tenancy; individuals subscribe). Roles: student / teacher / admin on one `users` table (D-014) |
| Q-005…Q-016 | **All closed 2026-09-13 → D-011…D-023.** Full context and per-question decisions in [2026-09-13-003/questions/](../../documents/2026-09-13-003-Concept-Validation/questions/) |
