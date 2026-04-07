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
            $jabatan = strtoupper($signatorySettings['signatory_title'] ?? 'KEPALA..........................');
            $userName = strtoupper($signatorySettings['signatory_name'] ?? '..........................................');
            $userNrpNip = $signatorySettings['signatory_nrp'] ?? '.............................';
            $location = $signatorySettings['location'] ?? 'Mataram';
        @endphp
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: center; font-family: Arial;">{{ $location }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</td>
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
