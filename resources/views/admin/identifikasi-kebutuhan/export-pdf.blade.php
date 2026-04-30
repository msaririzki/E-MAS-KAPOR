<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Identifikasi Kebutuhan Kapor TA {{ $fiscalYear }}</title>
    <style>
        @page { margin: 10mm; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.5pt;
            color: #000;
            margin: 0;
        }
        .kop {
            width: 310px;
            text-align: center;
            font-weight: bold;
            line-height: 1.35;
            font-size: 10pt;
            margin-bottom: 4px;
        }
        .kop-line {
            width: 310px;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-bottom: 16px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            line-height: 1.5;
            text-decoration: underline;
            margin-bottom: 14px;
        }
        .meta {
            margin-bottom: 8px;
            font-size: 8pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
        }
        th {
            background: #e5e7eb;
            text-align: center;
            font-weight: bold;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .category-row td {
            background: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
        }
        .item-title .main-cell {
            font-weight: bold;
        }
        .segment-bar {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #9ca3af;
            table-layout: fixed;
        }
        .segment-bar td {
            border: none !important;
            padding: 0 !important;
            height: 14px !important;
            line-height: 14px !important;
            border-right: 1px solid #fff !important;
        }
        .segment-bar td:last-child { border-right: none !important; }
        .scale-lbl {
            height: auto !important;
            line-height: 1 !important;
            font-size: 6pt !important;
            color: #555 !important;
            border: none !important;
            background: transparent !important;
            padding: 2px 0 0 0 !important;
        }
        .score-cell {
            width: 62px;
            text-align: center;
            font-weight: bold;
        }
        .count-cell {
            width: 90px;
            text-align: center;
            font-weight: bold;
        }
        .muted { color: #555; }
        .section-title {
            margin-top: 14px;
            margin-bottom: 6px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="kop">
        KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
        DAERAH NUSA TENGGARA BARAT<br>
        BIRO LOGISTIK
    </div>
    <div class="kop-line"></div>

    <div class="title">
        HASIL IDENTIFIKASI KEBUTUHAN KAPOR POLRI DAN PNS POLDA NTB T.A. {{ $fiscalYear }}
    </div>

    <div class="meta">
        Total satker: <strong>{{ number_format($totalSatkers) }}</strong>
        &nbsp;|&nbsp;
        Satker mengajukan: <strong>{{ number_format($submittedSatkers) }}</strong>
        &nbsp;|&nbsp;
        Total item terpilih: <strong>{{ number_format($totalItems) }}</strong>
        &nbsp;|&nbsp;
        Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">NO</th>
                <th class="text-left" style="width: 30%;">ITEM</th>
                <th>GRAFIK PERSENTASE</th>
                <th style="width: 90px;">SATKER MEMILIH</th>
                <th style="width: 62px;">NILAI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categoryGroups as $category)
                <tr class="category-row">
                    <td colspan="5">{{ $category['name'] }}</td>
                </tr>
                @foreach($category['items'] as $item)
                    <tr class="item-title">
                        <td class="text-center main-cell">{{ $loop->iteration }}</td>
                        <td class="text-left main-cell">{{ $item['name'] }}</td>
                        <td class="main-cell">
                            @php($score = min(100, max(0, $item['percentage'])))
                            <table class="segment-bar">
                                <tr>
                                    @php($filledSegments = (int) round($score / 2.5))
                                    @for($segment = 1; $segment <= 40; $segment++)
                                        <td style="width: 2.5%; background:{{ $segment <= $filledSegments ? '#2563eb' : '#e5e7eb' }};"></td>
                                    @endfor
                                </tr>
                                <tr>
                                    <td colspan="10" class="scale-lbl" style="padding: 2px 0 0 0 !important;">
                                        <span style="float: left;">0</span>
                                        <span style="float: right;">25</span>
                                    </td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">50</td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">75</td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">100</td>
                                </tr>
                            </table>
                        </td>
                        <td class="count-cell">{{ number_format($item['satker_count']) }} / {{ number_format($totalSatkers) }}</td>
                        <td class="score-cell">{{ number_format($item['percentage']) }}%</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada data identifikasi kebutuhan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
