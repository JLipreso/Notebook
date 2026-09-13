<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

/**
 * CHAR(36) UUIDv7 primary keys (D-013) — 2026-09-13-005 Phase 002.
 *
 * Laravel's HasUuids mints UUIDv4; the schema requires v7 for its time-ordered
 * B-tree locality (2026-09-13-004 database-schema.md §1). Overriding newUniqueId
 * is the whole difference.
 *
 * On the [RW] offline tables (notebooks, notebook_pages, page_attachments) the
 * DEVICE mints the id and the server stores it as-is — HasUuids only fills in
 * when no key was supplied, which is exactly the server-side create path.
 */
trait HasUuidV7
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
