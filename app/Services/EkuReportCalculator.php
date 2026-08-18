<?php

namespace App\Services;

use App\Models\EkuTransaction;
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
     *
     * WAJIB eager-load relasi 'details' dan 'realisasiHistory.details'
     * sebelum memanggil ini (lihat EkuTransaction::with([...])), supaya
     * tidak terjadi N+1 query saat dipanggil berulang untuk banyak baris.
     */
    public static function hitung(EkuTransaction $record): array
    {
        $realisasiDetails = $record->realisasiHistory->flatMap->details;

        $bulanRealisasiSetoran = $realisasiDetails
            ->where('jenis_file', 'Setoran')
            ->pluck('bulan')
            ->unique();

        $bulanRealisasiPenarikan = $realisasiDetails
            ->where('jenis_file', 'Penarikan')
            ->pluck('bulan')
            ->unique();

        $realisasiSetoran = (float) $realisasiDetails->where('jenis_file', 'Setoran')->sum('subtotal');
        $realisasiPenarikan = (float) $realisasiDetails->where('jenis_file', 'Penarikan')->sum('subtotal');

        $detailSetoran = $record->details->where('jenis_file', 'Setoran');
        $detailPenarikan = $record->details->where('jenis_file', 'Penarikan');

        // Pengajuan (Forecast) YTD -- HANYA bulan yang sudah ada Realisasi-nya,
        // supaya dibandingkan apple-to-apple dengan Realisasi.
        $pengajuanSetoranYtd = (float) $detailSetoran->whereIn('bulan', $bulanRealisasiSetoran)->sum('subtotal');
        $pengajuanPenarikanYtd = (float) $detailPenarikan->whereIn('bulan', $bulanRealisasiPenarikan)->sum('subtotal');

        // Pengajuan (Forecast) SETAHUN PENUH -- dipakai untuk kolom
        // UPB/UPK/Logam & Grand Total di laporan ringkasan (bukan YTD,
        // karena ini menggambarkan total rencana pengajuan tahun berjalan).
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
            'deviasiSetoran' => $pengajuanSetoranYtd - $realisasiSetoran,
            'deviasiPenarikan' => $pengajuanPenarikanYtd - $realisasiPenarikan,

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
     *
     * @param  Collection<int, EkuTransaction>  $records
     * @return Collection<int, array>
     */
    public static function hitungBanyak(Collection $records): Collection
    {
        return $records->map(fn (EkuTransaction $record) => self::hitung($record));
    }

    /**
     * Rincian mentah per-bulan (dipakai untuk sheet "Rincian Data Mentah"
     * di Excel) -- satu baris per kombinasi Bulan + Jenis File + Sumber
     * data (Pengajuan/Realisasi), lengkap dengan breakdown UPB/UPK/Logam.
     *
     * @return Collection<int, array>
     */
    public static function rincianMentah(EkuTransaction $record): Collection
    {
        $baris = collect();

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

        foreach ($record->realisasiHistory->flatMap->details as $detail) {
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

    protected static function sumPecahan(Collection $details, array $kolom): float
    {
        return (float) $details->sum(fn ($detail) => self::jumlahKolom($detail, $kolom));
    }

    protected static function jumlahKolom($detail, array $kolom): float
    {
        if ($kolom === self::KOLOM_UPB && isset($detail->total_upb) && (float) $detail->total_upb > 0) {
            return (float) $detail->total_upb;
        }

        if ($kolom === self::KOLOM_UPK && isset($detail->total_upk) && (float) $detail->total_upk > 0) {
            return (float) $detail->total_upk;
        }

        return (float) collect($kolom)->sum(fn (string $k) => (float) ($detail->{$k} ?? 0));
    }
}
