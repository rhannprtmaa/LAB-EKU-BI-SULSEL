<?php

namespace App\Filament\Resources\EkuTransactions\Tables;

// <-- FIX: Mengikuti struktur Filament 5 milikmu
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EkuTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bank.name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Petugas Pembuat')
                    ->searchable(),

                TextColumn::make('total_nominal')
                    ->label('Total Nominal (Rp)')
                    ->numeric(
                        decimalPlaces: 0,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Menunggu' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(), // <-- Tombol untuk memunculkan tabel rincian 12 bulan
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
