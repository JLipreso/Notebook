<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * [RW] attachment ROW; the binary file is uploaded when online (schema §2.5)
 * — 2026-09-13-005 Phase 002.
 *
 * upload_status walks pending -> uploaded (or failed); `file_upload_id` is
 * patched in once the file reaches the server.
 */
class PageAttachment extends Model
{
    use HasFactory, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'id',
        'page_id',
        'file_upload_id',
        'kind',
        'local_ref',
        'upload_status',
        'client_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'client_updated_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(NotebookPage::class, 'page_id');
    }

    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }
}
