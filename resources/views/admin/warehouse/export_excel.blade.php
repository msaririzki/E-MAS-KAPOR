@if(!isset($is_excel))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Stok Barang Gudang</title>
    <style>
        @page { size: landscape; margin: 15mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
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
        th, td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
        th { background-color: #e5e7eb; font-weight: bold; text-align: center; }
        .bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bg-gray { background-color: #f3f4f6; }
        .text-danger { color: #dc2626; }
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
        <h2>DATA STOK BARANG GUDANG</h2>
        <p>Tanggal Cetak: {{ date('d/m/Y') }}</p>
    </div>

    <table style="border-collapse: collapse; width: 100%; border: 1px solid #000;">
        <thead>
            <tr>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="5%">NO</th>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="30%">NAMA BARANG</th>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="10%">SATUAN</th>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="15%">KUANTITAS (STOK)</th>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="20%">HARGA SATUAN (Rp)</th>
                <th style="background-color: #d1d5db; font-weight: bold; text-align: center; border: 1px solid #000;" width="20%">JUMLAH HARGA (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalStock = 0;
                $totalValue = 0;
            @endphp
            @forelse($items as $index => $item)
                @php 
                    $qty = $item->sizes_sum_stock ?? 0;
                    $subtotal = $qty * $item->price;
                    $totalStock += $qty;
                    $totalValue += $subtotal;
                @endphp
                <tr>
                    <td style="text-align: center; border: 1px solid #000; vertical-align: middle;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #000; vertical-align: middle;">{{ $item->name }}</td>
                    <td style="text-align: center; border: 1px solid #000; vertical-align: middle;">{{ $item->unit }}</td>
                    <td style="text-align: center; border: 1px solid #000; vertical-align: middle; color: {{ $qty <= 0 ? '#dc2626' : '#000' }};">{{ number_format($qty, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000; vertical-align: middle;">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td style="text-align: right; border: 1px solid #000; vertical-align: middle;">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; border: 1px solid #000; padding: 15px;">Belum ada data barang.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align: right; font-weight: bold; border: 1px solid #000; background-color: #f3f4f6; padding-right: 15px;">TOTAL KESELURUHAN:</th>
                <th style="text-align: center; font-weight: bold; border: 1px solid #000; background-color: #f3f4f6;">{{ number_format($totalStock, 0, ',', '.') }}</th>
                <th style="text-align: right; border: 1px solid #000; background-color: #f3f4f6;">-</th>
                <th style="text-align: right; font-weight: bold; border: 1px solid #000; background-color: #f3f4f6;">{{ number_format($totalValue, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
@else
<table>
    <tr>
        <th colspan="6" style="text-align: center; font-size: 14pt; font-weight: bold;">DATA STOK BARANG GUDANG</th>
    </tr>
    <tr>
        <th colspan="6" style="text-align: center;">Tanggal Cetak: {{ date('d/m/Y') }}</th>
    </tr>
    <tr>
        <th colspan="6"></th>
    </tr>
    <tr>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">NO</th>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">NAMA BARANG</th>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">SATUAN</th>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">KUANTITAS (STOK)</th>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">HARGA SATUAN (Rp)</th>
        <th style="font-weight: bold; background-color: #d1d5db; border: 1px solid #000000; text-align: center;">JUMLAH HARGA (Rp)</th>
    </tr>
    @php 
        $totalStock = 0;
        $totalValue = 0;
    @endphp
    @foreach($items as $index => $item)
        @php 
            $qty = $item->sizes_sum_stock ?? 0;
            $subtotal = $qty * $item->price;
            $totalStock += $qty;
            $totalValue += $subtotal;
        @endphp
        <tr>
            <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000000;">{{ $item->name }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ $item->unit }}</td>
            <td style="border: 1px solid #000000; text-align: center; color: {{ $qty <= 0 ? '#ff0000' : '#000000' }};">{{ $qty }}</td>
            <td style="border: 1px solid #000000; text-align: right;">{{ $item->price }}</td>
            <td style="border: 1px solid #000000; text-align: right;">{{ $subtotal }}</td>
        </tr>
    @endforeach
    <tr>
        <th colspan="3" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL KESELURUHAN:</th>
        <th style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalStock }}</th>
        <th style="text-align: center; border: 1px solid #000000;">-</th>
        <th style="text-align: right; font-weight: bold; border: 1px solid #000000;">{{ $totalValue }}</th>
    </tr>
</table>
@endif
