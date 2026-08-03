<?php

namespace App\Filament\Resources\EkuDeadlines;

use App\Filament\Resources\EkuDeadlines\Pages\ListEkuDeadlines;
use App\Filament\Resources\EkuDeadlines\Schemas\EkuDeadlineForm;
use App\Filament\Resources\EkuDeadlines\Tables\EkuDeadlinesTable;
use App\Models\EkuDeadline;
use App\Support\CurrentUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EkuDeadlineResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = EkuDeadline::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static string|UnitEnum|null $navigationGroup = null;
    protected static ?string $navigationLabel = 'Batas Waktu Pengajuan EKU';
    protected static ?string $modelLabel = 'Batas Waktu';
    protected static ?string $pluralModelLabel = 'Batas Waktu Pengajuan EKU';

    protected static ?int $navigationSort = 4;

    // Hanya Admin BI yang menentukan deadline pengajuan EKU untuk bank.
    public static function canViewAny(): bool
    {
        return CurrentUser::get()?->isAdminBi() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return EkuDeadlineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EkuDeadlinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEkuDeadlines::route('/'),
        ];
    }
}
