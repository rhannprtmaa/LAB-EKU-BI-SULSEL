<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Exports\RealisasiTemplateExport;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Models\EkuTransactionRealisasiDetail;
use App\Support\CurrentUser;
use App\Support\UploadedFileNaming;
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
        // Input Realisasi Massal murni tugas Bank Indonesia (BI yang menginput
        // realisasi setoran/penarikan atas nama bank). User perbankan hanya
        // melihat hasilnya di tabel, jadi kedua aksi ini disembunyikan untuk
        // role user_perbankan.
        $bukanUserPerbankan = fn () => ! CurrentUser::get()?->isUserPerbankan();

        return [
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible($bukanUserPerbankan)
                ->action(fn () => Excel::download(new RealisasiTemplateExport(), 'template-realisasi-harian.xlsx')),

            Actions\Action::make('upload_realisasi')
                ->label('Upload Realisasi Massal')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible($bukanUserPerbankan)
                ->modalHeading('Upload Data Realisasi')
                ->modalDescription('Pilih periode dan unggah file excel yang telah diisi.')
                ->form([
                    Select::make('bulan')
                        ->label('Bulan Realisasi')
                        ->options([
                            'Januari'   => 'Januari',
                            'Februari'  => 'Februari',
                            'Maret'     => 'Maret',
                            'April'     => 'April',
                            'Mei'       => 'Mei',
                            'Juni'      => 'Juni',
                            'Juli'      => 'Juli',
                            'Agustus'   => 'Agustus',
                            'September' => 'September',
                            'Oktober'   => 'Oktober',
                            'November'  => 'November',
                            'Desember'  => 'Desember',
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
                        ->disk('public')
                        ->directory('realisasi-eku/massal')
                        ->getUploadedFileNameForStorageUsing(UploadedFileNaming::bersih())
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if (CurrentUser::get()?->isUserPerbankan()) {
                        Notification::make()
                            ->title('Tidak Diizinkan')
                            ->body('Input realisasi massal hanya dapat dilakukan oleh Bank Indonesia.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $filePath = Storage::disk('public')->path($data['file']);

                    try {
                        $importClass = new class implements \Maatwebsite\Excel\Concerns\ToArray {
                            public function array(array $array) { return $array; }
                        };

                        $excelData = Excel::toArray($importClass, $filePath);
                        $sheetSetoran = $excelData[0] ?? [];
                        $sheetPenarikan = $excelData[1] ?? [];
                        $bankData = [];

                        $clean = function($val) {
                            // Hapus tulisan Rp, spasi, atau huruf lainnya jika ada
                            $cleaned = preg_replace('/[^0-9.]/', '', (string) $val);
                            return is_numeric($cleaned) ? (float) $cleaned : 0;
                        };

                        // KONTROL PENGALI (Otomatis x 1 Juta)
                        $multiplier = 1000000;

                        // 1. PROSES SHEET SETORAN
                        foreach ($sheetSetoran as $index => $row) {
                            if ($index < 3) continue;
                            $bankId = $row[0] ?? null;
                            if (!$bankId) continue;

                            $upb = 0; $upk = 0;
                            $colIndex = 2;
                            for ($d = 1; $d <= 31; $d++) {
                                $upb += $clean($row[$colIndex] ?? 0) * $multiplier;
                                $upk += $clean($row[$colIndex+1] ?? 0) * $multiplier;
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
                                $upb += $clean($row[$colIndex] ?? 0) * $multiplier;
                                $upk += $clean($row[$colIndex+1] ?? 0) * $multiplier;
                                $colIndex += 2;
                            }

                            if ($upb + $upk > 0) {
                                $bankData[$bankId]['penarikan'] = ['upb' => $upb, 'upk' => $upk, 'subtotal' => $upb + $upk];
                            }
                        }

                        // 3. SIMPAN KE DATABASE
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

                                        // CREATE RIWAYAT BARU (Counter akan terus bertambah 1, 2, 3...)
                                        $realisasi = EkuTransactionRealisasi::create([
                                            'eku_transaction_id' => $transaction->id,
                                            'input_at'           => now(),
                                            'input_by'           => $user?->id,
                                            'file_setoran'       => $data['file'], // Disimpan sebagai File Realisasi
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
