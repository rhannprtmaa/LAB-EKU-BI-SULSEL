<?php

namespace App\Imports;

use App\Models\EkuTransaction;
use App\Models\EkuTransactionRealisasi;
use App\Models\EkuTransactionRealisasiDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class RealisasiMassalSheetImport implements ToCollection, WithStartRow
{
    protected string|int $bulan;
    protected string|int $tahun;
    protected string $jenisFile;

    public function __construct(string|int $bulan, string|int $tahun, string $jenisFile)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->jenisFile = $jenisFile;
    }

    public function startRow(): int
    {
        return 4; // Data dimulai dari baris ke-4
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $bankId = $row[0] ?? null; // Kolom A (Index 0)
            if (!$bankId) continue;

            $totalUpb = 0;
            $totalUpk = 0;

            // Membaca Tanggal 1 s/d 31 ke samping
            $colIndex = 2; // Mulai dari Kolom C (Index 2)
            for ($d = 1; $d <= 31; $d++) {
                $upbVal = isset($row[$colIndex]) ? (float) $row[$colIndex] : 0;
                $upkVal = isset($row[$colIndex + 1]) ? (float) $row[$colIndex + 1] : 0;

                $totalUpb += $upbVal;
                $totalUpk += $upkVal;

                $colIndex += 2;
            }

            $subtotal = $totalUpb + $totalUpk;

            // Simpan ke DB hanya jika ada nilai transaksi
            if ($subtotal > 0) {
                $transaction = EkuTransaction::where('bank_id', $bankId)
                    ->where('periode', $this->tahun)
                    ->first();

                if ($transaction) {
                    $realisasi = EkuTransactionRealisasi::firstOrCreate(
                        ['eku_transaction_id' => $transaction->id],
                        ['input_at' => now()]
                    );

                    EkuTransactionRealisasiDetail::updateOrCreate(
                        [
                            'eku_transaction_realisasi_id' => $realisasi->id,
                            'bulan' => $this->bulan,
                            'jenis_file' => $this->jenisFile,
                        ],
                        [
                            'total_upb' => $totalUpb,
                            'total_upk' => $totalUpk,
                            'subtotal' => $subtotal,
                        ]
                    );
                }
            }
        }
    }
}
