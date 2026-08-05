<?php

// PERBAIKAN 1: Namespace disesuaikan dengan lokasi file Anda
namespace App\Filament\Resources\RealisasiEkus\Widgets;

use App\Models\EkuTransactionRealisasiDetail;
use Filament\Widgets\ChartWidget;

class RealisasiUpbUpkPieChart extends ChartWidget
{
    // PERBAIKAN 2: Hapus kata "static" pada $heading
    protected ?string $heading = 'Komposisi Realisasi (UPB vs UPK)';
    
    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        // Menghitung total UPB dan UPK menggunakan query database
        $totals = EkuTransactionRealisasiDetail::selectRaw('
            SUM(kertas_100k + kertas_50k) as total_upb,
            SUM(kertas_20k + kertas_10k + kertas_5k + kertas_2k + kertas_1k + logam_1k + logam_500 + logam_200 + logam_100) as total_upk
        ')->first();

        $upb = (float) ($totals->total_upb ?? 0);
        $upk = (float) ($totals->total_upk ?? 0);
        $total = $upb + $upk;

        // Hitung persentase
        $persenUpb = $total > 0 ? round(($upb / $total) * 100, 1) : 0;
        $persenUpk = $total > 0 ? round(($upk / $total) * 100, 1) : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Total Realisasi',
                    'data' => [$upb, $upk],
                    'backgroundColor' => [
                        '#3b82f6', // Warna Biru untuk UPB
                        '#10b981', // Warna Hijau untuk UPK
                    ],
                ],
            ],
            'labels' => ["UPB ({$persenUpb}%)", "UPK ({$persenUpk}%)"],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}