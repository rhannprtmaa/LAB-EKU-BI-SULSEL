<?php

namespace App\Exports\Sheets;

use App\Models\EkuTransaction;
use App\Services\EkuReportCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RingkasanTahunanSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** @param  Collection<int, EkuTransaction>  $transactions */
    public function __construct(protected Collection $transactions)
    {
    }

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function title(): string
    {
        return 'Ringkasan Tahunan';
    }

    public function headings(): array
    {
        return [
            'No', 'Bank', 'Periode',
            'Setoran - UPB', 'Setoran - UPK', 'Setoran - Logam', 'Total Setoran',
            'Penarikan - UPB', 'Penarikan - UPK', 'Penarikan - Logam', 'Total Penarikan',
            'Grand Total',
            'Realisasi Setoran (YTD)', 'Realisasi Penarikan (YTD)',
            'Deviasi Setoran', 'Deviasi Penarikan',
        ];
    }

    public function map($record): array
    {
        static $no = 0;
        $no++;

        $r = EkuReportCalculator::hitung($record);

        return [
            $no,
            $r['bank'],
            $r['periode'],
            $r['setoranUpb'],
            $r['setoranUpk'],
            $r['setoranLogam'],
            $r['setoranTotal'],
            $r['penarikanUpb'],
            $r['penarikanUpk'],
            $r['penarikanLogam'],
            $r['penarikanTotal'],
            $r['grandTotal'],
            $r['realisasiSetoran'],
            $r['realisasiPenarikan'],
            $r['deviasiSetoran'],
            $r['deviasiPenarikan'],
        ];
    }

    public function columnFormats(): array
    {
        $formatRupiah = '#,##0';

        // Kolom D s.d. P (semua kolom nominal) diformat sebagai angka ribuan.
        return array_fill_keys(range('D', 'P'), $formatRupiah);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DCE6F1'],
            ]],
        ];
    }
}
