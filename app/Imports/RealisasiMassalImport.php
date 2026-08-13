<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RealisasiMassalImport implements WithMultipleSheets
{
    protected string|int $bulan;
    protected string|int $tahun;

    public function __construct(string|int $bulan, string|int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function sheets(): array
    {
        return [
            0 => new RealisasiMassalSheetImport($this->bulan, $this->tahun, 'Setoran'),
            1 => new RealisasiMassalSheetImport($this->bulan, $this->tahun, 'Penarikan'),
        ];
    }
}
