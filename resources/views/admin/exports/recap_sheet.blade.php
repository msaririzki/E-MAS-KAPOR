<table>
    <tr>
        <td colspan="5" style="font-weight: bold;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
    </tr>
    <tr>
        <td colspan="5" style="font-weight: bold;">DAERAH NUSA TENGGARA BARAT</td>
    </tr>
    <tr>
        <td colspan="5" style="font-weight: bold;">BIRO LOGISTIK</td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="{{ count($availableSizes) + 4 }}" style="font-weight: bold; text-align: center;">
            REKAP DATA UKURAN {{ strtoupper($kaporItem->item_name) }} POLDA NTB TAHUN {{ $budgetPackage->budgetYear->year }}
        </td>
    </tr>
    <tr></tr>
    
    <!-- Table Header Row 1 -->
    <tr>
        <th rowspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">NO</th>
        <th rowspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">SATKER</th>
        <th colspan="{{ count($availableSizes) + 1 }}" style="font-weight: bold; border: 1px solid #000; text-align: center;">UKURAN BARANG</th>
        <th rowspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">JML</th>
    </tr>
    <!-- Table Header Row 2 -->
    <tr>
        @foreach($availableSizes as $size)
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $size }}</th>
        @endforeach
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">Tdk Diketahui</th>
    </tr>

    <!-- Table Body -->
    @php $no = 1; @endphp
    @foreach($matrix as $row)
    <tr>
        <td style="border: 1px solid #000; text-align: center;">{{ $no++ }}</td>
        <td style="border: 1px solid #000;">{{ $row['satker_name'] }}</td>
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center;">{{ $row['sizes'][$size] > 0 ? $row['sizes'][$size] : '-' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center;">{{ $row['unknown'] > 0 ? $row['unknown'] : '-' }}</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $row['row_total'] }}</td>
    </tr>
    @endforeach

    <!-- Table Footer -->
    <tr>
        <td colspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center;">TOTAL</td>
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $totalPerSize[$size] > 0 ? $totalPerSize[$size] : '-' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $totalPerSize['UNKNOWN'] > 0 ? $totalPerSize['UNKNOWN'] : '-' }}</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $grandTotal }}</td>
    </tr>
</table>
