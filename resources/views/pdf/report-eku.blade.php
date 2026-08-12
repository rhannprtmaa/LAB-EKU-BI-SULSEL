<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan EKU</title>
    <style>
        /* DomPDF hanya mendukung subset CSS 2.1 -- hindari flexbox/grid,
           pakai table murni & properti dasar saja. */
        @page { margin: 90px 30px 60px 30px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1f2937;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 1.5px solid #054177;
            padding-bottom: 8px;
        }

        header .judul {
            font-size: 14px;
            font-weight: bold;
            color: #054177;
            margin: 0;
        }

        header .subjudul {
            font-size: 9px;
            color: #4b5563;
            margin: 2px 0 0 0;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 0.75px solid #d1d5db;
            padding-top: 6px;
            font-size: 7.5px;
            color: #6b7280;
        }

        /* Nomor halaman otomatis -- dompdf mendukung counter(page)/counter(pages)
           secara native lewat CSS, tanpa perlu mengaktifkan PHP-embedded-in-PDF. */
        #nomor-halaman:after {
            content: "Halaman " counter(page) " dari " counter(pages);
        }

        table.laporan {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.laporan th,
        table.laporan td {
            border: 0.75px solid #cbd5e1;
            padding: 4px 5px;
        }

        table.laporan thead th {
            background-color: #054177;
            color: #ffffff;
            font-size: 8px;
            text-align: center;
            vertical-align: middle;
        }

        table.laporan tbody td {
            text-align: right;
            font-size: 8px;
        }

        table.laporan tbody td.text-left {
            text-align: left;
        }

        table.laporan tbody td.text-center {
            text-align: center;
        }

        table.laporan tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .deviasi-kurang { color: #b45309; font-weight: bold; } /* realisasi < pengajuan */
        .deviasi-over { color: #b91c1c; font-weight: bold; }   /* realisasi > pengajuan */
        .deviasi-sesuai { color: #15803d; font-weight: bold; }

        tfoot td {
            font-weight: bold;
            background-color: #e2e8f0;
        }

        .meta-info {
            margin-bottom: 4px;
            font-size: 8.5px;
            color: #374151;
        }
    </style>
</head>
<body>
    <header>
        <p class="judul">Laporan Estimasi Kebutuhan Uang (EKU)</p>
        <p class="subjudul">
            Bank Indonesia Provinsi Sulawesi Selatan &middot;
            Dicetak: {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WITA
        </p>
    </header>

    <footer>
        Dokumen ini bersifat RAHASIA -- hanya untuk kepentingan internal Bank Indonesia dan bank terkait.
        <span id="nomor-halaman"></span>
    </footer>

    <div class="meta-info">
        Total baris: {{ $rows->count() }}
        @if ($bankName)
            &middot; Bank: {{ $bankName }}
        @endif
    </div>

    <table class="laporan">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%;">No</th>
                <th rowspan="2" style="width: 12%;">Bank</th>
                <th rowspan="2" style="width: 6%;">Periode</th>
                <th colspan="3">Setoran</th>
                <th colspan="3">Penarikan</th>
                <th rowspan="2" style="width: 9%;">Grand Total</th>
                <th colspan="2">Realisasi (YTD)</th>
                <th colspan="2">Deviasi (YTD)</th>
            </tr>
            <tr>
                <th>Total UPB</th>
                <th>Total UPK</th>
                <th>Total Setoran</th>
                <th>Total UPB</th>
                <th>Total UPK</th>
                <th>Total Penarikan</th>
                <th>Setoran</th>
                <th>Penarikan</th>
                <th>Setoran</th>
                <th>Penarikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-left">{{ $r['bank'] }}</td>
                    <td class="text-center">{{ $r['periode'] }}</td>
                    <td>{{ $rupiah($r['setoranUpb']) }}</td>
                    <td>{{ $rupiah($r['setoranUpk']) }}</td>
                    <td><strong>{{ $rupiah($r['setoranTotal']) }}</strong></td>
                    <td>{{ $rupiah($r['penarikanUpb']) }}</td>
                    <td>{{ $rupiah($r['penarikanUpk']) }}</td>
                    <td><strong>{{ $rupiah($r['penarikanTotal']) }}</strong></td>
                    <td><strong>{{ $rupiah($r['grandTotal']) }}</strong></td>
                    <td>{{ $rupiah($r['realisasiSetoran']) }}</td>
                    <td>{{ $rupiah($r['realisasiPenarikan']) }}</td>
                    <td class="{{ $kelasDeviasi($r['deviasiSetoran']) }}">{{ $rupiah($r['deviasiSetoran']) }}</td>
                    <td class="{{ $kelasDeviasi($r['deviasiPenarikan']) }}">{{ $rupiah($r['deviasiPenarikan']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">Tidak ada data untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" class="text-center">TOTAL</td>
                    <td>{{ $rupiah($rows->sum('setoranUpb')) }}</td>
                    <td>{{ $rupiah($rows->sum('setoranUpk')) }}</td>
                    <td>{{ $rupiah($rows->sum('setoranTotal')) }}</td>
                    <td>{{ $rupiah($rows->sum('penarikanUpb')) }}</td>
                    <td>{{ $rupiah($rows->sum('penarikanUpk')) }}</td>
                    <td>{{ $rupiah($rows->sum('penarikanTotal')) }}</td>
                    <td>{{ $rupiah($rows->sum('grandTotal')) }}</td>
                    <td>{{ $rupiah($rows->sum('realisasiSetoran')) }}</td>
                    <td>{{ $rupiah($rows->sum('realisasiPenarikan')) }}</td>
                    <td>{{ $rupiah($rows->sum('deviasiSetoran')) }}</td>
                    <td>{{ $rupiah($rows->sum('deviasiPenarikan')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <p style="margin-top: 10px; font-size: 7.5px; color: #6b7280;">
        Keterangan: UPB = Uang Pecahan Besar (Rp100.000 &amp; Rp50.000) &middot;
        UPK = Uang Pecahan Kecil (Rp20.000 s.d Rp1.000, kertas) &middot;
        Logam = Rp1.000 s.d Rp100 (uang logam).
    </p>
</body>
</html>
