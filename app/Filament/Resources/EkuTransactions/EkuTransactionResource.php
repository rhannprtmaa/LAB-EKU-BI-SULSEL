<?php

namespace App\Filament\Resources\EkuTransactions;

use App\Filament\Resources\EkuTransactions\Pages\CreateEkuTransaction;
use App\Filament\Resources\EkuTransactions\Pages\EditEkuTransaction;
use App\Filament\Resources\EkuTransactions\Pages\ListEkuTransactions;
use App\Filament\Resources\EkuTransactions\Pages\ViewEkuTransaction;
use App\Filament\Resources\EkuTransactions\RelationManagers\DetailsRelationManager;
use App\Filament\Resources\EkuTransactions\Schemas\EkuTransactionForm;
use App\Filament\Resources\EkuTransactions\Tables\EkuTransactionsTable;
use App\Models\EkuTransaction;
use App\Support\CurrentUser;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EkuTransactionResource extends Resource
{
    protected static ?string $model = EkuTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pengajuan EKU';
    protected static ?string $pluralModelLabel = 'Daftar Pengajuan EKU';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = CurrentUser::get();

        if ($user?->isUserPerbankan()) {
            $query->where('bank_id', $user->bank_id);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canCreate(): bool
    {
        return CurrentUser::get()?->isUserPerbankan() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return Auth::check();
    }

    public static function canEdit(Model $record): bool
    {
        $user = CurrentUser::get();

        if (! $user) {
            return false;
        }

        if ($user->isAdminBi()) {
            return false;
        }

        if ($user->isUserBi()) {
            return $record->status !== EkuTransaction::STATUS_DISETUJUI;
        }

        if ($user->isUserPerbankan()) {
            return $record->bank_id === $user->bank_id && $record->isEditableByBankOwner();
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = CurrentUser::get();

        return $user?->isUserPerbankan()
            && $record->bank_id === $user->bank_id
            && $record->status === EkuTransaction::STATUS_MENUNGGU;
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
            'create' => CreateEkuTransaction::route('/create'),
            'edit' => EditEkuTransaction::route('/{record}/edit'),
            'view' => ViewEkuTransaction::route('/{record}'),
        ];
    }
}
