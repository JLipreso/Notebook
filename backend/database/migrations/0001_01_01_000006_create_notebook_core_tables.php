<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notebook core: notebooks, notebook_pages, page_attachments
 * (2026-09-13-004 database-schema.md §5, tables 8-10) — 2026-09-13-005 Phase 002.
 *
 * All three are [RW] offline-writable (D-016), so each carries:
 *   - a CHAR(36) UUIDv7 PK MINTED ON THE DEVICE (D-013) — the server accepts
 *     the client's id as-is, it does not generate one;
 *   - client_updated_at TIMESTAMP(3), the device clock used for last-write-wins
 *     in Phase 009's sync push (schema §2.1). It exists from this migration on
 *     purpose — the sync engine depends on it being there from the start;
 *   - deleted_at, because sync deletes are soft on both sides (§2.4): a
 *     tombstone must survive to propagate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('notebook_type_id');
            $table->string('title', 150);
            // The brief's per-school-year lifecycle, e.g. '2026-2027'.
            $table->string('school_year', 9);
            $table->uuid('cover_upload_id')->nullable();
            $table->string('font_family', 64)->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->integer('position')->default(0);

            $table->timestamp('client_updated_at', 3)->nullable();
            $table->timestamps(3);
            $table->softDeletes();

            // Pull cursors and library filters (schema §5).
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'school_year']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('notebook_type_id')->references('id')->on('notebook_types')->restrictOnDelete();
        });

        Schema::create('notebook_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notebook_id');
            $table->integer('position')->default(0);
            $table->string('title', 150)->nullable();

            // Tiptap/ProseMirror document — NEVER HTML (D-018). Sanitized
            // server-side against the allowed node/mark whitelist on every write.
            $table->json('content')->nullable();
            // Plain-text extraction of content, maintained on write (Phase 007).
            $table->mediumText('search_text')->nullable();

            $table->timestamp('client_updated_at', 3)->nullable();
            $table->timestamps(3);
            $table->softDeletes();

            $table->index(['notebook_id', 'position']);
            $table->index(['notebook_id', 'updated_at']);

            $table->foreign('notebook_id')->references('id')->on('notebooks')->cascadeOnDelete();
        });

        // FULLTEXT is MySQL-only; sqlite (the dev DB, D-019) has no equivalent
        // and would fail the migration. Client-side search uses search_text
        // directly, so dev loses nothing.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notebook_pages ADD FULLTEXT search_text_fulltext (search_text)');
        }

        Schema::create('page_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('page_id');
            // Set only after the file reaches the server (§2.5) — the row syncs
            // offline, the binary does not.
            $table->uuid('file_upload_id')->nullable();
            $table->string('kind', 16);
            // Device-side path while the upload is still pending.
            $table->string('local_ref')->nullable();
            $table->string('upload_status', 16)->default('pending');

            $table->timestamp('client_updated_at', 3)->nullable();
            $table->timestamps(3);
            $table->softDeletes();

            $table->index(['page_id', 'updated_at']);
            $table->index('upload_status');

            $table->foreign('page_id')->references('id')->on('notebook_pages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_attachments');
        Schema::dropIfExists('notebook_pages');
        Schema::dropIfExists('notebooks');
    }
};
