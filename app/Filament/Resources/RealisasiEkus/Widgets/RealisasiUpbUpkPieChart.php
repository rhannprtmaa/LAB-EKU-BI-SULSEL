<?php

namespace App\Filament\Resources\RealisasiEkus\Widgets;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasiDetail;
use Filament\Widgets\ChartWidget;

class RealisasiUpbUpkPieChart extends ChartWidget
{
    protected ?string $heading = 'Komposisi Realisasi EKU';

    public ?EkuTransaction $record = null;

    protected function getData(): array
    {
        // 1. Ambil semua ID riwayat realisasi milik transaksi (bank) ini
        $realisasiIds = $this->record?->realisasiHistory->pluck('id') ?? [];

        // 2. Jumlahkan total UPB dan UPK khusus dari tabel riwayat
        $totalUpb = EkuTransactionRealisasiDetail::whereIn('eku_transaction_realisasi_id', $realisasiIds)->sum('total_upb');
        $totalUpk = EkuTransactionRealisasiDetail::whereIn('eku_transaction_realisasi_id', $realisasiIds)->sum('total_upk');

        return [
            'datasets' => [
                [
                    'label' => 'Nominal (Rp)',
                    'data' => [$totalUpb, $totalUpk],
                    'backgroundColor' => [
                        '#3b82f6', // Warna Biru untuk UPB
                        '#10b981', // Warna Hijau untuk UPK
                    ],
                ],
            ],
            'labels' => ['Total UPB', 'Total UPK'],
        ];
    }

    protected function getType(): string
    {
        // Bisa menggunakan 'pie' atau 'doughnut'
        return 'doughnut';
    }

    // Sembunyikan grafik jika nilainya masih 0 (belum ada realisasi)
    public static function canView(): bool
    {
        return true;
    }
}
