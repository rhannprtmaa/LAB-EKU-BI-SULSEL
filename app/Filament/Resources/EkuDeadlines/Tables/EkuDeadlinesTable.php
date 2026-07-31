<?php

namespace App\Filament\Resources\EkuDeadlines\Tables;

use App\Models\EkuDeadline;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EkuDeadlinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('batas_waktu')
                    ->label('Batas Waktu Pengajuan')
                    ->date('d F Y')
                    ->sortable(),

                IconColumn::make('status')
                    ->label('Status')
                    ->state(fn (EkuDeadline $record) => ! $record->isSudahLewat())
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (EkuDeadline $record) => $record->isSudahLewat() ? 'Sudah tertutup' : 'Masih terbuka'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(40),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('periode', 'desc')
            ->actions([
                EditAction::make()->modalWidth(Width::Medium),
                DeleteAction::make(),
            ]);
    }
}
