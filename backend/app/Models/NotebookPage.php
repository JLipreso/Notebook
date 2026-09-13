<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [RW] offline-writable page (schema §5, table 9; D-012, D-018)
 * — 2026-09-13-005 Phase 002.
 *
 * `content` is a Tiptap/ProseMirror JSON document — NEVER HTML (D-018).
 * Phase 007 adds server-side sanitization against the node whitelist and
 * maintains `search_text` on every write.
 */
class NotebookPage extends Model
{
    use HasFactory, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'id',
        'notebook_id',
        'position',
        'title',
        'content',
        'search_text',
        'client_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'client_updated_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function notebook(): BelongsTo
    {
        return $this->belongsTo(Notebook::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PageAttachment::class, 'page_id');
    }
}
