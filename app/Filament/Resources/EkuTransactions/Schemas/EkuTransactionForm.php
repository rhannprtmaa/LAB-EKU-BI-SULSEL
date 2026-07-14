<?php

namespace App\Filament\Resources\EkuTransactionResource\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EkuTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload File Pengajuan EKU')
                    ->description('Unggah file Excel format standar BI. Anda dapat mengunggah file Setoran, Penarikan, atau keduanya sekaligus.')
                    ->schema([
                        FileUpload::make('file_setoran')
                            ->label('File Excel Setoran (Opsional)')
                            ->directory('pengajuan-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(5120),

                        FileUpload::make('file_penarikan')
                            ->label('File Excel Penarikan (Opsional)')
                            ->directory('pengajuan-eku/penarikan')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(5120),
                    ])
                    ->columns(2),

                Hidden::make('bank_id')
                    ->default(fn () => Auth::user()->bank_id),

                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }
}
