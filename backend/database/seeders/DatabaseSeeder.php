<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Reference-data seeders only (2026-09-13-005 Phase 002).
 *
 * No demo/test users here — `migrate:fresh --seed` runs against real databases,
 * and a seeded account with a known identity is an auth hole. The M1 demo
 * account gets its own dedicated seeder in Phase 012.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PsgcSeeder::class,
            NotebookTypeSeeder::class,
        ]);
    }
}
