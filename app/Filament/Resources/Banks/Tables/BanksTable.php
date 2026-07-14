<?php

namespace App\Filament\Resources\Banks\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BanksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users') // Menghitung otomatis dari relasi User
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
