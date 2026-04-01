<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usulan Kaporlap — {{ $kebutuhan->satker->name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #000; background: #fff; padding: 0; }
        .print-container { max-width: 800px; margin: 0 auto; padding: 30px 40px; }

        /* ── KOP SURAT ── */
        .kop-surat { width: 350px; text-align: center; margin-bottom: 30px; }
        .kop-logo { height: 50px; margin-bottom: 4px; }
        .kop-text { font-size: 13px; font-weight: bold; line-height: 1.2; font-family: Arial, Helvetica, sans-serif; }
        .kop-line-1 { border-bottom: 2px solid #000; margin-top: 4px; width: 100%; }

        /* ── JUDUL DOKUMEN ── */
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 20px;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.4;
        }

        /* ── TABEL DATA ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 2px solid #000;
            font-family: Arial, Helvetica, sans-serif;
        }
        .data-table th {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            background: #fff;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 12px;
            vertical-align: top;
        }
        .data-table td:first-child {
            text-align: center;
            font-weight: bold;
        }
        .data-table .category-row {
            font-weight: bold;
            background: #fff;
        }
        .data-table .category-row td {
            padding: 8px 10px;
            text-transform: uppercase;
            font-size: 12px;
        }

        /* ── FOOTER TTD ── */
        .print-footer {
            margin-top: 40px;
            float: right;
            width: 350px;
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .print-footer .ttd-location {
            margin-bottom: 10px;
        }
        .print-footer .ttd-an {
            margin-bottom: 0px;
        }
        .print-footer .ttd-jabatan {
            margin-bottom: 70px;
        }
        .print-footer .ttd-nama {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .print-footer .ttd-nrp {
            font-size: 11px;
        }

        .clearfix::after { content: ""; clear: both; display: table; }

        /* ── TOMBOL CETAK ── */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding-top: 20px;
        }
        .no-print button {
            padding: 10px 24px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .no-print button:hover { background: #2563eb; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .print-container { padding: 15px 20px; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="print-container clearfix">
        <!-- Tanda tangan dihapus dari tombol cetak web, karena hanya digunakan untuk export PDF -->
        {{-- KOP SURAT --}}
        <div class="kop-surat">
            <?php 
                $path = public_path('kop suratt.png');
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            ?>
            <img src="{{ $base64 }}" alt="Logo Polri" class="kop-logo">
            <div class="kop-text">
                KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
                DAERAH NUSA TENGGARA BARAT<br>
                {{ strtoupper($kebutuhan->satker->name ?? '') }}
            </div>
            <div class="kop-line-1"></div>
        </div>

        {{-- JUDUL --}}
        <div class="doc-title">
            USULAN KAPORLAP {{ strtoupper($kebutuhan->satker->name ?? '') }} TAHUN ANGGARAN {{ $kebutuhan->fiscal_year }}
        </div>

        {{-- TABEL ITEM --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 60%;">JENIS KAPORLAP</th>
                    <th style="width: 35%;">KATEGORI</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grouped = $kebutuhan->items->groupBy(fn($item) => $item->identifikasiItem->category ?? 'Lainnya');
                    $noCategory = 1;
                @endphp
                @foreach($grouped as $category => $items)
                    <tr class="category-row">
                        <td>{{ $noCategory++ }}</td>
                        <td colspan="2">{{ strtoupper(str_replace('_', ' ', $category)) }}</td>
                    </tr>
                    @php $alphaNum = 'A'; @endphp
                    @foreach($items as $item)
                    <tr>
                        <td></td>
                        <td style="padding-left: 20px;">{{ $alphaNum++ }}. {{ strtoupper($item->identifikasiItem->item_name ?? '-') }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', $item->identifikasiItem->category ?? '-')) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        {{-- ── FOOTER TANDA TANGAN ── --}}
        @php
            // ── Bulan Indonesia ──
            $bulanIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $bulan = $bulanIndo[date('n')];
            $tahun = date('Y');

            // ── Tentukan nama kepala dan Jabatan ──
            $jabatan = 'KEPALA..........................';

            // ── User pengaju ──
            $userName = '..........................................';
            $userNrpNip = '.............................';
        @endphp

        <div class="print-footer">
            <div class="ttd-location">................................., ................. {{ $bulan }} {{ $tahun }}</div>
            <div class="ttd-jabatan">{{ $jabatan }}</div>
            <div class="ttd-nama">{{ $userName }}</div>
            <div class="ttd-nrp">NRP/NIP. {{ $userNrpNip }}</div>
        </div>
    </div>
</body>
</html>
