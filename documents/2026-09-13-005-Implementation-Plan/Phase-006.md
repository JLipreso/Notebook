# Phase 006 — Notebook library

**Goal:** the student's notebook shelf — create, list, reorder, archive per school year, covers deferred to Phase-008 (needs uploads). This is the product's home screen.

**Prereq:** Phase-004 (auth). **Decisions:** D-010, D-011, D-013, D-023 (archives are forever).

## 1. Backend — `NotebookController`

Banner `// ==== NOTEBOOKS (2026-09-13-005 Phase 006) ====`; all `auth:sanctum`; ids `->whereUuid()`. Literal routes BEFORE the resource wildcards.

| Endpoint | Behavior |
|---|---|
| `GET /api/notebook-types` | active types with `page_template`, sorted — public shape straight to the `NotebookType` contract |
| `GET /api/notebooks?status=&school_year=` | own notebooks (`user_id = auth`), filterable, ordered by `position`; `->select()` only contract columns |
| `POST /api/notebooks` | validate `id` (**client-minted UUID, D-013** — accept and insert as-is; `uuid` + uniqueness), `notebook_type_id` exists, `title`, `school_year` format `\d{4}-\d{4}`; `user_id` = auth user ALWAYS |
| `PUT /api/notebooks/{id}` | own row only; title/font_family/position/notebook_type |
| `POST /api/notebooks/{id}/archive` and `/unarchive` | set/clear `status` + `archived_at` |
| `DELETE /api/notebooks/{id}` | SOFT delete (tombstone — sync depends on it) |

Ownership: every query scoped `where('user_id', $request->user()->id)` — write once as a model scope (`Notebook::owned($user)`), reuse everywhere including Phase-009's sync push.

## 2. Frontend

Shared in **`packages/ui/notebook/`** (both form factors render these): `NotebookCard.vue` (cover/type visual, title, school year, archived badge), `NotebookGrid.vue`, `NewNotebookDialog.vue` (type picker rendering a mini paper preview per type from `page_template`, title, school-year default from `deriveSchoolYear()`), `ArchiveShelf.vue` (grouped by school_year).

Per app, thin composition:
- **browser**: library route = default post-signin view; grid + sidebar filters (school year, type, archived); dialog for create.
- **mobile**: library = home tab; vertical shelf; FAB → full-screen create flow; archive under a segmented control.
- `useNotebooks()` composable per app (list/create/archive/reorder via `notebook.service`); **client mints the UUID** on create — use `mintId()` from `@notebook/sync` — so the same code path works offline later.
- School-year rule: `deriveSchoolYear()` from `@notebook/utility` is the ONLY source of the default; never re-derive inline.

## 3. Verification

- Feature tests: ownership isolation (user B cannot read/update/delete user A's notebook — 404 not 403 to avoid existence leaks), client-minted id accepted, duplicate id rejected, archive round-trip, soft delete leaves the row.
- Manual: create one notebook per type; archive one; switch school-year filter; reorder; both apps.

## Acceptance checklist

- [ ] Library CRUD + archive works in both apps, mock and live
- [ ] Client-side UUIDv7 minting on create (verify the id sent equals the id stored)
- [ ] Ownership tests green — cross-user access is a 404
- [ ] typecheck + build:all green · refresh-docs ran · PR (`M1 Phase 006 — notebook library`) · completion note
