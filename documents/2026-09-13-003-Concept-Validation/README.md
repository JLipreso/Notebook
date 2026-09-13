# 2026-09-13-003 — Concept Validation: Notebook

Validates and sharpens the brief in [documents/2026-09-12-001-Project-Details/about.md](../2026-09-12-001-Project-Details/about.md) (left untouched — this folder is the companion analysis) against a full scan of the MIIT/Elea LMS ([findings/miit-scan.md](findings/miit-scan.md)).

**Status: COMPLETE — all questions answered, concept locked 2026-09-13.** Q-005…Q-016 are closed as **D-007…D-023** (see each file's `**Decision:**` line and [.claude/memory/decisions.md](../../.claude/memory/decisions.md)). The finalized concept lives at [documents/2026-09-12-001-Project-Details/concept-final.md](../2026-09-12-001-Project-Details/concept-final.md) and CLAUDE.md §1 now reflects it. This folder remains the rationale archive.

---

## 1. Verdict: the concept is sound — with three structural caveats

The idea is validated. It builds on a domain the team has already shipped in production (MIIT/Elea runs real Philippine schools today), the cultural anchor is real (students genuinely buy 8–12 paper notebooks a school year), and the differentiator is genuinely different: **no competitor combines a student-owned digital notebook with a lightweight teacher-course layer at Philippine price points with GCash payment.** GoodNotes/Notability/OneNote own "notebook" but have no classroom layer and no PH-market pricing; Google Classroom owns "free classroom" but has no notebook and no reason for a student to keep using it after the semester. The combination is the moat — the notebook makes the data student-owned and durable (kept as archives across school years), the course layer gives teachers a reason to pull whole classes in.

Three caveats that shape everything below:

### Caveat A — the differentiator is the least-specified feature in the brief
The brief spends ~80% of its lines on LMS features (courses, lessons, quizzes, exams, grades — all of which MIIT already proves we can build) and almost none on **what a notebook page actually is**: what does the student see when they open a "Composition" notebook and start writing? Typed rich text? Freeform handwriting/stylus ink? Pages or an infinite scroll? This is the single most important unanswered product question — it drives the editor choice, the SQLite schema, the sync design, and whether a tablet pen experience is even possible. → **Q-006**.

### Caveat B — the phasing has a supply-and-demand inversion
Phase 1 (student app) includes consuming courses, lessons, and quizzes — but teachers can't author any of that until Phase 3. Phase 1 students would open a course module with nothing in it. Two coherent fixes: make Phase 1 purely the notebook (LMS waits until teacher authoring exists), or pull a minimal teacher authoring portal forward alongside Phase 1. → **Q-005**.

### Caveat C — the MVP as written is four products
A notebook app + a full LMS + a messenger + a billing/admin platform. Each of those is a real product; MIIT took years to build just the middle one. The brief needs a deliberate cut line, not just phases. Recommended cut in §3 below. → **Q-005, Q-016**.

Two business-risk flags (not blockers, but say them out loud):
- **B2C is a different game than MIIT's B2B.** MIIT sells to schools; Notebook asks individual students for ₱69–129/month and teachers for ₱89–169/month. The 14-day free window means the product must demonstrate keep-paying value inside two weeks. The likely growth loop is **teacher-led**: one paying teacher pulls in 30–40 students. Pricing/packaging should be designed around that loop. → **Q-013**.
- **Geolocation of minors.** Collecting student location on every app open, without a stated purpose, is a Data Privacy Act (RA 10173) exposure — most students are minors. Either name the purpose and gate it behind consent, or drop it. → **Q-015**.

---

## 2. What MIIT gives Notebook (and what it doesn't)

Full detail in [findings/miit-scan.md](findings/miit-scan.md). The short version:

**Proven and portable** — the assessment domain model (normalized quiz/exam question–option–attempt–answer tables with snapshot scoring and mixed auto/manual grading), the exam-from-quiz-bank pattern (`source_question_id`), the `ApiController` envelope (identical to our §5 conventions), enrollment-derived realtime channel auth, the core-library + thin-app factory for white-labeling, and a working Firebase-ID-token → Sanctum-token exchange.

**Absent — net-new engineering for Notebook** — the notebook editor itself, offline/SQLite sync (MIIT has none, at all), invitation flows (MIIT adds students directly), payments/subscriptions/entitlements (no tables, no code), the PH address hierarchy, and push notifications beyond auth-key persistence.

**Present but must NOT be copied** — the seven anti-patterns in findings §6: dual grade models with whole-class recompute per page view, duplicated per-role tables, mixed collations, raw Quill HTML content, FTP uploads, committed secrets, hard deletes, zero tests, dead schema in production.

---

## 3. The finalized concept (proposed)

> **Notebook** is a Philippine-market mobile-first app that replaces the stack of paper notebooks a student buys every school year with digital notebooks they own forever — searchable, shareable, and organized by school year — with a lightweight layer that lets a teacher run a class (lessons, quizzes, scores) directly on top of the notebooks students already live in. Students and teachers subscribe individually (₱ pricing, GCash), offline-first on mobile and tablet.

It serves **preschool through college**, and the notebook types mirror the real paper formats each level uses (Lead Developer clarification, 2026-09-13, with reference photos in the chat): **Composition** = single-color ruled lines + red margin, the HS/college standard; **Writing** = the preschool penmanship format with alternating blue/red guide lines, a `Date:` header, and `Teacher's Signature` / `Parent's Signature` footer lines. Page fidelity to the paper original is part of the product's identity, not decoration — each notebook type is a page template (ruling, margins, header/footer fields), which feeds directly into Q-006.

### Proposed MVP cut (replaces the brief's phasing — pending Q-005)

**Milestone 1 — the Notebook core (student app, web + Capacitor mobile/tablet):**
- Firebase auth (email/password + Google), profile with PSGC address dropdowns
- Multi-notebook library: create per school year, notebook types, cover photos, archive
- **The notebook editor** (shape per Q-006) — offline-capable from day one, SQLite on device
- Sharing: read-only share links first; read-write + expiration second
- Image attach/view inside pages; PDF viewing
- In-app notification plumbing (single polymorphic table, used by sharing first)

**Milestone 2 — the classroom layer (teacher web portal + student app additions):**
- Teacher: course CRUD, lesson authoring (Tiptap JSON), quiz builder from question bank, invitations by email with accept/decline+reason, course timeline/reuse-next-term
- Student: join course, view lessons, take quizzes (score-now / answers-when-teacher-releases, per the brief), completion-activates-quiz flow
- **Lesson PDF export + direct print** (teacher AND student side) — required for students with no device (Lead Developer clarification, 2026-09-13)
- **Scores only, no final-grade computation** — the app collects and shows raw quiz/activity scores; final grades differ per level and per school (elementary/HS/college), so grade computation ships later as free "Grade Calculator" add-on tools (Lead Developer clarification, 2026-09-13)
- Course-scoped chat room (not a general messenger — Q-016)

**Milestone 3 — money + admin:**
- Plans/entitlements module (dynamic tiers per the brief), GCash QR + reference-number manual verification with the brief's 5 payment statuses, admin panel (dashboard counts, student/teacher/payment management)

**Milestone 4+ (explicitly deferred):** exams, assignments, attendance, **Grade Calculator add-on tools** (free, per level: "Elementary Grade Calculator", "College Grade Calculator", … — the deliberate replacement for a built-in grade book), AI add-ons (Student's/Teacher's AI), Google Drive/Calendar, doc/3D editing, full private+group messenger, PayMongo/Maya.

### Trims to the brief (recommend accepting as-is)
- **Fonts:** "download all free fonts" → ship a curated 12–15 Google-Fonts set bundled locally (offline requirement makes bundling mandatory anyway; more fonts can be added as premium content later).
- **Document viewer "all types":** MVP = PDF + images. doc/docx/xlsx render via later integration; editing is Phase-2-optional in the brief already.
- **Geolocation:** drop from MVP or reduce to coarse region at signup with explicit consent (Q-015).
- **Messenger:** course-room chat first; DMs and group chats later (Q-016).

---

## 4. Subjects of improvement (over MIIT — the do-differently list)

Each of these cost MIIT real pain or blocks a Notebook requirement. These become learnings-ledger entries / conventions as their code lands.

1. **Scores are facts; grades are add-on tools.** MVP stores raw scores only (Lead Developer, 2026-09-13) — final grades vary per level and per school, so computation ships later as free per-level Grade Calculator add-ons reading the score data. When those land, apply the MIIT lesson: compute on write and persist — never recompute a whole class to render one page (MIIT's live path recomputes every enrolled student's rank on every dashboard view).
2. **One assessment tree.** Quiz and exam share question/option/attempt/answer tables with a `kind` discriminator; keep MIIT's question-bank `source_question_id` idea. The brief itself says exams are "almost identical" to quizzes — that's a schema hint, not two modules.
3. **One polymorphic `notifications` table**, not per-role clones.
4. **Structured JSON content, never raw HTML.** Lessons and notebook pages stored as Tiptap/ProseMirror JSON (the brief explicitly asks for JSON output) — sanitizable, diffable, offline-mergeable, renderable natively on mobile.
5. **Offline-first is a schema decision, not a feature.** UUIDv7 client-generated PKs, `updated_at` cursor pulls, soft-delete tombstones — designed into migration one, because it cannot be retrofitted (Q-009/Q-010).
6. **utf8mb4 everywhere, one PK convention** from the first migration.
7. **Soft deletes on all user content.** Notebooks are the product's promise ("keep as archive") — a hard delete of a notebook is data loss of the thing we sell.
8. **Storage on Laravel local disk → S3-compatible**, never FTP. No secrets in the repo, ever (MIIT committed its Firebase service account).
9. **Real invitation flow** (token, accept/decline with reason, notification back to teacher) — net-new; MIIT has none.
10. **Entitlements as one canonical module.** Dynamic tier→feature mapping lives in one place, checked by one gate — money rules are Lead-Developer decisions per CLAUDE.md, and MIIT's absence of any billing code means no legacy to fight.
11. **Feature tests from day one; controllers stay thin** (MIIT: zero tests, multi-thousand-line controllers).
12. **No dead schema.** Migrations land with the feature that uses them (MIIT shipped an entire unused assignments/peer-evaluation family to production).

---

## 5. Recommended tech stack

Backend is locked by D-001/D-002 and the user's instruction (Laravel; SQLite on tablet/mobile for the local data copy). The rest is recommendation pending the Q-decisions.

| Layer | Choice | Why |
|---|---|---|
| Backend | **Laravel 12 + PHP 8.2, Sanctum stateless bearer** (locked, already scaffolded) | D-001/D-002; §5 conventions |
| Server DB | **MySQL 8** (Q-014; SQLite stays dev + on-device) | Hostinger/CyberPanel deploy pattern, MIIT operational familiarity |
| Auth | **Firebase Auth (email/password + Google) → backend verifies ID token (kreait) → issues Sanctum token** | Brief requires Firebase; MIIT already proves the exchange; keeps every API call on our own tokens per §5 (Q-008) |
| Web apps | **Vue 3.5 + Vite 6 + TS + Pinia + Tailwind 3.4 + Reka UI** pnpm workspace: `apps/student`, `apps/teacher`, `apps/admin` | Locked D-001 |
| Editor | **Tiptap 2** (ProseMirror) | JSON document model = the brief's explicit requirement; extensible with custom blocks (drawing/ink block later); Vue 3 first-class (Q-012) |
| Mobile/tablet | **Capacitor 7 wrapping the same Vue apps** + `@capacitor-community/sqlite` | One codebase per D-001; MIIT precedent (Ionic/Capacitor); native SQLite per the user's requirement (Q-011) |
| Sync engine | **Custom pull/push**: UUIDv7 client PKs, per-table `updated_at` cursor pulls, offline write queue, last-write-wins + server arbitration, tombstones | Fits Laravel + MySQL without new infra; managed alternatives (PowerSync/ElectricSQL) are Postgres-centric — revisit under Q-014 (Q-009/Q-010) |
| Realtime | **Pusher Channels (paid account)** + Laravel Echo — **Lead Developer decision 2026-09-13 (D-007)**; Reverb considered and rejected | Managed service, zero self-hosted websocket ops; MIIT's exact stack (`pusher-php-server` + `pusher-js` + enrollment-derived channel auth) ports verbatim |
| Lesson PDF/print | Tiptap JSON → HTML print stylesheet for direct printing (browser print on web; native print dialog via Capacitor on devices) + server-side PDF download (dompdf via `laravel-dompdf`, or Browsershot if fidelity demands) | Required: students without devices get printed lessons (Lead Developer, 2026-09-13); MIIT precedent for server PDFs is mpdf |
| Push | **Firebase Cloud Messaging** | Already inside the Firebase footprint the brief mandates |
| Files | Laravel `local`/`public` disk → S3-compatible (Cloudflare R2 / DO Spaces) when volume demands | Never FTP (MIIT lesson) |
| Address data | **PSGC** (PSA's Philippine Standard Geographic Code) seeded into `regions/provinces/cities_municipalities/barangays` | The official ~42k-barangay dataset the brief's dropdowns need; refresh on PSA quarterly releases |
| Payments (MVP) | GCash QR + reference number, manual verification, brief's 5 statuses; entitlements module designed for PayMongo/Maya later | Per the brief |
| AI (deferred add-on) | Anthropic API server-side (default `claude-sonnet-5`) | MIIT already ships `anthropic-ai/sdk`; add-on pricing maps to metered server-side usage |
| Fonts | Curated Google-Fonts subset, bundled locally | Offline requirement forbids runtime font CDN anyway |

---

## 6. Open questions index

All in [questions/](questions/), each ending with a `**Decision:**` line for the Lead Developer. Answered ones get IDs in `.claude/memory/decisions.md` and become final. Q-001–Q-004 predate this task (Q-001 is now largely answered by about.md + this doc; Q-002 by the brief's pricing section — both still need formal closure).

| ID | Question | Blocks |
|---|---|---|
| [Q-005](questions/q-005-mvp-scope-and-phasing.md) | MVP scope & phasing — notebook-first, LMS-first, or full brief? | Everything: first migration, first app |
| [Q-006](questions/q-006-notebook-content-model.md) | What is a notebook page — typed blocks, freeform ink, or hybrid? | Editor, SQLite schema, sync, tablet UX |
| [Q-007](questions/q-007-user-model.md) | One `users` table + roles, or separate student/teacher/admin tables (MIIT style)? | Auth, every FK in the schema |
| [Q-008](questions/q-008-auth-flow.md) | Firebase→Sanctum exchange vs Firebase-only vs Sanctum-only | Auth middleware, mobile token storage |
| [Q-009](questions/q-009-id-strategy.md) | UUIDv7 client-generated PKs vs server auto-increment | First migration; cannot retrofit |
| [Q-010](questions/q-010-offline-scope.md) | Which data is offline-capable in SQLite? | Sync engine size, MVP timeline |
| [Q-011](questions/q-011-mobile-shell.md) | Capacitor vs Flutter vs native for mobile/tablet | Workspace layout, hiring, timeline |
| [Q-012](questions/q-012-rich-text-editor.md) | Tiptap vs Quill vs Editor.js | Content schema for notebooks AND lessons |
| [Q-013](questions/q-013-monetization-loop.md) | Who pays first — teacher-led vs student-led growth; what does Free mean after 14 days? | Pricing module, onboarding funnel |
| [Q-014](questions/q-014-production-db.md) | Production DB engine — MySQL 8 vs Postgres 16 | Deploy runbook, sync-engine options |
| [Q-015](questions/q-015-geolocation.md) | Keep, reduce, or drop student geolocation? | Privacy/consent screens, signup flow |
| [Q-016](questions/q-016-chat-scope.md) | Messenger scope for MVP | Realtime infra, moderation exposure |

---

## 7. Next steps

1. Lead Developer fills the `**Decision:**` lines (or answers inline in chat and Claude records them into `decisions.md`).
2. Write the finalized concept paragraph into `documents/2026-09-12-001-Project-Details/` (separate file beside about.md) and replace CLAUDE.md §1's TBD with it + pointer.
3. Then, per current-status: scaffold the pnpm workspace and draft the first data-model plan (`plan/phase-1-*.md` in this folder or a successor task).
