<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateRealisasiEku extends CreateRecord
{
    protected static string $resource = RealisasiEkuResource::class;

    protected function getCreateFormAction(): CreateAction
    {
        return parent::getCreateFormAction()
            ->label('Submit');
    }

    protected function getCreateAnotherFormAction(): CreateAction
    {
        return parent::getCreateAnotherFormAction()
            ->hidden();
    }
}
