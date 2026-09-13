<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registered device for FCM push targeting and session visibility
 * (schema §4, table 2) — 2026-09-13-005 Phase 002.
 *
 * Sync pull cursors live on the device, NOT here.
 */
class UserDevice extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'user_id',
        'platform',
        'device_identifier',
        'device_name',
        'fcm_token',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
