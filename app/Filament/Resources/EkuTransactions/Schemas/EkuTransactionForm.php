<?php

namespace App\Filament\Resources\EkuTransactions\Schemas; //

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EkuTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Form Pengajuan Estimasi Kebutuhan Uang (EKU)')
                    ->description('Silakan lengkapi data pengajuan EKU tahunan di bawah ini.')
                    ->schema([

                        // 1. Nama Bank (Otomatis menampilkan nama bank dari user yang login & dikunci)
                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->default(fn () => Auth::user()->bank?->name ?? '-')
                            ->disabled() // Mengunci input agar tidak bisa diubah manual
                            ->dehydrated(false), // Tidak ikut dikirim ke database karena sudah ada bank_id

                        // 2. Periode (Contoh: Pilihan Tahun Proyeksi EKU)
                        Select::make('periode')
                            ->label('Periode (Tahun)')
                            ->options([
                                date('Y') => date('Y'),
                                date('Y') + 1 => date('Y') + 1,
                                date('Y') + 2 => date('Y') + 2,
                            ])
                            ->default(date('Y') + 1)
                            ->required(),

                        // 3. Batasan Periode (Contoh: Keterangan batas waktu atau rentang semester/kuartal)
                        TextInput::make('batasan_periode')
                            ->label('Batasan Periode')
                            ->placeholder('Contoh: Batas Pengajuan s.d 31 Desember')
                            ->maxLength(255),

                        // 4. File Excel Setoran
                        FileUpload::make('file_setoran')
                            ->label('File Excel Setoran')
                            ->directory('pengajuan-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(5120),

                        // 5. File Excel Penarikan
                        FileUpload::make('file_penarikan')
                            ->label('File Excel Penarikan')
                            ->directory('pengajuan-eku/penarikan')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->maxSize(5120),

                        // 6. File Lampiran (Bisa untuk PDF surat pengantar, dll)
                        FileUpload::make('file_lampiran')
                            ->label('File Lampiran (PDF / Pendukung)')
                            ->directory('pengajuan-eku/lampiran')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240), // Batas maksimal 10MB
                    ])
                    ->columns(1), // Memaksa susunan grid berurutan rapi ke bawah (1 kolom penuh)

                // Hidden input untuk mencatat relasi ID ke database di latar belakang
                Hidden::make('bank_id')
                    ->default(fn () => Auth::user()->bank_id),

                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }
}
