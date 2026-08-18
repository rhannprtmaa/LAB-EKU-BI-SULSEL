<?php

namespace App\Filament\Resources\RealisasiEkus\Pages;

use App\Filament\Resources\RealisasiEkus\RealisasiEkuResource;
use App\Exports\RealisasiTemplateExport;
use App\Imports\RealisasiMassalImport;
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
                ->label('Upload FIle Realisasi')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Upload Data Realisasi')
                ->modalDescription('Pilih periode dan unggah file excel yang telah diisi.')
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
                        Excel::import(new RealisasiMassalImport($data['bulan'], $data['tahun']), $filePath);
                        Storage::disk('local')->delete($data['file']);

                        Notification::make()
                            ->title('Berhasil!')
                            ->body('Data Realisasi Massal berhasil didistribusikan ke masing-masing bank.')
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
