<?php

namespace App\Imports;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Models\EkuTransactionRealisasiDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Baca 1 sheet ("Setoran" ATAU "Penarikan") dari template
 * "Input Realisasi Harian" dan simpan hasilnya sebagai satu baris
 * EkuTransactionRealisasiDetail per bank (per bulan & jenis_file).
 *
 * Struktur kolom (0-indexed, sesuai RealisasiTemplateSheet):
 *   0        = ID BANK (kolom A, disembunyikan di template tapi datanya tetap ada)
 *   1        = Nama Bank (kolom B, hanya informatif, tidak dipakai untuk logic)
 *   2,4,6...  = UPB tiap tanggal (kolom C, E, G, ...)
 *   3,5,7...  = UPK tiap tanggal (kolom D, F, H, ...)
 */
class RealisasiMassalSheetImport implements ToCollection, WithStartRow
{
    private const KOLOM_BANK_ID = 0;

    private const KOLOM_MULAI_NOMINAL = 2; // kolom C ke atas berisi UPB/UPK

    /**
     * PENTING: konvensi seluruh sistem EKU (sama persis dengan
     * EkuTransaction::reprocessExcelFiles() untuk forecast) -> angka di
     * dalam cell Excel satuannya JUTA. Cell berisi "1" = Rp 1.000.000,
     * cell berisi "1000" = Rp 1.000.000.000 (1 miliar).
     * Sebelumnya multiplier ini KELEWATAN di import massal, jadi semua
     * angka realisasi kesimpen 1 juta kali lebih kecil dari seharusnya.
     */
    private const MULTIPLIER = 1_000_000;

    protected array $bulanUrut = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Kumpulan id EkuTransactionRealisasi yang tersentuh selama import ini
     * berjalan -> dipakai untuk recalculateTotals() di akhir, supaya
     * kolom total_setoran/total_penarikan di parent ikut ter-update
     * (sebelumnya ini juga kelewatan, makanya "Riwayat Input Realisasi"
     * valuenya tetap 0 walau detail-nya sudah masuk).
     */
    protected array $realisasiIdYangTersentuh = [];

    public function __construct(
        protected string|int $bulan,
        protected string|int $tahun,
        protected string $jenisFile, // 'Setoran' atau 'Penarikan'
    ) {}

    public function startRow(): int
    {
        return 4;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $this->prosesBaris($row);
            }

            // Recalculate SETELAH semua baris di sheet ini selesai diproses,
            // sekali per realisasi (bukan per baris) supaya efisien.
            foreach (array_unique($this->realisasiIdYangTersentuh) as $realisasiId) {
                EkuTransactionRealisasi::recalculateTotals($realisasiId);
            }
        });
    }

    protected function prosesBaris(Collection $row): void
    {
        $bankId = $row->get(self::KOLOM_BANK_ID);

        if (! is_numeric($bankId)) {
            return;
        }

        $bankId = (int) $bankId;

        [$totalUpb, $totalUpk] = $this->hitungTotal($row);
        $subtotal = $totalUpb + $totalUpk;

        if ($subtotal <= 0) {
            return;
        }

        $transaksi = EkuTransaction::query()
            ->where('bank_id', $bankId)
            ->where('periode', (string) $this->tahun)
            ->first();

        if (! $transaksi) {
            return;
        }

        $realisasi = EkuTransactionRealisasi::updateOrCreate(
            ['eku_transaction_id' => $transaksi->id],
            [
                'input_by' => Auth::id(),
                'input_at' => now(),
            ]
        );

        // Selalu simpan sebagai NAMA BULAN Indonesia ("Agustus"), BUKAN
        // angka mentah ("8") -- ini yang dipakai EkuTransaction::hitungDeviasi()
        // untuk mencocokkan dengan bulan forecast. Kalau kesimpen sebagai
        // angka, deviasi tidak akan pernah ketemu pasangannya dan malah
        // muncul sebagai bulan "8" yang aneh sendiri.
        EkuTransactionRealisasiDetail::updateOrCreate(
            [
                'eku_transaction_realisasi_id' => $realisasi->id,
                'bulan' => $this->namaBulan($this->bulan),
                'jenis_file' => $this->jenisFile,
            ],
            [
                'total_upb' => $totalUpb,
                'total_upk' => $totalUpk,
                'subtotal' => $subtotal,
            ]
        );

        $this->realisasiIdYangTersentuh[] = $realisasi->id;
    }

    /**
     * @return array{0: float, 1: float} [total_upb, total_upk] -- SUDAH
     * dikali MULTIPLIER, jadi hasilnya nominal Rupiah asli.
     */
    protected function hitungTotal(Collection $row): array
    {
        $totalUpb = 0.0;
        $totalUpk = 0.0;

        $jumlahKolom = $row->count();

        for ($kolom = self::KOLOM_MULAI_NOMINAL; $kolom < $jumlahKolom; $kolom++) {
            $nilai = $this->bersihkanAngka($row->get($kolom)) * self::MULTIPLIER;

            if ($kolom % 2 === 0) {
                $totalUpb += $nilai;
            } else {
                $totalUpk += $nilai;
            }
        }

        return [$totalUpb, $totalUpk];
    }

    protected function bersihkanAngka(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $teks = trim((string) $value);

        if ($teks === '') {
            return 0.0;
        }

        $teks = preg_replace('/[^\d\-.,]/', '', $teks);
        $teks = str_replace('.', '', $teks);
        $teks = str_replace(',', '.', $teks);

        return is_numeric($teks) ? (float) $teks : 0.0;
    }

    /**
     * Terima input angka (1-12) ATAU nama bulan yang sudah benar, selalu
     * kembalikan nama bulan Indonesia. Dibuat robust terhadap kemungkinan
     * $this->bulan dikirim sebagai string angka ("8"), int (8), atau
     * kebetulan sudah berupa nama ("Agustus") dari sisi UI.
     */
    protected function namaBulan(string|int $bulan): string
    {
        if (is_numeric($bulan)) {
            return $this->bulanUrut[(int) $bulan] ?? (string) $bulan;
        }

        return (string) $bulan;
    }
}
