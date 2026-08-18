<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Exports\RealisasiTemplateExport;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Models\EkuTransactionRealisasiDetail;
use App\Support\CurrentUser;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListRealisasiEkus extends ListRecords
{
    protected static string $resource = RealisasiEkuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TOMBOL 1: DOWNLOAD TEMPLATE
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new RealisasiTemplateExport(), 'Template_Realisasi_Harian.xlsx')),

            // TOMBOL 2: UPLOAD REALISASI
            Actions\Action::make('upload_realisasi')
                ->label('Upload Realisasi Massal')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Upload Data Realisasi')
                ->modalDescription('Pilih periode dan unggah file excel yang telah diisi. (Angka 1 di excel akan otomatis dikalikan 1 Juta)')
                ->form([
                    Select::make('bulan')
                        ->label('Bulan Realisasi')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->native(false)
                        ->required(),
                    Select::make('tahun')
                        ->label('Tahun / Periode')
                        ->options(function () {
                            $currentYear = date('Y');
                            return collect(range($currentYear - 5, $currentYear + 5))->mapWithKeys(fn ($year) => [$year => $year])->toArray();
                        })
                        ->default(date('Y'))
                        ->native(false)
                        ->required(),
                    FileUpload::make('file')
                        ->label('File Excel Realisasi')
                        ->disk('local')
                        ->directory('temp-excel-realisasi')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
               ->action(function (array $data): void {
                    $filePath = Storage::disk('local')->path($data['file']);

                    try {
                        // Class rahasia untuk membedah Excel
                        $importClass = new class implements \Maatwebsite\Excel\Concerns\ToArray {
                            public function array(array $array) { return $array; }
                        };

                        $excelData = Excel::toArray($importClass, $filePath);

                        $sheetSetoran = $excelData[0] ?? [];
                        $sheetPenarikan = $excelData[1] ?? [];

                        $bankData = [];

                        // 1. PROSES SHEET SETORAN
                        foreach ($sheetSetoran as $index => $row) {
                            if ($index < 3) continue; // Skip 3 baris header Excel
                            $bankId = $row[0] ?? null;
                            if (!$bankId) continue;

                            $upb = 0; $upk = 0;
                            $colIndex = 2; // Mulai dari Kolom C
                            for ($d = 1; $d <= 31; $d++) {
                                // Mengalikan inputan dengan 1.000.000 (1 Juta)
                                $upb += (isset($row[$colIndex]) && is_numeric($row[$colIndex]) ? (float)$row[$colIndex] : 0) * 1000000;
                                $upk += (isset($row[$colIndex+1]) && is_numeric($row[$colIndex+1]) ? (float)$row[$colIndex+1] : 0) * 1000000;
                                $colIndex += 2;
                            }

                            if ($upb + $upk > 0) {
                                $bankData[$bankId]['setoran'] = ['upb' => $upb, 'upk' => $upk, 'subtotal' => $upb + $upk];
                            }
                        }

                        // 2. PROSES SHEET PENARIKAN
                        foreach ($sheetPenarikan as $index => $row) {
                            if ($index < 3) continue;
                            $bankId = $row[0] ?? null;
                            if (!$bankId) continue;

                            $upb = 0; $upk = 0;
                            $colIndex = 2;
                            for ($d = 1; $d <= 31; $d++) {
                                // Mengalikan inputan dengan 1.000.000 (1 Juta)
                                $upb += (isset($row[$colIndex]) && is_numeric($row[$colIndex]) ? (float)$row[$colIndex] : 0) * 1000000;
                                $upk += (isset($row[$colIndex+1]) && is_numeric($row[$colIndex+1]) ? (float)$row[$colIndex+1] : 0) * 1000000;
                                $colIndex += 2;
                            }

                            if ($upb + $upk > 0) {
                                $bankData[$bankId]['penarikan'] = ['upb' => $upb, 'upk' => $upk, 'subtotal' => $upb + $upk];
                            }
                        }

                        // 3. SIMPAN KE DATABASE (BYPASS PARSER LAMA DENGAN withoutEvents)
                        $user = CurrentUser::get();

                        \App\Models\EkuTransactionRealisasi::withoutEvents(function () use ($bankData, $data, $user) {
                            \App\Models\EkuTransactionRealisasiDetail::withoutEvents(function () use ($bankData, $data, $user) {

                                foreach ($bankData as $bankId => $types) {
                                    $transaction = EkuTransaction::where('bank_id', $bankId)
                                        ->where('periode', $data['tahun'])
                                        ->first();

                                    if ($transaction) {
                                        $totalSetoran = $types['setoran']['subtotal'] ?? 0;
                                        $totalPenarikan = $types['penarikan']['subtotal'] ?? 0;

                                        // CREATE RIWAYAT BARU (Counter naik: 1x, 2x, 3x)
                                        $realisasi = EkuTransactionRealisasi::create([
                                            'eku_transaction_id' => $transaction->id,
                                            'input_at'           => now(),
                                            'input_by'           => $user?->id,
                                            'file_setoran'       => null, // Ubah ke null agar tidak dideteksi parser lama
                                            'file_penarikan'     => null,
                                            'total_setoran'      => $totalSetoran,
                                            'total_penarikan'    => $totalPenarikan,
                                        ]);

                                        if ($totalSetoran > 0) {
                                            EkuTransactionRealisasiDetail::create([
                                                'eku_transaction_realisasi_id' => $realisasi->id,
                                                'bulan'      => $data['bulan'],
                                                'jenis_file' => 'Setoran',
                                                'total_upb'  => $types['setoran']['upb'],
                                                'total_upk'  => $types['setoran']['upk'],
                                                'subtotal'   => $totalSetoran,
                                            ]);
                                        }

                                        if ($totalPenarikan > 0) {
                                            EkuTransactionRealisasiDetail::create([
                                                'eku_transaction_realisasi_id' => $realisasi->id,
                                                'bulan'      => $data['bulan'],
                                                'jenis_file' => 'Penarikan',
                                                'total_upb'  => $types['penarikan']['upb'],
                                                'total_upk'  => $types['penarikan']['upk'],
                                                'subtotal'   => $totalPenarikan,
                                            ]);
                                        }
                                    }
                                }

                            });
                        });

                        // Hapus file temporary agar server tidak penuh
                        Storage::disk('local')->delete($data['file']);

                        Notification::make()
                            ->title('Berhasil!')
                            ->body('Data Realisasi Harian berhasil diakumulasikan dan riwayat bertambah.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal Mengunggah')
                            ->body('Terdapat kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
