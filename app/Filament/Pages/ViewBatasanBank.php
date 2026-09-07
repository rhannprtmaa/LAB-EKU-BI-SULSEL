<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Support\EkuExcelParser;
use Filament\Pages\Page;

class ViewBatasanBank extends Page
{
    protected static ?string $slug = 'view-batasan-bank';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.view-batasan-bank';

    public ?Bank $bank = null;

    public array $rincian = [];

    public function mount(): void
    {
        $recordId = request()->query('record');

        if (!$recordId) {
            abort(404, 'Data Bank tidak ditemukan.');
        }

        $this->bank = Bank::findOrFail($recordId);

        $setoran = $this->bank->file_batasan_setoran
            ? array_values(EkuExcelParser::rincianDariFile($this->bank->file_batasan_setoran, 'Setoran'))
            : [];

        $penarikan = $this->bank->file_batasan_penarikan
            ? array_values(EkuExcelParser::rincianDariFile($this->bank->file_batasan_penarikan, 'Penarikan'))
            : [];

        $this->rincian = array_merge($setoran, $penarikan);
    }

    public function getRingkasan(): array
    {
        $totalSetoran = 0.0;
        $totalPenarikan = 0.0;
        $totalUK = 0.0;
        $totalUL = 0.0;
        $totalUPB = 0.0;

        $pecahanKeys = [
            'kertas_100k', 'kertas_50k', 'kertas_20k', 'kertas_10k', 'kertas_5k',
            'kertas_2k', 'kertas_1k', 'logam_1k', 'logam_500', 'logam_200', 'logam_100',
        ];

        $totalPerPecahan = array_fill_keys($pecahanKeys, 0.0);

        foreach ($this->rincian as $row) {
            $subtotal = $row['kertas_100k'] + $row['kertas_50k'] + $row['kertas_20k']
                + $row['kertas_10k'] + $row['kertas_5k'] + $row['kertas_2k'] + $row['kertas_1k']
                + $row['logam_1k'] + $row['logam_500'] + $row['logam_200'] + $row['logam_100'];

            if ($row['jenis'] === 'Setoran') {
                $totalSetoran += $subtotal;
            } else {
                $totalPenarikan += $subtotal;
            }

            $totalUK += $row['kertas_100k'] + $row['kertas_50k'] + $row['kertas_20k']
                + $row['kertas_10k'] + $row['kertas_5k'] + $row['kertas_2k'] + $row['kertas_1k'];

            $totalUL += $row['logam_1k'] + $row['logam_500'] + $row['logam_200'] + $row['logam_100'];

            $totalUPB += $row['kertas_100k'] + $row['kertas_50k'];

            foreach ($pecahanKeys as $pecahan) {
                $totalPerPecahan[$pecahan] += $row[$pecahan] ?? 0;
            }
        }

        $grandTotal = $totalSetoran + $totalPenarikan;
        $totalUPK = $grandTotal - $totalUPB;

        return [
            'totalSetoran' => $totalSetoran,
            'totalPenarikan' => $totalPenarikan,
            'totalUK' => $totalUK,
            'totalUL' => $totalUL,
            'totalUPB' => $totalUPB,
            'totalUPK' => $totalUPK,
            'grandTotal' => $grandTotal,
            'totalPerPecahan' => $totalPerPecahan,
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Batasan EKU - ' . ($this->bank->name ?? '');
    }
}
