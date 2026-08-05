<?php

namespace App\Filament\Resources\RealisasiEkus;

use App\Filament\Resources\RealisasiEkus\Pages\ListRealisasiEkus;
use App\Filament\Resources\RealisasiEkus\Pages\ViewRealisasiEku;
use App\Filament\Resources\RealisasiEkus\RelationManagers\HistoryRelationManager;
use App\Filament\Resources\RealisasiEkus\Schemas\RealisasiEkuInfolist;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Support\CurrentUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RealisasiEkuResource extends Resource
{
    protected static ?string $model = EkuTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Realisasi & Deviasi EKU';
    protected static ?string $modelLabel = 'Realisasi EKU';
    protected static ?string $pluralModelLabel = 'Realisasi & Deviasi EKU';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', EkuTransaction::STATUS_DISETUJUI)
            ->withCount('realisasiHistory');
    }

    public static function infolist(Schema $schema): Schema
    {
        return RealisasiEkuInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bank.name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('total_setoran')
                    ->label('Forecast Setoran')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                TextColumn::make('total_penarikan')
                    ->label('Forecast Penarikan')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                TextColumn::make('realisasiTerbaru.total_setoran')
                    ->label('Realisasi Setoran')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.')
                    ->placeholder('Belum ada'),

                TextColumn::make('realisasiTerbaru.total_penarikan')
                    ->label('Realisasi Penarikan')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.')
                    ->placeholder('Belum ada'),

                TextColumn::make('realisasi_history_count')
                    ->label('Riwayat Input')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state . 'x diinput'),

                TextColumn::make('realisasiTerbaru.input_at')
                    ->label('Realisasi Terakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum diinput'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Gunakan ViewAction milik Table
                ViewAction::make()
                    ->label('Detail')
                    ->color('gray'),

                Action::make('uploadRealisasi')
                    ->label('Input Realisasi')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn (EkuTransaction $record) => (bool) CurrentUser::get()?->isUserBi())    
                    ->form([
                        FileUpload::make('file_setoran')
                            ->label('Upload File Excel Realisasi Setoran')
                            ->disk('public')
                            ->directory('realisasi-eku/setoran')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),

                        FileUpload::make('file_penarikan')
                            ->label('Upload File Excel Realisasi Penarikan')
                            ->disk('public')
                            ->directory('realisasi-eku/penarikan')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $user = CurrentUser::get();

                        // Simpan ke tabel history baru menggunakan nama kolom yang benar sesuai Model
                        EkuTransactionRealisasi::create([
                            'eku_transaction_id' => $record->id,
                            'file_setoran'       => $data['file_setoran'],
                            'file_penarikan'     => $data['file_penarikan'],
                            'input_by'           => $user?->id,
                            'input_at'           => now(),
                        ]);

                        if (method_exists($record, 'hitungDeviasi')) {
                            $record->hitungDeviasi();
                        }

                        Notification::make()
                            ->title('File Realisasi EKU Berhasil Diunggah!')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRealisasiEkus::route('/'),
            'view' => ViewRealisasiEku::route('/{record}'),
        ];
    }
}
