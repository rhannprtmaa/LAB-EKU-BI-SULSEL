<?php

namespace App\Console\Commands;

use App\Models\EkuTransaction;
use Illuminate\Console\Command;

class ReparseEkuTransaction extends Command
{
    protected $signature = 'eku:reparse {id? : ID pengajuan EKU tertentu, kosongkan untuk proses SEMUA}';

    protected $description = 'Paksa baca ulang file Excel Setoran/Penarikan untuk 1 atau semua pengajuan EKU '
        . '(berguna untuk data lama yang masih 0 dari sebelum perbaikan parser, tanpa perlu upload ulang file)';

    public function handle(): int
    {
        $query = EkuTransaction::query();

        $this->comment('Disk "public" root path: ' . \Illuminate\Support\Facades\Storage::disk('public')->path(''));

        if ($id = $this->argument('id')) {
            $query->where('id', $id);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            $this->error('Tidak ada pengajuan EKU yang ditemukan.');

            return self::FAILURE;
        }

        $this->info("Memproses ulang {$transactions->count()} pengajuan EKU...");

        foreach ($transactions as $transaction) {
            $this->line("  #{$transaction->id} [{$transaction->bank?->name} - {$transaction->periode}]");

            foreach (['file_setoran' => 'Setoran', 'file_penarikan' => 'Penarikan'] as $kolom => $label) {
                $path = $transaction->{$kolom};

                if (! $path) {
                    continue;
                }

                $adaDiPublic = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
                $adaDiLocal = \Illuminate\Support\Facades\Storage::disk('local')->exists($path);

                $this->line("    {$label}: " . ($path));

                // File nyasar di disk 'local' (private) — pindahkan ke 'public'
                // supaya bisa dibaca parser & diakses lewat URL publik.
                if (! $adaDiPublic && $adaDiLocal) {
                    $isi = \Illuminate\Support\Facades\Storage::disk('local')->get($path);
                    \Illuminate\Support\Facades\Storage::disk('public')->put($path, $isi);
                    $this->line("      -> dipindahkan dari disk 'local' ke 'public' ✅");
                } elseif ($adaDiPublic) {
                    $this->line('      -> sudah ada di disk public');
                } else {
                    $this->line('      -> TIDAK ditemukan di disk manapun (file hilang/rusak)');
                }
            }

            $hasil = $transaction->reprocessExcelFiles();

            $this->line(sprintf(
                '    -> Hasil baca: Setoran %d baris, Penarikan %d baris',
                $hasil['setoran'],
                $hasil['penarikan'],
            ));
        }

        $this->info('Selesai. Cek Dashboard sekarang.');

        return self::SUCCESS;
    }
}
