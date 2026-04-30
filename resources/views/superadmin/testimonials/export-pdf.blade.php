<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Review Kapor TA {{ $fiscalYear }}</title>
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
        .section-title {
            margin-top: 14px;
            margin-bottom: 6px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            page-break-after: avoid;
        }
        .satker-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
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
        .muted { color: #555; }
        .review-table th {
            font-size: 8pt;
        }
        .review-table td {
            line-height: 1.45;
            vertical-align: top;
            padding: 8px 10px;
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
        HASIL REVIEW PENGADAAN KAPOR POLRI DAN PNS POLDA NTB T.A. {{ $fiscalYear }}
    </div>

    <div class="meta">
        Total data testimoni: <strong>{{ number_format($totalReviews) }}</strong>
        &nbsp;|&nbsp;
        Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}
    </div>

    <table style="table-layout: fixed;">
        <thead>
            <tr style="height: 0; line-height: 0;">
                <td style="width: 4%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 34%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 54%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 8%; border: none !important; padding: 0 !important;"></td>
            </tr>
            <tr>
                <th colspan="4" style="background: transparent; border: none; text-align: left; padding: 0; padding-bottom: 6px;">
                    <div class="section-title" style="margin-top: 0; margin-bottom: 0;">Persentase Per Item</div>
                </th>
            </tr>
            <tr>
                <th>NO</th>
                <th class="text-left">ITEM</th>
                <th>GRAFIK PERSENTASE</th>
                <th>NILAI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categoryGroups as $category)
                <tr class="category-row">
                    <td colspan="4">{{ $category['name'] }}</td>
                </tr>
                @foreach($category['items'] as $item)
                    <tr class="item-title">
                        <td class="text-center main-cell">{{ $loop->iteration }}</td>
                        <td class="text-left main-cell">{{ $item['name'] }}</td>
                        <td class="main-cell">
                            @if($item['overall_score'] !== null)
                                @php($score = min(100, max(0, $item['overall_score'])))
                                <table class="segment-bar">
                                    <tr>
                                        @php($filledSegments = (int) round($score / 2.5))
                                        @for($segment = 1; $segment <= 40; $segment++)
                                            <td style="width: 2.5%; background:{{ $segment <= $filledSegments ? '#2563eb' : '#e5e7eb' }};"></td>
                                        @endfor
                                    </tr>
                                    <tr>
                                        @for($i = 1; $i <= 40; $i++)
                                            <td class="scale-lbl" style="text-align: {{ $i == 1 ? 'left' : 'right' }}; padding: 2px 0 0 0 !important; overflow: visible;">
                                                @if($i == 1)
                                                    <span style="white-space: nowrap; display: inline-block;">0</span>
                                                @elseif(in_array($i, [10, 20, 30, 40]))
                                                    <span style="white-space: nowrap; display: inline-block;">{{ $i * 2.5 }}</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                </table>
                            @else
                                <span class="muted">Belum ada nilai</span>
                            @endif
                        </td>
                        <td class="score-cell">
                            {{ $item['overall_score'] !== null ? number_format($item['overall_score'], 1) . '%' : '-' }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada data testimoni untuk tahun anggaran ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="margin-top: 14px; table-layout: fixed;">
        <thead>
            <tr style="height: 0; line-height: 0;">
                <td style="width: 38%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 54%; border: none !important; padding: 0 !important;"></td>
                <td style="width: 8%; border: none !important; padding: 0 !important;"></td>
            </tr>
            <tr>
                <th colspan="3" style="background: transparent; border: none; text-align: left; padding: 0; padding-bottom: 6px;">
                    <div class="section-title" style="margin-top: 0; margin-bottom: 0;">Ringkasan Persentase Per Kategori</div>
                </th>
            </tr>
            <tr>
                <th class="text-left">KATEGORI</th>
                <th>GRAFIK PERSENTASE</th>
                <th>NILAI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categorySummaries as $category)
                <tr>
                    <td class="text-left">{{ $category['name'] }}</td>
                    <td>
                        @if($category['overall_score'] !== null)
                            @php($score = min(100, max(0, $category['overall_score'])))
                            <table class="segment-bar">
                                <tr>
                                    @php($filledSegments = (int) round($score / 2.5))
                                    @for($segment = 1; $segment <= 40; $segment++)
                                        <td style="width: 2.5%; background:{{ $segment <= $filledSegments ? '#2563eb' : '#e5e7eb' }};"></td>
                                    @endfor
                                </tr>
                                <tr>
                                    @for($i = 1; $i <= 40; $i++)
                                        <td class="scale-lbl" style="text-align: {{ $i == 1 ? 'left' : 'right' }}; padding: 2px 0 0 0 !important; overflow: visible;">
                                            @if($i == 1)
                                                <span style="white-space: nowrap; display: inline-block;">0</span>
                                            @elseif(in_array($i, [10, 20, 30, 40]))
                                                <span style="white-space: nowrap; display: inline-block;">{{ $i * 2.5 }}</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            </table>
                        @else
                            <span class="muted">Belum ada nilai</span>
                        @endif
                    </td>
                    <td class="score-cell">
                        {{ $category['overall_score'] !== null ? number_format($category['overall_score'], 1) . '%' : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Belum ada ringkasan kategori.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="review-table" style="margin-top: 14px;">
        <thead>
            <tr>
                <th colspan="2" style="background: transparent; border: none; text-align: left; padding: 0; padding-bottom: 6px;">
                    <div class="section-title" style="margin-top: 0; margin-bottom: 0;">Review Personel</div>
                </th>
            </tr>
            <tr>
                <th style="width: 90px;">BINTANG</th>
                <th class="text-left">KOMENTAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commentsByRating as $rating => $comments)
                <tr>
                    <td class="text-center" style="vertical-align: middle; font-size: 9pt;">
                        {{ $rating }}
                    </td>
                    <td class="text-left">
                        @forelse($comments as $comment)
                            <div style="margin-bottom: {{ $loop->last ? '0' : '10px' }};">
                                <div style="color: #000; font-size: 8.5pt;">"{{ $comment['comment'] }}"</div>
                                <div style="color: #000; font-size: 7.5pt; margin-top: 2px;">
                                    {{ $comment['personnel'] }} - {{ $comment['satker'] }} - {{ $comment['item'] }}
                                </div>
                            </div>
                        @empty
                            <div style="color: #000; font-size: 8.5pt;">Tidak ada komentar.</div>
                        @endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
