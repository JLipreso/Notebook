<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PSGC barangay (schema §4, table 6) — 2026-09-13-005 Phase 002.
 *
 * The deepest level of the address chain and the only one stored on `users`;
 * region/province/city are derivable by joins.
 */
class Barangay extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'city_muni_code', 'name'];

    public function cityMunicipality(): BelongsTo
    {
        return $this->belongsTo(CityMunicipality::class, 'city_muni_code', 'code');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'barangay_code', 'code');
    }
}
