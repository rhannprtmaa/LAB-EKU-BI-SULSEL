<?php

namespace App\Filament\Resources\RealisasiEkus\RelationManagers;

use App\Models\EkuTransactionRealisasiDetail;
use App\Services\EkuReportCalculator;
use App\Support\CurrentUser;
use App\Support\Rupiah;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'realisasiHistory';

    protected static ?string $title = 'Riwayat Input Realisasi';

    protected static ?string $modelLabel = 'Realisasi';

    // Fungsi form() dihapus karena input manual sudah digantikan Upload Massal

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('input_at')
            ->defaultSort('input_at', 'desc')
            ->columns([
                TextColumn::make('input_at')
                    ->label('Tanggal Input')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('inputBy.name')
                    ->label('Diinput oleh')
                    ->placeholder('-'),

                // === GROUP KOLOM SETORAN ===
                ColumnGroup::make('Setoran', [
                    TextColumn::make('upb_setoran')
                        ->label('UPB')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Setoran')->value('total_upb') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('upk_setoran')
                        ->label('UPK')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Setoran')->value('total_upk') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('total_setoran')
                        ->label('Total Setoran')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),
                ]),

                // === GROUP KOLOM PENARIKAN ===
                ColumnGroup::make('Penarikan', [
                    TextColumn::make('upb_penarikan')
                        ->label('UPB')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Penarikan')->value('total_upb') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('upk_penarikan')
                        ->label('UPK')
                        ->getStateUsing(fn ($record) => EkuTransactionRealisasiDetail::where('eku_transaction_realisasi_id', $record->id)->where('jenis_file', 'Penarikan')->value('total_upk') ?? 0)
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                    TextColumn::make('total_penarikan')
                        ->label('Total Penarikan')
                        ->weight('bold')
                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),
                ]),

                // Kolom file_penarikan dihapus, tersisa 1 kolom file terpusat
                TextColumn::make('file_setoran')
                    ->label('File Realisasi')
                    ->formatStateUsing(fn ($state) => $state ? 'Download File' : 'Tidak Ada File')
                    ->url(fn ($state) => $state ? Storage::disk('public')->url($state) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-o-document-arrow-down'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                // Kosong, karena tombol Create/Input Manual ditiadakan
            ])
            ->actions([
                Action::make('unduh_pdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn ($record): StreamedResponse => $this->unduhPdf($record)),

               DeleteAction::make()
                    ->visible(fn () => (bool) CurrentUser::get()?->isAdminBi()),
            ])
            ->bulkActions([]);
    }

    /**
     * Generate PDF rincian SATU input realisasi (satu baris tabel
     * "Riwayat Input Realisasi" = satu record EkuTransactionRealisasi,
     * yang bisa punya banyak detail bulan+jenis sekaligus).
     */
    protected function unduhPdf($record): StreamedResponse
    {
        $transaksi = $record->ekuTransaction;

        $rincian = $record->details
            ->sortBy([['bulan', 'asc'], ['jenis_file', 'asc']])
            ->map(function (EkuTransactionRealisasiDetail $detail) {
                $upbUpk = EkuReportCalculator::upbUpk($detail);

                return [
                    'bulan' => $detail->bulan,
                    'jenis_file' => $detail->jenis_file,
                    'upb' => $upbUpk['upb'],
                    'upk' => $upbUpk['upk'],
                    'subtotal' => (float) $detail->subtotal,
                ];
            })
            ->values()
            ->all();

        $rupiah = fn (float $v) => Rupiah::format($v);

        $pdf = Pdf::loadView('pdf.realisasi-detail', [
            'bankName' => $transaksi?->bank?->name ?? '-',
            'periode' => $transaksi?->periode ?? '-',
            'tanggalInput' => $record->input_at
                ? $record->input_at->locale('id')->translatedFormat('d F Y, H:i') . ' WITA'
                : '-',
            'diinputOleh' => $record->inputBy?->name ?? '-',
            'keterangan' => $record->keterangan,
            'totalSetoran' => (float) ($record->total_setoran ?? 0),
            'totalPenarikan' => (float) ($record->total_penarikan ?? 0),
            'rincian' => $rincian,
            'rupiah' => $rupiah,
        ])->setPaper('a4', 'portrait');

        $namaFile = 'realisasi-' . str($transaksi?->bank?->name ?? 'bank')->slug() . '-' . $record->id . '.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
