<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EkuExcelImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Models\EkuDeadline;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EkuTransaction extends Model
{
    protected $guarded = [];

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_REVISI = 'Perlu Revisi';
    public const STATUS_DITOLAK = 'Ditolak';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_MENUNGGU => 'Menunggu Review',
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_REVISI => 'Perlu Revisi',
            self::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_edited_by_bi' => 'boolean',
        ];
    }

  protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->file_setoran_original ??= $transaction->file_setoran;
            $transaction->file_penarikan_original ??= $transaction->file_penarikan;
            $transaction->file_lampiran_original ??= $transaction->file_lampiran;
            $transaction->status ??= self::STATUS_MENUNGGU;
        });

        static::saving(function ($transaction) {
            // "Batasan Periode" ditentukan oleh Admin BI lewat halaman
            // "Management EKU" (bisa per-periode, atau satu batas waktu
            // global untuk semua periode). Setiap kali record disimpan,
            // isi otomatis dari pengaturan yang berlaku.
            $deadline = EkuDeadline::untukPeriode($transaction->periode) ?? EkuDeadline::current();

            $transaction->batasan_periode = $deadline?->batas_waktu
                ? 'Batas Pengajuan s.d ' . $deadline->batas_waktu->locale('id')->translatedFormat('d F Y')
                : null;
        });

        static::updating(function ($transaction) {
            $fileBerubah = $transaction->isDirty(['file_setoran', 'file_penarikan'])
                && (
                    $transaction->file_setoran !== $transaction->file_setoran_original
                    || $transaction->file_penarikan !== $transaction->file_penarikan_original
                );

            if ($fileBerubah) {
                $transaction->is_edited_by_bi = true;
            }
        });

        static::saved(function ($transaction) {
            if ($transaction->wasRecentlyCreated) {
                $transaction->reprocessExcelFiles();

                return;
            }

            if (! $transaction->wasChanged(['file_setoran', 'file_penarikan'])) {
                return;
            }

            $transaction->reprocessExcelFiles();
        });
    }

    public function reprocessExcelFiles(): array
    {
        $transaction = $this;

        $transaction->details()->delete();

        $processExcel = function($filePath, $jenisFile) use ($transaction) {
            if (!$filePath) return 0;

            if (! Storage::disk('public')->exists($filePath)) return 0;

            $fullPath = Storage::disk('public')->path($filePath);

            $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);
            if (empty($arrayData) || empty($arrayData[0])) return 0;

            $sheet = $arrayData[0];
            $multiplier = 1000000;

            $clean = fn($val) => is_numeric($val)
                ? (float) $val
                : (float) str_replace(['.', ',', ' '], '', (string) $val);

            $kolomBulan = [
                3 => 'Januari', 4 => 'Februari', 5 => 'Maret', 6 => 'April',
                7 => 'Mei', 8 => 'Juni', 9 => 'Juli', 10 => 'Agustus',
                11 => 'September', 12 => 'Oktober', 13 => 'November', 14 => 'Desember',
            ];

            $petaKertas = [
                100000 => 'kertas_100k', 50000 => 'kertas_50k', 20000 => 'kertas_20k',
                10000 => 'kertas_10k', 5000 => 'kertas_5k', 2000 => 'kertas_2k', 1000 => 'kertas_1k',
            ];
            $petaLogam = [
                1000 => 'logam_1k', 500 => 'logam_500', 200 => 'logam_200', 100 => 'logam_100',
            ];

            $akumulasi = [];
            foreach ($kolomBulan as $namaBulan) {
                $akumulasi[$namaBulan] = [
                    'kertas_100k' => 0, 'kertas_50k' => 0, 'kertas_20k' => 0, 'kertas_10k' => 0,
                    'kertas_5k' => 0, 'kertas_2k' => 0, 'kertas_1k' => 0,
                    'logam_1k' => 0, 'logam_500' => 0, 'logam_200' => 0, 'logam_100' => 0,
                ];
            }

            $section = null;

            foreach ($sheet as $row) {
                $jenisUang = strtoupper(trim((string) ($row[1] ?? '')));
                $nominalRaw = $row[2] ?? null;

                if (str_contains($jenisUang, 'UANG KERTAS')) {
                    $section = 'kertas';
                } elseif (str_contains($jenisUang, 'UANG LOGAM')) {
                    $section = 'logam';
                }

                if (str_contains($jenisUang, 'TOTAL')) {
                    continue;
                }
                if (is_string($nominalRaw) && str_contains(strtoupper($nominalRaw), 'TOTAL')) {
                    continue;
                }

                if (! is_numeric($nominalRaw) || ! $section) {
                    continue;
                }

                $nominal = (int) $nominalRaw;
                $namaKolom = $section === 'kertas'
                    ? ($petaKertas[$nominal] ?? null)
                    : ($petaLogam[$nominal] ?? null);

                if (! $namaKolom) {
                    continue;
                }

                foreach ($kolomBulan as $colIdx => $namaBulan) {
                    $akumulasi[$namaBulan][$namaKolom] += $clean($row[$colIdx] ?? 0) * $multiplier;
                }
            }

            $baris = 0;
            foreach ($akumulasi as $namaBulan => $pecahan) {
                $transaction->details()->create(array_merge(
                    ['bulan' => $namaBulan, 'jenis_file' => $jenisFile, 'subtotal' => array_sum($pecahan)],
                    $pecahan
                ));

                $baris++;
            }

            return $baris;
        };

        $jumlahSetoran = $processExcel($transaction->file_setoran, 'Setoran');
        $jumlahPenarikan = $processExcel($transaction->file_penarikan, 'Penarikan');

        if ($jumlahSetoran === 0 && $jumlahPenarikan === 0 && ($transaction->file_setoran || $transaction->file_penarikan)) {
            Notification::make()
                ->title('Peringatan: Excel tidak berhasil dibaca')
                ->body('Sistem tidak menemukan struktur "UANG KERTAS" / "UANG LOGAM" di file yang diupload. Pastikan file mengikuti format Template Kerja EKU.')
                ->warning()
                ->persistent()
                ->send();
        }

        static::recalculateTotals($transaction->id);

        return ['setoran' => $jumlahSetoran, 'penarikan' => $jumlahPenarikan];
    }

    public static function recalculateTotals(int $transactionId): void
    {
        $totals = EkuTransactionDetail::query()
            ->where('eku_transaction_id', $transactionId)
            ->selectRaw('
                SUM(kertas_100k) as total_100k, SUM(kertas_50k) as total_50k,
                SUM(kertas_20k) as total_20k, SUM(kertas_10k) as total_10k,
                SUM(kertas_5k) as total_5k, SUM(kertas_2k) as total_2k,
                SUM(kertas_1k) as total_1k, SUM(logam_1k) as total_l1k,
                SUM(logam_500) as total_l500, SUM(logam_200) as total_l200,
                SUM(logam_100) as total_l100, SUM(subtotal) as grand_total
            ')->first();

        $totalPerJenis = EkuTransactionDetail::query()
            ->where('eku_transaction_id', $transactionId)
            ->selectRaw('jenis_file, SUM(subtotal) as total')
            ->groupBy('jenis_file')
            ->pluck('total', 'jenis_file');

        if ($totals) {
            DB::table('eku_transactions')->where('id', $transactionId)->update([
                'kertas_100k' => $totals->total_100k ?? 0, 'kertas_50k' => $totals->total_50k ?? 0,
                'kertas_20k' => $totals->total_20k ?? 0, 'kertas_10k' => $totals->total_10k ?? 0,
                'kertas_5k' => $totals->total_5k ?? 0, 'kertas_2k' => $totals->total_2k ?? 0,
                'kertas_1k' => $totals->total_1k ?? 0, 'logam_1k' => $totals->total_l1k ?? 0,
                'logam_500' => $totals->total_l500 ?? 0, 'logam_200' => $totals->total_l200 ?? 0,
                'logam_100' => $totals->total_l100 ?? 0, 'total_nominal' => $totals->grand_total ?? 0,
                'total_setoran' => $totalPerJenis['Setoran'] ?? 0,
                'total_penarikan' => $totalPerJenis['Penarikan'] ?? 0,
            ]);
        }
    }

    public function syncExcelValuesToFile(string $jenisFile): void
    {
        $fieldFile = $jenisFile === 'Setoran' ? 'file_setoran' : 'file_penarikan';
        $fieldOriginal = $jenisFile === 'Setoran' ? 'file_setoran_original' : 'file_penarikan_original';

        $filePath = $this->{$fieldFile};

        if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return;
        }
        if ($this->{$fieldOriginal} === $filePath) {
            $pathInfo = pathinfo($filePath);
            $newPath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '')
                . $pathInfo['filename'] . '_diterima_bi.' . ($pathInfo['extension'] ?? 'xlsx');

            Storage::disk('public')->copy($filePath, $newPath);

            $this->{$fieldFile} = $newPath;
            $this->saveQuietly();

            $filePath = $newPath;
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $multiplier = 1000000;

        $kolomBulan = [
            3 => 'Januari', 4 => 'Februari', 5 => 'Maret', 6 => 'April',
            7 => 'Mei', 8 => 'Juni', 9 => 'Juli', 10 => 'Agustus',
            11 => 'September', 12 => 'Oktober', 13 => 'November', 14 => 'Desember',
        ];

        $petaKertas = [
            100000 => 'kertas_100k', 50000 => 'kertas_50k', 20000 => 'kertas_20k',
            10000 => 'kertas_10k', 5000 => 'kertas_5k', 2000 => 'kertas_2k', 1000 => 'kertas_1k',
        ];
        $petaLogam = [
            1000 => 'logam_1k', 500 => 'logam_500', 200 => 'logam_200', 100 => 'logam_100',
        ];

        $details = $this->details()->where('jenis_file', $jenisFile)->get()->keyBy('bulan');

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $coord = fn (int $col, int $row) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;

            $section = null;

            for ($row = 1; $row <= $highestRow; $row++) {
                $jenisUang = strtoupper(trim((string) $sheet->getCell($coord(2, $row))->getValue()));
                $nominalRaw = $sheet->getCell($coord(3, $row))->getValue();

                if (str_contains($jenisUang, 'UANG KERTAS')) {
                    $section = 'kertas';
                } elseif (str_contains($jenisUang, 'UANG LOGAM')) {
                    $section = 'logam';
                }

                if (str_contains($jenisUang, 'TOTAL')) {
                    continue;
                }
                if (is_string($nominalRaw) && str_contains(strtoupper($nominalRaw), 'TOTAL')) {
                    continue;
                }

                if (! is_numeric($nominalRaw) || ! $section) {
                    continue;
                }

                $nominal = (int) $nominalRaw;
                $namaKolom = $section === 'kertas'
                    ? ($petaKertas[$nominal] ?? null)
                    : ($petaLogam[$nominal] ?? null);

                if (! $namaKolom) {
                    continue;
                }

                foreach ($kolomBulan as $colIdx => $namaBulan) {
                    $detail = $details->get($namaBulan);
                    $nilaiBaru = $detail ? ((float) $detail->{$namaKolom}) / $multiplier : 0;

                    $sheet->setCellValue($coord($colIdx + 1, $row), $nilaiBaru);
                }
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($fullPath);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menyinkronkan file Excel')
                ->body('Angka di sistem sudah tersimpan, tapi file Excel yang diunduh gagal diperbarui: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function bank(): BelongsTo { return $this->belongsTo(Bank::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function details(): HasMany { return $this->hasMany(EkuTransactionDetail::class); }

    public function scopeForBank(Builder $query, ?int $bankId): Builder
    {
        return $bankId ? $query->where('bank_id', $bankId) : $query;
    }

    public function isEditableByBankOwner(): bool
    {
        if (! in_array($this->status, [self::STATUS_MENUNGGU, self::STATUS_REVISI], true)) {
            return false;
        }
        return ! EkuDeadline::isTertutup($this->periode);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_DISETUJUI;
    }

    // Perhitungan Deviasi Otomatis
    public function hitungDeviasi(): void
    {
        $this->deviasi_setoran = $this->total_realisasi_setoran - $this->total_setoran;
        $this->deviasi_penarikan = $this->total_realisasi_penarikan - $this->total_penarikan;
        $this->save();
    }

    public function getPersentaseDeviasiSetoranAttribute(): float
    {
        if ($this->total_setoran == 0) return 0;
        return round(($this->deviasi_setoran / $this->total_setoran) * 100, 2);
    }

    public function getPersentaseDeviasiPenarikanAttribute(): float
    {
        if ($this->total_penarikan == 0) return 0;
        return round(($this->deviasi_penarikan / $this->total_penarikan) * 100, 2);
    }

   public function processRealisasiExcel(string $setoranPath, string $penarikanPath): void
    {
        $fullSetoranPath = storage_path('app/public/' . $setoranPath);
        $fullPenarikanPath = storage_path('app/public/' . $penarikanPath);

        $totalRealisasiSetoran = 0;
        $totalRealisasiPenarikan = 0;

        $parseMoney = function ($value) {
            if (is_null($value) || $value === '') return 0;
            if (is_numeric($value)) return (float) $value;
            $cleaned = preg_replace('/[^\d]/', '', (string) $value);
            return (float) $cleaned;
        };

        // 1. Parsing Excel Realisasi Setoran
        if (file_exists($fullSetoranPath)) {
            $spreadsheet = IOFactory::load($fullSetoranPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex < 2) continue; // Skip header

                $bulan = trim($row['A'] ?? '');
                $pecahan = trim($row['B'] ?? '');
                $nominal = $parseMoney($row['C'] ?? 0);

                if (empty($bulan) || empty($pecahan)) continue;

                $detail = EkuTransactionDetail::where('eku_transaction_id', $this->id)
                    ->where('bulan', $bulan)
                    ->where('pecahan', $pecahan)
                    ->first();

                if ($detail) {
                    $detail->realisasi_setoran = $nominal;
                    $detail->deviasi_setoran = $nominal - $detail->setoran;
                    $detail->save();
                }

                $totalRealisasiSetoran += $nominal;
            }
        }

        // 2. Parsing Excel Realisasi Penarikan
        if (file_exists($fullPenarikanPath)) {
            $spreadsheet = IOFactory::load($fullPenarikanPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex < 2) continue; // Skip header

                $bulan = trim($row['A'] ?? '');
                $pecahan = trim($row['B'] ?? '');
                $nominal = $parseMoney($row['C'] ?? 0);

                if (empty($bulan) || empty($pecahan)) continue;

                $detail = EkuTransactionDetail::where('eku_transaction_id', $this->id)
                    ->where('bulan', $bulan)
                    ->where('pecahan', $pecahan)
                    ->first();

                if ($detail) {
                    $detail->realisasi_penarikan = $nominal;
                    $detail->deviasi_penarikan = $nominal - $detail->penarikan;
                    $detail->save();
                }

                $totalRealisasiPenarikan += $nominal;
            }
        }

        // 3. Simpan Total Realisasi & Deviasi ke Tabel Utama Transaksi
        $this->total_realisasi_setoran = $totalRealisasiSetoran;
        $this->total_realisasi_penarikan = $totalRealisasiPenarikan;
        $this->deviasi_setoran = $totalRealisasiSetoran - $this->total_setoran;
        $this->deviasi_penarikan = $totalRealisasiPenarikan - $this->total_penarikan;
        $this->realisasi_uploaded_at = now();
        $this->save();
    }
}
