<?php

namespace App\Filament\Pages;

use App\Exports\ReportEkuExport;
use App\Models\Bank;
use App\Models\EkuTransaction;
use App\Services\EkuReportCalculator;
use App\Support\CurrentUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportEku extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report-eku';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reporting EKU';

    protected static ?string $title = 'Reporting EKU';

    protected static ?int $navigationSort = 5;

    /**
     * Cache hasil perhitungan Pengajuan/Realisasi/Deviasi per baris (per
     * EkuTransaction) supaya tidak dihitung ulang untuk tiap kolom --
     * 6 kolom angka di tabel ini semuanya berasal dari satu perhitungan
     * yang sama, cukup dihitung SEKALI per baris.
     *
     * @var array<int, array<string, float>>
     */
    protected array $cacheLaporan = [];

    public static function canAccess(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi() || $user?->isUserPerbankan());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQueryBase())
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('bank.name')
                    ->label('Nama Bank')
                    ->searchable()
                    ->sortable()
                    // Bank sendiri sudah pasti diketahui bank itu sendiri --
                    // kolom ini hanya relevan untuk sisi BI yang memantau
                    // banyak bank sekaligus.
                    ->visible(fn () => $this->isPihakBi()),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('pengajuan_setoran')
                    ->label('Pengajuan Setoran')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['setoranTotal'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state)),

                TextColumn::make('pengajuan_penarikan')
                    ->label('Pengajuan Penarikan')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['penarikanTotal'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state)),

                TextColumn::make('realisasi_setoran')
                    ->label('Realisasi Setoran (YTD)')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['realisasiSetoran'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state)),

                TextColumn::make('realisasi_penarikan')
                    ->label('Realisasi Penarikan (YTD)')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['realisasiPenarikan'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state)),

                TextColumn::make('deviasi_setoran')
                    ->label('Deviasi Setoran')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['deviasiSetoran'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state))
                    ->color(fn (float $state) => match (true) {
                        $state > 0 => 'warning', // Realisasi < Pengajuan (kurang)
                        $state < 0 => 'danger',  // Realisasi > Pengajuan (over)
                        default => 'success',
                    })
                    ->weight('semibold'),

                TextColumn::make('deviasi_penarikan')
                    ->label('Deviasi Penarikan')
                    ->alignEnd()
                    ->state(fn (EkuTransaction $record) => $this->laporanUntuk($record)['deviasiPenarikan'])
                    ->formatStateUsing(fn (float $state) => $this->rupiah($state))
                    ->color(fn (float $state) => match (true) {
                        $state > 0 => 'warning',
                        $state < 0 => 'danger',
                        default => 'success',
                    })
                    ->weight('semibold'),
            ])
            ->filters([
                SelectFilter::make('bank_id')
                    ->label('Filter Bank')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload()
                    // Bank hanya bisa melihat datanya sendiri (dipaksa lewat
                    // getTableQueryBase()), jadi filter ini cuma berguna
                    // untuk BI yang memantau banyak bank.
                    ->visible(fn () => $this->isPihakBi()),

                SelectFilter::make('periode_filter')
                    ->label('Filter Periode')
                    ->options([
                        'tahun_ini' => 'Tahun Ini',
                        '5_tahun' => '5 Tahun Terakhir',
                        '10_tahun' => '10 Tahun Terakhir',
                        'all' => 'Semua Periode',
                    ])
                    ->default('tahun_ini')
                    ->query(function (Builder $query, array $data): Builder {
                        $tahunSekarang = (int) now()->year;

                        return match ($data['value'] ?? null) {
                            'tahun_ini' => $query->where('periode', (string) $tahunSekarang),
                            '5_tahun' => $query->where('periode', '>=', (string) ($tahunSekarang - 4)),
                            '10_tahun' => $query->where('periode', '>=', (string) ($tahunSekarang - 9)),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Action::make('unduh_pdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn (EkuTransaction $record): StreamedResponse => $this->unduhPdf(collect([$record]))),

                Action::make('unduh_excel')
                    ->label('Unduh Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(fn (EkuTransaction $record) => $this->unduhExcel(collect([$record]))),
            ])
            ->headerActions([
                Action::make('unduh_pdf_semua')
                    ->label('Unduh PDF Semua')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(fn (): StreamedResponse => $this->unduhPdf($this->getFilteredRowsForExport())),

                Action::make('unduh_excel_semua')
                    ->label('Unduh Excel Semua')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(fn () => $this->unduhExcel($this->getFilteredRowsForExport())),
            ])
            ->defaultSort('periode', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Query dasar tabel -- WAJIB melalui method ini, jangan query
     * EkuTransaction langsung di tempat lain pada halaman ini, supaya
     * pembatasan akses per-role tetap konsisten di satu tempat.
     */
    protected function getTableQueryBase(): Builder
    {
        $user = CurrentUser::get();

        $query = EkuTransaction::query()
            ->with(['bank', 'details', 'realisasiHistory.details'])
            // Laporan hanya relevan untuk pengajuan yang sudah final/disetujui,
            // konsisten dengan modul Realisasi & Deviasi EKU lainnya.
            ->where('status', EkuTransaction::STATUS_DISETUJUI);

        // WAJIB: User Perbankan hanya boleh melihat data bank-nya sendiri.
        if ($user?->isUserPerbankan()) {
            $query->where('bank_id', $user->bank_id);
        }

        return $query;
    }

    protected function isPihakBi(): bool
    {
        $user = CurrentUser::get();

        return (bool) ($user?->isAdminBi() || $user?->isUserBi());
    }

    /**
     * Ambil hasil perhitungan laporan untuk satu baris (delegasi penuh ke
     * EkuReportCalculator supaya angka di tabel, Excel, dan PDF selalu
     * konsisten satu sama lain -- lihat app/Services/EkuReportCalculator.php).
     */
    protected function laporanUntuk(EkuTransaction $record): array
    {
        return $this->cacheLaporan[$record->id] ??= EkuReportCalculator::hitung($record);
    }

    protected function rupiah(float $value): string
    {
        $prefix = $value < 0 ? '-Rp ' : 'Rp ';

        return $prefix . number_format(abs($value), 0, ',', '.');
    }

    /**
     * Ambil SEMUA baris yang cocok dengan filter tabel yang sedang aktif
     * (bukan cuma halaman yang sedang tampil) -- dipakai oleh tombol
     * "Unduh ... Semua" di header tabel.
     */
    protected function getFilteredRowsForExport(): Collection
    {
        return $this->getFilteredTableQuery()->get();
    }

    protected function unduhExcel(Collection $transactions): mixed
    {
        if ($transactions->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data untuk diekspor')
                ->warning()
                ->send();

            return null;
        }

        $namaFile = 'laporan-eku-' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ReportEkuExport($transactions), $namaFile);
    }

    protected function unduhPdf(Collection $transactions): ?StreamedResponse
    {
        if ($transactions->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data untuk diekspor')
                ->warning()
                ->send();

            return null;
        }

        $rows = EkuReportCalculator::hitungBanyak($transactions);

        $bankTunggal = $transactions->pluck('bank_id')->unique();
        $bankName = $bankTunggal->count() === 1
            ? $transactions->first()->bank?->name
            : null;

        $kelasDeviasi = function (float $deviasi): string {
            return match (true) {
                $deviasi > 0 => 'deviasi-kurang', // realisasi masih kurang dari pengajuan
                $deviasi < 0 => 'deviasi-over',   // realisasi melebihi pengajuan
                default => 'deviasi-sesuai',
            };
        };

        $pdf = Pdf::loadView('pdf.report-eku', [
            'rows' => $rows,
            'bankName' => $bankName,
            'rupiah' => fn (float $v) => $this->rupiah($v),
            'kelasDeviasi' => $kelasDeviasi,
        ])->setPaper('a4', 'landscape');

        $namaFile = 'laporan-eku-' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
