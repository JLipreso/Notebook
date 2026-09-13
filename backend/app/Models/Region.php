<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PSGC region (schema §4, table 3) — 2026-09-13-005 Phase 002.
 *
 * The official 10-digit PSGC code IS the primary key — the one documented
 * exception to the UUID rule (schema §1). Server-only, never client-minted,
 * so no HasUuidV7 here.
 */
class Region extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'name'];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'region_code', 'code');
    }

    public function citiesMunicipalities(): HasMany
    {
        return $this->hasMany(CityMunicipality::class, 'region_code', 'code');
    }
}
