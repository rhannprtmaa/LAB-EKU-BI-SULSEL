<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bank_id',
        'is_active',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // Dipakai Filament untuk menampilkan foto profil di sidebar/menu user.
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_url);
    }

    // Helper method untuk cek role pengguna
    public function isAdminBi(): bool
    {
        return $this->role === 'admin_bi';
    }

    public function isUserBi(): bool
    {
        return $this->role === 'user_bi';
    }

    public function isUserPerbankan(): bool
    {
        return $this->role === 'user_perbankan';
    }
}
