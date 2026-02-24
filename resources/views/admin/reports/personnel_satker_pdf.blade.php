<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Ukuran Kapor - {{ $satker->name }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 6.5pt;
            margin: 0;
            padding: 5px;
        }
        .header-left {
            text-align: center;
            margin-bottom: 3px;
            font-size: 6pt;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding-bottom: 1px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 0px;
            font-size: 8pt;
        }
        .subtitle {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
            font-size: 8pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        th, td {
            border: 1px solid black;
            padding: 0.5px 1px;
            text-align: center;
            word-wrap: break-word;
            line-height: 1;
        }
        th {
            background-color: #f2f2f2;
            font-size: 6pt;
        }
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .footer-table {
            width: 100%;
            border: none;
        }
        .footer-table td {
            border: none;
            text-align: center;
            width: 33%;
        }
    </style>
</head>
<body>
    <div class="header-left">
        KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
        DAERAH NUSA TENGGARA BARAT<br>
        BIRO LOGISTIK
    </div>

    <div class="title">DATA UKURAN KAPOR PERSONEL POLRI SATKER {{ strtoupper($satker->name) }}</div>
    <div class="subtitle">UNTUK DUKUNGAN KAPOR TA. {{ $fiscalYear }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="3" style="width: 20px;">NO</th>
                <th rowspan="3">NAMA</th>
                <th rowspan="3">PANGKAT</th>
                <th rowspan="3">GOLONGAN</th>
                <th rowspan="3">NRP</th>
                <th rowspan="3">JABATAN</th>
                <th rowspan="3">BAG/FUNGSI</th>
                <th rowspan="3">JENIS KELAMIN P/W</th>
                <th colspan="9">UKURAN</th>
                <th rowspan="3">KETERANGAN</th>
            </tr>
            <tr>
                <th rowspan="2">TUTUP KEPALA</th>
                <th colspan="3">TUTUP BADAN</th>
                <th colspan="2">TUTUP KAKI</th>
                <th rowspan="2">JAKET</th>
                <th rowspan="2">SABUK</th>
                <th rowspan="2">JILBAB</th>
            </tr>
            <tr>
                <th>KEMEJA</th>
                <th>CELANA/ ROK</th>
                <th>T.SHIRT/ OLHRG</th>
                <th>DINAS</th>
                <th>OLHRG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($personnels as $index => $p)
                @php
                    $subs = $p->submissions->keyBy(function($s) {
                        return strtolower($s->kaporItem->item_name ?? '');
                    });
                    
                    $jsonMapping = [
                        'tutup kepala' => 'topi',
                        'kemeja' => 'kemeja',
                        'celana/rok' => 'celana',
                        't-shirt/olahraga' => 'olahraga',
                        'sepatu dinas' => 'sepatu_dinas',
                        'sepatu olahraga' => 'sepatu_olahraga',
                        'jaket' => 'jaket',
                        'sabuk' => 'sabuk',
                        'jilbab' => 'jilbab',
                    ];
                    
                    $getVal = function($itemName) use ($subs, $p, $jsonMapping) {
                        $itemNameLower = strtolower($itemName);
                        
                        // Try from submissions first (formal tracking)
                        $s = $subs->get($itemNameLower);
                        if ($s && $s->kaporSize) {
                            return $s->kaporSize->size_label;
                        }
                        
                        // Fallback to kapor_sizes JSON (direct import data)
                        $sizes = $p->kapor_sizes; // Explicitly get the array
                        $jsonKey = $jsonMapping[$itemNameLower] ?? null;
                        
                        if ($jsonKey && is_array($sizes) && isset($sizes[$jsonKey])) {
                            $val = $sizes[$jsonKey];
                            return ($val !== '-' && $val !== '0') ? $val : '';
                        }
                        
                        return '';
                    };
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left;">{{ strtoupper($p->full_name) }}</td>
                    <td>{{ $p->rank->name }}</td>
                    <td>{{ $p->golongan }}</td>
                    <td>{{ $p->nrp }}</td>
                    <td style="text-align: left;">{{ strtoupper($p->jabatan) }}</td>
                    <td>{{ strtoupper($p->bagian) }}</td>
                    <td>{{ $p->gender == 'L' ? 'P' : 'W' }}</td>
                    
                    {{-- UKURAN Columns --}}
                    <td>{{ $getVal('Tutup Kepala') }}</td>
                    <td>{{ $getVal('Kemeja') }}</td>
                    <td>{{ $getVal('Celana/Rok') }}</td>
                    <td>{{ $getVal('T-Shirt/Olahraga') }}</td>
                    <td>{{ $getVal('Sepatu Dinas') }}</td>
                    <td>{{ $getVal('Sepatu Olahraga') }}</td>
                    <td>{{ $getVal('Jaket') }}</td>
                    <td>{{ $getVal('Sabuk') }}</td>
                    <td>{{ $getVal('Jilbab') }}</td>
                    
                    <td>{{ $p->keterangan }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td></td>
                <td></td>
                <td>
                    {{ $location }}, {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}<br>
                    {{ strtoupper($signatory_role) }}<br>
                    <br><br><br><br>
                    <span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($signatory_name) }}</span><br>
                    {{ strtoupper($signatory_nrp) }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
