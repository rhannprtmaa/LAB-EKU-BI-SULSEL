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

    /**
     * Periode dianggap TERTUTUP kalau:
     * 1) Ada batas waktu yang spesifik diatur untuk periode ini dan sudah lewat, ATAU
     * 2) Tidak ada yang spesifik, tapi ada batas waktu GLOBAL (dari halaman
     *    "Management EKU" — tanpa memilih periode) yang sudah lewat.
     * Kalau belum ada batas waktu sama sekali, dianggap masih terbuka.
     */
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

    /**
     * Batas waktu GLOBAL terbaru (yang diatur lewat halaman "Management EKU"
     * tanpa memilih periode tertentu). Sengaja hanya melihat record dengan
     * periode kosong, supaya tidak bentrok kalau suatu saat ada juga batas
     * waktu khusus per-periode.
     */
    public static function current(): ?self
    {
        return static::whereNull('periode')
            ->latest('id')
            ->first();
    }
}
