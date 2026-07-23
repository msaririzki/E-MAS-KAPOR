<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Ulasan Seluruh Satker T.A. {{ $fiscalYear }}</title>
    <style>
        @page { margin: 10mm 10mm 14mm; footer: html_reportFooter; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 8pt; }
        .kop { width: 315px; text-align: center; font-size: 9.5pt; font-weight: bold; line-height: 1.4; }
        .kop-line { width: 315px; height: 2px; margin: 4px 0 14px; border-top: 2px solid #000; border-bottom: 1px solid #000; }
        h1 { margin: 0 0 4px; text-align: center; font-size: 11pt; text-decoration: underline; }
        .subtitle { margin-bottom: 12px; text-align: center; font-size: 8pt; }
        .meta { margin-bottom: 7px; color: #333; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 5px 6px; border: 1px solid #222; vertical-align: middle; }
        th { background: #e5e7eb; text-align: center; font-size: 7.4pt; }
        td.center { text-align: center; }
        td.name { font-weight: bold; }
        tr:nth-child(even) td { background: #f8fafc; }
        .page-footer { border-top: 1px solid #9ca3af; padding-top: 3px; color: #555; text-align: right; font-size: 7pt; }
    </style>
</head>
<body>
    <htmlpagefooter name="reportFooter">
        <div class="page-footer">Halaman {PAGENO} dari {nbpg}</div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />

    <div class="kop">
        KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
        DAERAH NUSA TENGGARA BARAT<br>
        BIRO LOGISTIK
    </div>
    <div class="kop-line"></div>

    <h1>REKAP ULASAN ITEM KAPOR SELURUH SATKER</h1>
    <div class="subtitle">TAHUN ANGGARAN {{ $fiscalYear }}</div>
    <div class="meta">
        Jumlah satker: <strong>{{ number_format($satkerStats->count()) }}</strong>
        &nbsp; | &nbsp; Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 28%;">SATKER</th>
                <th style="width: 12%;">PERSONEL MERESPONS</th>
                <th style="width: 12%;">TOTAL ULASAN</th>
                <th style="width: 12%;">DITERIMA</th>
                <th style="width: 14%;">BELUM MENERIMA</th>
                <th style="width: 10%;">RATA-RATA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($satkerStats as $satker)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="name">{{ $satker['satker_name'] }}</td>
                    <td class="center">{{ number_format($satker['respondent_count']) }}</td>
                    <td class="center">{{ number_format($satker['total_feedback']) }}</td>
                    <td class="center">{{ number_format($satker['reviewed_count']) }}</td>
                    <td class="center">{{ number_format($satker['not_received_count']) }}</td>
                    <td class="center">{{ $satker['average_rating'] !== null ? number_format($satker['average_rating'], 1) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
