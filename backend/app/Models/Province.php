<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PSGC province (schema §4, table 4) — 2026-09-13-005 Phase 002.
 * Natural CHAR(10) PK; see Region for why there is no UUID trait.
 */
class Province extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'region_code', 'name'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function citiesMunicipalities(): HasMany
    {
        return $this->hasMany(CityMunicipality::class, 'province_code', 'code');
    }
}
