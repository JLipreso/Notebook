<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uploaded file record (schema §5, table 11) — 2026-09-13-005 Phase 002.
 *
 * Laravel storage disks only, never FTP (validation §5). Phase 008 enforces the
 * per-user quota against SUM(size_bytes) and a content-based MIME allowlist.
 */
class FileUpload extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
