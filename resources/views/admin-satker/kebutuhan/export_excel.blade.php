<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <!-- Baris ini untuk kop surat yang dirender oleh Maatwebsite Excel Drawing -->
            <!-- Kami sisakan ruang agar gambar tidak menimpa text sepenuhnya -->
        </tr><tr></tr><tr></tr><tr></tr><tr></tr>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: left; font-family: Arial;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
        </tr>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: left; font-family: Arial;">DAERAH NUSA TENGGARA BARAT</td>
        </tr>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: left; font-family: Arial; text-decoration: underline;">{{ strtoupper($kebutuhan->satker->name ?? '') }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; font-weight: bold; text-decoration: underline; font-family: Arial;">
                USULAN KAPORLAP {{ strtoupper($kebutuhan->satker->name ?? '') }} TAHUN ANGGARAN {{ $kebutuhan->fiscal_year }}
            </td>
        </tr>
        <tr>
            <td colspan="3"></td>
        </tr>
    </table>

    <table border="1" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th style="font-weight: bold; text-align: center; width: 50px; background-color: #f5f5f5; border: 1px solid #000000;">NO</th>
                <th style="font-weight: bold; text-align: center; width: 400px; background-color: #f5f5f5; border: 1px solid #000000;">JENIS KAPORLAP</th>
                <th style="font-weight: bold; text-align: center; width: 200px; background-color: #f5f5f5; border: 1px solid #000000;">KATEGORI</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grouped = $kebutuhan->items->groupBy(fn($item) => $item->identifikasiItem->category ?? 'Lainnya');
                $noCategory = 1;
            @endphp
            @foreach($grouped as $category => $items)
                <tr>
                    <td style="text-align: center; font-weight: bold; background-color: #fafafa; border: 1px solid #000000;">{{ $noCategory++ }}</td>
                    <td colspan="2" style="font-weight: bold; background-color: #fafafa; border: 1px solid #000000;">{{ strtoupper(str_replace('_', ' ', $category)) }}</td>
                </tr>
                @php $alphaNum = 'A'; @endphp
                @foreach($items as $item)
                <tr>
                    <td style="border: 1px solid #000000;"></td>
                    <td style="border: 1px solid #000000;">{{ $alphaNum++ }}. {{ strtoupper($item->identifikasiItem->item_name ?? '-') }}</td>
                    <td style="border: 1px solid #000000;">{{ strtoupper(str_replace('_', ' ', $item->identifikasiItem->category ?? '-')) }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td colspan="3"></td>
        </tr>
        @php
            $satkerName = strtoupper($kebutuhan->satker->name ?? '');

            $bulanIndo = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $bulan = $bulanIndo[date('n')];
            $tahun = date('Y');

            if (str_starts_with($satkerName, 'POLRESTA ')) {
                $an = 'a.n. KEPALA KEPOLISIAN RESOR KOTA ' . str_replace('POLRESTA ', '', $satkerName);
                $jabatan = 'KEPALA BAGIAN LOGISTIK';
            } elseif (str_starts_with($satkerName, 'POLRES ')) {
                $an = 'a.n. KEPALA KEPOLISIAN RESOR ' . str_replace('POLRES ', '', $satkerName);
                $jabatan = 'KEPALA BAGIAN LOGISTIK';
            } else {
                $an = 'a.n. KEPALA KEPOLISIAN POLDA NTB';
                $jabatan = 'KEPALA ' . $satkerName;
            }

            $userName = '..........................................';
            $userNrpNip = '.............................';
        @endphp
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-family: Arial;">................................., ................. {{ $bulan }} {{ $tahun }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-family: Arial;">{{ $an }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-weight: bold; font-family: Arial;">{{ $jabatan }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="height: 60px;"></td> <!-- Ruang tanda tangan manual -->
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-weight: bold; font-family: Arial;">{{ $userName }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-family: Arial;">NRP/NIP. {{ $userNrpNip }}</td>
        </tr>
    </table>
</body>
</html>
