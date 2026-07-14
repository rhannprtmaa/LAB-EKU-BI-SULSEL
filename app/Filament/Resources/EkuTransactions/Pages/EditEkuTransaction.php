<?php

namespace App\Filament\Resources\EkuTransactionResource\Pages;

use App\Filament\Resources\EkuTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEkuTransaction extends EditRecord
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
