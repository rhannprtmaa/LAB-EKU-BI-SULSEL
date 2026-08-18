<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Satu sheet template "Input Realisasi Harian" (dipakai untuk sheet
 * "Setoran" maupun "Penarikan" -- layout-nya identik, cuma judul & nama
 * sheet yang beda, jadi class ini di-reuse untuk keduanya lewat
 * RealisasiTemplateExport::sheets()).
 *
 * Struktur:
 *   Row 1     : Judul, merged dari kolom A s.d kolom paling akhir
 *   Row 2     : Tanggal 1..31 (tiap tanggal merge 2 kolom UPB+UPK),
 *               lalu blok "TOTAL" (merge 3 kolom) di paling kanan
 *   Row 3     : Sub-header -> ID BANK | BANK | UPB|UPK berulang | Total UPB | Total UPK | Grand Total
 *   Row 4 dst.: Data bank -- ID & Nama terisi, kolom tanggal kosong untuk
 *               diisi user, 3 kolom Total di kanan berisi FORMULA (bukan
 *               angka statis) yang otomatis mengikuti isian user pada
 *               BARIS itu sendiri (per bank, bukan gabungan semua bank --
 *               karena tiap baris/bank realisasinya masing-masing,
 *               cuma kebetulan diunggah bersamaan dalam satu file).
 *
 * Kolom A ("ID BANK") disembunyikan lewat WithEvents supaya user tidak
 * sengaja mengubah/menghapus ID bank saat mengisi file. Sheet juga
 * diproteksi: cuma cell input tanggal yang bisa diedit, sisanya (header,
 * ID, nama bank, kolom Total berformula) dikunci.
 */
class RealisasiTemplateSheet implements WithColumnWidths, WithEvents, WithTitle
{
    private const JUMLAH_HARI = 31;

    private const KOLOM_ID_BANK = 1;   // A
    private const KOLOM_NAMA_BANK = 2; // B
    private const KOLOM_TANGGAL_MULAI = 3; // C -> awal blok UPB/UPK tanggal 1

    public function __construct(
        protected string $jenis, // 'Setoran' atau 'Penarikan'
    ) {}

    public function title(): string
    {
        return $this->jenis;
    }

    /** Kolom terakhir yang dipakai blok tanggal (SEBELUM blok Total). */
    public function kolomTerakhirTanggal(): int
    {
        return self::KOLOM_TANGGAL_MULAI + (self::JUMLAH_HARI * 2) - 1;
    }

    public function kolomTotalUpb(): int
    {
        return $this->kolomTerakhirTanggal() + 1;
    }

    public function kolomTotalUpk(): int
    {
        return $this->kolomTerakhirTanggal() + 2;
    }

    public function kolomGrandTotal(): int
    {
        return $this->kolomTerakhirTanggal() + 3;
    }

