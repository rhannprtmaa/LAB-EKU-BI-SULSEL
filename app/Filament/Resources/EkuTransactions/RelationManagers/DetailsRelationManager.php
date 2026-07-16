<?php

namespace App\Filament\Resources\EkuTransactions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Rincian Proyeksi EKU Bulanan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bulan')
            ->columns([
                TextColumn::make('bulan')->label('Bulan')->searchable(),
                TextColumn::make('jenis_file')->label('Jenis')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Setoran' => 'warning',
                        'Penarikan' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('kertas_100k')->label('Rp 100.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_50k')->label('Rp 50.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_20k')->label('Rp 20.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_10k')->label('Rp 10.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_5k')->label('Rp 5.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_2k')->label('Rp 2.000')->numeric(0, ',', '.'),
                TextColumn::make('kertas_1k')->label('Rp 1.000 (K)')->numeric(0, ',', '.'),
                TextColumn::make('logam_1k')->label('Rp 1.000 (L)')->numeric(0, ',', '.'),
                TextColumn::make('logam_500')->label('Rp 500')->numeric(0, ',', '.'),
                TextColumn::make('logam_200')->label('Rp 200')->numeric(0, ',', '.'),
                TextColumn::make('logam_100')->label('Rp 100')->numeric(0, ',', '.'),
                TextColumn::make('subtotal')->label('Subtotal')->numeric(0, ',', '.')->bold(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
