<?php

namespace App\Filament\Resources\KnowledgePosts\Pages;

use App\Filament\Resources\KnowledgePosts\KnowledgePostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKnowledgePost extends CreateRecord
{
    protected static string $resource = KnowledgePostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

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
