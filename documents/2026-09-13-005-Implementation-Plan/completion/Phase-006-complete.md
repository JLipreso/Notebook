# Phase 006 complete — Notebook library

_2026-09-13 · branch `Workstation-Laptop` · task 2026-09-13-005._

The student's shelf: create, list, filter, archive per school year, soft delete. This is now the default post-signin view in both apps, replacing the Phase-003 placeholder.

## What shipped

**Backend — `NotebookController`** (8 routes, all `auth:sanctum`, ids `->whereUuid()`):

| Endpoint | Notes |
|---|---|
| `GET /api/notebook-types` | active types with `page_template`, sorted |
| `GET /api/notebooks?status=&school_year=` | own rows only, ordered by `position` |
| `POST /api/notebooks` | **client-minted id** accepted verbatim (D-013) |
| `GET/PUT /api/notebooks/{id}` | own row only |
| `POST /api/notebooks/{id}/archive` · `/unarchive` | sets/clears `status` + `archived_at` |
| `DELETE /api/notebooks/{id}` | SOFT delete — tombstone for sync |

Ownership runs through `Notebook::owned()` on every query, written once in Phase 002 and reused here (and by Phase 009's sync push).

**Frontend — `packages/ui/notebook/`** (shared, both form factors):

`NotebookCard.vue` · `NotebookCover.vue` · `NotebookGrid.vue` · `NewNotebookDialog.vue` · `PaperPreview.vue` · `ArchiveShelf.vue` · `useNotebooks.ts`

Per app, thin composition only: browser = shelf + sidebar filters; mobile = portrait shelf, segmented control, FAB, full-screen create flow.

## Design fidelity

Built against the approved mobile canvas ([board 05, Library](../../2026-09-13-006-Brand-Colors/Notebook-Mobile-22-screens.html)), decoded from the canvas source rather than eyeballed:

- Greeting header ("Magandang umaga, {first_name}!") with the school year beneath
- "Active notebooks" label with a count
- 2-up card grid, 152px covers
- Cover visual: ink-navy board, darker spine down the left edge, one rule line near the top, title in Fraunces on cream
- Archive entry point; FAB bottom-right in margin red

`NotebookCover` tints by ruling so a shelf is scannable. **Those four hex values are the one place outside the Tailwind preset that names brand colours** — Tailwind cannot build a class from a runtime value. They are lifted from the preset's own palette (D-032) and must move with it; the comment says so.

## Decisions worth knowing

1. **`PaperPreview` is deliberately separate from Phase 007's `PaperPage`.** It is a swatch for the type picker — CSS-drawn ruling from `page_template`, no editor surface. Keeping them apart stops Phase 007 from growing this one into the real paper renderer.

2. **The card menu is a `prompt()`, and that is temporary.** A real context menu belongs with the editor's chrome in Phase 007. Rather than ship a dead `⋯` button, archive/restore/delete are reachable through a prompt — honest and functional, obviously interim. **Phase 007 should replace it.**

3. **Opening a notebook routes to `?notebook=<id>` and does nothing yet** — the page editor is Phase 007. The click is wired so the card is not dead, but there is no destination.

4. **`fresh()` takes RELATIONS, not columns.** `$notebook->fresh(self::COLUMNS)` threw *"Call to undefined relationship [id]"*. Replaced with a `reload()` helper that re-queries through the column list. Worth remembering — the signature reads like `select()` but is not.

## Verified

- **56 backend tests pass** (177 assertions); 14 are new, including: client-minted id stored verbatim, duplicate id rejected, `user_id` ignored when posted, school-year format enforced, index lists only own rows, status + school-year filters, **cross-user access is a 404 on every verb** (not 403 — a 403 confirms the id exists), update ignores `user_id`/`status`, archive round trip, soft delete leaves a tombstone and clears the shelf, non-UUID ids do not match the route, position defaults to the end.
- **Live round trip against the API:** minted `01a09b65-…` client-side → `POST` → **stored id matches exactly**; archive set `archived_at`; `DELETE` returned 200 and `withTrashed()` still finds the row.
- **Mock mode with the backend stopped:** 7 types, create with verbatim id, archived filter, soft delete removes from the shelf.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (22 routes).

## Removed

Both apps' `HomeView.vue` — the Phase-003 contract proof. `LibraryView` is now the `/` route, as Phase 006 specifies.

## Notes for Phase 007

The editor is the largest phase and the product's soul. Two things from here feed directly into it:

- **`page_template` is now consumed in three places**: the Phase-002 seeder writes it, `@notebook/types` types it, and `PaperPreview` renders a thumbnail from it. Phase 007's `PaperPage.vue` is the fourth and the real one — the swatch is not a starting point for it, because the fidelity requirement (text sitting ON the ruling, driven by one CSS variable) is what the full renderer exists to solve.
- **Replace the `prompt()` card menu** with real chrome while building the editor's toolbar.
