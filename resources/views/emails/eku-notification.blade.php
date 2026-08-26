<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <title>{{ $judul }}</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #EFEBE2;
            font-family: Arial, Helvetica, sans-serif;
            color: #1F2937;
            -webkit-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
        }

        img {
            border: 0;
            display: block;
        }

        .email-wrapper {
            width: 100%;
            background-color: #EFEBE2;
            padding: 40px 16px;
        }

        .email-container {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 42, 74, 0.08), 0 12px 32px rgba(15, 42, 74, 0.10);
        }

        /* ===== LETTERHEAD HEADER ===== */
        .header {
            background-color: #0F2A4A;
            background-image: linear-gradient(135deg, #0F2A4A 0%, #16385E 100%);
            padding: 30px 36px 26px;
        }

        .seal {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 1.5px solid #C4A968;
            text-align: center;
            vertical-align: middle;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 15px;
            font-weight: bold;
            color: #D4C08A;
            letter-spacing: 0.5px;
        }

        .brand-block {
            padding-left: 14px;
        }

        .brand {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.6px;
            color: #ffffff;
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 11px;
            letter-spacing: 0.3px;
            color: #A9BAD0;
        }

        .accent {
            height: 3px;
            background-color: #B49A5A;
            background-image: linear-gradient(90deg, #8C7440 0%, #D4C08A 50%, #8C7440 100%);
            width: 100%;
            font-size: 0;
            line-height: 0;
        }

        /* ===== CONTENT ===== */
        .content {
            padding: 38px 36px 30px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            background-color: #F4F0E5;
            border: 1px solid #DCCEA3;
            color: #8C7440;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.6px;
            margin-bottom: 20px;
        }

        .title {
            margin: 0 0 22px;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 25px;
            line-height: 1.35;
            color: #0F2A4A;
        }

        .greeting {
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 14px;
            color: #1F2937;
        }

        .message {
            font-size: 15px;
            line-height: 1.8;
            color: #4b5563;
            margin-bottom: 26px;
        }

        .info-box {
            border: 1px solid #E5E1D8;
            border-left: 3px solid #B49A5A;
            border-radius: 4px;
            padding: 4px 20px;
            margin: 26px 0;
        }

        .info-row td {
            padding: 12px 0;
            font-size: 14px;
            border-bottom: 1px solid #F0EEE8;
        }

        .info-row.last td {
            border-bottom: none;
        }

        .info-label {
            color: #8A93A0;
            width: 38%;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-size: 11px;
        }

        .info-value {
            color: #0F2A4A;
            font-weight: bold;
            text-align: right;
        }

        .button-wrapper {
            text-align: center;
            padding: 8px 0 16px;
        }

        .button {
            display: inline-block;
            background-color: #0F2A4A;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.3px;
            padding: 14px 32px;
            border-radius: 4px;
        }

        .notice {
            margin-top: 28px;
            padding: 14px 18px;
            background-color: #F9F8F5;
            border-radius: 4px;
            font-size: 12px;
            line-height: 1.6;
            color: #8A93A0;
            text-align: center;
        }

        /* ===== FOOTER ===== */
        .footer {
            background-color: #0F2A4A;
            padding: 28px 36px;
            text-align: center;
        }

        .footer-seal {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 11px;
            letter-spacing: 2px;
            color: #3D5578;
            margin-bottom: 10px;
        }

        .footer-title {
            font-size: 12px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
        }

        .footer-text {
            font-size: 11px;
            line-height: 1.7;
            color: #8296B3;
        }

        .footer-url {
            margin-top: 12px;
            font-size: 10px;
            color: #5D7392;
            word-break: break-all;
        }

        .footer-url a {
            color: #C4A968;
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 8px;
            }

            .header {
                padding: 24px 22px 20px;
            }

            .content,
            .footer {
                padding-left: 22px;
                padding-right: 22px;
            }

            .title {
                font-size: 21px;
            }

            .info-label {
                width: 44%;
                font-size: 10px;
            }
        }
    </style>
</head>

<body>

<table role="presentation" width="100%">
    <tr>
        <td>

            <div class="email-wrapper">

                <table
                    role="presentation"
                    class="email-container"
                    width="100%"
                    align="center"
                >

                    {{-- HEADER / LETTERHEAD --}}
                    <tr>
                        <td class="header">
                            <table role="presentation" width="100%">
                                <tr>
                                    {{--
                                        Monogram seal below is drawn purely in CSS so it always
                                        renders even when remote images are blocked. Swap it for
                                        your official logo by replacing this <td> with:
                                        <img src="{{ asset('images/logo-bi.png') }}" width="46" height="46" alt="Bank Indonesia">
                                    --}}
                                    <td width="46" class="seal">
                                        <table role="presentation" width="46" height="46">
                                            <tr><td align="center" valign="middle">BI</td></tr>
                                        </table>
                                    </td>
                                    <td class="brand-block">
                                        <div class="brand">BANK INDONESIA</div>
                                        <div class="brand-subtitle">Sistem LAB EKU BI Sulsel</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ACCENT --}}
                    <tr>
                        <td>
                            <div class="accent">&nbsp;</div>
                        </td>
                    </tr>

                    {{-- CONTENT --}}
                    <tr>
                        <td class="content">

                            <div class="badge">
                                NOTIFIKASI SISTEM
                            </div>

                            <h1 class="title">
                                {{ $judul }}
                            </h1>

                            <div class="greeting">
                                Halo, <strong>{{ $namaPenerima }}</strong>!
                            </div>

                            <div class="message">
                                {{ $pesan }}
                            </div>

                            @if ($bank)
                                <table
                                    role="presentation"
                                    width="100%"
                                    class="info-box"
                                >
                                    <tr class="info-row last">
                                        <td class="info-label">Bank</td>
                                        <td class="info-value">{{ $bank }}</td>
                                    </tr>
                                </table>
                            @endif

                            @if ($url)
                                <div class="button-wrapper">
                                    <a href="{{ $url }}" class="button">
                                        {{ $emailAction }}
                                    </a>
                                </div>
                            @endif

                            <div class="notice">
                                Email ini dikirim secara otomatis oleh <strong>Sistem LAB EKU BI Sulsel</strong>.
                                Mohon tidak membalas email ini.
                            </div>

                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td class="footer">
                            <div class="footer-seal">• BI •</div>
                            <div class="footer-title">Sistem LAB EKU BI Sulsel</div>
                            <div class="footer-text">Bank Indonesia — Provinsi Sulawesi Selatan</div>
                            <div class="footer-text">
                                Email ini merupakan pemberitahuan otomatis dari sistem dan tidak memerlukan balasan.
                            </div>

                            @if ($url)
                                <div class="footer-url">
                                    Jika tombol di atas tidak dapat digunakan, silakan akses:<br>
                                    <a href="{{ $url }}">{{ $url }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>

                </table>

            </div>

        </td>
    </tr>
</table>

</body>
</html>
