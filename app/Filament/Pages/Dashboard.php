<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionDetail;
use App\Models\User;
use App\Support\CurrentUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RealisasiEkus\Widgets\RealisasiUpbUpkPieChart;

class Dashboard extends BaseDashboard implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.custom-dashboard';

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return null;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $user = CurrentUser::get();

        $this->form->fill([
            'jenisGrafik' => 'forecast_eku',
            'periode' => (string) now()->year,
            'bankIdFilter' => $user?->isUserPerbankan() ? $user->bank_id : null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('jenisGrafik')
                    ->label('Jenis Grafik')
                    ->live()
                    ->options([
                        'forecast_eku' => 'Forecast EKU',
                        'realisasi_eku' => 'Realisasi EKU (segera hadir)',
                        'deviasi_forecast' => 'Deviasi Forecast (segera hadir)',
                        'tukab' => 'Tukab (segera hadir)',
                    ]),

                Select::make('periode')
                    ->label('Periode')
                    ->live()
                    ->options(fn () => ['all' => 'Semua Tahun'] + array_combine($this->availablePeriods(), $this->availablePeriods())),

                Select::make('bankIdFilter')
                    ->label('Jenis Bank')
                    ->live()
                    ->visible(fn () => $this->isInternalBi())
                    ->placeholder('Semua Bank')
                    ->options(fn () => $this->availableBanks()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function isInternalBi(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    protected function bulanUrut(): array
    {
        return [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
    }

    protected function bulanSingkat(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    }

    protected function scopedTransactionsQuery(): Builder
    {
        $user = CurrentUser::get();

        $query = EkuTransaction::query();

        if ($user?->isUserPerbankan()) {
            $query->where('bank_id', $user->bank_id);
        } elseif (! empty($this->data['bankIdFilter'])) {
            $query->where('bank_id', $this->data['bankIdFilter']);
        }

        if (! empty($this->data['periode']) && $this->data['periode'] !== 'all') {
            $query->where('periode', $this->data['periode']);
        }

        return $query;
    }

    public function getStats(): array
    {
        $user = CurrentUser::get();
        $base = $this->scopedTransactionsQuery();

        $submitted = (clone $base)->count();

        $doneReview = (clone $base)
            ->where('status', EkuTransaction::STATUS_DISETUJUI)
            ->count();

        $notReview = (clone $base)
            ->whereIn('status', [
                EkuTransaction::STATUS_MENUNGGU,
                EkuTransaction::STATUS_REVISI,
            ])
            ->count();

        if ($user?->isUserPerbankan()) {
            $fourthLabel = 'Perlu Revisi';

            $fourthValue = (clone $base)
                ->where('status', EkuTransaction::STATUS_REVISI)
                ->count();
        } else {
            $fourthLabel = 'Total Users';
            $fourthValue = User::count();
        }

        return [
            [
                'label' => 'Submitted',
                'value' => $submitted,
                'color' => 'green',
                'icon' => 'heroicon-o-paper-airplane',
                'tier' => $this->intensityTier($submitted),
            ],

            [
                'label' => 'Done Review',
                'value' => $doneReview,
                'color' => 'yellow',
                'icon' => 'heroicon-o-tag',
                'tier' => $this->intensityTier($doneReview),
            ],

            [
                'label' => 'Not Review',
                'value' => $notReview,
                'color' => 'red',
                'icon' => 'heroicon-o-hand-thumb-up',
                'tier' => $this->intensityTier($notReview),
            ],

            [
                'label' => $fourthLabel,
                'value' => $fourthValue,
                'color' => 'blue',
                'icon' => 'heroicon-o-users',
                'tier' => $this->intensityTier($fourthValue),
            ],
        ];
    }

    protected function intensityTier(int $value): int
    {
        return match (true) {
            $value <= 0 => 0,
            $value < 5 => 1,
            $value < 15 => 2,
            $value < 30 => 3,
            default => 4,
        };
    }

    protected function forecastChartData(): array
    {
        $bulanUrut = $this->bulanUrut();

        $approvedIds = (clone $this->scopedTransactionsQuery())
            ->where('status', EkuTransaction::STATUS_DISETUJUI)
            ->pluck('id');

        $rows = EkuTransactionDetail::query()
            ->whereIn('eku_transaction_id', $approvedIds)
            ->selectRaw('bulan, jenis_file, SUM(subtotal) as total')
            ->groupBy('bulan', 'jenis_file')
            ->get();

        $setoran = array_fill_keys($bulanUrut, 0.0);
        $penarikan = array_fill_keys($bulanUrut, 0.0);

        foreach ($rows as $row) {
            if (! array_key_exists($row->bulan, $setoran)) {
                continue;
            }

            if ($row->jenis_file === 'Setoran') {
                $setoran[$row->bulan] = (float) $row->total;
            } elseif ($row->jenis_file === 'Penarikan') {
                $penarikan[$row->bulan] = (float) $row->total;
            }
        }

        return [
            'labels' => $this->bulanSingkat(),
            'setoran' => array_values($setoran),
            'penarikan' => array_values($penarikan),
        ];
    }

    public function chartSvgData(): array
    {
        $data = $this->forecastChartData();

        $max = max(1.0, max($data['setoran']), max($data['penarikan']));

        $width = 1000;
        $height = 300;
        $paddingLeft = 70;
        $paddingBottom = 30;
        $paddingTop = 20;
        $plotWidth = $width - $paddingLeft - 20;
        $plotHeight = $height - $paddingTop - $paddingBottom;

        $n = count($data['labels']);
        $stepX = $n > 1 ? $plotWidth / ($n - 1) : 0;

        $toXY = function (array $values) use ($max, $stepX, $paddingLeft, $paddingTop, $plotHeight) {
            $titik = [];
            foreach (array_values($values) as $i => $v) {
                $x = $paddingLeft + $i * $stepX;
                $y = $paddingTop + $plotHeight - ($max > 0 ? ($v / $max) * $plotHeight : 0);
                $titik[] = [round($x, 1), round($y, 1)];
            }

            return $titik;
        };

        $toSmoothPath = function (array $titik): string {
            $n = count($titik);
            if ($n === 0) {
                return '';
            }
            if ($n === 1) {
                return "M {$titik[0][0]},{$titik[0][1]}";
            }

            $d = "M {$titik[0][0]},{$titik[0][1]}";

            for ($i = 0; $i < $n - 1; $i++) {
                $p0 = $titik[$i - 1] ?? $titik[$i];
                $p1 = $titik[$i];
                $p2 = $titik[$i + 1];
                $p3 = $titik[$i + 2] ?? $p2;

                $cp1x = round($p1[0] + ($p2[0] - $p0[0]) / 6, 2);
                $cp1y = round($p1[1] + ($p2[1] - $p0[1]) / 6, 2);
                $cp2x = round($p2[0] - ($p3[0] - $p1[0]) / 6, 2);
                $cp2y = round($p2[1] - ($p3[1] - $p1[1]) / 6, 2);

                $d .= " C {$cp1x},{$cp1y} {$cp2x},{$cp2y} {$p2[0]},{$p2[1]}";
            }

            return $d;
        };

        $toPointsString = fn (array $titik) => implode(' ', array_map(fn ($t) => "{$t[0]},{$t[1]}", $titik));

        $setoranXY = $toXY($data['setoran']);
        $penarikanXY = $toXY($data['penarikan']);

        $labelPositions = [];
        foreach ($data['labels'] as $i => $label) {
            $labelPositions[] = [
                'x' => round($paddingLeft + $i * $stepX, 1),
                'label' => $label,
            ];
        }

        $gridLines = [];
        for ($i = 0; $i <= 4; $i++) {
            $ratio = $i / 4;
            $gridLines[] = [
                'y' => round($paddingTop + $plotHeight - ($ratio * $plotHeight), 1),
                'value' => $this->formatRupiahSingkat($max * $ratio),
            ];
        }

        $bulanPenuh = $this->bulanUrut();
        $points = [];
        foreach ($data['labels'] as $i => $label) {
            $setoranValue = $data['setoran'][$i] ?? 0.0;
            $penarikanValue = $data['penarikan'][$i] ?? 0.0;
            $total = $setoranValue + $penarikanValue;

            $points[] = [
                'x' => $setoranXY[$i][0] ?? round($paddingLeft + $i * $stepX, 1),
                'ySetoran' => $setoranXY[$i][1] ?? ($paddingTop + $plotHeight),
                'yPenarikan' => $penarikanXY[$i][1] ?? ($paddingTop + $plotHeight),
                'bulan' => $bulanPenuh[$i] ?? $label,
                'setoranFmt' => $this->formatRupiahPenuh($setoranValue),
                'penarikanFmt' => $this->formatRupiahPenuh($penarikanValue),
                'totalFmt' => $this->formatRupiahPenuh($total),
                'persenSetoran' => $total > 0 ? round($setoranValue / $total * 100, 1) : 0,
                'persenPenarikan' => $total > 0 ? round($penarikanValue / $total * 100, 1) : 0,
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'paddingLeft' => $paddingLeft,
            'paddingTop' => $paddingTop,
            'plotHeight' => $plotHeight,
            'stepX' => round($stepX, 1),
            'labels' => $labelPositions,
            'gridLines' => $gridLines,
            'points' => $points,
            'setoranPath' => $toSmoothPath($setoranXY),
            'penarikanPath' => $toSmoothPath($penarikanXY),
            'setoranPoints' => $toPointsString($setoranXY),
            'penarikanPoints' => $toPointsString($penarikanXY),
            'hasData' => array_sum($data['setoran']) > 0 || array_sum($data['penarikan']) > 0,
        ];
    }

    protected function formatRupiahPenuh(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    protected function formatRupiahSingkat(float $value): string
    {
        if ($value >= 1_000_000_000_000) {
            return 'Rp ' . number_format($value / 1_000_000_000_000, 1) . ' T';
        }

        if ($value >= 1_000_000_000) {
            return 'Rp ' . number_format($value / 1_000_000_000, 1) . ' M';
        }

        if ($value >= 1_000_000) {
            return 'Rp ' . number_format($value / 1_000_000, 1) . ' Jt';
        }

        return 'Rp ' . number_format($value, 0);
    }

    public function availablePeriods(): array
    {
        $user = CurrentUser::get();
        $query = EkuTransaction::query();

        if ($user?->isUserPerbankan()) {
            $query->where('bank_id', $user->bank_id);
        }

        $periods = $query->distinct()->pluck('periode')->toArray();

        $defaults = [(string) now()->year, (string) (now()->year + 1)];

        return collect(array_unique(array_merge($periods, $defaults)))
            ->sortDesc()
            ->values()
            ->all();
    }

    public function availableBanks(): array
    {
        return Bank::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getWidgets(): array
    {
        return [
            RealisasiUpbUpkPieChart::class,
        ];
    }
}