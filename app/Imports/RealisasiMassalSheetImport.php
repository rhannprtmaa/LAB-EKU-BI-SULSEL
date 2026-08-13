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

    public function __construct(
        protected string|int $bulan,
        protected string|int $tahun,
        protected string $jenisFile, // 'Setoran' atau 'Penarikan'
    ) {}

    /**
     * Data di template baru mulai baris ke-4 (baris 1-3 = judul, tanggal,
     * sub-header) -- Maatwebsite otomatis skip baris di atasnya.
     */
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
        });
    }

    protected function prosesBaris(Collection $row): void
    {
        $bankId = $row->get(self::KOLOM_BANK_ID);

        // Lewati baris kosong / baris yang bukan data bank yang valid
        // (misal baris terakhir kosong karena Excel ikut membaca sampai
        // batas dimensi sheet).
        if (! is_numeric($bankId)) {
            return;
        }

        $bankId = (int) $bankId;

        [$totalUpb, $totalUpk] = $this->hitungTotal($row);
        $subtotal = $totalUpb + $totalUpk;

        // Requirement #5: hanya diproses kalau ada nominal yang diisi.
        if ($subtotal <= 0) {
            return;
        }

        $transaksi = EkuTransaction::query()
            ->where('bank_id', $bankId)
            ->where('periode', (string) $this->tahun)
            ->first();

        // Tidak ada pengajuan EKU (forecast) untuk kombinasi bank+periode
        // ini -> tidak ada yang bisa dibandingkan realisasinya, skip baris.
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

        EkuTransactionRealisasiDetail::updateOrCreate(
            [
                'eku_transaction_realisasi_id' => $realisasi->id,
                'bulan' => $this->namaBulan((int) $this->bulan),
                'jenis_file' => $this->jenisFile,
            ],
            [
                'total_upb' => $totalUpb,
                'total_upk' => $totalUpk,
                'subtotal' => $subtotal,
            ]
        );
    }

    /**
     * @return array{0: float, 1: float} [total_upb, total_upk]
     */
    protected function hitungTotal(Collection $row): array
    {
        $totalUpb = 0.0;
        $totalUpk = 0.0;

        // +1 supaya kolom terakhir (indeks genap/UPB) ikut terbaca kalau
        // jumlah kolomnya pas genap; count() bisa lebih pendek dari kolom
        // sebenarnya kalau cell-cell terakhir kosong semua, jadi kita pakai
        // max() antara panjang baris ini dengan panjang baris terpanjang
        // yang pernah ditemukan tidak diperlukan di sini -- cukup iterasi
        // apa yang tersedia di $row, sisanya otomatis dianggap 0.
        $jumlahKolom = $row->count();

        for ($kolom = self::KOLOM_MULAI_NOMINAL; $kolom < $jumlahKolom; $kolom++) {
            $nilai = $this->bersihkanAngka($row->get($kolom));

            // Kolom C, E, G, ... (indeks genap: 2, 4, 6, ...) = UPB
            // Kolom D, F, H, ... (indeks ganjil: 3, 5, 7, ...) = UPK
            if ($kolom % 2 === 0) {
                $totalUpb += $nilai;
            } else {
                $totalUpk += $nilai;
            }
        }

        return [$totalUpb, $totalUpk];
    }

    /**
     * Bersihkan nilai cell jadi float murni. Robust terhadap: cell kosong,
     * null, spasi, format ribuan (titik) & desimal (koma) ala Indonesia,
     * atau nilai yang sudah numeric asli dari Excel.
     */
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

        // Buang semua karakter selain digit, titik, koma, dan minus
        $teks = preg_replace('/[^\d\-.,]/', '', $teks);

        // Format Indonesia: titik = pemisah ribuan, koma = desimal
        // Contoh: "1.250.000,50" -> "1250000.50"
        $teks = str_replace('.', '', $teks);
        $teks = str_replace(',', '.', $teks);

        return is_numeric($teks) ? (float) $teks : 0.0;
    }

    protected function namaBulan(int $bulan): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$bulan] ?? (string) $bulan;
    }
}
