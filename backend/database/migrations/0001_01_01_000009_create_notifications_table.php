<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifications (2026-09-13-004 database-schema.md §5, table 13)
 * — 2026-09-13-005 Phase 002.
 *
 * ONE table for every role and every event type (improvement #3), not a table
 * per feature. Multi-recipient = one row each, which IS the brief's "seen log".
 * Phase 010 adds the App\Support\Notify::send() helper every later feature calls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Recipient.
            $table->uuid('user_id');
            $table->uuid('actor_user_id')->nullable();
            // share_received, invite_received, quiz_submitted, payment_verified, ...
            $table->string('type', 48);
            $table->string('title', 150);
            $table->text('body')->nullable();
            // Deep-link payload: { route, params } (shape fixed in Phase 010).
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Unread-first listing (schema §5).
            $table->index(['user_id', 'read_at', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
