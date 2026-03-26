<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengajuan Kebutuhan — {{ $kebutuhan->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }
        .print-container { max-width: 800px; margin: 0 auto; padding: 30px 40px; }
        .print-header { text-align: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px double #1e293b; }
        .print-header h1 { font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .print-header h2 { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .print-header .subtitle { font-size: 11px; color: #64748b; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 4px; border: 1px solid #e2e8f0; }
        .info-item { display: flex; gap: 8px; font-size: 12px; }
        .info-item .label { color: #64748b; min-width: 110px; font-weight: 600; }
        .info-item .value { color: #1e293b; font-weight: 500; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #1e293b; color: #fff; padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table th:first-child { text-align: center; width: 40px; }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .data-table td:first-child { text-align: center; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table .category-row { background: #f1f5f9; font-weight: 700; color: #475569; }
        .data-table .category-row td { padding: 6px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e1; }
        .print-footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { text-align: center; width: 200px; }
        .signature-box .sig-title { font-size: 11px; color: #64748b; margin-bottom: 60px; }
        .signature-box .sig-line { border-top: 1px solid #1e293b; padding-top: 4px; font-weight: 700; font-size: 12px; }
        .signature-box .sig-nip { font-size: 10px; color: #64748b; margin-top: 2px; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button { padding: 10px 24px; background: #c62828; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit; }
        .no-print button:hover { background: #991b1b; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .print-container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()"><i class="ri-printer-line"></i> Cetak / Simpan PDF</button>
        </div>

        <div class="print-header">
            <h1>Kepolisian Negara Republik Indonesia</h1>
            <h2>Laporan Pengajuan Kebutuhan Kapor</h2>
            <div class="subtitle">Sistem Informasi E-MAS KAPOR — {{ $kebutuhan->satker->name ?? '-' }}</div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="label">Judul Pengajuan</span>
                <span class="value">: {{ $kebutuhan->title }}</span>
            </div>
            <div class="info-item">
                <span class="label">Status</span>
                <span class="value">: {{ $kebutuhan->status_label }}</span>
            </div>
            <div class="info-item">
                <span class="label">Satker</span>
                <span class="value">: {{ $kebutuhan->satker->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="label">Tahun Anggaran</span>
                <span class="value">: {{ $kebutuhan->fiscal_year }}</span>
            </div>
            <div class="info-item">
                <span class="label">Pengaju</span>
                <span class="value">: {{ $kebutuhan->user->name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="label">Tanggal</span>
                <span class="value">: {{ $kebutuhan->submitted_at ? $kebutuhan->submitted_at->format('d M Y') : $kebutuhan->created_at->format('d M Y') }}</span>
            </div>
            <div class="info-item">
                <span class="label">Total Item</span>
                <span class="value">: {{ $kebutuhan->items->count() }} barang yang diajukan</span>
            </div>
        </div>

        @if($kebutuhan->notes)
        <div style="margin-bottom: 16px; padding: 10px 14px; background: #f8fafc; border-left: 3px solid #3b82f6; border-radius: 2px; font-size: 12px;">
            <strong>Catatan:</strong> {{ $kebutuhan->notes }}
        </div>
        @endif

        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Item Kapor</th>
                    <th>Kategori</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grouped = $kebutuhan->items->groupBy(fn($item) => $item->kaporItem->category ?? 'Lainnya');
                    $no = 1;
                @endphp
                @foreach($grouped as $category => $items)
                    <tr class="category-row">
                        <td colspan="3">{{ str_replace('_', ' ', $category) }} ({{ $items->count() }} item)</td>
                    </tr>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $item->kaporItem->item_name ?? '-' }}</td>
                        <td>{{ str_replace('_', ' ', $item->kaporItem->category ?? '-') }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="print-footer">
            <div class="signature-box">
                <div class="sig-title">Mengetahui,<br>Pimpinan Satker</div>
                <div class="sig-line">( ................................ )</div>
                <div class="sig-nip">NIP/NRP.</div>
            </div>
            <div class="signature-box">
                <div class="sig-title">{{ $kebutuhan->submitted_at ? $kebutuhan->submitted_at->format('d M Y') : $kebutuhan->created_at->format('d M Y') }},<br>Pengaju</div>
                <div class="sig-line">{{ $kebutuhan->user->name ?? '................................' }}</div>
                <div class="sig-nip">Admin Satker</div>
            </div>
        </div>
    </div>
</body>
</html>
