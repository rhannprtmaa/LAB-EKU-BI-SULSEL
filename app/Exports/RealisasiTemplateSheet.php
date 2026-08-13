<?php

namespace App\Exports;

use App\Models\Bank;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RealisasiTemplateSheet implements FromArray, WithTitle, WithEvents
{
    protected string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $rows = [];

        // Baris 1: Judul Utama
        $row1 = ['INPUT REALISASI HARIAN - ' . strtoupper($this->title)];
        for($i = 1; $i < 64; $i++) { $row1[] = ''; }
        $rows[] = $row1;

        // Baris 2: Header Tanggal (1-31)
        $row2 = ['', '']; // Kolom A dan B kosong
        for($d = 1; $d <= 31; $d++) {
            $row2[] = $d;
            $row2[] = '';
        }
        $rows[] = $row2;

        // Baris 3: Sub Header (UPB/UPK)
        $row3 = ['ID BANK', 'NAMA BANK'];
        for($d = 1; $d <= 31; $d++) {
            $row3[] = 'UPB';
            $row3[] = 'UPK';
        }
        $rows[] = $row3;

        // Baris 4 dsb: Looping Data Bank
        $banks = Bank::all();
        foreach($banks as $bank) {
            $row = [$bank->id, $bank->name];
            for($d = 1; $d <= 31; $d++) {
                $row[] = ''; // UPB
                $row[] = ''; // UPK
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Sembunyikan Kolom A (Keamanan ID)
                $sheet->getColumnDimension('A')->setVisible(false);
                $sheet->getColumnDimension('B')->setAutoSize(true);

                // 2. Styling Judul
                $sheet->mergeCells('A1:BL1');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // 3. Merge Tanggal
                $colIndex = 3; // Mulai dari Kolom C
                for($d = 1; $d <= 31; $d++) {
                    $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    $sheet->mergeCells($col1 . '2:' . $col2 . '2');
                    $sheet->getStyle($col1 . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $colIndex += 2;
                }

                // 4. Warna dan Border Header
                $styleArray = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
                    'font' => ['bold' => true]
                ];
                $sheet->getStyle('A2:BL3')->applyFromArray($styleArray);
            }
        ];
    }
}
