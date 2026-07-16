<?php

namespace App\Filament\Resources\EkuTransactions;

use App\Filament\Resources\EkuTransactions\Pages\ListEkuTransactions;
use App\Filament\Resources\EkuTransactions\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\EkuTransactions\Schemas\EkuTransactionForm;
use App\Filament\Resources\EkuTransactions\Tables\EkuTransactionsTable;
use App\Models\EkuTransaction;
use BackedEnum; // <-- IMPORT INI WAJIB ADA
use UnitEnum;   // <-- IMPORT INI WAJIB ADA
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EkuTransactionResource extends Resource
{
    protected static ?string $model = EkuTransaction::class;

    // --- FIX TIPE DATA WAJIB FILAMENT TERBARU ---
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pengajuan EKU';
    protected static ?string $pluralModelLabel = 'Daftar Pengajuan EKU';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return EkuTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EkuTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEkuTransactions::route('/'),
        ];
    }
}
