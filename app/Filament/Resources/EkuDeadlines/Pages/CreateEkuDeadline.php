<?php

namespace App\Filament\Resources\EkuDeadlines\Pages;

use App\Filament\Resources\EkuDeadlines\EkuDeadlineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateEkuDeadline extends CreateRecord
{
    protected static string $resource = EkuDeadlineResource::class;

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
