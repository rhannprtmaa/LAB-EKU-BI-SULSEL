<?php

namespace App\Filament\Resources\EkuDeadlines\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EkuDeadlineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('batas_waktu')
                    ->label('Batas Pengajuan (Kalender)')
                    ->helperText('Pilih tanggal batas akhir. Periode (Tahun) akan terisi secara otomatis.')
                    ->native(false)
                    ->displayFormat('d F Y')
                    ->required()
                    ->live() // Membaca perubahan tanggal secara real-time
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Jika tanggal dipilih, ambil tahunnya dan masukkan ke kolom periode
                        if ($state) {
                            $set('periode', Carbon::parse($state)->format('Y'));
                        } else {
                            $set('periode', null);
                        }
                    }),

                TextInput::make('periode')
                    ->label('Periode (Tahun)')
                    ->required()
                    ->readOnly() // Mengunci inputan agar tidak bisa diubah manual oleh Admin
                    ->unique(ignoreRecord: true),

                TextInput::make('keterangan')
                    ->label('Keterangan (opsional)')
                    ->placeholder('Contoh: Batas pengajuan proyeksi EKU')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
