<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
