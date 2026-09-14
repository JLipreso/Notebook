<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tiptap node / mark whitelist (2026-09-13-005 Phase 007, D-018)
    |--------------------------------------------------------------------------
    |
    | ============================ TWO-SIDED CONTRACT ==========================
    | This is the PHP MIRROR of ALLOWED_NODES / ALLOWED_MARKS in
    | packages/types/notebook-page.ts. The editor enables exactly these, and the
    | sanitizer drops anything else on every content write.
    |
    | Adding a node type is ALWAYS a two-file change in ONE commit: this array
    | AND the TS const. A node allowed on only one side either silently vanishes
    | on save (allowed in the editor, stripped here) or slips past sanitization
    | (allowed here, never produced — harmless but a lie).
    |
    | The drawing/ink node is deliberately ABSENT — reserved for post-M1 (D-012).
    | ==========================================================================
    */

    'allowed_nodes' => [
        'doc',
        'paragraph',
        'text',
        'heading',
        'bulletList',
        'orderedList',
        'listItem',
        'table',
        'tableRow',
        'tableHeader',
        'tableCell',
        // Reachable from Phase 008 (attachments); the node is allowed now so a
        // page written by a newer client is not silently mangled by an older API.
        'image',
        'horizontalRule',
        'hardBreak',
    ],

    'allowed_marks' => [
        'bold',
        'italic',
        'underline',
        'strike',
    ],

    /** Headings are capped at two levels (Phase 007 MVP). */
    'allowed_heading_levels' => [1, 2],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */

    /** Max serialized size of one page's content. Never trust client JSON. */
    'max_page_content_bytes' => 512 * 1024,

    /** Depth guard: a pathological nesting would blow the stack while walking. */
    'max_content_depth' => 50,

    /*
    |--------------------------------------------------------------------------
    | Uploads (2026-09-13-005 Phase 008)
    |--------------------------------------------------------------------------
    |
    | MIME types are matched against the file's CONTENT, never its extension or
    | the client-supplied Content-Type — renaming evil.exe to photo.jpg must be
    | rejected (validation lesson, phase file §3).
    */

    'upload_mimes' => [
        'image' => ['image/jpeg', 'image/png', 'image/webp'],
        // Covers are images; kept separate so the size cap can differ.
        'cover' => ['image/jpeg', 'image/png', 'image/webp'],
        'pdf' => ['application/pdf'],
        'document' => ['application/pdf'],
    ],

    /** Per-kind size caps, in bytes. */
    'upload_max_bytes' => [
        'image' => 10 * 1024 * 1024,
        'cover' => 5 * 1024 * 1024,
        'pdf' => 25 * 1024 * 1024,
        'document' => 25 * 1024 * 1024,
    ],

    /*
    | Per-user storage quota. A flat constant for M1; D-022/D-023 make this a
    | per-plan lever in M3, at which point this value becomes the free-tier
    | floor rather than the only number.
    */
    'quota_mb' => 500,

    /*
    |--------------------------------------------------------------------------
    | Offline sync (2026-09-13-005 Phase 009, schema §2)
    |--------------------------------------------------------------------------
    |
    | A FIXED map — {table} from the URL is checked against these keys and never
    | interpolated into a query. A free string here would be a table-name
    | injection straight into the query builder.
    |
    | rw = pulled AND pushed. ro = pulled only; a push to one is refused.
    |
    | PSGC is deliberately absent: 42k rows that change quarterly ship with the
    | app, not down a sync pipe (phase file §1).
    */

    'sync_tables' => [
        'rw' => ['notebooks', 'notebook_pages', 'page_attachments'],
        'ro' => ['notebook_types', 'users'],
    ],

    /** Max rows per pull page (schema §2.2). */
    'sync_pull_limit' => 500,

    /** Max rows the client may push in one batch (schema §2.3). */
    'sync_push_limit' => 100,

];
