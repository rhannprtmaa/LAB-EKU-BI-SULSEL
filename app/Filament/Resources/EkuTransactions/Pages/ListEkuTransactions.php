<?php

namespace App\Filament\Resources\EkuTransactionResource\Pages;

use App\Filament\Resources\EkuTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEkuTransactions extends ListRecords
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
