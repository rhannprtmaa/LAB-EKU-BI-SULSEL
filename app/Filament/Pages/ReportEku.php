<?php

namespace App\Filament\Pages;

use App\Exports\ReportEkuExport;
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
use Illuminate\Support\HtmlString;
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
                    ->visible(fn () => $this->isPihakBi()),

                // Kolom Periode: Dibuat teks biasa (tanpa badge) agar rapi
                TextColumn::make('periode')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('pengajuan_setoran')
                    ->label('Pengajuan Setoran')
                    ->alignEnd()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['setoranTotal'] ?? 0)
                    ->formatStateUsing(fn ($state) => $this->rupiah((float) $state)),

                TextColumn::make('pengajuan_penarikan')
                    ->label('Pengajuan Penarikan')
                    ->alignEnd()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['penarikanTotal'] ?? 0)
                    ->formatStateUsing(fn ($state) => $this->rupiah((float) $state)),

                TextColumn::make('realisasi_setoran')
                    ->label('Realisasi Setoran (YTD)')
                    ->alignEnd()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['realisasiSetoran'] ?? 0)
                    ->formatStateUsing(fn ($state) => $this->rupiah((float) $state)),

                TextColumn::make('realisasi_penarikan')
                    ->label('Realisasi Penarikan (YTD)')
                    ->alignEnd()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['realisasiPenarikan'] ?? 0)
                    ->formatStateUsing(fn ($state) => $this->rupiah((float) $state)),

                // Deviasi Setoran dengan Badge HTML agar sama dengan halaman Realisasi
                TextColumn::make('deviasi_setoran')
                    ->label('Deviasi Setoran')
                    ->alignEnd()
                    ->html()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['deviasiSetoran'] ?? 0)
                    ->formatStateUsing(function ($state) {
                        $val = (float) $state;
                        $nominal = 'Rp ' . number_format(abs($val), 0, ',', '.');

                        if ($val < 0) {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Over</span>';
                            $nominal = '(Mines) ' . $nominal;
                        } elseif ($val > 0) {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400">Sisa</span>';
                        } else {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">Sesuai</span>';
                        }

                        return new HtmlString("<div class='flex items-center justify-end whitespace-nowrap'>{$nominal} {$badge}</div>");
                    }),

                // Deviasi Penarikan dengan Badge HTML agar sama dengan halaman Realisasi
                TextColumn::make('deviasi_penarikan')
                    ->label('Deviasi Penarikan')
                    ->alignEnd()
                    ->html()
                    ->getStateUsing(fn (EkuTransaction $record) => $this->laporanUntuk($record)['deviasiPenarikan'] ?? 0)
                    ->formatStateUsing(function ($state) {
                        $val = (float) $state;
                        $nominal = 'Rp ' . number_format(abs($val), 0, ',', '.');

                        if ($val < 0) {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Over</span>';
                            $nominal = '(Mines) ' . $nominal;
                        } elseif ($val > 0) {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400">Sisa</span>';
                        } else {
                            $badge = '<span class="ml-2 px-2 py-0.5 rounded-md text-[11px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">Sesuai</span>';
                        }

                        return new HtmlString("<div class='flex items-center justify-end whitespace-nowrap'>{$nominal} {$badge}</div>");
                    }),
            ])
            ->filters([
                SelectFilter::make('bank_id')
                    ->label('Filter Bank')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload()
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

    protected function getTableQueryBase(): Builder
    {
        $user = CurrentUser::get();

        $query = EkuTransaction::query()
            ->with(['bank', 'details', 'realisasiHistory.details'])
            ->where('status', EkuTransaction::STATUS_DISETUJUI);

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

    protected function laporanUntuk(EkuTransaction $record): array
    {
        return $this->cacheLaporan[$record->id] ??= EkuReportCalculator::hitung($record);
    }

    protected function rupiah(float $value): string
    {
        $prefix = $value < 0 ? '-Rp ' : 'Rp ';

        return $prefix . number_format(abs($value), 0, ',', '.');
    }

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
                $deviasi > 0 => 'deviasi-kurang',
                $deviasi < 0 => 'deviasi-over',
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
