<?php

namespace App\Services;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasiDetail;
use Illuminate\Support\Collection;

class EkuReportCalculator
{
    /** UPB = Uang Pecahan Besar (100rb & 50rb). */
    public const KOLOM_UPB = ['kertas_100k', 'kertas_50k'];

    /** UPK = Uang Pecahan Kecil (20rb s.d. 1rb, kertas). */
    public const KOLOM_UPK = ['kertas_20k', 'kertas_10k', 'kertas_5k', 'kertas_2k', 'kertas_1k'];

    /** Logam = 1rb s.d. 100 (uang logam). */
    public const KOLOM_LOGAM = ['logam_1k', 'logam_500', 'logam_200', 'logam_100'];

    /**
     * Hitung satu baris laporan lengkap untuk satu EkuTransaction.
     */
    public static function hitung(EkuTransaction $record): array
    {
        // PERBAIKAN: Ambil ID dari riwayat, lalu tarik data detail secara langsung.
        // Ini menjamin 100% data realisasi terbaca tanpa takut bug Eager Loading.
        $realisasiIds = $record->realisasiHistory->pluck('id');
        $realisasiDetails = EkuTransactionRealisasiDetail::whereIn('eku_transaction_realisasi_id', $realisasiIds)->get();

        $bulanRealisasiSetoran = $realisasiDetails
            ->where('jenis_file', 'Setoran')
            ->pluck('bulan')
            ->unique();

        $bulanRealisasiPenarikan = $realisasiDetails
            ->where('jenis_file', 'Penarikan')
            ->pluck('bulan')
            ->unique();

        $realisasiSetoranDetails = $realisasiDetails->where('jenis_file', 'Setoran');
        $realisasiPenarikanDetails = $realisasiDetails->where('jenis_file', 'Penarikan');

        $realisasiSetoran = (float) $realisasiSetoranDetails->sum('subtotal');
        $realisasiPenarikan = (float) $realisasiPenarikanDetails->sum('subtotal');

        // Breakdown UPB/UPK realisasi -- dipakai untuk sub-kolom di PDF Report EKU
        $realisasiSetoranUpb = self::sumPecahan($realisasiSetoranDetails, self::KOLOM_UPB);
        $realisasiSetoranUpk = self::sumPecahan($realisasiSetoranDetails, self::KOLOM_UPK);
        $realisasiPenarikanUpb = self::sumPecahan($realisasiPenarikanDetails, self::KOLOM_UPB);
        $realisasiPenarikanUpk = self::sumPecahan($realisasiPenarikanDetails, self::KOLOM_UPK);

        $detailSetoran = $record->details->where('jenis_file', 'Setoran');
        $detailPenarikan = $record->details->where('jenis_file', 'Penarikan');

        // Pengajuan (Forecast) YTD
        $pengajuanSetoranYtd = (float) $detailSetoran->whereIn('bulan', $bulanRealisasiSetoran)->sum('subtotal');
        $pengajuanPenarikanYtd = (float) $detailPenarikan->whereIn('bulan', $bulanRealisasiPenarikan)->sum('subtotal');

        // Pengajuan (Forecast) SETAHUN PENUH
        $setoranUpb = self::sumPecahan($detailSetoran, self::KOLOM_UPB);
        $setoranUpk = self::sumPecahan($detailSetoran, self::KOLOM_UPK);
        $setoranLogam = self::sumPecahan($detailSetoran, self::KOLOM_LOGAM);
        $setoranTotal = (float) $detailSetoran->sum('subtotal');

        $penarikanUpb = self::sumPecahan($detailPenarikan, self::KOLOM_UPB);
        $penarikanUpk = self::sumPecahan($detailPenarikan, self::KOLOM_UPK);
        $penarikanLogam = self::sumPecahan($detailPenarikan, self::KOLOM_LOGAM);
        $penarikanTotal = (float) $detailPenarikan->sum('subtotal');

        return [
            'bank' => $record->bank?->name ?? '-',
            'periode' => $record->periode,

            'pengajuanSetoran' => $pengajuanSetoranYtd,
            'pengajuanPenarikan' => $pengajuanPenarikanYtd,
            'realisasiSetoran' => $realisasiSetoran,
            'realisasiPenarikan' => $realisasiPenarikan,
            'deviasiSetoran' => $setoranTotal - $realisasiSetoran,
            'deviasiPenarikan' => $penarikanTotal - $realisasiPenarikan,

            // Breakdown UPB/UPK realisasi (untuk sub-kolom PDF Report EKU)
            'realisasiSetoranUpb' => $realisasiSetoranUpb,
            'realisasiSetoranUpk' => $realisasiSetoranUpk,
            'realisasiPenarikanUpb' => $realisasiPenarikanUpb,
            'realisasiPenarikanUpk' => $realisasiPenarikanUpk,

            // Breakdown UPB/UPK deviasi = proyeksi UPB/UPK dikurangi realisasi UPB/UPK
            'deviasiSetoranUpb' => $setoranUpb - $realisasiSetoranUpb,
            'deviasiSetoranUpk' => $setoranUpk - $realisasiSetoranUpk,
            'deviasiPenarikanUpb' => $penarikanUpb - $realisasiPenarikanUpb,
            'deviasiPenarikanUpk' => $penarikanUpk - $realisasiPenarikanUpk,

            'setoranUpb' => $setoranUpb,
            'setoranUpk' => $setoranUpk,
            'setoranLogam' => $setoranLogam,
            'setoranTotal' => $setoranTotal,

            'penarikanUpb' => $penarikanUpb,
            'penarikanUpk' => $penarikanUpk,
            'penarikanLogam' => $penarikanLogam,
            'penarikanTotal' => $penarikanTotal,

            'grandTotal' => $setoranTotal + $penarikanTotal,
        ];
    }

