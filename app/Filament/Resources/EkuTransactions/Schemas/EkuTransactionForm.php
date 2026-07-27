<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuTransaction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EkuTransactionForm
{
    /**
     * Simpan file dengan nama asli yang diupload user (bukan kode/hash acak),
     * tapi tetap diberi prefix waktu supaya tidak saling menimpa jika ada
     * beberapa bank yang upload file dengan nama yang sama persis.
     */
    protected static function namaFileAsli(): \Closure
    {
        return fn ($file) => date('YmdHis') . '_' . $file->getClientOriginalName();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('bank_name')
                    ->label('Nama Bank')
                    ->default(fn ($record) => $record?->bank?->name ?? Auth::user()->bank?->name ?? '-')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('periode')
                    ->label('Periode (Tahun)')
                    ->options([
                        date('Y') => date('Y'),
                        date('Y') + 1 => date('Y') + 1,
                        date('Y') + 2 => date('Y') + 2,
                    ])
                    ->default(date('Y') + 1)
                    ->required(),

                TextInput::make('batasan_periode')
                    ->label('Batasan Periode')
                    ->placeholder('Contoh: Batas Pengajuan s.d 31 Desember')
                    ->maxLength(255)
                    ->columnSpanFull(),

                FileUpload::make('file_setoran')
                    ->label('File Excel Setoran')
                    ->disk('public')
                    ->directory('pengajuan-eku/setoran')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                FileUpload::make('file_penarikan')
                    ->label('File Excel Penarikan')
                    ->disk('public')
                    ->directory('pengajuan-eku/penarikan')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                FileUpload::make('file_lampiran')
                    ->label('File Lampiran (PDF / Pendukung)')
                    ->disk('public')
                    ->directory('pengajuan-eku/lampiran')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Section::make('Feedback dari BI')
                    ->columnSpanFull()
                    ->visible(fn ($record) => filled($record) && filled($record->catatan))
                    ->columns(2)
                    ->schema([
                        TextInput::make('status_display')
                            ->label('Status Saat Ini')
                            ->default(fn ($record) => $record ? (EkuTransaction::statusOptions()[$record->status] ?? $record->status) : '-')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('catatan')
                            ->label('Catatan dari User BI')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Belum ada catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Hidden::make('bank_id')->default(fn () => Auth::user()->bank_id),
                Hidden::make('user_id')->default(fn () => Auth::id()),
            ]);
    }
}
