# Notebook — Project Structure Plan

_Task 2026-09-13-004, Phase 1. Development reference — the source of truth for the workspace scaffold (Phase 3) and the structure half of the "Project Structure with ER Diagram" client document. Derives from D-001 (stack), D-005 (types contract), D-014…D-018, D-025/D-026 (platform matrix + sequencing) and CLAUDE.md §2–§5._

Status: **LOCKED — approved by the Lead Developer 2026-09-13** (including the two additional packages `@notebook/ui` and `@notebook/sync`). Boss review 2026-09-13: approved; platform reach recorded as **D-025/D-026**, a structural request reshaped `apps/` into per-form-factor apps (**D-027**, §1/§2/§2a below), and the Lead Developer closed O-5 → **D-028** (one Capacitor app per role) and O-6 → **D-029** (M1 = browser + mobile; tablet later). Changes from here require a new decision, not a drive-by edit.

---

## 1. Repository layout (target)

```
Monorepo-Notebook/
├── pnpm-workspace.yaml            # apps/* + packages/*
├── package.json                   # root scripts: dev, build, typecheck (all workspaces)
├── apps/
│   ├── student/                   # role folder — one THIN app per form factor (D-027)
│   │   ├── browser/               # responsive web for laptop/desktop → app.<domain>; its dist also feeds desktop/
│   │   ├── desktop/               # Electron wrapper consuming ../browser/dist — NO UI code of its own (D-025/D-027)
│   │   ├── mobile/                # phone UI, locked PORTRAIT (M1)
│   │   ├── tablet/                # tablet UI, locked LANDSCAPE (deferred — D-029)
│   │   └── native/                # THE Capacitor project (D-028): webDir bundles mobile (+ tablet later)
│   │                              #   builds behind a startup form-factor bootstrap → ONE Play listing
│   ├── teacher/                   # same five form-factor folders (M2 — D-025/D-027)
│   └── admin/                     # web only (M3); nests as admin/browser/ for glob uniformity
│                                  # each form-factor app = own workspace package with vite.config.ts +
│                                  # tsconfig.app.json (aliases in BOTH, §4 rule) + its own .env.example;
│                                  # workspace globs are apps/*/* (role folders hold no package.json)
├── packages/
│   ├── types/                     # @notebook/types — THE API contract (D-005)
│   ├── services/                  # @notebook/services — API layer + datasource switch
│   ├── utility/                   # @notebook/utility — pure business logic + helpers
│   ├── ui/                        # @notebook/ui — shared Vue components (editor, paper templates)
│   └── sync/                      # @notebook/sync — offline engine (D-013/D-016/D-017)
├── backend/                       # Laravel 12 — sibling, NOT a workspace member (D-001)
├── documents/  scripts/  .claude/ # unchanged
```

Deltas vs CLAUDE.md §2's three-package table: **`packages/ui`** and **`packages/sync`** are additions this plan proposes (justified in §3); §2's table gets updated in the same commit that scaffolds them (convention rule).

## 2. Apps

| App | Package name | Dev port | Prod target (Q-004 pending) | Milestone |
|---|---|---|---|---|
| `apps/student/browser` | `@notebook/student-browser` | **5171** | `app.<domain>` | M1 |
| `apps/student/mobile` | `@notebook/student-mobile` | **5174** | ships inside `native/` — portrait phone UI | M1 |
| `apps/student/tablet` | `@notebook/student-tablet` | **5176** | joins the `native/` bundle — landscape tablet UI | after M1 core (D-029) |
| `apps/student/native` | — (Capacitor project, not a Vite app) | — | Play Store / App Store — ONE listing bundling the form-factor builds (D-028) | M1 |
| `apps/student/desktop` | `@notebook/student-desktop` | — (wraps `browser/dist`) | Windows/macOS installers — after Android is feature-complete (D-026) | post-Android |
| `apps/teacher/browser` | `@notebook/teacher-browser` | **5172** | `teach.<domain>` | M2 |
| `apps/teacher/mobile` | `@notebook/teacher-mobile` | **5175** | ships inside teacher `native/` — portrait | M2 |
| `apps/teacher/tablet` | `@notebook/teacher-tablet` | **5177** | joins teacher `native/` — landscape | after M2 core (D-029) |
| `apps/teacher/desktop` | `@notebook/teacher-desktop` | — (wraps `browser/dist`) | installers — after Android (D-026) | post-Android |
| `apps/admin/browser` | `@notebook/admin` | **5173** | `admin.<domain>` (web only) | M3 |

