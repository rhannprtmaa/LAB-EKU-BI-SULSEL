<?php

namespace App\Filament\Widgets;

use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use Filament\Widgets\ChartWidget;

class EkuForecastChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Forecast EKU (Setoran & Penarikan)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = CurrentUser::get();

        // 1. Ambil data EKU yang HANYA berstatus 'Disetujui' (Sudah di-ACC BI)
        $query = EkuTransaction::query()->where('status', EkuTransaction::STATUS_DISETUJUI);

        // 2. LOGIKA ROLE (User Perbankan hanya lihat miliknya)
        if ($user?->isUserPerbankan()) {
            $query->where('bank_id', $user->bank_id);
        }

        $transactions = $query->get();

        // 3. Mapping data untuk ditampilkan di Grafik (Berdasarkan Periode / Bank)
        // Note: Anda bisa menyesuaikan sumbu X (labels) dan Y (data) sesuai kebutuhan bisnis.
        // Di sini saya mencontohkan pengelompokan berdasarkan 'periode'.

        $labels = $transactions->pluck('periode')->unique()->values()->toArray();
        $dataTotal = $transactions->pluck('total_nominal')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Nominal (Rp)',
                    'data' => $dataTotal,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Bisa diganti 'line'
    }
}
