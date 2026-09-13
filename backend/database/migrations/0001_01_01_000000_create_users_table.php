<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users + auth scaffolding + user_devices.
 *
 * Reconciled from the Laravel 12 stock users migration to the locked M1 schema
 * (2026-09-13-004 database-schema.md §4, tables 1-2) — 2026-09-13-005 Phase 002.
 *
 * Shape changes vs stock: CHAR(36) UUIDv7 PK (D-013), split name into
 * first/middle/last, role + PSGC address + Firebase identity columns, nullable
 * password (Firebase owns primary auth — D-015), soft deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // One person = one row; a role upgrade is an UPDATE, never a second
            // account (D-014). VARCHAR + PHP enum, not a DB enum (schema §1).
            $table->string('role', 16)->default('student');

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('birthday');

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile_number', 20);

            // Set on the first Firebase -> Sanctum exchange (D-015); NULL only
            // for seeded admins using the local password fallback.
            $table->string('firebase_uid', 128)->nullable()->unique();
            $table->string('password')->nullable();

            // PSGC address (D-020: no geolocation). region/province/city are
            // derivable by joins, so only the barangay is stored.
            $table->char('barangay_code', 10)->nullable();
            $table->string('address_line')->nullable();

            $table->string('avatar_path')->nullable();
            $table->string('status', 16)->default('active');

            $table->rememberToken();
            $table->timestamps();
            // Soft delete: a deleted user's notebooks stay recoverable (§8).
            $table->softDeletes();

            $table->index('role');
            $table->index('status');
            $table->index('barangay_code');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // CHAR(36) to match users.id (D-013).
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Push targeting (FCM) + session visibility. Pull cursors live on the
        // device, not here (schema §4).
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('platform', 10);
            $table->string('device_identifier', 128);
            $table->string('device_name', 100)->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_identifier']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
