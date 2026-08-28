<?php

namespace App\Filament\Resources\Banks\Pages;

use App\Filament\Resources\Banks\BankResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateBank extends CreateRecord
{
    protected static string $resource = BankResource::class;

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
