<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRealisasiEku extends EditRecord
{
    protected static string $resource = RealisasiEkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
