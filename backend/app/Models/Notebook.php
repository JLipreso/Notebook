<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [RW] offline-writable notebook (schema §5, table 8; D-011, D-016)
 * — 2026-09-13-005 Phase 002.
 *
 * The id is minted ON THE DEVICE (D-013) and accepted as-is. `client_updated_at`
 * is the device clock used for last-write-wins on sync push (§2.3).
 */
class Notebook extends Model
{
    use HasFactory, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'notebook_type_id',
        'title',
        'school_year',
        'cover_upload_id',
        'font_family',
        'status',
        'archived_at',
        'position',
        'client_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * Ownership scope — every notebook query is scoped to the caller, including
     * Phase 009's sync push. Written once here so it is never re-improvised.
     */
    public function scopeOwned(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notebookType(): BelongsTo
    {
        return $this->belongsTo(NotebookType::class);
    }

    public function coverUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'cover_upload_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(NotebookPage::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(NotebookShare::class);
    }
}
