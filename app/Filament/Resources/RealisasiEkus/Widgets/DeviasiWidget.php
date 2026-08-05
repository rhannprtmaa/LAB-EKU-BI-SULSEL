<?php

namespace App\Filament\Resources\RealisasiEkus\Widgets;

use App\Models\EkuTransaction;
use Filament\Widgets\Widget;

class DeviasiWidget extends Widget
{
    protected string $view = 'filament.widgets.deviasi-widget';

    protected int|string|array $columnSpan = 'full';

    public ?EkuTransaction $record = null;

    public function getDeviasi(): array
    {
        return $this->record?->hitungDeviasi() ?? [];
    }
}
