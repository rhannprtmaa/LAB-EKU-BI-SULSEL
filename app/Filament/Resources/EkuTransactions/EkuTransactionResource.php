<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EkuTransactionResource\Pages\CreateEkuTransaction;
use App\Filament\Resources\EkuTransactionResource\Pages\EditEkuTransaction;
use App\Filament\Resources\EkuTransactionResource\Pages\ListEkuTransactions;
use App\Filament\Resources\EkuTransactionResource\Schemas\EkuTransactionForm;
use App\Filament\Resources\EkuTransactionResource\Tables\EkuTransactionsTable;
use App\Models\EkuTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EkuTransactionResource extends Resource
{
    protected static ?string $model = EkuTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pengajuan EKU';
    protected static ?string $pluralModelLabel = 'Daftar Pengajuan EKU';

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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEkuTransactions::route('/'),
            'create' => CreateEkuTransaction::route('/create'),
            'edit' => EditEkuTransaction::route('/{record}/edit'),
        ];
    }
}
