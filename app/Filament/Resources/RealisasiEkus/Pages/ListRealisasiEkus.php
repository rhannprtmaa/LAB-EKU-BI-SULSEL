<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Exports\RealisasiTemplateExport;
use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
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
                        ->disk('public') // UBAH KE PUBLIC AGAR BISA DI-DOWNLOAD
                        ->directory('realisasi-eku/massal') // UBAH FOLDER
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Gunakan disk public
                    $filePath = Storage::disk('public')->path($data['file']);

                    try {
                        $importClass = new class implements \Maatwebsite\Excel\Concerns\ToArray {
                            public function array(array $array) { return $array; }
                        };

                        $excelData = Excel::toArray($importClass, $filePath);
                        $sheetSetoran = $excelData[0] ?? [];
                        $sheetPenarikan = $excelData[1] ?? [];
                        $bankData = [];

                        // ... (KODE LOOPING SHEET SETORAN & PENARIKAN TETAP SAMA SEPERTI SEBELUMNYA) ...
                        // (Lewati baris 1-3, kalikan dengan 1000000, dst)

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

                                        // CREATE RIWAYAT BARU
                                        $realisasi = EkuTransactionRealisasi::create([
                                            'eku_transaction_id' => $transaction->id,
                                            'input_at'           => now(),
                                            'input_by'           => $user?->id,
                                            'file_setoran'       => $data['file'], // PINJAM KOLOM INI UNTUK MENYIMPAN FILE MASSAL
                                            'file_penarikan'     => null, // KOSONGKAN
                                            'total_setoran'      => $totalSetoran,
                                            'total_penarikan'    => $totalPenarikan,
                                        ]);
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
