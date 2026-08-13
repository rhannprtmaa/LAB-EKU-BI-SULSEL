<?php

namespace App\Filament\Pages;

use App\Exports\RealisasiTemplateExport;
use App\Imports\RealisasiMassalImport;
use App\Support\CurrentUser;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class UploadRealisasiMassal extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.upload-realisasi-massal';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Upload Realisasi Massal';

    protected static ?string $title = 'Bulk Upload Realisasi Harian';

    // Requirement #1: hanya untuk role BI (Admin BI & User BI).
    public static function canAccess(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => (string) now()->month,
            'tahun' => (string) now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bulan')
                    ->label('Bulan')
                    ->required()
                    ->native(false)
                    ->options($this->opsiBulan()),

                Select::make('tahun')
                    ->label('Tahun')
                    ->required()
                    ->native(false)
                    ->options($this->opsiTahun()),

                FileUpload::make('file_excel')
                    ->label('File Excel Realisasi')
                    ->required()
                    ->disk('local')
                    ->directory('realisasi-massal-tmp')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->helperText('Gunakan file hasil unduhan "Download Template Excel" di atas, jangan diubah strukturnya.')
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /**
     * Requirement #2: header action untuk download template Excel
     * (RealisasiTemplateExport dari Step 1).
     */
    protected function opsiBulan(): array
    {
        return [
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
            '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
            '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];
    }

    protected function opsiTahun(): array
    {
        $tahunSekarang = (int) now()->year;

        // 5 tahun ke belakang s.d. 1 tahun ke depan.
        return collect(range($tahunSekarang - 5, $tahunSekarang + 1))
            ->reverse()
            ->mapWithKeys(fn (int $tahun) => [(string) $tahun => (string) $tahun])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new RealisasiTemplateExport(),
                    'template-realisasi-harian.xlsx'
                )),

            Action::make('prosesImport')
                ->label('Proses Import')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Data realisasi pada file ini akan menimpa data realisasi bulan yang sama (kalau sudah pernah diinput sebelumnya). Lanjutkan?')
                ->action(fn () => $this->prosesImport()),
        ];
    }

    /**
     * Requirement #4: baca Bulan, Tahun, dan file, lalu jalankan
     * RealisasiMassalImport dari Step 2.
     */
    public function prosesImport(): void
    {
        $state = $this->form->getState();

        $bulan = $state['bulan'] ?? null;
        $tahun = $state['tahun'] ?? null;
        $filePath = $state['file_excel'] ?? null;

        if (! $bulan || ! $tahun || ! $filePath) {
            Notification::make()
                ->title('Lengkapi Bulan, Tahun, dan File Excel terlebih dahulu')
                ->warning()
                ->send();

            return;
        }

        if (! Storage::disk('local')->exists($filePath)) {
            Notification::make()
                ->title('File tidak ditemukan, coba upload ulang')
                ->danger()
                ->send();

            return;
        }

        try {
            Excel::import(
                new RealisasiMassalImport($bulan, $tahun),
                $filePath,
                'local'
            );

            Notification::make()
                ->title('Import Realisasi Massal berhasil diproses')
                ->body('Bulan: ' . $this->opsiBulan()[$bulan] . ' ' . $tahun)
                ->success()
                ->send();

            // File upload ini cuma perantara sementara -- bukan arsip resmi
            // (arsip resminya ada di file_setoran/file_penarikan per bank),
            // jadi aman dibersihkan setelah berhasil diproses.
            Storage::disk('local')->delete($filePath);

            $this->form->fill([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'file_excel' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal memproses file')
                ->body('Pastikan file sesuai format Template Excel yang diunduh. Detail: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
