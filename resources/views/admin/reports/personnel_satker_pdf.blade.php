<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Data Personel - {{ $satker->name }}</title>
    <style>
        @page { margin: 0.8cm 0.5cm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /* ── Header / KOP ── */
        .kop { text-align: center; font-size: 8pt; font-weight: bold;
               line-height: 1.5; margin-bottom: 3px; }
        .divider-top { border-top: 2.5px solid #000; border-bottom: 1px solid #000;
                       padding-top: 0; margin-bottom: 4px; }

        /* ── Judul ── */
        .doc-title { text-align: center; font-size: 9pt; font-weight: bold; margin-bottom: 1px; }
        .doc-subtitle { text-align: center; font-size: 8pt; font-weight: bold;
                        text-decoration: underline; margin-bottom: 5px; }

        /* ── Ringkasan ── */
        .summary { margin-bottom: 5px; }
        .summary td { font-size: 7pt; border: 0.5px solid #888; padding: 1px 4px; }
        .summary .lbl { font-weight: bold; background: #e8e8e8; }

        /* ── Tabel Utama ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #000; padding: 1px 1px;
                 text-align: center; vertical-align: middle; }
        thead th {
            background: #d0d0d0;
            font-size: 6pt;
            font-weight: bold;
            line-height: 1.1;
        }
        tbody td { font-size: 6.5pt; line-height: 1.1; }
        .text-left { text-align: left; padding-left: 2px; }
        .text-small { font-size: 5.5pt; }

        /* Lebar kolom */
        .w-no      { width: 16px; }
        .w-nama    { width: 98px; }
        .w-pangkat { width: 54px; }
        .w-gol     { width: 22px; }
        .w-nrp     { width: 62px; }
        .w-jabatan { width: 72px; }
        .w-bagian  { width: 58px; }
        .w-jk      { width: 18px; }
        .w-uk      { width: 28px; }
        .w-ket     { width: 46px; }

        /* ── Footer ── */
        .footer { margin-top: 14px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { border: none; vertical-align: top; font-size: 7pt;
                           width: 33%; text-align: center; }
        .ttd-space { height: 38px; }
        .ttd-nama  { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

{{-- KOP --}}
<div class="kop">
    KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
    DAERAH NUSA TENGGARA BARAT<br>
    BIRO LOGISTIK
</div>
<div class="divider-top"></div>

{{-- JUDUL --}}
<div class="doc-title">DATA PERSONEL DAN UKURAN KAPOR SATKER {{ strtoupper($satker->name) }}</div>
<div class="doc-subtitle">UNTUK DUKUNGAN KAPOR TA. {{ $fiscalYear }}</div>

{{-- RINGKASAN --}}
@php
    $total  = $personnels->count();
    $polri  = $personnels->where('personnel_type', 'Polri')->count();
    $pns    = $personnels->where('personnel_type', 'PNS')->count();
    $pria   = $personnels->where('gender', 'L')->count();
    $wanita = $personnels->where('gender', 'P')->count();
@endphp
<table class="summary" style="width:auto; margin-bottom:5px;">
    <tr>
        <td class="lbl">Total</td><td><b>{{ $total }}</b></td>
        <td class="lbl">Polri</td><td>{{ $polri }}</td>
        <td class="lbl">PNS</td><td>{{ $pns }}</td>
        <td class="lbl">Pria (P)</td><td>{{ $pria }}</td>
        <td class="lbl">Wanita (W)</td><td>{{ $wanita }}</td>
    </tr>
</table>

{{-- TABEL UTAMA --}}
<table>
    <thead>
        <tr>
            <th class="w-no"      rowspan="3">NO</th>
            <th class="w-nama"    rowspan="3">NAMA LENGKAP</th>
            <th class="w-pangkat" rowspan="3">PANGKAT</th>
            <th class="w-gol"     rowspan="3">GOL</th>
            <th class="w-nrp"     rowspan="3">NRP / NIP</th>
            <th class="w-jabatan" rowspan="3">JABATAN</th>
            <th class="w-bagian"  rowspan="3">BAG / FUNGSI</th>
            <th class="w-jk"      rowspan="3">JK</th>
            <th colspan="9">U K U R A N</th>
            <th class="w-ket"     rowspan="3">KET</th>
        </tr>
        <tr>
            <th class="w-uk" rowspan="2">TUTUP KEPALA</th>
            <th colspan="3">TUTUP BADAN</th>
            <th colspan="2">TUTUP KAKI</th>
            <th class="w-uk" rowspan="2">JAKET</th>
            <th class="w-uk" rowspan="2">SABUK</th>
            <th class="w-uk" rowspan="2">JILBAB</th>
        </tr>
        <tr>
            <th class="w-uk">KEMEJA</th>
            <th class="w-uk">CELANA/ ROK</th>
            <th class="w-uk">T.SHIRT OLHRG</th>
            <th class="w-uk">DINAS</th>
            <th class="w-uk">OLHRG</th>
        </tr>
    </thead>
    <tbody>
        @php
        $jsonMapping = [
            'tutup kepala'     => 'topi',
            'kemeja'           => 'kemeja',
            'celana/rok'       => 'celana',
            't-shirt/olahraga' => 'olahraga',
            'sepatu dinas'     => 'sepatu_dinas',
            'sepatu olahraga'  => 'sepatu_olahraga',
            'jaket'            => 'jaket',
            'sabuk'            => 'sabuk',
            'jilbab'           => 'jilbab',
        ];
        $no = 1;
        @endphp

        @foreach($personnels as $p)
        @php
            $subs       = $p->submissions->keyBy(fn($s) => strtolower($s?->kaporItem?->item_name ?? ''));
            $kaporSizes = is_array($p->kapor_sizes) ? $p->kapor_sizes : [];

            // Helper: cari nilai ukuran
            $uk = function(string $name) use ($subs, $kaporSizes, $jsonMapping): string {
                $lower = strtolower($name);
                $s = $subs->get($lower);
                if ($s && $s->kaporSize) return $s->kaporSize->size_label ?? '';
                $key = $jsonMapping[$lower] ?? null;
                if ($key && isset($kaporSizes[$key])) {
                    $v = trim($kaporSizes[$key]);
                    return ($v !== '' && $v !== '-' && $v !== '0') ? $v : '';
                }
                return '';
            };
        @endphp
        <tr>
            <td>{{ $no++ }}</td>
            <td class="text-left">{{ strtoupper($p->full_name) }}</td>
            <td>{{ strtoupper($p->rank->name ?? '-') }}</td>
            <td>{{ $p->golongan ?: '-' }}</td>
            <td class="text-small">{{ $p->nrp }}</td>
            <td class="text-left">{{ strtoupper($p->jabatan ?: '-') }}</td>
            <td>{{ strtoupper($p->bagian ?: '-') }}</td>
            <td><b>{{ $p->gender === 'L' ? 'P' : 'W' }}</b></td>
            <td>{{ $uk('Tutup Kepala') }}</td>
            <td>{{ $uk('Kemeja') }}</td>
            <td>{{ $uk('Celana/Rok') }}</td>
            <td>{{ $uk('T-Shirt/Olahraga') }}</td>
            <td>{{ $uk('Sepatu Dinas') }}</td>
            <td>{{ $uk('Sepatu Olahraga') }}</td>
            <td>{{ $uk('Jaket') }}</td>
            <td>{{ $uk('Sabuk') }}</td>
            <td>{{ $uk('Jilbab') }}</td>
            <td class="text-left">{{ $p->keterangan ?: '' }}</td>
        </tr>
        @endforeach

        {{-- Baris total --}}
        <tr>
            <td colspan="2" style="font-weight:bold; text-align:right; background:#e8e8e8;">JUMLAH</td>
            <td colspan="16" style="font-weight:bold; text-align:left; padding-left:4px; background:#e8e8e8;">
                Total: {{ $total }} &nbsp;|&nbsp; Polri: {{ $polri }} &nbsp;|&nbsp;
                PNS: {{ $pns }} &nbsp;|&nbsp; Pria(P): {{ $pria }} &nbsp;|&nbsp; Wanita(W): {{ $wanita }}
            </td>
        </tr>
    </tbody>
</table>

{{-- TANDA TANGAN --}}
<div class="footer">
    <table class="footer-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala {{ strtoupper($satker->name) }}<br>
                <div class="ttd-space"></div>
                <span class="ttd-nama">___________________________</span>
            </td>
            <td></td>
            <td>
                {{ $location }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                <b>{{ strtoupper($signatory_role) }}</b><br>
                <div class="ttd-space"></div>
                <span class="ttd-nama">{{ strtoupper($signatory_name) }}</span><br>
                {{ strtoupper($signatory_nrp) }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
