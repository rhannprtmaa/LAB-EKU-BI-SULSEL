<?php

namespace App\Filament\Resources\RealisasiEkus\Schemas;

use App\Support\UploadedFileNaming;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;

class RealisasiEkuForm
{
    public static function configure (Form $form): Form
    {
        return $form
            ->schema([
                // ... (Field Realisasi dan Deviasi Anda yang lain) ...

                FileUpload::make('file_realisasi')
                    ->label('Upload File Realisasi')
                    ->disk('public')
                    ->directory('realisasi-files')
                    ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
