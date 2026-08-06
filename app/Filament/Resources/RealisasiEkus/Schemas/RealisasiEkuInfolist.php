<?php

namespace App\Filament\Resources\RealisasiEkus\Schemas;

use App\Models\EkuTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;

class RealisasiEkuInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengajuan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('bank.name')
                            ->label('Nama Bank')
                            ->icon('heroicon-o-building-library'),
                        TextEntry::make('periode')
                            ->label('Periode')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('approved_at')
                            ->label('Disetujui Sejak')
                            ->icon('heroicon-o-check-circle')
                            ->dateTime('d M Y')
                            ->placeholder('-'),
                    ]),

                Section::make('Ringkasan Realisasi & Deviasi')
    ->schema([
        Grid::make(2)->schema([

            // --- BAGIAN SETORAN ---
            TextEntry::make('total_akumulasi_setoran')
                ->label('Total Realisasi Setoran')
                ->state(function (EkuTransaction $record) {
                    // Ambil dari fungsi hitungDeviasi yang sudah kita perbaiki
                    $data = collect($record->hitungDeviasi());
                    return $data->where('jenis', 'Setoran')->sum('realisasi');
                })
                ->money('IDR', locale: 'id')
                ->color('success')
                ->weight('bold'),

            TextEntry::make('total_deviasi_setoran')
                ->label('Deviasi Setoran (Sisa/Over)')
                ->state(function (EkuTransaction $record) {
                    $data = collect($record->hitungDeviasi());
                    return $data->where('jenis', 'Setoran')->sum('deviasi');
                })
                ->formatStateUsing(function ($state) {
                    $prefix = $state < 0 ? '(Mines) Rp ' : 'Rp ';
                    return $prefix . number_format(abs($state), 0, ',', '.');
                })
                ->color(fn ($state) => $state < 0 ? 'danger' : 'warning'),

            // --- BAGIAN PENARIKAN ---
            TextEntry::make('total_akumulasi_penarikan')
                ->label('Total Realisasi Penarikan')
                ->state(function (EkuTransaction $record) {
                    $data = collect($record->hitungDeviasi());
                    return $data->where('jenis', 'Penarikan')->sum('realisasi');
                })
                ->money('IDR', locale: 'id')
                ->color('success')
                ->weight('bold'),

            TextEntry::make('total_deviasi_penarikan')
                ->label('Deviasi Penarikan (Sisa/Over)')
                ->state(function (EkuTransaction $record) {
                    $data = collect($record->hitungDeviasi());
                    return $data->where('jenis', 'Penarikan')->sum('deviasi');
                })
                ->formatStateUsing(function ($state) {
                    $prefix = $state < 0 ? '(Mines) Rp ' : 'Rp ';
                    return $prefix . number_format(abs($state), 0, ',', '.');
                })
                ->color(fn ($state) => $state < 0 ? 'danger' : 'warning'),

        ])
    ])
            ]);
    }
}
