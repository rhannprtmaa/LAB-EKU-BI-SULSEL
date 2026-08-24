<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Realisasi EKU</title>
    <style>
        @page { margin: 90px 30px 55px 30px; }

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
            bottom: -45px;
            left: 0;
            right: 0;
            height: 35px;
            border-top: 0.75px solid #d1d5db;
            padding-top: 6px;
            font-size: 7.5px;
            color: #6b7280;
        }

        #nomor-halaman:after {
            content: "Halaman " counter(page) " dari " counter(pages);
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 14px;
        }

        table.info td {
            padding: 3px 6px;
            font-size: 9px;
            vertical-align: top;
        }

        table.info td.label {
            width: 110px;
            color: #6b7280;
        }

        table.info td.nilai {
            font-weight: bold;
            color: #111827;
        }

        table.rincian {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.rincian th,
        table.rincian td {
            border: 0.75px solid #cbd5e1;
            padding: 5px 6px;
        }

        table.rincian thead th {
            background-color: #054177;
            color: #ffffff;
            font-size: 8.5px;
            text-align: center;
        }

        table.rincian tbody td {
            text-align: right;
            font-size: 8.5px;
        }

        table.rincian tbody td.text-left {
            text-align: left;
        }

        table.rincian tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.rincian tfoot td {
            font-weight: bold;
            background-color: #e2e8f0;
        }

        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #054177;
            margin: 16px 0 4px 0;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        .ringkasan-box {
            width: 100%;
            margin-top: 4px;
        }

        .ringkasan-box td {
            width: 33%;
            padding: 8px 10px;
            border: 0.75px solid #cbd5e1;
            text-align: center;
        }

        .ringkasan-box .label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .ringkasan-box .nilai {
            font-size: 12px;
            font-weight: bold;
            color: #054177;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <header>
        <p class="judul">Detail Input Realisasi EKU</p>
        <p class="subjudul">
            Bank Indonesia Provinsi Sulawesi Selatan &middot;
            Dicetak: {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WITA
        </p>
    </header>

    <footer>
        Dokumen ini bersifat RAHASIA -- hanya untuk kepentingan internal Bank Indonesia dan bank terkait.
        <span id="nomor-halaman"></span>
    </footer>

    <table class="info">
        <tr>
            <td class="label">Bank</td>
            <td class="nilai">{{ $bankName }}</td>
            <td class="label">Tanggal Input</td>
            <td class="nilai">{{ $tanggalInput }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td class="nilai">{{ $periode }}</td>
            <td class="label">Diinput oleh</td>
            <td class="nilai">{{ $diinputOleh }}</td>
        </tr>
        @if ($keterangan)
            <tr>
                <td class="label">Keterangan</td>
                <td class="nilai" colspan="3">{{ $keterangan }}</td>
            </tr>
        @endif
    </table>

    <table class="ringkasan-box">
        <tr>
            <td>
                <div class="label">Total Setoran</div>
                <div class="nilai">{{ $rupiah($totalSetoran) }}</div>
            </td>
            <td>
                <div class="label">Total Penarikan</div>
                <div class="nilai">{{ $rupiah($totalPenarikan) }}</div>
            </td>
            <td>
                <div class="label">Grand Total</div>
                <div class="nilai">{{ $rupiah($totalSetoran + $totalPenarikan) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Rincian per Bulan</div>

    <table class="rincian">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Bulan</th>
                <th style="width: 15%;">Jenis</th>
                <th>UPB</th>
                <th>UPK</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rincian as $i => $baris)
                <tr>
                    <td class="text-left" style="text-align:center;">{{ $i + 1 }}</td>
                    <td class="text-left">{{ $baris['bulan'] }}</td>
                    <td class="text-left">{{ $baris['jenis_file'] }}</td>
                    <td>{{ $rupiah($baris['upb']) }}</td>
                    <td>{{ $rupiah($baris['upk']) }}</td>
                    <td><strong>{{ $rupiah($baris['subtotal']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Belum ada rincian data untuk input realisasi ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rincian) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:center;">TOTAL</td>
                    <td>{{ $rupiah(collect($rincian)->sum('upb')) }}</td>
                    <td>{{ $rupiah(collect($rincian)->sum('upk')) }}</td>
                    <td>{{ $rupiah(collect($rincian)->sum('subtotal')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
