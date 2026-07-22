<?php

namespace App\Filament\Resources\EkuTransactions\Schemas;

use App\Models\EkuTransaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class EkuTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengajuan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('bank.name')->label('Nama Bank'),
                        TextEntry::make('periode')->label('Periode'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                EkuTransaction::STATUS_MENUNGGU => 'warning',
                                EkuTransaction::STATUS_DISETUJUI => 'success',
                                EkuTransaction::STATUS_REVISI => 'danger',
                                EkuTransaction::STATUS_DITOLAK => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => EkuTransaction::statusOptions()[$state] ?? $state),
                        TextEntry::make('batasan_periode')->label('Batasan Periode')->placeholder('-'),
                        TextEntry::make('user.name')->label('Diajukan oleh'),
                        TextEntry::make('created_at')->label('Tanggal Pengajuan')->dateTime('d M Y H:i'),
                    ]),

                Section::make('Feedback dari BI')
                    ->description('Catatan atau alasan yang disampaikan User BI saat mereview pengajuan ini.')
                    ->columns(2)
                    ->visible(fn ($record) => filled($record->catatan) || filled($record->approved_by))
                    ->schema([
                        TextEntry::make('approver.name')->label('Direview oleh')->placeholder('-'),
                        TextEntry::make('approved_at')->label('Tanggal Review')->dateTime('d M Y H:i')->placeholder('-'),
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
                        TextEntry::make('file_setoran_original')
                            ->label('Setoran — File Asli (Awal Diajukan Bank)')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_setoran_original ? Storage::disk('public')->url($record->file_setoran_original) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file_setoran')
                            ->label('Setoran — File Diterima BI (Sudah Direvisi)')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_setoran ? Storage::disk('public')->url($record->file_setoran) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file_penarikan_original')
                            ->label('Penarikan — File Asli (Awal Diajukan Bank)')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_penarikan_original ? Storage::disk('public')->url($record->file_penarikan_original) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file_penarikan')
                            ->label('Penarikan — File Diterima BI (Sudah Direvisi)')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_penarikan ? Storage::disk('public')->url($record->file_penarikan) : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('File Terlampir')
                    ->columns(3)
                    ->visible(fn ($record) => ! $record->is_edited_by_bi)
                    ->schema([
                        TextEntry::make('file_setoran')
                            ->label('File Setoran')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_setoran ? Storage::disk('public')->url($record->file_setoran) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file_penarikan')
                            ->label('File Penarikan')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_penarikan ? Storage::disk('public')->url($record->file_penarikan) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file_lampiran')
                            ->label('File Lampiran')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_lampiran ? Storage::disk('public')->url($record->file_lampiran) : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('File Lampiran')
                    ->visible(fn ($record) => $record->is_edited_by_bi)
                    ->schema([
                        TextEntry::make('file_lampiran')
                            ->label('File Lampiran')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : '-')
                            ->url(fn ($record) => $record->file_lampiran ? Storage::disk('public')->url($record->file_lampiran) : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }
}
