<?php

namespace Database\Seeders;

use App\Models\NotebookType;
use Illuminate\Database\Seeder;

/**
 * The seven launch notebook types (D-010, D-012) — 2026-09-13-005 Phase 002.
 *
 * ============================ READ BEFORE EDITING ============================
 * `page_template` is a CONTRACT, not free-form config. Phase 003 declares its
 * typed shape as `PageTemplate` in @notebook/types, and Phase 007's
 * packages/ui/paper/PaperPage.vue renders directly from it. The three move
 * together — changing a key here without changing the interface and the
 * renderer breaks paper fidelity, which IS the product (D-010).
 *
 * Shape:
 *   ruling            single_ruled | penmanship_blue_red | blank | grid
 *   line_spacing_mm   float   — drives the --paper-line-height CSS variable so
 *                               the editor's text baseline sits ON the ruling
 *   margin            null | { side, offset_mm, color }
 *   grid              null | { size_mm }         (ruling=grid only)
 *   header_fields     array of { key, label, type }   — chrome, NOT editable blocks
 *   footer_fields     array of { key, label, type }
 *   default_blocks    array of Tiptap node names pre-inserted into a new page
 *
 * Colors are token NAMES from packages/ui/brand/tailwind-preset.cjs (D-032),
 * never hex — CLAUDE.md §4 forbids a hardcoded hex in a view, and this JSON
 * feeds a view.
 * =============================================================================
 *
 * Idempotent: upserts on the unique `key`, so re-running never duplicates.
 */
class NotebookTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->types() as $sortOrder => $type) {
            NotebookType::updateOrCreate(
                ['key' => $type['key']],
                [...$type, 'sort_order' => $sortOrder],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function types(): array
    {
        return [
            [
                'key' => 'composition',
                'name' => 'Composition Notebook',
                'description' => 'The classic ruled notebook with a red margin line — the everyday workhorse for notes and essays.',
                'requires_ink' => false,
                'page_template' => [
                    'ruling' => 'single_ruled',
                    'line_spacing_mm' => 8.0,
                    'margin' => ['side' => 'left', 'offset_mm' => 25.0, 'color' => 'margin'],
                    'grid' => null,
                    'header_fields' => [],
                    'footer_fields' => [],
                    'default_blocks' => ['paragraph'],
                ],
            ],
            [
                'key' => 'writing',
                'name' => 'Writing Notebook',
                'description' => 'Penmanship guide lines for early learners, with date and signature chrome.',
                'min_level' => 'preschool',
                'audience_hint' => 'Preschool to Grade 3',
                'requires_ink' => false,
                'page_template' => [
                    // Blue/blue/red guide bands — the classic penmanship ruling.
                    'ruling' => 'penmanship_blue_red',
                    'line_spacing_mm' => 12.0,
                    'margin' => null,
                    'grid' => null,
                    'header_fields' => [
                        ['key' => 'date', 'label' => 'Date:', 'type' => 'date'],
                    ],
                    'footer_fields' => [
                        ['key' => 'teacher_signature', 'label' => "Teacher's Signature", 'type' => 'signature'],
                        ['key' => 'parent_signature', 'label' => "Parent's Signature", 'type' => 'signature'],
                    ],
                    'default_blocks' => ['paragraph'],
                ],
            ],
            [
                'key' => 'drawing',
                'name' => 'Drawing Notebook',
                'description' => 'Blank pages for sketching and art.',
                'audience_hint' => 'All levels',
                // Only fully unlocks once the ink/drawing block ships (D-012).
                'requires_ink' => true,
                'page_template' => [
                    'ruling' => 'blank',
                    'line_spacing_mm' => 0.0,
                    'margin' => null,
                    'grid' => null,
                    'header_fields' => [],
                    'footer_fields' => [],
                    'default_blocks' => [],
                ],
            ],
            [
                'key' => 'diary',
                'name' => 'Diary',
                'description' => 'A dated personal journal, one entry per page.',
                'requires_ink' => false,
                'page_template' => [
                    'ruling' => 'single_ruled',
                    'line_spacing_mm' => 8.0,
                    'margin' => null,
                    'grid' => null,
                    'header_fields' => [
                        ['key' => 'date', 'label' => 'Date:', 'type' => 'date'],
                    ],
                    'footer_fields' => [],
                    'default_blocks' => ['paragraph'],
                ],
            ],
            [
                'key' => 'scrapbook',
                'name' => 'Scrapbook',
                'description' => 'Blank pages built for photos, clippings and captions.',
                'requires_ink' => false,
                'page_template' => [
                    'ruling' => 'blank',
                    'line_spacing_mm' => 0.0,
                    'margin' => null,
                    'grid' => null,
                    'header_fields' => [
                        ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ],
                    'footer_fields' => [],
                    'default_blocks' => ['image', 'paragraph'],
                ],
            ],
            [
                'key' => 'logbook',
                'name' => 'Log Book',
                'description' => 'Grid pages for records, observations and lab work.',
                'requires_ink' => false,
                'page_template' => [
                    'ruling' => 'grid',
                    'line_spacing_mm' => 5.0,
                    'margin' => null,
                    'grid' => ['size_mm' => 5.0],
                    'header_fields' => [
                        ['key' => 'date', 'label' => 'Date:', 'type' => 'date'],
                    ],
                    'footer_fields' => [],
                    'default_blocks' => ['paragraph'],
                ],
            ],
            [
                'key' => 'timesheet',
                'name' => 'Time Sheet',
                'description' => 'Grid pages that open with a table for hours and activities.',
                'requires_ink' => false,
                'page_template' => [
                    'ruling' => 'grid',
                    'line_spacing_mm' => 5.0,
                    'margin' => null,
                    'grid' => ['size_mm' => 5.0],
                    'header_fields' => [
                        ['key' => 'week_of', 'label' => 'Week of:', 'type' => 'date'],
                    ],
                    'footer_fields' => [
                        ['key' => 'supervisor_signature', 'label' => "Supervisor's Signature", 'type' => 'signature'],
                    ],
                    // The brief's Timesheet -> table preset (D-010).
                    'default_blocks' => ['table'],
                ],
            ],
        ];
    }
}
