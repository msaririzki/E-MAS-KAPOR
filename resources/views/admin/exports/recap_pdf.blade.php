<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rekapan {{ $budgetPackage->name }}</title>
@php
    /* Margin dihitung di sini agar bisa di-output ke @page CSS tanpa konflik Blade directive */
    $pgMargin = (!empty($isLandscape) && $isLandscape)
        ? '20mm 20mm 20mm 20mm'        /* landscape: 20mm semua sisi */
        : '25mm 20mm 20mm 25mm';       /* portrait : atas kanan bawah kiri (standar pemerintah) */
@endphp
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #000; background: #fff; }
    * { box-sizing: border-box; }

    .page-item { /* dihapus page-break-after: always karena diganti pagebreak mPDF */ position: relative; }

    /* Judul */
    .ttl { text-align: center; font-weight: bold; font-size: 10pt; margin-bottom: 10pt; }

    /* Tabel */
    table.rek { width: 100%; border-collapse: collapse; }
    table.rek th, table.rek td { border: 0.5pt solid #000; text-align: center; vertical-align: middle; }
    table.rek th { font-weight: bold; }
    table.rek tr { page-break-inside: avoid; } /* Mencegah baris tabel terpotong di tengah halaman */
    .sl { text-align: left !important; }       /* satker left */
    .bd { font-weight: bold; }                  /* bold cell */
    table.rek tfoot td { font-weight: bold; }

    /* Tanda tangan (tabel 2 kolom agar stabil di dompdf) */
    table.ttd { width: 100%; border: none; margin-top: 16pt; page-break-inside: avoid; } /* Mencegah blok TTD pisah halaman terpisah dari tabel jika masih ada ruang */
    table.ttd tr { page-break-inside: avoid; }
    table.ttd td { border: none; }
    .ttd-blk { text-align: center; font-size: 9.5pt; line-height: 1.6; }
    .ttd-blk p { margin: 0; }
    .ttd-sp { height: 42pt; }
    .ttd-nm { font-weight: bold; text-decoration: underline; }
    .ttd-jbt { font-weight: bold; }
</style>
</head>
<body>

@foreach($pages as $index => $page)
@php
    $isCombined = ($page['mode'] === 'combined');
    $sz         = $page['available_sizes'];
    $cnt        = count($sz);

    /* Font & dimensi adaptif */
    if      ($cnt >= 20) { $fs='6pt';   $pd='1.5pt 1.5pt'; $sw='65pt'; $nw='10pt'; $jw='15pt'; }
    elseif  ($cnt >= 16) { $fs='6.5pt'; $pd='2pt 2pt';     $sw='75pt'; $nw='12pt'; $jw='17pt'; }
    elseif  ($cnt >= 12) { $fs='7pt';   $pd='2.5pt 2.5pt'; $sw='85pt'; $nw='13pt'; $jw='19pt'; }
    elseif  ($cnt >= 8)  { $fs='8pt';   $pd='3pt 3pt';     $sw='100pt';$nw='16pt'; $jw='21pt'; }
    else                 { $fs='8.5pt'; $pd='3.5pt 4pt';   $sw='130pt';$nw='18pt'; $jw='23pt'; }

    $judulBagian = strtoupper($page['display_title'] ?? $page['item_name']);

    $bl = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $bln = $bl[now()->month - 1];

    /* mPDF orientasi adaptif khusus per halaman */
    $isLandscape = !empty($page['is_landscape']) && $page['is_landscape'];
    $pageOr = $isLandscape ? 'L' : 'P';
    $pgMrgT_L = $isLandscape ? 20 : 25;
@endphp

@if($index > 0)
    <pagebreak orientation="{{ $pageOr }}" margin-left="{{ $pgMrgT_L }}mm" margin-right="20mm" margin-top="{{ $pgMrgT_L }}mm" margin-bottom="20mm" margin-header="0mm" margin-footer="0mm" />
@endif

<div class="page-item">

    {{-- KOP --}}
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 10pt;">
        <tr>
            <td align="left">
                <table border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="border-bottom: 1.5px solid #000; padding-bottom: 1.5pt;">
                            <p style="font-size: 10pt; font-weight: bold; line-height: 1.35; margin: 0; white-space: nowrap;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</p>
                            <p style="font-size: 10pt; font-weight: bold; line-height: 1.35; margin: 0; white-space: nowrap;">DAERAH NUSA TENGGARA BARAT</p>
                            <p style="font-size: 10pt; font-weight: bold; line-height: 1.35; margin: 0; white-space: nowrap;">BIRO LOGISTIK</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- JUDUL --}}
    <p class="ttl">REKAP DATA UKURAN {{ $judulBagian }} POLDA NTB TAHUN {{ $budgetPackage->budgetYear->year }}</p>

    {{-- ── COMBINED (Olahraga: Pria + Wanita) ── --}}
    @if($isCombined)
    <table class="rek" style="font-size:{{ $fs }};">
        <thead>
            <tr>
                <th rowspan="4" style="width:{{ $nw }};padding:{{ $pd }};">NO</th>
                <th rowspan="4" style="width:{{ $sw }};padding:{{ $pd }};">SATKER</th>
                <th colspan="{{ ($cnt*2)+2 }}" style="padding:{{ $pd }};">UKURAN {{ strtoupper($page['display_title'] ?? $page['item_name']) }}</th>
                <th rowspan="4" style="width:{{ $jw }};padding:{{ $pd }};">JML TOTAL</th>
            </tr>
            <tr>
                <th colspan="{{ $cnt+1 }}" style="padding:{{ $pd }};">PRIA</th>
                <th colspan="{{ $cnt+1 }}" style="padding:{{ $pd }};">WANITA</th>
            </tr>
            <tr>
                @foreach($sz as $s)<th style="padding:{{ $pd }};">{{ $s }}</th>@endforeach
                <th style="width:{{ $jw }};padding:{{ $pd }};">JML</th>
                @foreach($sz as $s)<th style="padding:{{ $pd }};">{{ $s }}</th>@endforeach
                <th style="width:{{ $jw }};padding:{{ $pd }};">JML</th>
            </tr>
            <tr>@php $cn=1; @endphp
                <td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>
                <td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>
                @foreach($sz as $s)<td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>@endforeach
                <td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>
                @foreach($sz as $s)<td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>@endforeach
                <td style="font-size:5.5pt;padding:1pt;">{{ $cn++ }}</td>
                <td style="font-size:5.5pt;padding:1pt;">{{ $cn }}</td>
            </tr>
        </thead>
        <tbody>@php $no=1; @endphp
            @foreach($page['matrix'] as $row)
            <tr>
                <td style="padding:{{ $pd }};">{{ $no++ }}</td>
                <td class="sl" style="padding:{{ $pd }};padding-left:4pt;">{{ $row['satker_name'] }}</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $row['sizes_pria'][$s]>0?$row['sizes_pria'][$s]:'' }}</td>@endforeach
                <td class="bd" style="padding:{{ $pd }};">{{ $row['total_pria']>0?$row['total_pria']:'' }}</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $row['sizes_wanita'][$s]>0?$row['sizes_wanita'][$s]:'' }}</td>@endforeach
                <td class="bd" style="padding:{{ $pd }};">{{ $row['total_wanita']>0?$row['total_wanita']:'' }}</td>
                <td class="bd" style="padding:{{ $pd }};">{{ ($row['total_pria']+$row['total_wanita'])>0?($row['total_pria']+$row['total_wanita']):'' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:center;padding:{{ $pd }};">JUMLAH TOTAL</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $page['totalPerSizePria'][$s]>0?$page['totalPerSizePria'][$s]:'' }}</td>@endforeach
                <td style="padding:{{ $pd }};">{{ $page['grandTotalPria'] }}</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $page['totalPerSizeWanita'][$s]>0?$page['totalPerSizeWanita'][$s]:'' }}</td>@endforeach
                <td style="padding:{{ $pd }};">{{ $page['grandTotalWanita'] }}</td>
                <td style="padding:{{ $pd }};">{{ $page['grandTotalPria']+$page['grandTotalWanita'] }}</td>
            </tr>
        </tfoot>
    </table>

    @else
    {{-- ── NORMAL (per gender) ── --}}
    <table class="rek" style="font-size:{{ $fs }};">
        <thead>
            <tr>
                <th rowspan="2" style="width:{{ $nw }};padding:{{ $pd }};">NO</th>
                <th rowspan="2" style="width:{{ $sw }};padding:{{ $pd }};">SATKER</th>
                <th colspan="{{ $cnt }}" style="padding:{{ $pd }};">UKURAN BARANG</th>
                <th rowspan="2" style="width:{{ $jw }};padding:{{ $pd }};">JML</th>
            </tr>
            <tr>
                @foreach($sz as $s)<th style="padding:{{ $pd }};">{{ $s }}</th>@endforeach
            </tr>
        </thead>
        <tbody>@php $no=1; @endphp
            @foreach($page['matrix'] as $row)
            <tr>
                <td style="padding:{{ $pd }};">{{ $no++ }}</td>
                <td class="sl" style="padding:{{ $pd }};padding-left:4pt;">{{ $row['satker_name'] }}</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $row['sizes'][$s]>0?$row['sizes'][$s]:'' }}</td>@endforeach
                <td class="bd" style="padding:{{ $pd }};">{{ $row['row_total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:center;padding:{{ $pd }};">JUMLAH</td>
                @foreach($sz as $s)<td style="padding:{{ $pd }};">{{ $page['totalPerSize'][$s]>0?$page['totalPerSize'][$s]:'' }}</td>@endforeach
                <td style="padding:{{ $pd }};">{{ $page['grandTotal'] }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- TANDA TANGAN (50% Kiri Kosong, 50% Kanan untuk TTD) --}}
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top: 16pt; page-break-inside: avoid;">
        <tr style="page-break-inside: avoid;">
            <td width="55%"></td>
            <td width="45%" align="center" style="font-size: 9.5pt; line-height: 1.6; vertical-align: top;">
                <p style="margin: 0; padding-bottom: 2pt;">{{ $settings->location ?? 'Mataram' }}, {{ $bln }} {{ $budgetPackage->budgetYear->year }}</p>
                <p style="margin: 0;">a.n. {{ strtoupper($settings->organization_name ?? 'KEPALA BIRO LOGISTIK POLDA NTB') }}</p>
                <p style="font-weight: bold; margin: 0;">{{ strtoupper($settings->signatory_title ?? 'PEJABAT PEMBUAT KOMITMEN') }}</p>
                <br><br><br>
                <p style="font-weight: bold; text-decoration: underline; margin: 0; padding-top: 10pt;">{{ $settings->signatory_name ?? '.............................' }}</p>
                <p style="margin: 0;">{{ strtoupper($settings->signatory_rank ?? '') }} NRP {{ $settings->signatory_nrp ?? '' }}</p>
            </td>
        </tr>
    </table>

</div>
@endforeach

</body>
</html>
