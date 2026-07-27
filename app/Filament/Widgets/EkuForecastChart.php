<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\EkuTransactionDetail;

class EkuForecastChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Proyeksi EKU (Setoran vs Penarikan)';

    protected function getData(): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $setoranData = [];
        $penarikanData = [];

        foreach (range(1, 12) as $month) {
            $setoranData[] = EkuTransactionDetail::whereHas('transaction', fn($q) => $q->whereMonth('tanggal', $month))
                ->where('jenis_file', 'Setoran')
                ->sum('subtotal');

            $penarikanData[] = EkuTransactionDetail::whereHas('transaction', fn($q) => $q->whereMonth('tanggal', $month))
                ->where('jenis_file', 'Penarikan')
                ->sum('subtotal');
        }

        // --- TIPS MENGUJI LENGKUNGAN (DUMMY DATA VARIATIF) ---
        // Jika data DB kamu masih sama semua nilainya, kamu bisa hapus tanda komentar di 2 baris bawah ini untuk tes visual melengkungnya:
        // $setoranData = [8, 12, 10, 15, 9, 14, 20, 18, 11, 16, 13, 19];
        // $penarikanData = [5, 8, 7, 10, 6, 11, 13, 12, 9, 14, 10, 15];

        return [
            'datasets' => [
                [
                    'label' => 'Total Setoran',
                    'data' => $setoranData,
                    'borderColor' => '#22c55e', // Warna Hijau
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.4, // <--- Bikin garis melengkung halus
                    'pointRadius' => 5, // Titik bulat di setiap bulan
                    'pointHoverRadius' => 8, // Titik membesar saat cursor didekatkan
                    'pointBackgroundColor' => '#22c55e',
                    'fill' => true,
                ],
                [
                    'label' => 'Total Penarikan',
                    'data' => $penarikanData,
                    'borderColor' => '#ef4444', // Warna Merah
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4, 
                    'pointRadius' => 5,
                    'pointHoverRadius' => 8,
                    'pointBackgroundColor' => '#ef4444',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Mengatur Detail Hover Tooltip saat Cursor Mengarah ke Garis/Titik
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index', // Menampilkan Setoran & Penarikan sekaligus saat hover di bulan tsb
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
