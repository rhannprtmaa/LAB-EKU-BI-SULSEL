<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class EkuTransactionInfolist
{
    protected static function fileEntry(
        string $namaKolom,
        string $label,
        string $color,
        string $icon,
        ?string $kolomNamaAsli = null,
    ): TextEntry {
        return TextEntry::make($namaKolom)
            ->label($label)
            ->badge()
            ->color($color)
            ->icon($icon)
            ->limit(28) // panjang teks disamakan, biar semua badge seragam ukurannya
            ->formatStateUsing(function ($state, $record) use ($namaKolom, $kolomNamaAsli) {
                if (! $state) {
                    return 'Belum ada file';
                }

                return ($kolomNamaAsli ? $record->{$kolomNamaAsli} : null) ?? basename($state);
            })
            ->url(fn ($record) => $record->{$namaKolom} ? Storage::disk('public')->url($record->{$namaKolom}) : null)
            ->openUrlInNewTab();
    }

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
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->icon('heroicon-o-flag')
                            ->color(fn (string $state): string => match ($state) {
                                EkuTransaction::STATUS_MENUNGGU => 'warning',
                                EkuTransaction::STATUS_DISETUJUI => 'success',
                                EkuTransaction::STATUS_REVISI => 'danger',
                                EkuTransaction::STATUS_DITOLAK => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => EkuTransaction::statusOptions()[$state] ?? $state),
                        TextEntry::make('batasan_periode')
                            ->label('Batasan Periode')
                            ->icon('heroicon-o-clock')
                            ->placeholder('-'),
                        TextEntry::make('user.name')
                            ->label('Diajukan oleh')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->icon('heroicon-o-paper-airplane')
                            ->dateTime('d M Y H:i'),
                    ]),

                Section::make('Feedback dari BI')
                    ->description('Catatan atau alasan yang disampaikan User BI saat mereview pengajuan ini.')
                    ->columns(2)
                    ->visible(fn ($record) => filled($record->catatan) || filled($record->approved_by))
                    ->schema([
                        TextEntry::make('approver.name')->label('Direview oleh')->icon('heroicon-o-user-circle')->placeholder('-'),
                        TextEntry::make('approved_at')->label('Tanggal Review')->icon('heroicon-o-calendar-days')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('catatan')
                            ->label('Feedback / Catatan dari BI')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                    ]),

                Section::make('File Asli & Diterima Bank Indonesia')
                    ->description('File sebelah kiri adalah original dan yang kanan yang di terima oleh Bank Indonesia')
                    ->columns(2)
                    ->visible(fn ($record) => $record->is_edited_by_bi
                        && $record->status === EkuTransaction::STATUS_DISETUJUI)
                    ->schema([
                        static::fileEntry('file_setoran_original', 'EKUSetoran (File Asli)', 'gray', 'heroicon-o-document-text'),
                        static::fileEntry('file_setoran', 'EKU Setoran (Diterima BI)', 'success', 'heroicon-o-document-check', 'file_setoran_nama_asli'),
                        static::fileEntry('file_penarikan_original', 'EKU Penarikan (File Asli)', 'gray', 'heroicon-o-document-text'),
                        static::fileEntry('file_penarikan', 'EKU Penarikan (Diterima BI)', 'success', 'heroicon-o-document-check', 'file_penarikan_nama_asli'),
                    ]),

                Section::make('File Terlampir')
                    ->columns(3)
                    ->visible(fn ($record) => ! (
                        $record->is_edited_by_bi && $record->status === EkuTransaction::STATUS_DISETUJUI
                    ))
                    ->schema([
                        static::fileEntry('file_setoran', 'File Setoran', 'info', 'heroicon-o-document-text', 'file_setoran_nama_asli'),
                        static::fileEntry('file_penarikan', 'File Penarikan', 'info', 'heroicon-o-document-text', 'file_penarikan_nama_asli'),
                        static::fileEntry('file_lampiran', 'File Lampiran', 'gray', 'heroicon-o-paper-clip', 'file_lampiran_nama_asli'),
                    ]),

                Section::make('File Lampiran')
                    ->visible(fn ($record) => $record->is_edited_by_bi
                        && $record->status === EkuTransaction::STATUS_DISETUJUI)
                    ->schema([
                        static::fileEntry('file_lampiran', 'File Lampiran', 'gray', 'heroicon-o-paper-clip', 'file_lampiran_nama_asli'),
                    ]),

                Section::make('Ringkasan Realisasi & Deviasi EKU')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_setoran')
                            ->label('Pengajuan Setoran')
                            ->money('IDR'),
                        TextEntry::make('total_realisasi_setoran')
                            ->label('Realisasi Setoran')
                            ->money('IDR'),
                        TextEntry::make('deviasi_setoran')
                            ->label('Deviasi Setoran')
                            ->money('IDR')
                            ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),

                        TextEntry::make('total_penarikan')
                            ->label('Pengajuan Penarikan')
                            ->money('IDR'),
                        TextEntry::make('total_realisasi_penarikan')
                            ->label('Realisasi Penarikan')
                            ->money('IDR'),
                        TextEntry::make('deviasi_penarikan')
                            ->label('Deviasi Penarikan')
                            ->money('IDR')
                            ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
                    ]),
            ]);
    }
}
