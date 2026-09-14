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

];
