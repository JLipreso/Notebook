# Q-012 — Rich-text editor (notebook pages AND lesson content)

The brief explicitly asks for a WYSIWYG editor that outputs JSON, not HTML. MIIT uses Quill (HTML in mediumText — listed as a do-differently). One editor should serve both notebook pages and teacher lesson authoring.

**Option A — Tiptap 2 (recommended).** ProseMirror-based, Vue 3 first-class, JSON document model.
- ✅ Exactly the JSON-output requirement; custom node types (checkbox, table, image, future drawing/ink block per Q-006) are the extension model, not a hack; collaborative-editing path exists (Yjs) if shared read-write notebooks need it; huge ecosystem.
- ❌ Advanced extensions are a paid tier (core is MIT and sufficient for MVP).

**Option B — Editor.js / BlockNote.**
- ✅ Block-JSON native; simple output shape.
- ❌ Weaker Vue integration (React-centric or vanilla); smaller ecosystems; inline formatting model weaker than ProseMirror.

**Option C — Quill 2 (MIIT continuity).**
- ✅ Team familiarity.
- ❌ Delta format is not the document-tree JSON the brief wants; custom block types are painful; repeats the MIIT content-model lesson.

**Decision:** Option A — Tiptap 2. Locked 2026-09-13 as **D-018** (Lead Developer via option prompt).
