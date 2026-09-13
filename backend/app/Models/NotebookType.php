<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notebook type = a row, never a migration (D-010, D-012)
 * — 2026-09-13-005 Phase 002.
 *
 * `page_template` is the paper-fidelity contract shared with PaperPage.vue via
 * the PageTemplate interface in @notebook/types — seeder and type change together.
 */
class NotebookType extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'key',
        'name',
        'description',
        'page_template',
        'min_level',
        'audience_hint',
        'requires_ink',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'page_template' => 'array',
            'requires_ink' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function notebooks(): HasMany
    {
        return $this->hasMany(Notebook::class);
    }
}
