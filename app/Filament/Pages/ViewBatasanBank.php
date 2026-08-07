<?php

namespace App\Filament\Pages;

use App\Models\Bank;
use App\Imports\EkuExcelImport;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ViewBatasanBank extends Page
{
    // Menggunakan slug statis agar aman dari RouteNotFoundException
    protected static ?string $slug = 'view-batasan-bank';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.view-batasan-bank';

    public ?Bank $bank = null;

    // Variabel array tunggal untuk menggabungkan Setoran dan Penarikan
    public array $rincian = [];

    public function mount(): void
    {
        // Tangkap parameter ID dari Query String (?record=...)
        $recordId = request()->query('record');

        if (!$recordId) {
            abort(404, 'Data Bank tidak ditemukan.');
        }

        $this->bank = Bank::findOrFail($recordId);

        $setoran = [];
        $penarikan = [];

        // Parsing file batasan Setoran
        if ($this->bank->file_batasan_setoran) {
            $setoran = $this->parseExcel($this->bank->file_batasan_setoran, 'Setoran');
        }

        // Parsing file batasan Penarikan
        if ($this->bank->file_batasan_penarikan) {
            $penarikan = $this->parseExcel($this->bank->file_batasan_penarikan, 'Penarikan');
        }

        // Gabungkan Setoran dan Penarikan ke dalam satu array
        $this->rincian = array_merge($setoran, $penarikan);
    }

    /**
     * Ringkasan Total dari batasan (Setoran + Penarikan), sama persis
     * perhitungannya (dan tampilannya) dengan "Rincian Proyeksi EKU
     * Bulanan" punya pengajuan EKU -- cuma sumber datanya beda: di sini
     * dari hasil parsing file batasan ($this->rincian), bukan dari
     * database eku_transaction_details.
     */
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

    /**
     * Ditambahkan parameter $jenis untuk menandai baris (Setoran/Penarikan)
     */
    private function parseExcel(?string $filePath, string $jenis): array
    {
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return [];
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $arrayData = Excel::toArray(new EkuExcelImport(), $fullPath);

        if (empty($arrayData) || empty($arrayData[0])) {
            return [];
        }

        $sheet = $arrayData[0];
        $multiplier = 1000000;

        $clean = fn($val) => is_numeric($val)
            ? (float) $val
            : (float) str_replace(['.', ',', ' '], '', (string) $val);

        $kolomBulan = [
            3 => 'Januari', 4 => 'Februari', 5 => 'Maret', 6 => 'April',
            7 => 'Mei', 8 => 'Juni', 9 => 'Juli', 10 => 'Agustus',
            11 => 'September', 12 => 'Oktober', 13 => 'November', 14 => 'Desember',
        ];

        // Format Key Persis Seperti Model Anda
        $petaKertas = [
            100000 => 'kertas_100k', 50000 => 'kertas_50k', 20000 => 'kertas_20k',
            10000 => 'kertas_10k', 5000 => 'kertas_5k', 2000 => 'kertas_2k', 1000 => 'kertas_1k',
        ];
        $petaLogam = [
            1000 => 'logam_1k', 500 => 'logam_500', 200 => 'logam_200', 100 => 'logam_100',
        ];

        $akumulasi = [];
        foreach ($kolomBulan as $namaBulan) {
            $akumulasi[$namaBulan] = [
                'bulan' => $namaBulan,
                'jenis' => $jenis, // <-- Menyematkan jenis transaksi
                'kertas_100k' => 0, 'kertas_50k' => 0, 'kertas_20k' => 0, 'kertas_10k' => 0,
                'kertas_5k' => 0, 'kertas_2k' => 0, 'kertas_1k' => 0,
                'logam_1k' => 0, 'logam_500' => 0, 'logam_200' => 0, 'logam_100' => 0,
            ];
        }

        $section = null;

        foreach ($sheet as $row) {
            $jenisUang = strtoupper(trim((string) ($row[1] ?? '')));
            $nominalRaw = $row[2] ?? null;

            if (str_contains($jenisUang, 'UANG KERTAS')) {
                $section = 'kertas';
            } elseif (str_contains($jenisUang, 'UANG LOGAM')) {
                $section = 'logam';
            }

            if (str_contains($jenisUang, 'TOTAL') || (is_string($nominalRaw) && str_contains(strtoupper($nominalRaw), 'TOTAL'))) {
                continue;
            }

            if (! is_numeric($nominalRaw) || ! $section) {
                continue;
            }

            $nominal = (int) $nominalRaw;
            $namaKolom = $section === 'kertas' ? ($petaKertas[$nominal] ?? null) : ($petaLogam[$nominal] ?? null);

            if (! $namaKolom) {
                continue;
            }

            foreach ($kolomBulan as $colIdx => $namaBulan) {
                $akumulasi[$namaBulan][$namaKolom] += $clean($row[$colIdx] ?? 0) * $multiplier;
            }
        }

        return array_values($akumulasi);
    }
}
