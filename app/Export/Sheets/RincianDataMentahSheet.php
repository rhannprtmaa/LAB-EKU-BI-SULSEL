<?php

namespace App\Exports\Sheets;

use App\Models\EkuTransaction;
use App\Services\EkuReportCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet kedua: satu baris untuk SETIAP kombinasi Bulan + Jenis File +
 * Sumber data (Pengajuan/Realisasi) dari seluruh transaksi yang di-export,
 * lengkap dengan breakdown UPB/UPK/Logam -- untuk audit/telusur rinci.
 */
class RincianDataMentahSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles, WithTitle
{
    /** @param  Collection<int, EkuTransaction>  $transactions */
    public function __construct(protected Collection $transactions)
    {
    }

    public function collection(): Collection
    {
        $baris = collect();
        $no = 0;

        foreach ($this->transactions as $record) {
            foreach (EkuReportCalculator::rincianMentah($record) as $rincian) {
                $no++;

                $baris->push([
                    $no,
                    $rincian['bank'],
                    $rincian['periode'],
                    $rincian['bulan'],
                    $rincian['jenis_file'],
                    $rincian['sumber'],
                    $rincian['upb'],
                    $rincian['upk'],
                    $rincian['logam'],
                    $rincian['subtotal'],
                ]);
            }
        }

        return $baris;
    }

    public function title(): string
    {
        return 'Rincian Data Mentah';
    }

    public function headings(): array
    {
        return [
            'No', 'Bank', 'Periode', 'Bulan', 'Jenis File', 'Sumber Data',
            'UPB', 'UPK', 'Logam', 'Subtotal',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
        ];
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
