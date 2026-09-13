# Phase 007 complete — Paper templates & page editor

_2026-09-14 · branch `Workstation-Laptop` · task 2026-09-13-005._

Opening a notebook shows faithful paper and typing happens in Tiptap blocks rendered ON that paper. The largest phase; both core components live in `packages/ui` and are never forked into an app (M2's teacher lesson-authoring needs them).

## What shipped

**Backend**

- `config/notebook.php` — the PHP mirror of `ALLOWED_NODES` / `ALLOWED_MARKS`, plus the 512KB size cap and a depth guard.
- `App\Support\TiptapSanitizer` — walks the document, drops any node/mark off the whitelist, caps heading levels, strips non-scalar attrs, forces a `doc` root. Runs on **every** content write.
- `App\Support\TiptapText::flatten()` — the `search_text` source, mirroring `packages/utility/page-search.ts`.
- `NotebookPageController` — 5 routes, `auth:sanctum`, `whereUuid()`. Ownership is **transitive**: every route resolves the notebook through `Notebook::owned()` first, so a page in someone else's notebook is a 404.

**Frontend — `packages/ui`**

- `paper/PaperPage.vue` — all four rulings, CSS-drawn, plus header/footer chrome and an `@media print` block.
- `editor/NotebookEditor.vue` — Tiptap, JSON in / JSON out, no HTML anywhere.
- `editor/EditorToolbar.vue` — controls for the whitelist and only the whitelist.
- `editor/usePages.ts` — load, debounced autosave (800ms), add/delete page, dirty indicator.

Notebook views per app: browser = page rail + centred paper; mobile = single portrait page with arrows. Library cards now **open** — the Phase-006 dead end is gone.

## The fidelity mechanism

`--paper-line-height` is set once on the paper element and drives **both** the ruling pitch and every text block's `line-height`. Verified by grep: no `line-height` in `PaperPage.vue` bypasses the variable. Two independent numbers is exactly how paper fidelity dies, so there is only one.

Values come from the approved canvas (board 07, decoded from source): 28px pitch at 16px text, ruling offset 2px so the line falls under the baseline rather than through it, margin rule at 44px, white paper.

## ⚠ Tiptap 3, not 2 — and StarterKit ships more than we allow

The phase file says "Tiptap 2". npm now resolves `@tiptap/*` to **3.31.3**, which changes two things:

1. **Underline moved into StarterKit**, so `@tiptap/extension-underline` is redundant — installed, then removed.
2. **Table extensions regrouped** into `TableKit` from `@tiptap/extension-table`.

The more important discovery: **StarterKit also bundles `blockquote`, `code`, `codeBlock` and `link` — none of which are on our whitelist.** Left enabled, the editor would happily produce nodes the server silently strips on save, which reads to a student as *"my work vanished"*. All four are explicitly `false` in the config, with a comment saying why.

This is the whitelist's first real test, and it found something. Anyone upgrading Tiptap must re-check what StarterKit includes.

## Verified

- **74 backend tests pass** (217 assertions); 18 new — flatten in document order / empty / whitespace-collapsing, sanitizer drops disallowed nodes and marks and nested ones, heading cap, size cap, doc root forced, page auth, client-minted id verbatim, JSON-not-HTML round trip, `search_text` maintained and **not client-settable**, stripping through the API, 422 envelope on oversize, transitive-ownership 404s on every verb, position ordering, soft delete.
- **The two-sided flatten contract proven**, not assumed: the same document through `TiptapText::flatten()` (PHP) and `flattenPageContent()` (TS) produces byte-identical output, accents and all.
- **Live round trip:** a page with a heading, an italic mark, a table and an injected `script` node → the script was stripped, everything else stored verbatim, and sent → stored → reloaded are all identical. `search_text` came back `"Week 1 Bacoór accents and 1/5 fractions Day Monday"`.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (27 routes).

## Not done — needs a human with a browser

**The manual fidelity pass is not mine to sign off.** The phase asks to eyeball each ruling against a real notebook photo at 100% zoom, in both apps. I verified the *mechanism* (one variable drives both, matching the approved canvas numbers) but **"text sits on the lines" is a visual judgement** that needs a person looking at a screen. Worth ten minutes with `pnpm dev:student:browser` and `:mobile`, checking one notebook per type.

The `⋯` card menu is still a `prompt()`. I left it: replacing it well means designing a real context menu, and this phase was already the largest. It is flagged in Phase-006's note and remains open.

## Notes for Phase 008

Attachments. The `image` node is **already enabled and whitelisted** on both sides, so a page carrying one round-trips today — what is missing is the toolbar affordance to insert one and the upload behind it. `PaperPage`'s slot takes any content, so images need no paper changes.
