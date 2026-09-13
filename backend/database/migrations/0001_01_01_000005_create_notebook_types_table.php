<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notebook_types (2026-09-13-004 database-schema.md §5, table 7)
 * — 2026-09-13-005 Phase 002.
 *
 * Types are ROWS, not schema: the brief promises new notebook types over time,
 * so adding one is a seeder change, never a migration (D-010, D-012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebook_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // The paper-fidelity config (D-010) consumed by @notebook/ui's
            // PaperPage.vue in Phase 007: ruling pattern, line spacing, margin
            // line, header/footer fields, default block preset. The typed shape
            // is PageTemplate in @notebook/types (Phase 003) — the seeder and
            // that interface are ONE contract; change them together.
            $table->json('page_template');

            $table->string('min_level', 32)->nullable();
            $table->string('audience_hint', 64)->nullable();
            // Reserved: types that only fully unlock once the ink block ships (D-012).
            $table->boolean('requires_ink')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notebook_types');
    }
};
