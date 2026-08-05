<?php

namespace App\Filament\Resources\RealisasiEkus;

use App\Filament\Resources\RealisasiEkus\Pages\ListRealisasiEkus;
use App\Filament\Resources\RealisasiEkus\Pages\ViewRealisasiEku;
use App\Filament\Resources\RealisasiEkus\RelationManagers\HistoryRelationManager;
use App\Filament\Resources\RealisasiEkus\Schemas\RealisasiEkuInfolist;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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

    // Hanya User BI & Admin BI yang berurusan dengan realisasi. Bank tidak
    // input realisasi -- yang mereka ajukan adalah forecast/proyeksi.
    public static function canViewAny(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    public static function canCreate(): bool
    {
        return false; // Realisasi diinput lewat Detail (History), bukan create record EkuTransaction baru.
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Menghapus pengajuan EKU bukan urusan resource ini.
    }

    public static function getEloquentQuery(): Builder
    {
        // Realisasi cuma relevan untuk pengajuan yang sudah Disetujui --
        // sebelum itu belum ada forecast final yang bisa dibandingkan.
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
            ->defaultSort('created_at', 'desc');
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
