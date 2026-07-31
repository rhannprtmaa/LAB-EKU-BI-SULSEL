<?php

namespace App\Filament\Resources\EkuDeadlines\Pages;

use App\Filament\Resources\EkuDeadlines\EkuDeadlineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListEkuDeadlines extends ListRecords
{
    protected static string $resource = EkuDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Atur Batas Waktu')
                ->modalWidth(Width::Medium),
        ];
    }
}
