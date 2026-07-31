<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EkuDeadline extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'date',
        ];
    }

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

        return now()->startOfDay()->gt($deadline->batas_waktu->copy()->endOfDay());
    }

    public function isSudahLewat(): bool
    {
        return $this->batas_waktu ? now()->startOfDay()->gt($this->batas_waktu->copy()->endOfDay()) : false;
    }
}
