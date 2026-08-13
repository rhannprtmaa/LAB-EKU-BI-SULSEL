<?php

namespace App\Exports;

use App\Exports\Sheets\RingkasanTahunanSheet;
use App\Exports\Sheets\RincianDataMentahSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Orkestrator export Excel "Reporting EKU" -- 2 sheet:
 * 1. Ringkasan Tahunan  : satu baris per Bank+Periode, angka agregat.
 * 2. Rincian Data Mentah: satu baris per Bulan+Jenis File+Sumber data.
 *
 * @see RingkasanTahunanSheet
 * @see RincianDataMentahSheet
 */
class ReportEkuExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, \App\Models\EkuTransaction>  $transactions  Data yang SUDAH di-filter & di-scope per role (lihat ReportEku::getTableQueryBase()).
     */
    public function __construct(protected Collection $transactions)
    {
    }

    public function sheets(): array
    {
        return [
            'Ringkasan Tahunan' => new RingkasanTahunanSheet($this->transactions),
            'Rincian Data Mentah' => new RincianDataMentahSheet($this->transactions),
        ];
    }
}
