<?php

namespace App\Filament\Resources\RealisasiEkus\Widgets;

use App\Models\EkuTransaction;
use Filament\Widgets\Widget;

class DeviasiWidget extends Widget
{
    protected string $view = 'filament.widgets.deviasi-widget';

    protected int|string|array $columnSpan = 'full';

    public ?EkuTransaction $record = null;

    public string $jenisFilter = 'Semua';

    public function setJenisFilter(string $jenis): void
    {
        $this->jenisFilter = $jenis;
    }

    public function getDeviasi(): array
    {
        $deviasi = $this->record?->hitungDeviasi() ?? [];

        if ($this->jenisFilter === 'Semua') {
            return $deviasi;
        }

        return collect($deviasi)
            ->where('jenis', $this->jenisFilter)
            ->values()
            ->all();
    }
}
