<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuTransaction;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EkuTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Auth::user();
        $isUserBi = $user?->isUserBi() ?? false;
        $isAdminBi = $user?->isAdminBi() ?? false;
        $isInternalBi = $isUserBi || $isAdminBi;

        return $schema
            ->components([
                Section::make('Form Pengajuan Estimasi Kebutuhan Uang (EKU)')
                    ->description('Silakan lengkapi data pengajuan EKU tahunan di bawah ini.')
                    ->schema([

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
                            ->maxLength(255),

                        FileUpload::make('file_setoran')
                            ->label($isInternalBi ? 'File Excel Setoran (Versi Berlaku)' : 'File Excel Setoran')
                            ->directory('pengajuan-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120),

                        FileUpload::make('file_penarikan')
                            ->label($isInternalBi ? 'File Excel Penarikan (Versi Berlaku)' : 'File Excel Penarikan')
                            ->directory('pengajuan-eku/penarikan')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(5120),

                        FileUpload::make('file_lampiran')
                            ->label('File Lampiran (PDF / Pendukung)')
                            ->directory('pengajuan-eku/lampiran')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240),
                    ])
                    ->columns(1),

                Section::make('File Asli dari Bank (Sebelum Direvisi)')
                    ->description('Referensi pembanding — file ini tidak bisa diubah, hanya untuk melihat versi asli sebelum dikoreksi.')
                    ->visible(fn ($record) => $isInternalBi && $record && $record->is_edited_by_bi)
                    ->schema([
                        FileUpload::make('file_setoran_original')
                            ->label('File Setoran Asli (dari Bank)')
                            ->disabled()
                            ->dehydrated(false),

                        FileUpload::make('file_penarikan_original')
                            ->label('File Penarikan Asli (dari Bank)')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Status Review')
                    ->visible(fn ($record) => filled($record))
                    ->schema([
                        TextEntry::make('status_display')
                            ->label('Status Saat Ini')
                            ->content(fn ($record) => $record ? (EkuTransaction::statusOptions()[$record->status] ?? $record->status) : '-'),

                        Textarea::make('catatan')
                            ->label('Catatan dari User BI')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Belum ada catatan')
                            ->rows(2),
                    ])
                    ->columns(2),

                Hidden::make('bank_id')
                    ->default(fn () => Auth::user()->bank_id),

                Hidden::make('user_id')
                    ->default(fn () => Auth::id()),
            ]);
    }
}
