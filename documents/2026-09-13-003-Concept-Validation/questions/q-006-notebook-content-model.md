# Q-006 — What is a notebook page?

The brief never defines the core editing experience. This decides the editor, the SQLite schema, the sync granularity, and whether tablet stylus/pen is in scope. The notebook types hint at different answers: "Composition/Diary/Log Book/Timesheet" suggest typed text; "Drawing Book/Scrapbook" suggest a canvas.

**Lead Developer clarification (2026-09-13, with reference photos):** notebook types mirror the real Philippine paper formats — **Composition** = single-color ruled lines with a red margin line (HS/college standard); **Writing** = the preschool penmanship format with alternating blue/red guide lines, a `Date:` header, and `Teacher's Signature` / `Parent's Signature` footer lines. Two consequences:
1. Whatever the content model, pages must **render as faithful paper templates** (ruling pattern, margin, header/footer fields per type) — the paper look is the product identity. This is a rendering/template concern and works under every option below.
2. The **Writing** type raises a sub-question the options below must answer: a real writing notebook exists for *penmanship practice* — is the digital Writing notebook (a) a visual style for typed text, or (b) an ink surface where a preschooler traces/writes with finger or stylus? (b) is the first concrete ink-canvas requirement in the product. → asked as Q-006b.

**Option A — Typed block document (recommended for MVP).** A page is a Tiptap/ProseMirror JSON document: headings, paragraphs, lists, tables, images, checkboxes. Notebook types are templates/presets over the same schema (Timesheet = table preset, Diary = dated entries).
- ✅ One schema for notebooks AND lessons; JSON syncs and merges well; works identically on web/mobile/tablet; fastest to ship.
- ❌ "Drawing Book" is reduced to image-attach until a drawing block exists.

**Option B — Freeform ink canvas.** Pages are stroke data (like GoodNotes/OneNote): stylus handwriting, drawing, image placement anywhere.
- ✅ Closest to a real paper notebook; killer on tablets with pens.
- ❌ Hardest engineering (stroke rendering, zoom, palm rejection); stroke data is heavy to sync; weak on phones/web; search requires OCR.

**Option C — Hybrid, staged.** Ship Option A now with the schema designed for a future `drawing` block type (a canvas/ink block embedded in the block document); Drawing Book/Scrapbook types unlock fully when that block lands.
- ✅ A's speed with a credible path to B's tablet experience; no schema rewrite.
- ❌ Drawing-first users underserved at launch — must be honest in marketing.

**Decision:** Effectively Option C staged as A-now: typed block documents (Tiptap JSON) on paper-styled templates, schema reserving a future drawing/ink block. Q-006b (Writing type): visual style only in MVP — typed content on the penmanship guide-line template; handwriting/tracing input arrives with the ink block. Locked 2026-09-13 as **D-012** (Lead Developer via option prompt).