(Ports: browser apps keep O-2's 5171–5173; mobile 5174/5175, tablet 5176/5177. `desktop/` has no dev port — during development you use the browser app; Electron is exercised only when packaging.)

### 2a. Platform matrix, form factors & release sequencing (D-025 / D-026 / D-027)

Both **student** and **teacher** reach every platform. Per D-027 each form factor gets its own THIN app — a deliberate design decision (portrait phone UI vs landscape tablet spread vs desktop web), NOT three products:

| Form factor | Folder | Shell | Orientation | Offline story | Ships |
|---|---|---|---|---|---|
| Laptop/desktop browser | `browser/` | none — web build at its subdomain | free | API-direct (no local DB in MVP) | first, each milestone |
| Phone | `mobile/` | Capacitor (D-017) | locked **portrait** (screen-orientation plugin + Android manifest) | full RW offline via `@capacitor-community/sqlite` | first, each milestone |
| Tablet | `tablet/` | Capacitor (D-017) | locked **landscape** | same as phone | deferred past the M-core (D-029) |
| Desktop app | `desktop/` | Electron consuming `../browser/dist` — no UI code of its own | free | inherits browser build (SQLite adapter possible later via the `@notebook/sync` seam) | **after Android is feature-complete** |

- **Sequencing (D-026):** web + Android first → Electron → iOS last, per form factor.
- **The anti-fork guardrail (D-027):** domain components and ALL business logic live in `packages/*` — `@notebook/ui` owns every component used by two or more form factors (editor, paper renderer, notebook cards, quiz widgets…). Form-factor apps contain ONLY layout, navigation and screen composition. In review: a PR adding a domain component under `apps/` is wrong by definition.
- **No view ever calls a native/desktop API raw** — everything goes through `@notebook/sync` adapters / `platform.ts`.
- **Android packaging (D-028, closed O-5):** ONE Capacitor project per role at `apps/<role>/native/` — Google Play allows one binary per listing, so `native/`'s webDir is a combined dist: a tiny startup bootstrap that picks the mobile or tablet build by screen size. One listing, one install, the right UI on every device. While tablet is deferred (D-029) the bootstrap simply always loads the mobile build.
- **M1 form-factor order (D-029, closed O-6):** M1 = `browser/` + `mobile/` (+ `native/` shipping the mobile build). The tablet design follows after the M1 core; teacher follows the same browser-and-mobile-first order in M2.
- Electron scaffolding stays out of the workspace scaffold phase; it gets its own task folder when Android reaches feature-complete.

Each app: Vue 3.5 + Vite 6 + TS 5.7 + Pinia (auth store ONLY, per §3 golden rule) + Vue Router 4 + Tailwind 3.4 + Reka UI. Per-view state in composables. Every app consumes the shared packages via workspace aliases; `vue-tsc -b` includes the packages so a shared type error fails every app build (§4).

**Capacitor placement (D-017):** native projects live INSIDE `apps/student` (the app is the unit that ships to stores). Plugins for M1: `@capacitor-community/sqlite`, `@capacitor/filesystem`, `@capacitor/camera`, `@capacitor/push-notifications` (FCM), `@capacitor/printer`-equivalent for D-009 lesson printing (M2). The web build of the same app runs without Capacitor — every native call goes through the adapter seam in `@notebook/sync` / a small `platform.ts` helper, never called raw from views.

## 3. Shared packages (raw TS, zero Vue except `ui`)

### `@notebook/types` — the frozen contract (D-005)
One file per domain mirroring the schema tables exactly (snake_case fields): `user.ts`, `address.ts`, `notebook.ts`, `notebook-page.ts` (incl. the Tiptap `PageContent` type + allowed node whitelist), `share.ts`, `notification.ts`, `course.ts`, `lesson.ts`, `assessment.ts`, `chat.ts`, `billing.ts`, plus `api.ts` (`ApiResponse<T>`, `PaginatedResponse<T>` — §3 envelopes) and `sync.ts` (pull/push payloads, cursors). **Laravel adapts to these types, never the reverse.**

### `@notebook/services` — API layer + the ONE mock switch
- `http.ts` — axios singleton (base URL from env, Sanctum bearer injection, 401 → re-exchange flow per D-015).
- `datasource.ts` — **the only module that reads `VITE_USE_MOCK`** and the only importer of `mock/` (§3 golden rule).
- `mock/` — mock db (JSON fixtures + latency shim) for pre-backend UI work.
- One service per domain: `auth.service.ts` (Firebase sign-in → `POST /api/auth/firebase` exchange), `notebook.service.ts`, `page.service.ts`, `share.service.ts`, `notification.service.ts`, `address.service.ts` (PSGC dropdown chain), then M2/M3 services as they land.

### `@notebook/utility` — canonical logic
`school-year.ts` (derive/format `2026-2027`), `page-search.ts` (Tiptap JSON → plain text, shared by client search + server parity), `entitlement-keys.ts` (feature-key constants shared with backend seeds), date/format helpers. **When money math appears (M3) its canonical module lives here** and gets a CLAUDE.md routing row.

### `@notebook/ui` — shared Vue components (proposed addition; role widened by D-027)
The reason this package must exist: the **notebook editor and paper templates (D-010/D-012/D-018) are needed by BOTH student (write) and teacher (lesson authoring + viewing shared pages)** — duplicating them per app would fork the product's core. Under D-027 its role widens: it owns **every domain component used by two or more form-factor apps** (notebook cards, page lists, quiz widgets, …) — the form-factor apps compose these into portrait/landscape/desktop layouts but never reimplement them.
- `editor/` — Tiptap 2 wrapper (`NotebookEditor.vue`, `LessonEditor.vue`), custom extensions, node whitelist (shared with `types`).
- `paper/` — `PaperPage.vue` rendering a `notebook_types.page_template` JSON: ruling patterns (`single_ruled`, `penmanship_blue_red`, `blank`, `grid`), margin lines, header/footer fields (`Date:`, signatures). CSS-drawn (repeating gradients/SVG), print-faithful for D-009.
- `brand/` — the ONE shared Tailwind preset + tokens (§4: brand tokens live in one preset, not per app).

### `@notebook/sync` — the offline engine (proposed addition)
Isolated so the hairy part has one home and real tests:
- `ids.ts` — UUIDv7 minting (D-013).
- `storage/` — adapter interface + `sqlite.adapter.ts` (Capacitor, on-device schema mirroring the [RW]/[RO] tables) + `memory.adapter.ts` (web dev/tests; web MVP runs API-direct).
- `outbox.ts` (dirty-row queue), `pull.ts` (per-table cursors), `push.ts` (LWW batches) — implementing exactly `database-schema.md` §2.
- `attachment-uploader.ts` — pending-upload worker (§2.5).

## 4. Backend organization (Laravel 12, per §5 conventions — flat, no improvisation)

```
backend/app/Http/Controllers/Api/
    ApiController.php          # exists (envelope)
    AuthController.php         # POST /api/auth/firebase (D-015), me, logout
    AddressController.php      # PSGC dropdown chain (public, rate-limited)
    NotebookController.php     # library CRUD, archive
    NotebookPageController.php # page CRUD (online path)
    SyncController.php         # GET/POST /api/sync/{table} — the D-016 contract
    FileUploadController.php   # store/serve, MIME allowlist, quota check
    ShareController.php        # create/revoke/resolve share links
    NotificationController.php # list, mark-read
    (M2) CourseController, InvitationController, LessonController,
         AssessmentController, AttemptController, ChatController
    (M3) PlanController, SubscriptionController, PaymentController,
         Admin/…  ← flat naming stays; admin-only routes guarded by role middleware, not a folder
```

- Routes: single `routes/api.php`, banner comments with task IDs, one `Route::` per line (generator constraint), literal routes before wildcards, `->whereUuid()` (not `whereNumber` — D-013) on id params.
- Auth: `firebase` exchange endpoint verifies the ID token via `kreait/laravel-firebase` (**service-account JSON via env path, NEVER committed** — MIIT lesson), issues Sanctum bearer with role ability.
- Middleware: existing scaffold (Cors first, SecurityHeaders, JSON-only) + `EnsureRole:{role}` for teacher/admin route groups.
- Broadcasting: Pusher Channels (D-007) — `routes/channels.php` authorizes `course.{id}` from `course_students`/`courses.teacher_id`.
- Models: `HasUuids` (UUIDv7) base trait; `SoftDeletes` per schema §8; `$casts` for JSON columns; **no API Resources** — `->select()`/`with('rel:cols')` shaped to `@notebook/types`.
- Seeders: `PsgcSeeder` (from committed PSA snapshot, O-1), `NotebookTypeSeeder` (7 launch types + `page_template` JSON), `(M3) PlanSeeder`.

## 5. Environment variables (each app + backend keeps `.env.example` committed; `/refresh-docs` regenerates the reference)

| Where | Key vars |
|---|---|
| `apps/*` (shared shape) | `VITE_API_URL`, `VITE_USE_MOCK`, `VITE_DEMO_MODE` (§4 gate), `VITE_FIREBASE_*` (public web config), `VITE_PUSHER_KEY` + `VITE_PUSHER_CLUSTER` |
| `backend/` | existing scaffold vars + `FIREBASE_CREDENTIALS` (absolute path, outside repo), `PUSHER_APP_ID/KEY/SECRET/CLUSTER`, `DB_*` (MySQL 8 in prod per D-019; sqlite dev), storage disk config |

## 6. Conventions checklist for Phase 3–4 implementers

1. Aliases in BOTH `tsconfig.app.json` and `vite.config.ts` — change both or neither (§4). Workspace globs are `apps/*/*`; role folders (`apps/student/`) hold no `package.json`.
1a. **D-027 guardrail:** form-factor apps contain ONLY layout/navigation/composition. Any domain component needed by a second form factor moves to `@notebook/ui` in the same PR.
2. `pnpm typecheck` + `pnpm build` from root before pushing (§4).
3. Any `routes/api.php` or `.env.example` change ⇒ `/refresh-docs` in the same commit (§6).
4. CLAUDE.md §2 table updated in the scaffold commit (convention rule 4/5) — including the two new packages.
5. New subsystem docs (sync engine!) get a routing-table row in the same commit.
6. PRs → `staging` (§8).
