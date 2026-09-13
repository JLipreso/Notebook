<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PSGC address reference tables (2026-09-13-004 database-schema.md §4, tables 3-6)
 * — 2026-09-13-005 Phase 002.
 *
 * Natural-key reference data from the PSA quarterly publication (D-030: latest
 * snapshot). The official 10-digit PSGC code IS the primary key — these are the
 * one documented exception to the UUID rule (schema §1). Server-only, never
 * client-minted; refreshed by re-running PsgcSeeder on a PSA release.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->char('code', 10)->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->char('code', 10)->primary();
            $table->char('region_code', 10);
            $table->string('name');
            $table->timestamps();

            $table->index('region_code');
            $table->foreign('region_code')->references('code')->on('regions')->cascadeOnDelete();
        });

        Schema::create('cities_municipalities', function (Blueprint $table) {
            $table->char('code', 10)->primary();
            // NCR cities sit directly under the region — no province (schema §4).
            $table->char('province_code', 10)->nullable();
            $table->char('region_code', 10);
            $table->string('name');
            $table->string('class', 16)->nullable();
            $table->timestamps();

            $table->index('province_code');
            $table->index('region_code');
            $table->foreign('province_code')->references('code')->on('provinces')->nullOnDelete();
            $table->foreign('region_code')->references('code')->on('regions')->cascadeOnDelete();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->char('code', 10)->primary();
            $table->char('city_muni_code', 10);
            $table->string('name');
            $table->timestamps();

            $table->index('city_muni_code');
            $table->foreign('city_muni_code')->references('code')->on('cities_municipalities')->cascadeOnDelete();
        });

        // users.barangay_code was created before barangays existed, so the FK is
        // attached here. sqlite cannot ALTER TABLE ADD CONSTRAINT — the column
        // is validated by `exists:barangays,code` at the request layer either way.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('barangay_code')->references('code')->on('barangays')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['barangay_code']);
            });
        }

        Schema::dropIfExists('barangays');
        Schema::dropIfExists('cities_municipalities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('regions');
    }
};
