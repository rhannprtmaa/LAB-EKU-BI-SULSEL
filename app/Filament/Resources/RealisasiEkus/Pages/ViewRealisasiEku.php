<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Filament\Resources\RealisasiEkus\Widgets\DeviasiWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewRealisasiEku extends ViewRecord
{
    protected static string $resource = RealisasiEkuResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            DeviasiWidget::class,
        ];
    }
}
