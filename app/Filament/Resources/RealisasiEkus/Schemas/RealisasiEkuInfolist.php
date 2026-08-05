<?php

namespace App\Filament\Resources\RealisasiEkus\Schemas;

use App\Models\EkuTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                    ->description('Realisasi memakai entri paling baru dari riwayat input di bawah -- kalau ada input baru, ringkasan ini otomatis mengikuti.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_setoran')
                            ->label('Forecast Setoran')
                            ->money('IDR')
                            ->icon('heroicon-o-arrow-down-circle')
                            ->color('gray'),
                        TextEntry::make('realisasiTerbaru.total_setoran')
                            ->label('Realisasi Setoran')
                            ->money('IDR')
                            ->placeholder('Belum ada realisasi')
                            ->color('success'),
                        TextEntry::make('deviasi_setoran_display')
                            ->label('Deviasi Setoran')
                            ->state(function (EkuTransaction $record) {
                                if (! $record->realisasiTerbaru) {
                                    return null;
                                }

                                return (float) $record->total_setoran - (float) $record->realisasiTerbaru->total_setoran;
                            })
                            ->money('IDR')
                            ->placeholder('-')
                            ->color(fn ($state) => $state === null ? 'gray' : ($state > 0 ? 'warning' : ($state < 0 ? 'info' : 'success'))),

                        TextEntry::make('total_penarikan')
                            ->label('Forecast Penarikan')
                            ->money('IDR')
                            ->icon('heroicon-o-arrow-up-circle')
                            ->color('gray'),
                        TextEntry::make('realisasiTerbaru.total_penarikan')
                            ->label('Realisasi Penarikan')
                            ->money('IDR')
                            ->placeholder('Belum ada realisasi')
                            ->color('danger'),
                        TextEntry::make('deviasi_penarikan_display')
                            ->label('Deviasi Penarikan')
                            ->state(function (EkuTransaction $record) {
                                if (! $record->realisasiTerbaru) {
                                    return null;
                                }

                                return (float) $record->total_penarikan - (float) $record->realisasiTerbaru->total_penarikan;
                            })
                            ->money('IDR')
                            ->placeholder('-')
                            ->color(fn ($state) => $state === null ? 'gray' : ($state > 0 ? 'warning' : ($state < 0 ? 'info' : 'success'))),

                        TextEntry::make('realisasiTerbaru.inputBy.name')
                            ->label('Realisasi Terakhir Diinput oleh')
                            ->icon('heroicon-o-user')
                            ->placeholder('-'),
                        TextEntry::make('realisasiTerbaru.input_at')
                            ->label('Tanggal Input Terakhir')
                            ->icon('heroicon-o-clock')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('realisasiHistory')
                            ->label('Jumlah Riwayat Input')
                            ->state(fn (EkuTransaction $record) => $record->realisasiHistory()->count() . 'x')
                            ->icon('heroicon-o-clock'),
                    ]),
            ]);
    }
}
