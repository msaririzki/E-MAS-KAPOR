<table>
    {{-- ═══ KOP SURAT (pojok kiri, kolom A-C) ═══ --}}
    <tr>
        <td colspan="3" style="font-weight: bold; text-align: center;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
        <td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr>
        <td colspan="3" style="font-weight: bold; text-align: center;">DAERAH NUSA TENGGARA BARAT</td>
        <td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr>
        <td colspan="3" style="font-weight: bold; text-align: center;">BIRO LOGISTIK</td>
        <td></td><td></td><td></td><td></td><td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    {{-- ═══ JUDUL DOKUMEN (centered full width) ═══ --}}
    <tr>
        <td colspan="8" style="font-weight: bold; text-align: center;">
            DAFTAR NOMINATIF PENERIMA {{ strtoupper($kaporItem->item_name) }}
        </td>
    </tr>
    <tr>
        <td colspan="8" style="font-weight: bold; text-align: center;">
            POLDA NTB TAHUN ANGGARAN {{ $budgetPackage->budgetYear->year }}
        </td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>

    {{-- ═══ TABLE HEADER ═══ --}}
    <tr>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">NO</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">NAMA</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">NRP/NIP</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">PANGKAT</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">JABATAN</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">SATKER</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">JK</td>
        <td style="font-weight: bold; border: 1px solid #000; text-align: center;">UKURAN</td>
    </tr>

    {{-- ═══ TABLE BODY ═══ --}}
    @php $no = 1; @endphp
    @foreach($personnelList as $person)
    <tr>
        <td style="border: 1px solid #000; text-align: center;">{{ $no++ }}</td>
        <td style="border: 1px solid #000;">{{ $person['full_name'] }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $person['nrp'] }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $person['rank_name'] }}</td>
        <td style="border: 1px solid #000;">{{ $person['jabatan'] }}</td>
        <td style="border: 1px solid #000;">{{ $person['satker_name'] }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $person['gender'] }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $person['size'] }}</td>
    </tr>
    @endforeach

    {{-- ═══ TABLE FOOTER ═══ --}}
    <tr>
        <td colspan="7" style="font-weight: bold; border: 1px solid #000; text-align: center;">TOTAL PENERIMA</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $grandTotal }}</td>
    </tr>

    {{-- ═══ SPASI ═══ --}}
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>

    {{-- ═══ TANDA TANGAN (pojok kanan, kolom F-H) ═══ --}}
    @php
        $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanSekarang = $bulanIndo[now()->month - 1];
    @endphp
    <tr>
        <td></td><td></td><td></td><td></td><td></td>
        <td colspan="3" style="text-align: center;">{{ $settings->location ?? 'Mataram' }},          {{ $bulanSekarang }}  {{ $budgetPackage->budgetYear->year }}</td>
    </tr>
    <tr>
        <td></td><td></td><td></td><td></td><td></td>
        <td colspan="3" style="text-align: center;">a.n. {{ strtoupper($settings->organization_name ?? 'KEPALA BIRO LOGISTIK POLDA NTB') }}</td>
    </tr>
    <tr>
        <td></td><td></td><td></td><td></td><td></td>
        <td colspan="3" style="text-align: center; font-weight: bold;">{{ strtoupper($settings->signatory_title ?? 'PS.KABAG BEKUM') }}</td>
    </tr>
    {{-- Ruang tanda tangan --}}
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td></td><td></td><td></td><td></td><td></td>
        <td colspan="3" style="text-align: center; font-weight: bold; text-decoration: underline;">{{ $settings->signatory_name ?? '.............................' }}</td>
    </tr>
    <tr>
        <td></td><td></td><td></td><td></td><td></td>
        <td colspan="3" style="text-align: center;">{{ strtoupper($settings->signatory_rank ?? '') }} NRP {{ $settings->signatory_nrp ?? '' }}</td>
    </tr>
</table>
