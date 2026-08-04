<?php

namespace App\Filament\Resources\KnowledgePosts\Pages;

use App\Filament\Resources\KnowledgePosts\KnowledgePostResource;
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
}