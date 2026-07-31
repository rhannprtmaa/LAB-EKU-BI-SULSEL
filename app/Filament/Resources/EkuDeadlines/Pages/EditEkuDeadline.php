<?php

namespace App\Filament\Resources\EkuDeadlines\Pages;

use App\Filament\Resources\EkuDeadlines\EkuDeadlineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEkuDeadline extends EditRecord
{
    protected static string $resource = EkuDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
