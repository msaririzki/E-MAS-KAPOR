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
            width: 360px;
            text-align: center;
            font-weight: bold;
            line-height: 1.35;
            font-size: 10pt;
            margin: 0 0 4px 0;
            white-space: nowrap;
        }
        .kop-line {
            width: 360px;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin: 0 0 16px 0;
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
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-table td {
            border: none;
            padding: 0;
        }
        .signature-box {
            width: 330px;
            text-align: center;
            font-size: 9pt;
            line-height: 1.5;
        }
        .signature-title {
            font-weight: bold;
        }
        .signature-space {
            height: 58px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
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

    <table style="table-layout: fixed;">
        <thead>
            <tr style="height: 0; line-height: 0;">
                <td style="width: 3%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 25%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 54%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 12%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 6%; border: none !important; padding: 0 !important;"></td>
            </tr>
            <tr>
                <th>NO</th>
                <th class="text-left">ITEM</th>
                <th>GRAFIK PERSENTASE</th>
                <th>SATKER MEMILIH</th>
                <th>NILAI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categoryGroups as $category)
                <tr class="category-row">
                    <td colspan="5">{{ $category['name'] }}</td>
                </tr>
                @foreach($category['items'] as $item)
                    @php $hasSatkers = isset($item['satkers']) && count($item['satkers']) > 0; @endphp
                    <tr class="item-title">
                        <td class="text-center main-cell" {!! $hasSatkers ? 'style="border-bottom: none;"' : '' !!}>{{ $loop->iteration }}</td>
                        <td class="text-left main-cell" {!! $hasSatkers ? 'style="border-bottom: none;"' : '' !!}>{{ $item['name'] }}</td>
                        <td class="main-cell" {!! $hasSatkers ? 'style="border-bottom: none;"' : '' !!}>
                            @php
                                $score = min(100, max(0, $item['percentage']));
                            @endphp
                            <table class="segment-bar">
                                <tr>
                                    @php
                                        $filledSegments = (int) round($score / 2.5);
                                    @endphp
                                    @for($segment = 1; $segment <= 40; $segment++)
                                        <td style="width: 2.5%; background:{{ $segment <= $filledSegments ? '#2563eb' : '#e5e7eb' }};"></td>
                                    @endfor
                                </tr>
                                <tr>
                                    <td colspan="5" class="scale-lbl" style="text-align: left; padding: 2px 0 0 0 !important;">0</td>
                                    <td colspan="5" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">25</td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">50</td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">75</td>
                                    <td colspan="10" class="scale-lbl" style="text-align: right; padding: 2px 0 0 0 !important;">100</td>
                                </tr>
                            </table>
                        </td>
                        <td class="count-cell" {!! $hasSatkers ? 'style="border-bottom: none;"' : '' !!}>{{ number_format($item['satker_count']) }} / {{ number_format($item['eligible_count']) }}</td>
                        <td class="score-cell" {!! $hasSatkers ? 'style="border-bottom: none;"' : '' !!}>{{ number_format($item['percentage']) }}%</td>
                    </tr>
                    @if($hasSatkers)
                        <tr>
                            <td style="border-top: none;"></td>
                            <td colspan="2" style="border-top: none; padding: 2px 5px 8px 5px;">
                                <div style="font-size: 7pt; color: #000; line-height: 1.4; text-align: justify;">
                                    <span style="font-weight: bold; color: #000;">Daftar Satker Memilih:</span> {{ implode(', ', $item['satkers']) }}
                                </div>
                            </td>
                            <td style="border-top: none;"></td>
                            <td style="border-top: none;"></td>
                        </tr>
                    @endif
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center">Belum ada data identifikasi kebutuhan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $signatorySettings = $signatorySettings ?? [];
        $location = $signatorySettings['location'] ?? 'Mataram';
        $organizationName = strtoupper($signatorySettings['organization_name'] ?? 'KEPALA BIRO LOGISTIK POLDA NTB');
        $signatoryTitle = strtoupper($signatorySettings['signatory_title'] ?? 'PEJABAT PEMBUAT KOMITMEN');
        $signatoryName = strtoupper($signatorySettings['signatory_name'] ?? '.............................');
        $signatoryRank = strtoupper($signatorySettings['signatory_rank'] ?? '');
        $signatoryNrp = $signatorySettings['signatory_nrp'] ?? '';
    @endphp

    <table class="signature-table">
        <tr>
            <td style="width: 58%;"></td>
            <td style="width: 42%;" align="center">
                <div class="signature-box">
                    <div>{{ $location }}, {{ $generatedAt->translatedFormat('d F Y') }}</div>
                    @if($organizationName)
                        <div>a.n. {{ $organizationName }}</div>
                    @endif
                    <div class="signature-title">{{ $signatoryTitle }}</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $signatoryName }}</div>
                    <div>
                        @if($signatoryRank)
                            {{ $signatoryRank }}
                        @endif
                        @if($signatoryNrp)
                            NRP/NIP {{ $signatoryNrp }}
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
