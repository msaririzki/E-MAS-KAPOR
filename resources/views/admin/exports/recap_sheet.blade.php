<table>
    {{-- ═══ KOP SURAT (full-width, merge seluruh kolom) ═══ --}}
    <tr>
        <td colspan="{{ count($availableSizes) + 4 }}" style="font-weight: bold; text-align: center;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
    </tr>
    <tr>
        <td colspan="{{ count($availableSizes) + 4 }}" style="font-weight: bold; text-align: center;">DAERAH NUSA TENGGARA BARAT</td>
    </tr>
    <tr>
        <td colspan="{{ count($availableSizes) + 4 }}" style="font-weight: bold; text-align: center;">{{ strtoupper($settings->organization_name ?? 'BIRO LOGISTIK') }}</td>
    </tr>
    <tr></tr>

    {{-- ═══ JUDUL DOKUMEN ═══ --}}
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

    {{-- ═══ SPASI ═══ --}}
    @php
        $totalColCount = count($availableSizes) + 4;
        // Kolom tanda tangan: 3 kolom terakhir
        $ttdStartCol = max($totalColCount - 2, 1);
        $emptyColsBefore = $ttdStartCol - 1;

        $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanSekarang = $bulanIndo[now()->month - 1];
    @endphp
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>

    {{-- ═══ TANDA TANGAN (pojok kanan) ═══ --}}
    <tr>
        @for($i = 0; $i < $emptyColsBefore; $i++)<td></td>@endfor
        <td colspan="3" style="text-align: center;">{{ $settings->location ?? 'Mataram' }},          {{ $bulanSekarang }}  {{ $budgetPackage->budgetYear->year }}</td>
    </tr>
    <tr>
        @for($i = 0; $i < $emptyColsBefore; $i++)<td></td>@endfor
        <td colspan="3" style="text-align: center;">a.n. {{ strtoupper($settings->organization_name ?? 'KEPALA BIRO LOGISTIK POLDA NTB') }}</td>
    </tr>
    <tr>
        @for($i = 0; $i < $emptyColsBefore; $i++)<td></td>@endfor
        <td colspan="3" style="text-align: center; font-weight: bold;">{{ strtoupper($settings->signatory_title ?? 'PS.KABAG BEKUM') }}</td>
    </tr>
    {{-- Ruang tanda tangan --}}
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>
    <tr>@for($i = 0; $i < $totalColCount; $i++)<td></td>@endfor</tr>
    <tr>
        @for($i = 0; $i < $emptyColsBefore; $i++)<td></td>@endfor
        <td colspan="3" style="text-align: center; font-weight: bold; text-decoration: underline;">{{ $settings->signatory_name ?? '.............................' }}</td>
    </tr>
    <tr>
        @for($i = 0; $i < $emptyColsBefore; $i++)<td></td>@endfor
        <td colspan="3" style="text-align: center;">{{ strtoupper($settings->signatory_rank ?? '') }} NRP {{ $settings->signatory_nrp ?? '' }}</td>
    </tr>
</table>
