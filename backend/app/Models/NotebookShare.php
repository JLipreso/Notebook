<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [ON] share of a notebook or a single page (schema §5, table 12)
 * — 2026-09-13-005 Phase 002.
 *
 * DB CHECK enforces exactly one of (shared_with_user_id, share_token).
 * M1 ships read-only token links only (Phase 010); read_write is post-M1 and
 * online-only either way (D-016).
 */
class NotebookShare extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'notebook_id',
        'page_id',
        'shared_by',
        'shared_with_user_id',
        'share_token',
        'access',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function notebook(): BelongsTo
    {
        return $this->belongsTo(Notebook::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(NotebookPage::class, 'page_id');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
