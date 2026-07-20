<?php

namespace App\Filament\Resources\EkuTransactions\Pages;

use App\Filament\Resources\EkuTransactions\EkuTransactionResource;
use App\Filament\Resources\EkuTransactions\Widgets\TemplateKerjaWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEkuTransactions extends ListRecords
{
    protected static string $resource = EkuTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TemplateKerjaWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
