<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EkuExcelImport implements ToCollection
{
    // Class ini sengaja dikosongkan karena logika pembacaannya
    // sudah kita buat langsung di dalam Model EkuTransaction
    public function collection(Collection $rows)
    {
        //
    }
}
