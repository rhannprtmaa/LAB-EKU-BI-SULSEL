<?php

namespace App\Console\Commands;

use App\Models\EkuTransactionRealisasi;
use App\Models\EkuTransactionRealisasiDetail;
use Illuminate\Console\Command;

class BersihkanRealisasiSisa extends Command
{
    protected $signature = 'eku:bersihkan-realisasi-sisa {--dry-run : Cuma tampilkan apa yang AKAN dihapus, tanpa benar-benar menghapus}';

    protected $description = 'Hapus baris eku_transaction_realisasi_details yang bulan-nya bukan nama bulan Indonesia yang valid (sisa bug sebelum multiplier & bulan diperbaiki), lalu hitung ulang total di parent-nya.';

    protected array $bulanValid = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function handle(): int
    {
        $rusak = EkuTransactionRealisasiDetail::whereNotIn('bulan', $this->bulanValid)->get();

        if ($rusak->isEmpty()) {
            $this->info('Tidak ditemukan baris rincian realisasi yang bulan-nya tidak valid. Aman.');

            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$rusak->count()} baris dengan nilai 'bulan' tidak valid:");

        $this->table(
            ['ID', 'eku_transaction_realisasi_id', 'bulan (tersimpan)', 'jenis_file', 'subtotal'],
            $rusak->map(fn ($r) => [$r->id, $r->eku_transaction_realisasi_id, $r->bulan, $r->jenis_file, number_format($r->subtotal, 0, ',', '.')])
        );

        if ($this->option('dry-run')) {
            $this->info('Dry-run aktif -- tidak ada yang dihapus. Jalankan tanpa --dry-run untuk benar-benar membersihkan.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Hapus semua baris di atas dan hitung ulang total-nya?', true)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $realisasiIdTerdampak = $rusak->pluck('eku_transaction_realisasi_id')->unique();

        EkuTransactionRealisasiDetail::whereNotIn('bulan', $this->bulanValid)->delete();

        foreach ($realisasiIdTerdampak as $id) {
            EkuTransactionRealisasi::recalculateTotals($id);
        }

        $this->info('Selesai. ' . $realisasiIdTerdampak->count() . ' realisasi sudah dihitung ulang total-nya.');

        return self::SUCCESS;
    }
}
