<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * file_uploads (2026-09-13-004 database-schema.md §5, table 11)
 * — 2026-09-13-005 Phase 002.
 *
 * Laravel storage disks only, never FTP (validation §5). Per-tier storage
 * quotas are enforced against SUM(size_bytes) per user (D-022/D-023) —
 * Phase 008 implements the check; the column exists for it now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            // 'public' now, 's3' later — a config swap, not a migration (§5).
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            // Content-based allowlist per category, enforced in Phase 008.
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            // Reserved for dedupe later.
            $table->char('sha256', 64)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // notebooks.cover_upload_id was created before this table existed.
        // sqlite cannot ALTER TABLE ADD CONSTRAINT (see the PSGC migration).
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('notebooks', function (Blueprint $table) {
                $table->foreign('cover_upload_id')->references('id')->on('file_uploads')->nullOnDelete();
            });

            Schema::table('page_attachments', function (Blueprint $table) {
                $table->foreign('file_upload_id')->references('id')->on('file_uploads')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('page_attachments', function (Blueprint $table) {
                $table->dropForeign(['file_upload_id']);
            });

            Schema::table('notebooks', function (Blueprint $table) {
                $table->dropForeign(['cover_upload_id']);
            });
        }

        Schema::dropIfExists('file_uploads');
    }
};
