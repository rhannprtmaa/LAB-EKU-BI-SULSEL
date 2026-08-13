<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RealisasiTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new RealisasiTemplateSheet('Setoran'),
            new RealisasiTemplateSheet('Penarikan'),
        ];
    }
}
