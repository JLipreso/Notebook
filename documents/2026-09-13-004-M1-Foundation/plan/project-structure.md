# Notebook — Project Structure Plan

_Task 2026-09-13-004, Phase 1. Development reference — the source of truth for the workspace scaffold (Phase 3) and the structure half of the "Project Structure with ER Diagram" client document. Derives from D-001 (stack), D-005 (types contract), D-014…D-018 and CLAUDE.md §2–§5._

Status: **LOCKED — approved by the Lead Developer 2026-09-13** (including the two additional packages `@notebook/ui` and `@notebook/sync`). Changes from here require a new decision, not a drive-by edit.

---

## 1. Repository layout (target)

```
Monorepo-Notebook/
├── pnpm-workspace.yaml            # apps/* + packages/*
├── package.json                   # root scripts: dev, build, typecheck (all workspaces)
├── apps/
│   ├── student/                   # THE product app — web + Capacitor mobile/tablet (D-017)
│   │   ├── src/
│   │   ├── android/  ios/         # Capacitor native projects (committed)
│   │   ├── capacitor.config.ts
│   │   ├── vite.config.ts  tsconfig.app.json   # aliases declared in BOTH (§4 rule)
│   │   └── .env.example
│   ├── teacher/                   # web portal (M2); Capacitor added later if needed
│   └── admin/                     # web only (M3)
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
| `apps/student` | `@notebook/student` | **5171** | `app.<domain>` + Play Store/App Store via Capacitor | M1 |
| `apps/teacher` | `@notebook/teacher` | **5172** | `teach.<domain>` | M2 |
| `apps/admin` | `@notebook/admin` | **5173** | `admin.<domain>` | M3 |

(Ports = plan README O-2; confirm against Exploria habits.)

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

### `@notebook/ui` — shared Vue components (proposed addition)
The reason this package must exist: the **notebook editor and paper templates (D-010/D-012/D-018) are needed by BOTH student (write) and teacher (lesson authoring + viewing shared pages)** — duplicating them per app would fork the product's core.
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

1. Aliases in BOTH `tsconfig.app.json` and `vite.config.ts` — change both or neither (§4).
2. `pnpm typecheck` + `pnpm build` from root before pushing (§4).
3. Any `routes/api.php` or `.env.example` change ⇒ `/refresh-docs` in the same commit (§6).
4. CLAUDE.md §2 table updated in the scaffold commit (convention rule 4/5) — including the two new packages.
5. New subsystem docs (sync engine!) get a routing-table row in the same commit.
6. PRs → `staging` (§8).
