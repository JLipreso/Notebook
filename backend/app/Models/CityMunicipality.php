<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PSGC city / municipality (schema §4, table 5) — 2026-09-13-005 Phase 002.
 *
 * province_code is NULLABLE: NCR cities sit directly under the region. The
 * Phase 005 dropdown chain has an explicit branch for that path.
 */
class CityMunicipality extends Model
{
    protected $table = 'cities_municipalities';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'province_code', 'region_code', 'name', 'class'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class, 'city_muni_code', 'code');
    }
}
