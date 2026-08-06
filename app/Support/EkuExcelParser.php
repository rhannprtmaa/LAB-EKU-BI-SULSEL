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
}