    /**
     * Hitung banyak baris sekaligus (dipakai untuk export "Semua").
     */
    public static function hitungBanyak(Collection $records): Collection
    {
        return $records->map(fn (EkuTransaction $record) => self::hitung($record));
    }

    /**
     * Rincian mentah per-bulan (dipakai untuk sheet "Rincian Data Mentah"
     */
    public static function rincianMentah(EkuTransaction $record): Collection
    {
        $baris = collect();

        // 1. Ekstrak Data Pengajuan (Forecast)
        foreach ($record->details as $detail) {
            $baris->push([
                'bank' => $record->bank?->name ?? '-',
                'periode' => $record->periode,
                'bulan' => $detail->bulan,
                'jenis_file' => $detail->jenis_file,
                'sumber' => 'Pengajuan',
                'upb' => self::jumlahKolom($detail, self::KOLOM_UPB),
                'upk' => self::jumlahKolom($detail, self::KOLOM_UPK),
                'logam' => self::jumlahKolom($detail, self::KOLOM_LOGAM),
                'subtotal' => (float) $detail->subtotal,
            ]);
        }

        // PERBAIKAN: Ambil data Realisasi langsung dengan ID nya
        $realisasiIds = $record->realisasiHistory->pluck('id');
        $realisasiDetails = EkuTransactionRealisasiDetail::whereIn('eku_transaction_realisasi_id', $realisasiIds)->get();

        // 2. Ekstrak Data Realisasi
        foreach ($realisasiDetails as $detail) {
            $baris->push([
                'bank' => $record->bank?->name ?? '-',
                'periode' => $record->periode,
                'bulan' => $detail->bulan,
                'jenis_file' => $detail->jenis_file,
                'sumber' => 'Realisasi',
                'upb' => self::jumlahKolom($detail, self::KOLOM_UPB),
                'upk' => self::jumlahKolom($detail, self::KOLOM_UPK),
                'logam' => self::jumlahKolom($detail, self::KOLOM_LOGAM),
                'subtotal' => (float) $detail->subtotal,
            ]);
        }

        return $baris;
    }

    /**
     * Wrapper publik: hitung UPB & UPK untuk SATU baris detail (dipakai
     * di luar class ini, misalnya PDF "Detail Input Realisasi" pada
     * Riwayat Input Realisasi), pakai logic yang sama persis dengan
     * jumlahKolom() supaya konsisten dengan Report EKU.
     *
     * @return array{upb: float, upk: float}
     */
    public static function upbUpk($detail): array
    {
        return [
            'upb' => self::jumlahKolom($detail, self::KOLOM_UPB),
            'upk' => self::jumlahKolom($detail, self::KOLOM_UPK),
        ];
    }

    protected static function sumPecahan(Collection $details, array $kolom): float
    {
        return (float) $details->sum(fn ($detail) => self::jumlahKolom($detail, $kolom));
    }

    protected static function jumlahKolom($detail, array $kolom): float
    {
        // Cek jika datanya adalah tipe Realisasi (memiliki kolom total_upb)
        $detailArray = is_array($detail) ? $detail : $detail->toArray();

        if ($kolom === self::KOLOM_UPB && array_key_exists('total_upb', $detailArray)) {
            return (float) ($detail->total_upb ?? 0);
        }

        if ($kolom === self::KOLOM_UPK && array_key_exists('total_upk', $detailArray)) {
            return (float) ($detail->total_upk ?? 0);
        }

        if ($kolom === self::KOLOM_LOGAM && array_key_exists('total_upb', $detailArray)) {
            return 0; // Karena realisasi harian BI Sulsel tidak mencatat uang logam
        }

        // Fallback untuk Pengajuan (Forecast) yang masih memiliki kolom granular kertas_100k dsb
        return (float) collect($kolom)->sum(fn (string $k) => (float) ($detail->{$k} ?? 0));
    }
}
