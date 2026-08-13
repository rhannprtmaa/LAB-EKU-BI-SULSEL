<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export utama untuk template "Input Realisasi Harian".
 * Menghasilkan 2 sheet dengan layout identik: "Setoran" dan "Penarikan".
 *
 * Dipakai lewat, misalnya:
 *   return Excel::download(new RealisasiTemplateExport(), 'template-realisasi-harian.xlsx');
 */
class RealisasiTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Setoran' => new RealisasiTemplateSheet('Setoran'),
            'Penarikan' => new RealisasiTemplateSheet('Penarikan'),
        ];
    }
}
