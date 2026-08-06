<?php

namespace App\Imports;

use App\Models\Bank;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankLimitImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $bank = null;

            // Mencocokkan berdasarkan kode bank atau nama bank dari file Excel
            if (isset($row['code'])) {
                $bank = Bank::where('code', $row['code'])->first();
            } elseif (isset($row['name'])) {
                $bank = Bank::where('name', $row['name'])->first();
            }

            if ($bank) {
                $bank->update([
                    'batasan_setoran' => $row['batasan_setoran'] ?? $bank->batasan_setoran,
                    'batasan_penarikan' => $row['batasan_penarikan'] ?? $bank->batasan_penarikan,
                ]);

                // Otomatis terapkan batasan ke transaksi bank terkait jika melebihi limit baru
                $bank->ekuTransactions()->get()->each(function ($transaksi) {
                    $transaksi->terapkanBatasanBank();
                });
            }
        }
    }
}
