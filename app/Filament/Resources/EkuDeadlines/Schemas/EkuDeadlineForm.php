<?php

namespace App\Filament\Resources\EkuDeadlines\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EkuDeadlineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('periode')
                    ->label('Periode (Tahun)')
                    ->options(fn () => collect(range(date('Y'), date('Y') + 3))
                        ->mapWithKeys(fn ($tahun) => [(string) $tahun => (string) $tahun])
                        ->all())
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->native(false),

                DatePicker::make('batas_waktu')
                    ->label('Batas Pengajuan')
                    ->helperText('Setelah tanggal ini, User Perbankan tidak bisa lagi membuat/mengunggah pengajuan EKU untuk periode ini.')
                    ->native(false)
                    ->displayFormat('d F Y')
                    ->required(),

                TextInput::make('keterangan')
                    ->label('Keterangan (opsional)')
                    ->placeholder('Contoh: Batas pengajuan proyeksi EKU tahun 2027')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
