<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EkuExcelImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Models\EkuDeadline;
use App\Services\NotifikasiService;

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
            // Notifikasi perubahan status (Disetujui / Perlu Revisi / Ditolak)
            // dipantau di sini, bukan di masing-masing tombol aksi, supaya
            // konsisten dari mana pun status itu diubah.
            if (! $transaction->wasRecentlyCreated && $transaction->wasChanged('status')) {
                match ($transaction->status) {
                    self::STATUS_DISETUJUI => NotifikasiService::pengajuanDisetujui($transaction, $transaction->catatan),
                    self::STATUS_REVISI => NotifikasiService::pengajuanPerluRevisi($transaction, (string) $transaction->catatan),
                    self::STATUS_DITOLAK => NotifikasiService::pengajuanDitolak($transaction, (string) $transaction->catatan),
                    default => null,
                };
            }

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

    public function terapkanBatasanBank(): bool
    {
        $bank = $this->bank;

        if (! $bank) {
            return false;
        }

        $disesuaikan = false;
        $ringkasan = [];

        foreach (['Setoran' => 'batasan_setoran', 'Penarikan' => 'batasan_penarikan'] as $jenisFile => $kolomBatasan) {
            $batasan = (float) ($bank->{$kolomBatasan} ?? 0);

            if ($batasan <= 0) {
                continue;
            }

            $totalSaatIni = (float) $this->details()
                ->where('jenis_file', $jenisFile)
                ->sum('subtotal');

            if ($totalSaatIni <= $batasan) {
                continue;
            }

            $faktor = $batasan / $totalSaatIni;

            $kolomPecahan = [
                'kertas_100k', 'kertas_50k', 'kertas_20k', 'kertas_10k', 'kertas_5k',
                'kertas_2k', 'kertas_1k', 'logam_1k', 'logam_500', 'logam_200', 'logam_100',
            ];

            $this->details()->where('jenis_file', $jenisFile)->get()->each(function ($detail) use ($kolomPecahan, $faktor) {
                foreach ($kolomPecahan as $kolom) {
                    $detail->{$kolom} = round($detail->{$kolom} * $faktor, 2);
                }

                $detail->recalculateSubtotal();
            });

            $ringkasan[] = "{$jenisFile}: " . number_format($totalSaatIni, 0, ',', '.')
                . ' -> ' . number_format($batasan, 0, ',', '.');

            $disesuaikan = true;
        }

        if ($disesuaikan) {
            static::recalculateTotals($this->id);
            $this->refresh();

            foreach (['Setoran', 'Penarikan'] as $jenisFile) {
                $this->syncExcelValuesToFile($jenisFile);
            }

            Notification::make()
                ->title('Pengajuan EKU otomatis disesuaikan dengan Batasan EKU')
                ->body(($bank->name ?? 'Bank') . ' -- ' . implode(' | ', $ringkasan))
                ->warning()
                ->persistent()
                ->send();
        }

        return $disesuaikan;
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

    /**
     * Tulis ulang nilai-nilai di file Excel fisik (file_setoran / file_penarikan)
     * supaya sesuai dengan data terbaru di database (misalnya setelah User BI
     * mengedit angka lewat "Rincian Proyeksi EKU Bulanan").
     */
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

    // Semua riwayat input realisasi (bisa lebih dari sekali), terbaru dulu.
    public function realisasiHistory(): HasMany
    {
        return $this->hasMany(EkuTransactionRealisasi::class)->latest('input_at');
    }
    public function realisasiTerbaru(): HasOne
    {
        return $this->hasOne(EkuTransactionRealisasi::class)->latestOfMany('input_at');
    }

    /**
     * Bandingkan Forecast (proyeksi yang disetujui) dengan realisasi PALING
     * BARU yang diinput BI, per bulan & jenis (Setoran/Penarikan).
     *
     * Deviasi = Forecast - Realisasi
     *   - Deviasi POSITIF -> realisasi LEBIH KECIL dari proyeksi (under-realisasi)
     *   - Deviasi NEGATIF -> realisasi LEBIH BESAR dari proyeksi (over-realisasi)
     */
   public function hitungDeviasi(): array
    {
        $forecasts = $this->details;

        // SAYA TAMBAHKAN SUM(total_upb) DAN SUM(total_upk) DI SINI
        $realisasiDetails = \App\Models\EkuTransactionRealisasiDetail::whereHas('realisasi', function ($q) {
            $q->where('eku_transaction_id', $this->id);
        })
        ->selectRaw('bulan, jenis_file, SUM(subtotal) as total_realisasi, SUM(total_upb) as sum_upb, SUM(total_upk) as sum_upk')
        ->groupBy('bulan', 'jenis_file')
        ->get();

        $hasil = [];

        foreach ($forecasts as $f) {
            $key = $f->bulan . '_' . $f->jenis_file;
            $hasil[$key] = [
                'bulan' => $f->bulan,
                'jenis' => $f->jenis_file,
                'forecast' => $f->subtotal,
                'realisasi' => 0,
                'total_upb' => 0, // Tambahan Baru
                'total_upk' => 0, // Tambahan Baru
                'deviasi' => $f->subtotal,
                'persen_deviasi' => -100
            ];
        }

        foreach ($realisasiDetails as $r) {
            $key = $r->bulan . '_' . $r->jenis_file;

            if (!isset($hasil[$key])) {
                $hasil[$key] = [
                    'bulan' => $r->bulan, 'jenis' => $r->jenis_file,
                    'forecast' => 0, 'realisasi' => 0, 'deviasi' => 0, 'persen_deviasi' => 0,
                    'total_upb' => 0, 'total_upk' => 0 // Tambahan Baru
                ];
            }

            // AKUMULASI NILAI REALISASI, UPB, & UPK
            $hasil[$key]['realisasi'] += $r->total_realisasi;
            $hasil[$key]['total_upb'] += $r->sum_upb; // Tambahan Baru
            $hasil[$key]['total_upk'] += $r->sum_upk; // Tambahan Baru

            $forecast = $hasil[$key]['forecast'];
            $realisasi = $hasil[$key]['realisasi'];

            // Hitung Deviasi (Sisa/Mines)
            $hasil[$key]['deviasi'] = $forecast - $realisasi;

            if ($forecast > 0) {
                $hasil[$key]['persen_deviasi'] = round((($realisasi - $forecast) / $forecast) * 100, 1);
            } else {
                $hasil[$key]['persen_deviasi'] = $realisasi > 0 ? 100 : 0;
            }
        }

        return array_values($hasil);
    }

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
}
