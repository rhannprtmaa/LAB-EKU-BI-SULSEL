<?php

namespace App\Models;

use App\Services\NotifikasiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EkuDeadline extends Model
{
    protected $guarded = [];

    protected $casts = [
        'batas_waktu' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (EkuDeadline $deadline) {
            NotifikasiService::deadlineBaruDibuat($deadline);
        });

        static::updated(function (EkuDeadline $deadline) {
            // Hanya kirim notifikasi kalau tanggal/keterangan yang benar-benar
            // berubah, supaya update lain (mis. pengingat_terakhir) tidak
            // ikut memicu notifikasi "deadline diubah".
            if ($deadline->wasChanged(['batas_waktu', 'keterangan'])) {
                NotifikasiService::deadlineDiubah($deadline);
            }
        });
    }

    public static function current(): ?self
    {
        return static::query()->oldest('id')->first();
    }

    public static function untukPeriode(?string $periode = null): ?self
    {
        return static::current();
    }

    public static function isTertutup(?string $periode = null): bool
    {
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
}
