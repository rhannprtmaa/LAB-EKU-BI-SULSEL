<?php

namespace App\Support;

use App\Imports\EkuExcelImport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EkuExcelParser
{
    /**
     * Baca 1 file Excel Template Kerja EKU (format UANG KERTAS / UANG LOGAM
     * per pecahan x 12 kolom bulan -- sama persis dengan yang dipakai bank
     * untuk pengajuan forecast) dan kembalikan TOTAL keseluruhan (jumlah
     * semua pecahan x semua bulan).
     *
     * Dipakai untuk menghitung "Batasan EKU per Bank" dari file yang
     * diupload Admin BI, memakai logic pembacaan yang sama persis dengan
     * EkuTransaction::reprocessExcelFiles() supaya hasilnya konsisten.
     */
    public static function totalDariFile(?string $filePath): float
    {
        if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return 0.0;
        }

        $fullPath = Storage::disk('public')->path($filePath);

        $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);

        if (empty($arrayData) || empty($arrayData[0])) {
            return 0.0;
        }

        $sheet = $arrayData[0];
        $multiplier = 1000000;

        $clean = fn ($val) => is_numeric($val)
            ? (float) $val
            : (float) str_replace(['.', ',', ' '], '', (string) $val);

        $kolomBulanIdx = range(3, 14); // kolom D..O (index 0-based array)

        $denominasiKertas = [100000, 50000, 20000, 10000, 5000, 2000, 1000];
        $denominasiLogam = [1000, 500, 200, 100];

        $section = null;
        $total = 0.0;

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
            $denominasiValid = $section === 'kertas' ? $denominasiKertas : $denominasiLogam;

            if (! in_array($nominal, $denominasiValid, true)) {
                continue;
            }

            foreach ($kolomBulanIdx as $colIdx) {
                $total += $clean($row[$colIdx] ?? 0) * $multiplier;
            }
        }

        return $total;
    }

    /**
     * Baca 1 file Excel Template Kerja EKU dan kembalikan rincian LENGKAP
     * per bulan x per pecahan (bukan cuma total keseluruhan seperti
     * totalDariFile()).
     *
     * Dipakai supaya "Sesuaikan Batasan" bisa menerapkan ANGKA ABSOLUT
     * yang BI tentukan per pecahan/bulan di file batasan -- bukan
     * memotong rata (proporsional) semua pecahan pengajuan bank dengan
     * faktor yang sama, yang salah karena menghilangkan detail bahwa BI
     * mungkin hanya mengubah SATU pecahan tertentu (mis. Rp20.000 saja)
     * sementara pecahan lain tetap sama seperti semula.
     *
     * @return array<string, array<string, float>> dikunci per nama bulan,
     *         berisi kolom-kolom pecahan (kertas_100k, kertas_50k, dst).
     */
    public static function rincianDariFile(?string $filePath, string $jenis): array
    {
        if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return [];
        }

        $fullPath = Storage::disk('public')->path($filePath);

        $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);

        if (empty($arrayData) || empty($arrayData[0])) {
            return [];
        }

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
                'bulan' => $namaBulan,
                'jenis' => $jenis,
                'kertas_100k' => 0.0, 'kertas_50k' => 0.0, 'kertas_20k' => 0.0, 'kertas_10k' => 0.0,
                'kertas_5k' => 0.0, 'kertas_2k' => 0.0, 'kertas_1k' => 0.0,
                'logam_1k' => 0.0, 'logam_500' => 0.0, 'logam_200' => 0.0, 'logam_100' => 0.0,
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
            $namaKolom = $section === 'kertas' ? ($petaKertas[$nominal] ?? null) : ($petaLogam[$nominal] ?? null);

            if (! $namaKolom) {
                continue;
            }

            foreach ($kolomBulan as $colIdx => $namaBulan) {
                $akumulasi[$namaBulan][$namaKolom] += $clean($row[$colIdx] ?? 0) * $multiplier;
            }
        }

        return $akumulasi;
    }
}
