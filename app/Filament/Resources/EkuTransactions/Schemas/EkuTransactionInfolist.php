<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class EkuTransactionInfolist
{
    // Semua entry file dibikin lewat helper ini biar KONSISTEN ukurannya
    // (limit panjang nama file yang sama, badge, icon, warna) — tidak lagi
    // beda-beda ukuran tergantung panjang nama file aslinya.
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

                Section::make('Perbandingan File: Asli vs Diterima BI')
                    ->description('File sebelah kiri adalah yang pertama kali diunggah bank. File sebelah kanan adalah versi yang sudah dikoreksi & diterima User BI.')
                    ->columns(2)
                    ->visible(fn ($record) => $record->is_edited_by_bi)
                    ->schema([
                        static::fileEntry('file_setoran_original', 'Setoran — File Asli (Awal Diajukan Bank)', 'gray', 'heroicon-o-document-text'),
                        static::fileEntry('file_setoran', 'Setoran — File Diterima BI (Sudah Direvisi)', 'success', 'heroicon-o-document-check', 'file_setoran_nama_asli'),
                        static::fileEntry('file_penarikan_original', 'Penarikan — File Asli (Awal Diajukan Bank)', 'gray', 'heroicon-o-document-text'),
                        static::fileEntry('file_penarikan', 'Penarikan — File Diterima BI (Sudah Direvisi)', 'success', 'heroicon-o-document-check', 'file_penarikan_nama_asli'),
                    ]),

                Section::make('File Terlampir')
                    ->columns(3)
                    ->visible(fn ($record) => ! $record->is_edited_by_bi)
                    ->schema([
                        static::fileEntry('file_setoran', 'File Setoran', 'info', 'heroicon-o-document-text', 'file_setoran_nama_asli'),
                        static::fileEntry('file_penarikan', 'File Penarikan', 'info', 'heroicon-o-document-text', 'file_penarikan_nama_asli'),
                        static::fileEntry('file_lampiran', 'File Lampiran', 'gray', 'heroicon-o-paper-clip', 'file_lampiran_nama_asli'),
                    ]),

                Section::make('File Lampiran')
                    ->visible(fn ($record) => $record->is_edited_by_bi)
                    ->schema([
                        static::fileEntry('file_lampiran', 'File Lampiran', 'gray', 'heroicon-o-paper-clip', 'file_lampiran_nama_asli'),
                    ]),
            ]);
    }
}
