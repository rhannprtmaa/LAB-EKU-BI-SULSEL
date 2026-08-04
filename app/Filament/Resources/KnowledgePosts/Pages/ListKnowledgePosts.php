<?php

namespace App\Filament\Resources\KnowledgePosts\Pages;

use App\Filament\Resources\KnowledgePosts\KnowledgePostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgePosts extends ListRecords
{
    protected static string $resource = KnowledgePostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Berita/Panduan Baru'),
        ];
    }
}