    /** Kolom paling akhir dari keseluruhan sheet (termasuk blok Total). */
    public function kolomPalingAkhir(): int
    {
        return $this->kolomGrandTotal();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, // tetap didefinisikan walau nanti disembunyikan
            'B' => 28,
            Coordinate::stringFromColumnIndex($this->kolomTotalUpb()) => 16,
            Coordinate::stringFromColumnIndex($this->kolomTotalUpk()) => 16,
            Coordinate::stringFromColumnIndex($this->kolomGrandTotal()) => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->tulisBarisTanggal($sheet);
                $this->tulisSubHeader($sheet);
                $barisTerakhirBank = $this->tulisDataBank($sheet);

                $this->terapkanStyling($sheet, $barisTerakhirBank);
                $this->terapkanValidasiInput($sheet, $barisTerakhirBank);
                $this->terapkanProteksiSheet($sheet, $barisTerakhirBank);

                // SECURITY / UX: sembunyikan kolom ID BANK supaya user
                // tidak sengaja mengedit/menghapus ID saat mengisi file.
                $sheet->getColumnDimension('A')->setVisible(false);
            },
        ];
    }

    protected function tulisJudul(Worksheet $sheet): void
    {
        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomPalingAkhir());

        $sheet->setCellValue("{$kolomAwal}1", 'INPUT REALISASI HARIAN');
        $sheet->mergeCells("{$kolomAwal}1:{$kolomAkhir}1");

        $sheet->getStyle("{$kolomAwal}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);
    }

    protected function tulisBarisTanggal(Worksheet $sheet): void
    {
        // Kolom A & B baris tanggal sengaja dikosongkan (spesifikasi #3).
        for ($tanggal = 1; $tanggal <= self::JUMLAH_HARI; $tanggal++) {
            [$kolomUpb, $kolomUpk] = $this->kolomUntukTanggal($tanggal);

            $hurufUpb = Coordinate::stringFromColumnIndex($kolomUpb);
            $hurufUpk = Coordinate::stringFromColumnIndex($kolomUpk);

            $sheet->setCellValue("{$hurufUpb}2", (string) $tanggal);
            $sheet->mergeCells("{$hurufUpb}2:{$hurufUpk}2");

            $sheet->getStyle("{$hurufUpb}2")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Blok "TOTAL" (3 kolom) di baris tanggal, biar tampilannya
        // konsisten dengan blok tanggal 1..31 -- tapi diberi warna beda
        // supaya jelas ini area HASIL HITUNGAN, bukan area input.
        $hurufTotalAwal = Coordinate::stringFromColumnIndex($this->kolomTotalUpb());
        $hurufTotalAkhir = Coordinate::stringFromColumnIndex($this->kolomGrandTotal());

        $sheet->setCellValue("{$hurufTotalAwal}2", 'TOTAL');
        $sheet->mergeCells("{$hurufTotalAwal}2:{$hurufTotalAkhir}2");

        $sheet->getStyle("{$hurufTotalAwal}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '7C2D12']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FDE68A'],
            ],
        ]);
    }

    protected function tulisSubHeader(Worksheet $sheet): void
    {
        $hurufIdBank = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $hurufNamaBank = Coordinate::stringFromColumnIndex(self::KOLOM_NAMA_BANK);

        $sheet->setCellValue("{$hurufIdBank}3", 'ID BANK');
        $sheet->setCellValue("{$hurufNamaBank}3", 'BANK');

        for ($tanggal = 1; $tanggal <= self::JUMLAH_HARI; $tanggal++) {
            [$kolomUpb, $kolomUpk] = $this->kolomUntukTanggal($tanggal);

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($kolomUpb) . '3', 'UPB');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($kolomUpk) . '3', 'UPK');
        }

        $hurufTotalUpb = Coordinate::stringFromColumnIndex($this->kolomTotalUpb());
        $hurufTotalUpk = Coordinate::stringFromColumnIndex($this->kolomTotalUpk());
        $hurufGrandTotal = Coordinate::stringFromColumnIndex($this->kolomGrandTotal());

        $sheet->setCellValue("{$hurufTotalUpb}3", 'Total UPB');
        $sheet->setCellValue("{$hurufTotalUpk}3", 'Total UPK');
        $sheet->setCellValue("{$hurufGrandTotal}3", 'Grand Total');

        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $kolomAkhirTanggal = Coordinate::stringFromColumnIndex($this->kolomTerakhirTanggal());
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomPalingAkhir());

        // Sub-header area input (ID s.d kolom tanggal terakhir) -> biru brand.
        $sheet->getStyle("{$kolomAwal}3:{$kolomAkhirTanggal}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '054177'], // samakan dengan warna brand panel
            ],
        ]);

        // Sub-header area Total -> warna beda (amber) biar jelas ini hasil hitungan.
        $sheet->getStyle("{$hurufTotalUpb}3:{$kolomAkhir}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '7C2D12']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FDE68A'],
            ],
        ]);
    }

    protected function tulisDataBank(Worksheet $sheet): int
    {
        $hurufIdBank = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $hurufNamaBank = Coordinate::stringFromColumnIndex(self::KOLOM_NAMA_BANK);

        $baris = 4;

        foreach (Bank::all() as $bank) {
            $sheet->setCellValue("{$hurufIdBank}{$baris}", $bank->id);
            $sheet->setCellValue("{$hurufNamaBank}{$baris}", $bank->name);

            // Sisa kolom (UPB/UPK tiap tanggal) sengaja dibiarkan kosong
            // untuk diisi user -- lihat spesifikasi #5.

            $this->tulisFormulaTotalBaris($sheet, $baris);

            $baris++;
        }

        // Baris terakhir yang benar-benar berisi data bank.
        return $baris - 1;
    }

    /**
     * Isi 3 kolom Total (UPB, UPK, Grand Total) untuk SATU baris bank
     * dengan FORMULA Excel -- bukan angka statis -- supaya otomatis
     * ter-update begitu user mengisi/mengubah angka harian.
     */
    protected function tulisFormulaTotalBaris(Worksheet $sheet, int $baris): void
    {
        $hurufUpbList = [];
        $hurufUpkList = [];

        for ($tanggal = 1; $tanggal <= self::JUMLAH_HARI; $tanggal++) {
            [$kolomUpb, $kolomUpk] = $this->kolomUntukTanggal($tanggal);

            $hurufUpbList[] = Coordinate::stringFromColumnIndex($kolomUpb) . $baris;
            $hurufUpkList[] = Coordinate::stringFromColumnIndex($kolomUpk) . $baris;
        }

        $hurufTotalUpb = Coordinate::stringFromColumnIndex($this->kolomTotalUpb());
        $hurufTotalUpk = Coordinate::stringFromColumnIndex($this->kolomTotalUpk());
        $hurufGrandTotal = Coordinate::stringFromColumnIndex($this->kolomGrandTotal());

        $sheet->setCellValue("{$hurufTotalUpb}{$baris}", '=SUM(' . implode(',', $hurufUpbList) . ')');
        $sheet->setCellValue("{$hurufTotalUpk}{$baris}", '=SUM(' . implode(',', $hurufUpkList) . ')');
        $sheet->setCellValue("{$hurufGrandTotal}{$baris}", "={$hurufTotalUpb}{$baris}+{$hurufTotalUpk}{$baris}");
    }

    protected function terapkanStyling(Worksheet $sheet, int $barisTerakhirBank): void
    {
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomPalingAkhir());
        $barisTerakhir = max(3, $barisTerakhirBank);

        $sheet->getStyle("A1:{$kolomAkhir}{$barisTerakhir}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Format angka ribuan pada seluruh area angka (input + total),
        // biar enak dibaca begitu user mulai mengisi.
        $kolomTanggalAwal = Coordinate::stringFromColumnIndex(self::KOLOM_TANGGAL_MULAI);
        if ($barisTerakhirBank >= 4) {
            $sheet->getStyle("{$kolomTanggalAwal}4:{$kolomAkhir}{$barisTerakhirBank}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->freezePane('C4');
    }

    /**
     * Data validation: cell input harian (tanggal 1..31, UPB & UPK) hanya
     * boleh diisi angka >= 0 -- mencegah kesalahan input teks/negatif
     * sebelum file diunggah balik ke sistem.
     */
    protected function terapkanValidasiInput(Worksheet $sheet, int $barisTerakhirBank): void
    {
        if ($barisTerakhirBank < 4) {
            return;
        }

        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_TANGGAL_MULAI);
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomTerakhirTanggal());
        $rentang = "{$kolomAwal}4:{$kolomAkhir}{$barisTerakhirBank}";

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_DECIMAL);
        $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1('0');
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Nilai Tidak Valid');
        $validation->setError('Isian hanya boleh berupa angka dan tidak boleh negatif.');
        $validation->setShowInputMessage(true);
        $validation->setPromptTitle('Isi Nominal');
        $validation->setPrompt('Masukkan nominal (angka saja, boleh kosong jika tidak ada transaksi).');

        foreach (Coordinate::extractAllCellReferencesInRange($rentang) as $cellRef) {
            $sheet->getCell($cellRef)->setDataValidation(clone $validation);
        }
    }

    /**
     * Proteksi sheet: kunci semua cell KECUALI area input harian (tanggal
     * 1..31 untuk tiap baris bank), supaya header, ID Bank, nama bank, dan
     * kolom Total (formula) tidak sengaja tertimpa/terhapus user.
     *
     * Tanpa password -- proteksi ini untuk mencegah kecelakaan input, BUKAN
     * untuk mencegah manipulasi yang disengaja (kalau butuh itu, tambahkan
     * password lewat $sheet->getProtection()->setPassword('...')).
     */
    protected function terapkanProteksiSheet(Worksheet $sheet, int $barisTerakhirBank): void
    {
        if ($barisTerakhirBank < 4) {
            return;
        }

        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_TANGGAL_MULAI);
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomTerakhirTanggal());
        $rentangInput = "{$kolomAwal}4:{$kolomAkhir}{$barisTerakhirBank}";

        $sheet->getStyle($rentangInput)
            ->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);

        $sheet->getProtection()->setSheet(true);
    }

    /**
     * @return array{0: int, 1: int} [kolom UPB, kolom UPK] untuk tanggal ke-$tanggal
     */
    protected function kolomUntukTanggal(int $tanggal): array
    {
        $kolomUpb = self::KOLOM_TANGGAL_MULAI + (($tanggal - 1) * 2);
        $kolomUpk = $kolomUpb + 1;

        return [$kolomUpb, $kolomUpk];
    }
}
