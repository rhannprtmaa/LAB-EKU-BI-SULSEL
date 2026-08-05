<?php

namespace App\Filament\Resources\RealisasiEkus\RelationManagers;

use App\Support\CurrentUser;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'realisasiHistory';

    protected static ?string $title = 'Riwayat Input Realisasi';

    protected static ?string $modelLabel = 'Realisasi';

    protected static function namaFileAsli(): \Closure
    {
        return fn ($file) => date('YmdHis') . '_' . $file->getClientOriginalName();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_setoran')
                    ->label('File Excel Realisasi Setoran')
                    ->disk('public')
                    ->directory('realisasi-eku/setoran')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->helperText('Format file sama seperti Template Kerja EKU (forecast).')
                    ->maxSize(5120),

                FileUpload::make('file_penarikan')
                    ->label('File Excel Realisasi Penarikan')
                    ->disk('public')
                    ->directory('realisasi-eku/penarikan')
                    ->visibility('public')
                    ->getUploadedFileNameForStorageUsing(static::namaFileAsli())
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(5120),

                Textarea::make('keterangan')
                    ->label('Keterangan (opsional)')
                    ->placeholder('Contoh: Update realisasi s.d bulan Maret')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        // Hanya User BI yang menginput realisasi -- Admin BI khusus memantau.
        $bisaInput = (bool) CurrentUser::get()?->isUserBi();

        return $table
            ->recordTitleAttribute('input_at')
            ->defaultSort('input_at', 'desc')
            ->columns([
                TextColumn::make('input_at')
                    ->label('Tanggal Input')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('inputBy.name')
                    ->label('Diinput oleh')
                    ->placeholder('-'),

                TextColumn::make('file_setoran')
                    ->label('File Setoran')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? ($record->file_setoran_nama_asli ?? basename($state))
                        : 'Tidak ada')
                    ->url(fn ($record) => $record->file_setoran
                        ? Storage::disk('public')->url($record->file_setoran)
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('file_penarikan')
                    ->label('File Penarikan')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-document-text')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? ($record->file_penarikan_nama_asli ?? basename($state))
                        : 'Tidak ada')
                    ->url(fn ($record) => $record->file_penarikan ? Storage::disk('public')->url($record->file_penarikan) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('total_setoran')
                    ->label('Total Setoran')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                TextColumn::make('total_penarikan')
                    ->label('Total Penarikan')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Input Realisasi Baru')
                    ->modalHeading('Input Realisasi EKU')
                    ->modalWidth(Width::Large)
                    ->visible($bisaInput)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['input_by'] = Auth::id();
                        $data['input_at'] = now();

                        return $data;
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->visible(fn () => (bool) CurrentUser::get()?->isAdminBi()),
            ])
            ->bulkActions([]);
    }
}
