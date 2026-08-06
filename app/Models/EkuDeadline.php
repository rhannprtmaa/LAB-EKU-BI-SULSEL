<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EkuDeadline extends Model
{
    protected $guarded = [];

    protected $casts = [
        'batas_waktu' => 'date',
    ];

    public static function untukPeriode(?string $periode): ?self
    {
        if (! $periode) {
            return null;
        }

        return static::where('periode', $periode)->first();
    }

    public static function isTertutup(?string $periode = null): bool
    {
        if ($periode) {
            $spesifik = static::untukPeriode($periode);

            if ($spesifik) {
                return $spesifik->isSudahLewat();
            }
        }

        return static::current()?->isSudahLewat() ?? false;
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
        return static::whereNull('periode')
            ->latest('id')
            ->first();
    }
}
