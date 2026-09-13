<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [ON] in-app notification (schema §5, table 13) — 2026-09-13-005 Phase 002.
 *
 * Named AppNotification, not Notification, to avoid colliding with Laravel's
 * Illuminate\Notifications\Notification — the same collision the TS contract
 * dodges (@notebook/types uses AppNotification for the DOM's Notification).
 * ONE table for every role and event type (improvement #3).
 */
class AppNotification extends Model
{
    use HasFactory, HasUuidV7;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
