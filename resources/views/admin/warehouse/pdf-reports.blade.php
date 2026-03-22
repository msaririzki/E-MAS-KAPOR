<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pengeluaran Gudang</title>
    <style>
        @page { size: landscape; margin: 30px; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; }
        
        /* KOP Surat - Top Left with centered text and underline */
        .kop-table { 
            margin-bottom: 20px; 
            border-bottom: 1px solid #000; 
            width: 250px; 
            margin-left: 0;
            margin-right: auto;
        }
        .kop-table td { 
            border: none; 
            padding: 0 0 5px 0; 
            text-align: center; 
            font-weight: bold; 
            font-size: 10px; 
            line-height: 1.2; 
            text-transform: uppercase;
            white-space: nowrap;
        }
        .kop-logo {
            width: 45px;
            margin-bottom: 5px;
        }

        /* Title Section - Centered */
        .title-section { text-align: center; margin-bottom: 20px; }
        .title-section h2 { margin: 0 0 5px 0; font-size: 14px; text-decoration: underline; text-transform: uppercase; }
        .title-section p { margin: 0; font-size: 11px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; font-size: 10px; }
        th { background: #f0f0f0; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td>
                <img src="{{ public_path('kop suratt.png') }}" class="kop-logo"><br>
                KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
                DAERAH NUSA TENGGARA BARAT<br>
                BIRO LOGISTIK
            </td>
        </tr>
    </table>

    <div class="title-section">
        <h2>LAPORAN PENGELUARAN BARANG GUDANG</h2>
        <p>Total Barang Keluar: {{ number_format($totalItemsOut, 0, ',', '.') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">NO</th>
                <th width="15%">SATKER</th>
                <th width="10%">TGL KELUAR</th>
                <th width="20%">NAMA BARANG</th>
                <th width="6%" class="text-center">UKURAN</th>
                <th width="6%" class="text-center">SATUAN</th>
                <th width="10%" class="text-center">JUMLAH</th>
                <th width="14%">PENERIMA</th>
                <th width="15%">SPPM</th>
            </tr>
        </thead>
        <tbody>
            @forelse($outflows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->satker->name ?? '-' }}</td>
                    <td>{{ $row->outflow_date->format('d/m/Y') }}</td>
                    <td>{{ $row->itemSize->item->name ?? '-' }}</td>
                    <td class="text-center">{{ $row->itemSize->size_label ?? '-' }}</td>
                    <td class="text-center">{{ $row->itemSize->item->unit ?? '-' }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                    <td>{{ $row->recipient_name ?: '-' }}</td>
                    <td>{{ $row->reference_note ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada riwayat pengeluaran barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
