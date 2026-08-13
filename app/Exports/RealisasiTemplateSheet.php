<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Satu sheet template "Input Realisasi Harian" (dipakai untuk sheet
 * "Setoran" maupun "Penarikan" -- layout-nya identik, cuma judul & nama
 * sheet yang beda, jadi class ini di-reuse untuk keduanya lewat
 * RealisasiTemplateExport::sheets()).
 *
 * Struktur:
 *   Row 1            : Judul, merged dari kolom A s.d kolom terakhir
 *   Row 2            : Tanggal 1..31, tiap tanggal merge 2 kolom (UPB + UPK)
 *   Row 3             : Sub-header -> "ID BANK" | "BANK" | UPB | UPK | UPB | UPK ...
 *   Row 4 dst.        : Data bank (ID BANK & BANK terisi, sisanya kosong untuk diisi user)
 *
 * Kolom A ("ID BANK") disembunyikan lewat WithEvents supaya user tidak
 * sengaja mengubah/menghapus ID bank saat mengisi file.
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

    public function kolomTerakhir(): int
    {
        // Tiap tanggal memakai 2 kolom (UPB, UPK).
        return self::KOLOM_TANGGAL_MULAI + (self::JUMLAH_HARI * 2) - 1;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10, // tetap didefinisikan walau nanti disembunyikan
            'B' => 28,
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
                $this->tulisDataBank($sheet);

                $this->terapkanStyling($sheet);

                // SECURITY / UX: sembunyikan kolom ID BANK supaya user
                // tidak sengaja mengedit/menghapus ID saat mengisi file.
                $sheet->getColumnDimension('A')->setVisible(false);
            },
        ];
    }

    protected function tulisJudul(Worksheet $sheet): void
    {
        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomTerakhir());

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

        $kolomAwal = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomTerakhir());

        $sheet->getStyle("{$kolomAwal}3:{$kolomAkhir}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '054177'], // samakan dengan warna brand panel
            ],
        ]);
    }

    protected function tulisDataBank(Worksheet $sheet): void
    {
        $hurufIdBank = Coordinate::stringFromColumnIndex(self::KOLOM_ID_BANK);
        $hurufNamaBank = Coordinate::stringFromColumnIndex(self::KOLOM_NAMA_BANK);

        $baris = 4;

        foreach (Bank::all() as $bank) {
            $sheet->setCellValue("{$hurufIdBank}{$baris}", $bank->id);
            $sheet->setCellValue("{$hurufNamaBank}{$baris}", $bank->name);

            // Sisa kolom (UPB/UPK tiap tanggal) sengaja dibiarkan kosong
            // untuk diisi user -- lihat spesifikasi #5.

            $baris++;
        }
    }

    protected function terapkanStyling(Worksheet $sheet): void
    {
        $kolomAkhir = Coordinate::stringFromColumnIndex($this->kolomTerakhir());
        $barisTerakhir = max(3, $sheet->getHighestRow());

        $sheet->getStyle("A1:{$kolomAkhir}{$barisTerakhir}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->freezePane('C4');
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
