<?php

namespace App\Models;

use App\Imports\EkuExcelImport;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EkuTransactionRealisasi extends Model
{
    protected $guarded = [];

    protected $table = 'eku_transaction_realisasis';

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'input_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::saved(function (EkuTransactionRealisasi $realisasi) {
            if ($realisasi->wasRecentlyCreated || $realisasi->wasChanged(['file_setoran', 'file_penarikan'])) {
                $realisasi->reprocessExcelFiles();
            }
        });
    }

    public function ekuTransaction(): BelongsTo
    {
        return $this->belongsTo(EkuTransaction::class);
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(EkuTransactionRealisasiDetail::class, 'eku_transaction_realisasi_id');
    }

    public function reprocessExcelFiles(): array
    {
        $realisasi = $this;

        $realisasi->details()->delete();

        $processExcel = function ($filePath, $jenisFile) use ($realisasi) {
            if (! $filePath) return 0;

            if (! Storage::disk('public')->exists($filePath)) return 0;

            $fullPath = Storage::disk('public')->path($filePath);

            $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);
            if (empty($arrayData) || empty($arrayData[0])) return 0;

            $sheet = $arrayData[0];
            $multiplier = 1000000;

            $clean = fn ($val) => is_numeric($val)
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
                $realisasi->details()->create(array_merge(
                    ['bulan' => $namaBulan, 'jenis_file' => $jenisFile, 'subtotal' => array_sum($pecahan)],
                    $pecahan
                ));

                $baris++;
            }

            return $baris;
        };

        $jumlahSetoran = $processExcel($realisasi->file_setoran, 'Setoran');
        $jumlahPenarikan = $processExcel($realisasi->file_penarikan, 'Penarikan');

        if ($jumlahSetoran === 0 && $jumlahPenarikan === 0 && ($realisasi->file_setoran || $realisasi->file_penarikan)) {
            Notification::make()
                ->title('Peringatan: Excel Realisasi tidak berhasil dibaca')
                ->body('Sistem tidak menemukan struktur "UANG KERTAS" / "UANG LOGAM" di file yang diupload. Pastikan file mengikuti format Template Kerja EKU.')
                ->warning()
                ->persistent()
                ->send();
        }

        static::recalculateTotals($realisasi->id);

        return ['setoran' => $jumlahSetoran, 'penarikan' => $jumlahPenarikan];
    }

   public static function recalculateTotals(int $realisasiId): void
    {
        // 1. Hitung total nominal untuk file realisasi ini saja
        $totalPerJenis = EkuTransactionRealisasiDetail::query()
            ->where('eku_transaction_realisasi_id', $realisasiId)
            ->selectRaw('jenis_file, SUM(subtotal) as total')
            ->groupBy('jenis_file')
            ->pluck('total', 'jenis_file');

        $grandTotal = EkuTransactionRealisasiDetail::query()
            ->where('eku_transaction_realisasi_id', $realisasiId)
            ->sum('subtotal');

        // 2. Hanya update ke tabel history (eku_transaction_realisasis), JANGAN update ke tabel eku_transactions
        DB::table('eku_transaction_realisasis')->where('id', $realisasiId)->update([
            'total_setoran' => $totalPerJenis['Setoran'] ?? 0,
            'total_penarikan' => $totalPerJenis['Penarikan'] ?? 0,
            'total_nominal' => $grandTotal ?? 0,
        ]);
    }
    }

