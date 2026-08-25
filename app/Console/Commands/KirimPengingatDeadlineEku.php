<?php

namespace App\Console\Commands;

use App\Models\EkuDeadline;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class KirimPengingatDeadlineEku extends Command
{
    protected $signature = 'eku:kirim-pengingat-deadline';

    protected $description = 'Kirim notifikasi (in-app + Gmail) ke semua bank kalau batas waktu pengajuan EKU tersisa 3 hari atau 1 hari lagi.';

    /** Ambang batas hari-tersisa yang memicu pengingat, urut dari yang paling jauh. */
    protected array $ambangHari = [3, 1, 0];

    public function handle(): int
    {
        $deadline = EkuDeadline::current();

        if (! $deadline || ! $deadline->batas_waktu) {
            $this->info('Tidak ada batas waktu EKU yang diatur. Tidak ada pengingat dikirim.');

            return self::SUCCESS;
        }

        $sisaHari = (int) round(now()->startOfDay()->diffInDays($deadline->batas_waktu->copy()->startOfDay(), false));

        // Sudah lewat atau masih jauh (belum masuk ambang) -> tidak perlu apa-apa.
        if ($sisaHari < 0 || ! in_array($sisaHari, $this->ambangHari, true)) {
            $this->info("Sisa hari saat ini: {$sisaHari}. Belum masuk ambang pengingat (" . implode(', ', $this->ambangHari) . '). Tidak ada yang dikirim.');

            return self::SUCCESS;
        }

        // Cegah kirim dobel kalau command ini kebetulan dijalankan lebih dari
        // sekali di hari yang sama untuk ambang yang sama.
        if ($deadline->pengingat_terakhir === (string) $sisaHari) {
            $this->info("Pengingat untuk sisa {$sisaHari} hari sudah pernah dikirim hari ini. Dilewati.");

            return self::SUCCESS;
        }

        NotifikasiService::deadlineSudahDekat($deadline, $sisaHari);

        $deadline->pengingat_terakhir = (string) $sisaHari;
        $deadline->saveQuietly();

        $this->info("Pengingat deadline (sisa {$sisaHari} hari) berhasil dikirim ke semua bank.");

        return self::SUCCESS;
    }
}
