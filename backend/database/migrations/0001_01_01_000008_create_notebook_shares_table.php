<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * notebook_shares (2026-09-13-004 database-schema.md §5, table 12)
 * — 2026-09-13-005 Phase 002.
 *
 * [ON] online-only: shared editing never merges offline (D-016). M1 ships
 * read-only token links; access='read_write' and direct user shares are
 * schema-ready but not implemented until after M1 (Phase 010).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebook_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notebook_id');
            // NULL = the whole notebook; set = that one page (the brief).
            $table->uuid('page_id')->nullable();
            $table->uuid('shared_by');
            // Either a direct share to a known user...
            $table->uuid('shared_with_user_id')->nullable();
            // ...or a link share. The CHECK below enforces exactly one.
            $table->char('share_token', 64)->nullable()->unique();
            $table->string('access', 16)->default('read');
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('notebook_id');
            $table->index('shared_by');
            $table->index('shared_with_user_id');

            $table->foreign('notebook_id')->references('id')->on('notebooks')->cascadeOnDelete();
            $table->foreign('page_id')->references('id')->on('notebook_pages')->cascadeOnDelete();
            $table->foreign('shared_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('shared_with_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Schema §5: exactly one of (shared_with_user_id, share_token).
        //
        // sqlite (the dev DB, D-019) has no ALTER TABLE ADD CONSTRAINT, so the
        // CHECK can only be declared at CREATE time. Laravel's schema builder
        // has no portable helper for it, so the table is rebuilt here with the
        // constraint inline — the sqlite ALTER-emulation copies data through a
        // temp table, and this runs on an empty table during migrate:fresh.
        // MySQL takes the ALTER path.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TABLE notebook_shares');
            DB::statement(
                'CREATE TABLE notebook_shares (
                    id varchar not null primary key,
                    notebook_id varchar not null,
                    page_id varchar null,
                    shared_by varchar not null,
                    shared_with_user_id varchar null,
                    share_token varchar null,
                    access varchar not null default \'read\',
                    expires_at datetime null,
                    revoked_at datetime null,
                    created_at datetime null,
                    updated_at datetime null,
                    constraint chk_share_target check (
                        (shared_with_user_id is not null and share_token is null)
                        or (shared_with_user_id is null and share_token is not null)
                    ),
                    foreign key (notebook_id) references notebooks (id) on delete cascade,
                    foreign key (page_id) references notebook_pages (id) on delete cascade,
                    foreign key (shared_by) references users (id) on delete cascade,
                    foreign key (shared_with_user_id) references users (id) on delete cascade
                )'
            );
            DB::statement('CREATE UNIQUE INDEX notebook_shares_share_token_unique ON notebook_shares (share_token)');
            DB::statement('CREATE INDEX notebook_shares_notebook_id_index ON notebook_shares (notebook_id)');
            DB::statement('CREATE INDEX notebook_shares_shared_by_index ON notebook_shares (shared_by)');
            DB::statement('CREATE INDEX notebook_shares_shared_with_user_id_index ON notebook_shares (shared_with_user_id)');
        } else {
            DB::statement(
                'ALTER TABLE notebook_shares ADD CONSTRAINT chk_share_target
                 CHECK (
                     (shared_with_user_id IS NOT NULL AND share_token IS NULL)
                     OR (shared_with_user_id IS NULL AND share_token IS NOT NULL)
                 )'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notebook_shares');
    }
};
