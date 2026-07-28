<?php
namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'bank' => 'info',
                        default => 'gray',
                    }),

                // Menampilkan nama Bank langsung di tabel pengguna
                TextColumn::make('bank.name')
                    ->label('Bank')
                    ->placeholder('Internal BI')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Bank
                SelectFilter::make('bank_id')
                    ->label('Filter Bank')
                    ->relationship('bank', 'name'),
            ]);
    }
}
