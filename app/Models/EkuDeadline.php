<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EkuDeadline extends Model
{
    protected $guarded = [];

    // Cast ke datetime agar menjadi instance Carbon
    protected $casts = [
        'batas_waktu' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public static function untukPeriode(?string $periode): ?self
    {
        if (! $periode) {
            return null;
        }

        return static::where('periode', $periode)->first();
    }

    public static function isTertutup(?string $periode): bool
    {
        $deadline = static::untukPeriode($periode);

        if (! $deadline || ! $deadline->batas_waktu) {
            return false;
        }

        $tanggal = $deadline->batas_waktu instanceof Carbon
            ? $deadline->batas_waktu
            : Carbon::parse($deadline->batas_waktu);

        return now()->startOfDay()->gt($tanggal->copy()->endOfDay());
    }

    public function isSudahLewat(): bool
    {
        if (! $this->batas_waktu) {
            return false;
        }

        $tanggal = $this->batas_waktu instanceof Carbon
            ? $this->batas_waktu
            : Carbon::parse($this->batas_waktu);

        return now()->startOfDay()->gt($tanggal->copy()->endOfDay());
    }

    public static function current(): ?self
    {
        return static::query()
            ->latest('id')
            ->first();
    }
}
