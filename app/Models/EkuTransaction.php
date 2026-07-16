<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EkuExcelImport;
use Illuminate\Support\Facades\DB;

class EkuTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    // =========================================================================
    // SIHIR OTOMATIS: Membaca Excel 12 Bulan (Januari - Desember)
    // =========================================================================
    protected static function booted()
    {
        // static::saved digunakan agar Detail dieksekusi SETELAH transaksi utama punya ID
        static::saved(function ($transaction) {

            // 1. Hapus rincian lama jika ini adalah proses "Edit/Update" file Excel
            $transaction->details()->delete();

            // 2. Fungsi dinamis untuk membaca baris demi baris Excel
            $processExcel = function($filePath, $jenisFile) use ($transaction) {
                if (!$filePath) return;

                $fullPath = storage_path('app/public/' . $filePath);
                if (!file_exists($fullPath)) return;

                // Buka dan ubah file Excel menjadi Array
                $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);
                if (empty($arrayData) || empty($arrayData[0])) return;

                $sheet = $arrayData[0]; // Ambil Sheet yang pertama
                $multiplier = 1000000;  // Standar BI (x 1 Juta)

                // Fungsi kecil pembersih format angka (misal: "3.000" jadi 3000)
                $clean = fn($val) => (float) str_replace(['.', ',', ' '], '', (string) $val);

                // Daftar bulan yang akan dicari sistem di dalam Excel
                $bulanValid = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                // Looping (Pindai) seluruh baris dari atas ke bawah
                foreach ($sheet as $row) {
                    $namaBulan = null;

                    // Cari apakah di baris ini terdapat nama bulan (Cek di Kolom A, B, atau C)
                    foreach ([0, 1, 2] as $colIdx) {
                        if (isset($row[$colIdx]) && in_array(trim((string)$row[$colIdx]), $bulanValid, true)) {
                            $namaBulan = trim((string)$row[$colIdx]);
                            break;
                        }
                    }

                    // Jika baris ini terdeteksi sebagai baris Bulan (Misal: baris Januari), sedot datanya!
                    if ($namaBulan) {
                        /* * PEMETAAN KOLOM (Berdasarkan gambar Excel BI):
                         * Index 2 (Kolom C) = 100rb | Index 3 = 50rb | Index 4 = 20rb | dst...
                         * Index 9 (Kolom J) = TOTAL UK (Kita lewati)
                         * Index 10 (Kolom K) = 1rb Logam | Index 11 = 500 Logam | dst...
                         */
                        $k100k = $clean($row[2] ?? 0) * $multiplier;
                        $k50k  = $clean($row[3] ?? 0) * $multiplier;
                        $k20k  = $clean($row[4] ?? 0) * $multiplier;
                        $k10k  = $clean($row[5] ?? 0) * $multiplier;
                        $k5k   = $clean($row[6] ?? 0) * $multiplier;
                        $k2k   = $clean($row[7] ?? 0) * $multiplier;
                        $k1k   = $clean($row[8] ?? 0) * $multiplier;

                        $l1k   = $clean($row[10] ?? 0) * $multiplier;
                        $l500  = $clean($row[11] ?? 0) * $multiplier;
                        $l200  = $clean($row[12] ?? 0) * $multiplier;
                        $l100  = $clean($row[13] ?? 0) * $multiplier;

                        $subtotal = $k100k + $k50k + $k20k + $k10k + $k5k + $k2k + $k1k + $l1k + $l500 + $l200 + $l100;

                        // Simpan rincian khusus bulan ini ke dalam database Detail
                        $transaction->details()->create([
                            'bulan' => $namaBulan,
                            'jenis_file' => $jenisFile,
                            'kertas_100k' => $k100k, 'kertas_50k' => $k50k, 'kertas_20k' => $k20k, 'kertas_10k' => $k10k,
                            'kertas_5k' => $k5k, 'kertas_2k' => $k2k, 'kertas_1k' => $k1k,
                            'logam_1k' => $l1k, 'logam_500' => $l500, 'logam_200' => $l200, 'logam_100' => $l100,
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            };

            // 3. Eksekusi baca file secara bergantian (Jika di-upload)
            $processExcel($transaction->file_setoran, 'Setoran');
            $processExcel($transaction->file_penarikan, 'Penarikan');

            // 4. PENGHITUNGAN GRAND TOTAL UNTUK DASHBOARD
            // Menggabungkan total seluruh bulan untuk ditampilkan di tabel pengajuan utama
            $totals = $transaction->details()
                ->selectRaw('
                    SUM(kertas_100k) as total_100k, SUM(kertas_50k) as total_50k,
                    SUM(kertas_20k) as total_20k, SUM(kertas_10k) as total_10k,
                    SUM(kertas_5k) as total_5k, SUM(kertas_2k) as total_2k,
                    SUM(kertas_1k) as total_1k, SUM(logam_1k) as total_l1k,
                    SUM(logam_500) as total_l500, SUM(logam_200) as total_l200,
                    SUM(logam_100) as total_l100, SUM(subtotal) as grand_total
                ')->first();

            if ($totals) {
                DB::table('eku_transactions')->where('id', $transaction->id)->update([
                    'kertas_100k' => $totals->total_100k ?? 0, 'kertas_50k' => $totals->total_50k ?? 0,
                    'kertas_20k' => $totals->total_20k ?? 0, 'kertas_10k' => $totals->total_10k ?? 0,
                    'kertas_5k' => $totals->total_5k ?? 0, 'kertas_2k' => $totals->total_2k ?? 0,
                    'kertas_1k' => $totals->total_1k ?? 0, 'logam_1k' => $totals->total_l1k ?? 0,
                    'logam_500' => $totals->total_l500 ?? 0, 'logam_200' => $totals->total_l200 ?? 0,
                    'logam_100' => $totals->total_l100 ?? 0, 'total_nominal' => $totals->grand_total ?? 0,
                ]);
            }
        });
    }

    // --- Relasi Tabel ---
    public function bank(): BelongsTo { return $this->belongsTo(Bank::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    // Relasi ke Tabel Detail Bulanan
    public function details(): HasMany { return $this->hasMany(EkuTransactionDetail::class); }
}
