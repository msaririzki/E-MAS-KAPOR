<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penerima Barang - {{ $satker->name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; color: #000; background: #fff; padding: 0; }
        .print-container { width: 100%; margin: 0 auto; padding: 20px 30px; }

        .kop-surat { width: 350px; text-align: center; margin-bottom: 20px; }
        .kop-logo { height: 50px; margin-bottom: 4px; }
        .kop-text { font-size: 13px; font-weight: bold; line-height: 1.2; font-family: Arial, Helvetica, sans-serif; }
        .kop-line-1 { border-bottom: 2px solid #000; margin-top: 4px; width: 100%; }

        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.4;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #000;
            font-family: Arial, Helvetica, sans-serif;
        }
        .data-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            background: #f3f4f6;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: top;
        }
        
        .item-list { margin: 0; padding-left: 14px; }
        .item-list li { margin-bottom: 2px; }
        
        .print-footer {
            margin-top: 30px;
            float: right;
            width: 300px;
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }
        .print-footer .ttd-location { margin-bottom: 10px; }
        .print-footer .ttd-jabatan { margin-bottom: 60px; }
        .print-footer .ttd-nama { font-weight: bold; text-decoration: underline; margin-bottom: 2px; }
        .print-footer .ttd-nrp { font-size: 11px; }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="print-container clearfix">
        <div class="kop-surat" style="width: 305px; text-align: center; margin-bottom: 20px;">
            <?php
                $path = public_path('kop suratt.png');
                $type = pathinfo($path, PATHINFO_EXTENSION);
                if (file_exists($path)) {
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                } else {
                    $base64 = '';
                }
            ?>
            @if($base64)
            <img src="{{ $base64 }}" alt="Logo Polri" class="kop-logo">
            @endif
            <div class="kop-text">
                KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
                DAERAH NUSA TENGGARA BARAT<br>
                {{ strtoupper($satker->name ?? '') }}
            </div>
            <div class="kop-line-1"></div>
        </div>

        <div class="doc-title">
            DAFTAR ALOKASI PENERIMA BARANG KAPORLAP
        </div>
        <div class="doc-subtitle">
            TAHUN ANGGARAN {{ $stats['fiscal_year'] }} - {{ strtoupper($satker->name ?? '') }}
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">NO</th>
                    <th style="width: 20%;">NAMA / NRP</th>
                    <th style="width: 10%;">PANGKAT</th>
                    <th style="width: 22%;">JABATAN</th>
                    <th style="width: 8%;">J.KELAMIN</th>
                    <th style="width: 20%;">BARANG & KATEGORI</th>
                    <th style="width: 10%;">UKURAN</th>
                    <th style="width: 6%;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $row['full_name'] }}</strong><br>
                            {{ $row['nrp'] ?: '-' }}
                        </td>
                        <td>{{ $row['rank'] ?? '-' }}</td>
                        <td>
                            {{ $row['jabatan'] ?? '-' }}
                            @if($row['bagian'] && trim((string)$row['bagian']) !== '-')
                                <br><span style="color: #4b5563;">({{ $row['bagian'] }})</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @php
                                $g = strtolower($row['gender']);
                            @endphp
                            {{ in_array($g, ['l', 'laki-laki', 'pria']) ? 'Laki-Laki' : (in_array($g, ['p', 'perempuan', 'wanita']) ? 'Perempuan' : ($row['gender'] ?: '-')) }}
                        </td>
                        <td>
                            @foreach($row['items'] as $itemIndex => $item)
                                <div style="margin-bottom: 4px;">
                                    <strong>{{ $item }}</strong><br>
                                    <span style="font-size: 9px; color: #555;">{{ $row['categories'][$itemIndex] ?? '' }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            @foreach($row['sizes'] as $size)
                                <div style="margin-bottom: 4px; font-weight: bold;">
                                    {{ $size }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center; vertical-align: middle; font-weight: bold;">
                            {{ $row['item_count'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">Belum ada data penerima kaporlap pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @php
            $jabatan = strtoupper($signatorySettings['signatory_title'] ?? 'KEPALA..........................');
            $userName = strtoupper($signatorySettings['signatory_name'] ?? '..........................................');
            $userNrpNip = $signatorySettings['signatory_nrp'] ?? '.............................';
            $location = $signatorySettings['location'] ?? 'Mataram';
        @endphp

        <div class="print-footer">
            <div class="ttd-location">{{ $location }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            <div class="ttd-jabatan">{{ $jabatan }}</div>
            <div class="ttd-nama">{{ $userName }}</div>
            <div class="ttd-nrp">NRP/NIP. {{ $userNrpNip }}</div>
        </div>
    </div>
</body>
</html>
