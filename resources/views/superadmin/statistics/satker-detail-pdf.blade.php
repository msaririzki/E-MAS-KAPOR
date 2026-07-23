<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Ulasan {{ $satker->name }} T.A. {{ $fiscalYear }}</title>
    <style>
        @page { margin: 9mm 9mm 14mm; footer: html_reportFooter; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 7.5pt; }
        .kop { width: 315px; text-align: center; font-size: 9.5pt; font-weight: bold; line-height: 1.4; }
        .kop-line { width: 315px; height: 2px; margin: 4px 0 13px; border-top: 2px solid #000; border-bottom: 1px solid #000; }
        h1 { margin: 0 0 3px; text-align: center; font-size: 10.5pt; text-decoration: underline; }
        .subtitle { margin-bottom: 10px; text-align: center; font-size: 8pt; font-weight: bold; }
        .meta-table { margin-bottom: 8px; border: 0; }
        .meta-table td { padding: 2px 8px 2px 0; border: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 4px 5px; border: 1px solid #222; vertical-align: top; }
        th { background: #e5e7eb; text-align: center; font-size: 7pt; }
        td.center { text-align: center; vertical-align: middle; }
        .name { font-weight: bold; }
        .muted { color: #555; font-size: 6.8pt; }
        tr { page-break-inside: avoid; }
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

    <h1>DETAIL ULASAN ITEM KAPOR PER SATKER</h1>
    <div class="subtitle">{{ strtoupper($satker->name) }} | T.A. {{ $fiscalYear }}</div>

    <table class="meta-table">
        <tr>
            <td>Total ulasan: <strong>{{ number_format(data_get($satkerSummary, 'total_feedback', 0)) }}</strong></td>
            <td>Personel merespons: <strong>{{ number_format(data_get($satkerSummary, 'respondent_count', 0)) }}</strong></td>
            <td>Belum menerima: <strong>{{ number_format(data_get($satkerSummary, 'not_received_count', 0)) }}</strong></td>
            <td>Rata-rata: <strong>{{ data_get($satkerSummary, 'average_rating') !== null ? number_format(data_get($satkerSummary, 'average_rating'), 1) : '-' }}</strong></td>
            <td>Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 18%;">PERSONEL</th>
                <th style="width: 18%;">ITEM KAPOR</th>
                <th style="width: 10%;">STATUS</th>
                <th style="width: 7%;">RATING</th>
                <th style="width: 34%;">CATATAN</th>
                <th style="width: 10%;">TANGGAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
                @php
                    $personnelName = $review->user?->name ?? $review->allocation?->full_name_snapshot ?? 'Personel';
                    $nrp = $review->user?->nrp_nip ?? $review->allocation?->nrp_snapshot ?? '-';
                    $itemName = $review->allocation?->kapor_item_name_snapshot ?? $review->kaporItem?->item_name ?? 'Item Kapor';
                    $submittedAt = $review->submitted_at ?? $review->created_at;
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td><span class="name">{{ $personnelName }}</span><br><span class="muted">{{ $nrp }}</span></td>
                    <td><span class="name">{{ $itemName }}</span><br><span class="muted">{{ $review->category_label }}</span></td>
                    <td class="center">{{ $review->response_label }}</td>
                    <td class="center">{{ $review->rating ? $review->rating . '/5' : '-' }}</td>
                    <td>{{ $review->display_message }}</td>
                    <td class="center">{{ $submittedAt?->translatedFormat('d/m/Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Tidak ada data sesuai filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
