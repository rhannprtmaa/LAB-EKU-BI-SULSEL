<?php

namespace App\Filament\Resources\RealisasiEkus\RelationManagers;

use App\Models\EkuTransactionRealisasiDetail;
use App\Support\CurrentUser;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'realisasiHistory';

    protected static ?string $title = 'Riwayat Input Realisasi';

    protected static ?string $modelLabel = 'Realisasi';

    // Fungsi form() dihapus karena input manual sudah digantikan Upload Massal

    public function table(Table $table): Table
    {
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

                // === GROUP KOLOM SETORAN ===
                ColumnGroup::make('Setoran', [
                    TextColumn::make('upb_setoran')
                        ->label('UPB')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Setoran')->value('total_upb') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('upk_setoran')
                        ->label('UPK')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Setoran')->value('total_upk') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('total_setoran')
                        ->label('Total Setoran')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),
                ]),

                // === GROUP KOLOM PENARIKAN ===
                ColumnGroup::make('Penarikan', [
                    TextColumn::make('upb_penarikan')
                        ->label('UPB')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Penarikan')->value('total_upb') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('upk_penarikan')
                        ->label('UPK')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Penarikan')->value('total_upk') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('total_penarikan')
                        ->label('Total Penarikan')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),
                ]),

                // Kolom file_penarikan dihapus, tersisa 1 kolom file terpusat
                TextColumn::make('file_setoran')
                    ->label('File Realisasi')
                    ->formatStateUsing(fn ($state) => $state ? 'Download File' : 'Tidak Ada File')
                    ->url(fn ($state) => $state ? Storage::disk('public')->url($state) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-o-document-arrow-down'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Kosong, karena tombol Create/Input Manual ditiadakan
            ])
            ->actions([
               DeleteAction::make()
                    ->visible(fn () => (bool) CurrentUser::get()?->isAdminBi()),
            ])
            ->bulkActions([]);
    }
}
