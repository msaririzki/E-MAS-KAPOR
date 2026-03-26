<table>
    @php
        $sizeCount = count($availableSizes);
        $totalHeadersSpan = ($sizeCount * 2) + 2;
    @endphp
    {{-- ═══ KOP SURAT (Akan dimerge & distyling murni via PHP PhpSpreadsheet) ═══ --}}
    <tr>
        <td>KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
    </tr>
    <tr>
        <td>DAERAH NUSA TENGGARA BARAT</td>
    </tr>
    <tr>
        <td>{{ strtoupper($settings->organization_name ?? 'BIRO LOGISTIK') }}</td>
    </tr>
    <tr></tr>

    {{-- ═══ JUDUL DOKUMEN ═══ --}}
    <tr>
        <td>
            REKAP DATA UKURAN {{ strtoupper($sheetTitle ?? $kaporItem->item_name) }} POLDA NTB TAHUN {{ $budgetPackage->budgetYear->year }}
        </td>
    </tr>
    <tr></tr>

    {{-- ══════════ DATA TABEL ══════════ --}}
    <!-- Header Row 1 -->
    <tr>
        <th rowspan="3" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">NO</th>
        <th rowspan="3" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">SATKER</th>
        <th colspan="{{ $totalHeadersSpan }}" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">UKURAN {{ strtoupper($sheetTitle ?? $kaporItem->item_name) }}</th>
        <th rowspan="3" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">JUMLAH<br>TOTAL</th>
    </tr>
    <!-- Header Row 2 -->
    <tr>
        <th colspan="{{ $sizeCount }}" style="font-weight: bold; border: 1px solid #000; text-align: center;">PRIA</th>
        <th rowspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">JUMLAH</th>
        <th colspan="{{ $sizeCount }}" style="font-weight: bold; border: 1px solid #000; text-align: center;">WANITA</th>
        <th rowspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center; vertical-align: middle;">JUMLAH</th>
    </tr>
    <!-- Header Row 3 -->
    <tr>
        @foreach($availableSizes as $size)
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $size }}</th>
        @endforeach
        @foreach($availableSizes as $size)
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $size }}</th>
        @endforeach
    </tr>
    <!-- Nomor Kolom (Optional, sesuai screenshot) -->
    <tr>
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">1</th>
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">2</th>
        @php $colNum = 3; @endphp
        @foreach($availableSizes as $size)
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $colNum++ }}</th>
        @endforeach
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $colNum++ }}</th>
        @foreach($availableSizes as $size)
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $colNum++ }}</th>
        @endforeach
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $colNum++ }}</th>
        <th style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $colNum }}</th>
    </tr>

    <!-- Body -->
    @php $no = 1; @endphp
    @foreach($matrix as $row)
    <tr>
        <td style="border: 1px solid #000; text-align: center;">{{ $no++ }}</td>
        <td style="border: 1px solid #000;">{{ $row['satker_name'] }}</td>
        
        <!-- Ukuran Pria -->
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center;">{{ $row['sizes_pria'][$size] > 0 ? $row['sizes_pria'][$size] : '' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $row['total_pria'] > 0 ? $row['total_pria'] : '' }}</td>
        
        <!-- Ukuran Wanita -->
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center;">{{ $row['sizes_wanita'][$size] > 0 ? $row['sizes_wanita'][$size] : '' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $row['total_wanita'] > 0 ? $row['total_wanita'] : '' }}</td>
        
        <!-- Jumlah Total -->
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $row['total_pria'] + $row['total_wanita'] }}</td>
    </tr>
    @endforeach

    <!-- Footer -->
    <tr>
        <td colspan="2" style="font-weight: bold; border: 1px solid #000; text-align: center;">TOTAL</td>
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $totalPerSizePria[$size] > 0 ? $totalPerSizePria[$size] : '' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $grandTotalPria }}</td>
        
        @foreach($availableSizes as $size)
            <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $totalPerSizeWanita[$size] > 0 ? $totalPerSizeWanita[$size] : '' }}</td>
        @endforeach
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $grandTotalWanita }}</td>
        
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $grandTotalPria + $grandTotalWanita }}</td>
    </tr>

</table>
