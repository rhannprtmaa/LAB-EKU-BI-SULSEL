<?php

namespace App\Filament\Resources\KnowledgePosts\Pages;

use App\Filament\Resources\KnowledgePosts\KnowledgePostResource;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgePost extends EditRecord
{
    protected static string $resource = KnowledgePostResource::class;
}
