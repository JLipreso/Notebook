# Phase 007 — Paper templates & page editor

**Goal:** the product's soul — opening a notebook shows faithful paper pages (D-010) and typing happens in Tiptap blocks rendered ON that paper (D-012/D-018). This is the largest phase; both core components live in `packages/ui` (they're needed by teacher lesson-authoring in M2 — never fork them into an app).

**Prereq:** Phase-006. **Decisions:** D-010, D-012, D-013, D-018.

## 1. `packages/ui/paper/PaperPage.vue` — the paper renderer

Renders one page's chrome from a `PageTemplate` (the typed `notebook_types.page_template` from Phase-002/003 — if the shape needs adjusting, change seeder + type TOGETHER):

- Ruling patterns, CSS-drawn (repeating-linear-gradient or inline SVG — no image assets): `single_ruled` (+ red margin line, Composition), `penmanship_blue_red` (alternating blue/blue/red guide lines, Writing), `blank` (Drawing/Scrapbook), `grid` (Log Book/Timesheet).
- Header/footer fields from the template (`Date:` line, `Teacher's Signature` / `Parent's Signature` rules) — rendered, not editable blocks.
- Line-height sync: the ruling spacing and the editor's text line-height derive from ONE CSS variable (`--paper-line-height`) so text sits ON the lines. This is the fidelity that IS the product (D-010) — eyeball it against a real notebook photo.
- Print-faithful: an `@media print` block keeping rulings + content (feeds M2's D-009 later).

## 2. `packages/ui/editor/NotebookEditor.vue` — Tiptap 2

- Deps (add to `packages/ui`): `@tiptap/core`, `@tiptap/vue-3`, `@tiptap/starter-kit` + the specific extensions you enable.
- **Node/mark whitelist comes from `ALLOWED_NODES` in `@notebook/types`** (Phase-003). MVP: paragraph, heading(2 levels), bold/italic/underline/strike, bullet/ordered list, table (Timesheet preset), image node (placeholder until Phase-008), horizontal rule. NOTHING else — the backend sanitizer mirrors this list; adding a node type is a two-sided change.
- Emits debounced `update:content` with the Tiptap JSON. NO HTML anywhere (D-018) — `content` is JSON in, JSON out.
- Reserved ink block (D-012): do NOT build it; just leave the node name reserved in the whitelist comment.

## 3. Page plumbing

### Backend — `NotebookPageController`
Banner `// ==== NOTEBOOK PAGES (2026-09-13-005 Phase 007) ====`, `auth:sanctum`, whereUuid:
- `GET /api/notebooks/{notebook}/pages` (own notebook; ordered by `position`; select contract columns, `content` included)
- `POST /api/notebooks/{notebook}/pages` — client-minted `id`, `position`, `content` JSON
- `PUT /api/pages/{id}` / `DELETE /api/pages/{id}` (soft)
- **Server-side sanitization on EVERY content write** (here and in Phase-009's push): walk the Tiptap JSON, drop any node/mark not in the PHP mirror of the whitelist (one config file `config/notebook.php` → `allowed_nodes`), enforce a max content size (e.g. 512KB per page). Never trust client JSON.
- **`search_text` maintained on every write**: flatten JSON text nodes server-side (one helper, `App\Support\TiptapText::flatten()`); mirrors `packages/utility/page-search.ts` (write that too — client-side search uses it in mock/offline).

### Frontend
- Notebook view per app: browser = two-page-ish desktop layout with page list sidebar; mobile = single page, swipe/arrows between pages, portrait. Both compose `PaperPage` + `NotebookEditor` from `packages/ui` and a `usePages()` composable (load, debounced autosave ~800ms, add page, delete page, dirty indicator).
- Autosave path goes through `page.service` — the SAME call shape Phase-009 will reroute through the outbox; keep the composable ignorant of transport.
- Page search (client): simple filter over `search_text` for now (server FULLTEXT endpoint can come with a later polish phase).

## 4. Verification

- Unit-test `TiptapText::flatten` and the sanitizer (disallowed node dropped, size cap enforced). Test page CRUD ownership like Phase-006.
- Manual fidelity pass per type: Composition ruling + margin, Writing guide lines with Date/signature chrome, grid, blank; text sits on lines at 100% zoom in browser AND on the 5174 mobile viewport.
- Type a page, wait for autosave, reload — content identical (JSON round-trip clean).

## Acceptance checklist

- [ ] All 4 ruling patterns render faithfully in both apps; text baseline sits on the ruling
- [ ] Editing autosaves Tiptap JSON; reload round-trips exactly; whitelist enforced both sides
- [ ] `search_text` populated on write; editor and paper components live in `packages/ui` ONLY
- [ ] Tests green · typecheck + build:all green · refresh-docs ran · PR (`M1 Phase 007 — paper & editor`) · completion note
