<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * One users table for every role (D-014) — 2026-09-13-005 Phase 002.
 *
 * A role upgrade is an UPDATE to `role`, never a second account. Firebase owns
 * primary auth (D-015), so `password` is nullable and used only for the seeded
 * admin fallback.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuidV7, Notifiable, SoftDeletes;

    /**
     * `role` is deliberately absent: it is set server-side at registration and
     * never accepted from client input afterwards (Phase 004).
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'birthday',
        'email',
        'mobile_number',
        'firebase_uid',
        'password',
        'barangay_code',
        'address_line',
        'avatar_path',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'firebase_uid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notebooks(): HasMany
    {
        return $this->hasMany(Notebook::class);
    }

    public function fileUploads(): HasMany
    {
        return $this->hasMany(FileUpload::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_code', 'code');
    }
}
