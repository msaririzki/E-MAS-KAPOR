@extends('layouts.app')

@section('title', 'Invoice HPS - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Invoice HPS</span>
@endsection

@section('content')
<div class="page-header no-print">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Invoice HPS</h1>
            <p style="color: #6B7280; font-size: 13px;">{{ $budgetPackage->name }} — {{ $budgetPackage->budgetYear->name }}</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.budget.recap', $budgetPackage) }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="ri-printer-line"></i> Cetak / PDF
            </button>
        </div>
    </div>
</div>

{{-- Settings Panel --}}
<details class="settings-panel no-print" style="margin-bottom: 16px;">
    <summary class="btn btn-outline btn-sm" style="cursor: pointer;">
        <i class="ri-settings-4-line"></i> Pengaturan Penandatangan
    </summary>
    <div class="settings-body">
        <form method="POST" action="{{ route('admin.budget.update-settings') }}">
            @csrf
            <div class="settings-grid">
                <div class="form-group">
                    <label>Nama Pejabat</label>
                    <input type="text" name="signatory_name" value="{{ $settings->signatory_name }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Pangkat</label>
                    <input type="text" name="signatory_rank" value="{{ $settings->signatory_rank }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>NRP</label>
                    <input type="text" name="signatory_nrp" value="{{ $settings->signatory_nrp }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="signatory_title" value="{{ $settings->signatory_title }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="location" value="{{ $settings->location }}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Organisasi</label>
                    <input type="text" name="organization_name" value="{{ $settings->organization_name }}" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                <i class="ri-save-line"></i> Simpan
            </button>
        </form>
    </div>
</details>

{{-- ==================== INVOICE DOCUMENT ==================== --}}
<div class="invoice-doc" id="invoiceDocument">

    {{-- ===== KOP SURAT (RATA KIRI, GARIS MENGIKUTI TEKS TERPANJANG) ===== --}}
    <div class="kop-wrapper">
        @php
            $kopPath = public_path('kop suratt.png');
            $kopBase64 = '';
            if(file_exists($kopPath)) {
                $kopType = pathinfo($kopPath, PATHINFO_EXTENSION);
                $kopData = file_get_contents($kopPath);
                $kopBase64 = 'data:image/' . $kopType . ';base64,' . base64_encode($kopData);
            }
        @endphp
        @if($kopBase64)
            <img src="{{ $kopBase64 }}" alt="Kop Surat" class="kop-logo">
        @else
            <img src="{{ asset('kop suratt.png') }}" alt="Kop Surat" class="kop-logo">
        @endif
        
        <div class="kop-text-line kop-text-bold">KEPOLISIAN NEGARA REPUBLIK INDONESIA</div>
        <div class="kop-text-line kop-text-bold">DAERAH NUSA TENGGARA BARAT</div>
        <div class="kop-text-line kop-text-bold">BIRO LOGISTIK</div>
        <div class="kop-text-line kop-text-addr">Jalan langko No. 77 Mataram 83114</div>
        <div class="kop-divider"></div>
    </div>

    {{-- ===== JUDUL ===== --}}
    <div class="hps-title">HARGA PERKIRAAN SENDIRI</div>


    {{-- ===== INFO PEKERJAAN ===== --}}
    <table class="info-tbl">
        <tr>
            <td class="info-lbl">SATUAN KERJA</td>
            <td class="info-sep">:</td>
            <td class="info-val">BIRO LOGISTIK POLDA NTB</td>
        </tr>
        <tr>
            <td class="info-lbl">PEKERJAAN</td>
            <td class="info-sep">:</td>
            <td class="info-val">E-PURCHASING</td>
        </tr>
        <tr>
            <td class="info-lbl">JENIS PEKERJAAN</td>
            <td class="info-sep">:</td>
            <td class="info-val">KAPOR POLRI DAN PNS {{ strtoupper($budgetPackage->name) }} BERUPA<br>
                {{ strtoupper($budgetPackage->description ?? 'PAKAIAN DINAS, TOPI LAPANGAN PATI, TOPI LAPANGAN PNS, JILBAB DAN PAKAIAN OLAHRAGA') }}
                POLDA NTB T.A. {{ $budgetPackage->budgetYear->year }}
            </td>
        </tr>
        <tr>
            <td class="info-lbl">LOKASI</td>
            <td class="info-sep">:</td>
            <td class="info-val">Jalan langko No. 77 Mataram 83114</td>
        </tr>
    </table>

    {{-- ===== TABEL HPS ===== --}}
    <table class="hps-tbl">
        <thead>
            <tr class="hps-hdr">
                <th class="col-no">NO</th>
                <th class="col-nama">JENIS BARANG/JASA</th>
                <th class="col-satuan">SATUAN UNIT</th>
                <th class="col-vol">VOLUME</th>
                <th class="col-harga">HARGA<br>SATUAN<br>(Rp)</th>
                <th class="col-jumlah">JUMLAH HARGA (Rp)</th>
            </tr>
            <tr class="hps-colnum">
                <td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td>
            </tr>
        </thead>
        <tbody>
            <tr class="empty-row">
                <td colspan="6" style="padding: 4px 0; font-size: 8px; line-height: 8px;">&nbsp;</td>
            </tr>
            @php $no = 1; @endphp
            @foreach($grouped_items as $group => $groupItems)
            <tr class="grp-hdr">
                <td colspan="6">{{ strtoupper($group) }}</td>
            </tr>
            @foreach($groupItems as $item)
            <tr>
                <td class="c">{{ $no++ }}</td>
                <td class="nama">&nbsp;&nbsp;- {{ strtoupper($item['item_name']) }}</td>
                <td class="c">{{ strtoupper($item['unit']) }}</td>
                <td class="r">{{ number_format($item['qty'], 0, ',', '.') }}</td>
                <td class="r">{{ number_format($item['price'], 0, ',', '.') }}</td>
                <td class="r">{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr class="jml-row">
                <td colspan="3" class="c"><strong>JUMLAH</strong></td>
                <td colspan="2"></td>
                <td class="r"><strong>{{ number_format($grand_total, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== TANDA TANGAN (posisi kanan, format resmi) ===== --}}
    <div class="ttd-area">
        <div class="ttd-box">
            @php
                $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            @endphp
            <div style="margin-bottom: 15px;">
                {{ $settings->location }},<span style="display:inline-block; width: 25px;"></span>{{ $bulanIndo[now()->month - 1] }} {{ $budgetPackage->budgetYear->year }}
            </div>
            <p>a.n {{ strtoupper($settings->organization_name) }}</p>
            <p class="ttd-jabatan">{{ strtoupper($settings->signatory_title) }}</p>

            <div class="ttd-ruang"></div>

            <p class="ttd-nama"><u><strong>{{ $settings->signatory_name ?: '.............................' }}</strong></u></p>
            @if($settings->signatory_rank)
            <p class="ttd-kecil">{{ $settings->signatory_rank }}</p>
            @endif
            @if($settings->signatory_nrp)
            <p class="ttd-kecil">NRP. {{ $settings->signatory_nrp }}</p>
            @endif
        </div>
    </div>

</div>
@endsection

@section('styles')
<style>
    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

    .settings-panel { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; }
    .settings-panel summary { padding: 12px 16px; list-style: none; }
    .settings-panel summary::-webkit-details-marker { display: none; }
    .settings-body { padding: 16px; border-top: 1px solid #F3F4F6; }
    .settings-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .settings-grid .form-group label { font-size: 11px; font-weight: 600; color: #6B7280; text-transform: uppercase; }
    .settings-grid .form-control { width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; }

    /* ============= INVOICE DOCUMENT ============= */
    .invoice-doc {
        background: #fff;
        border: 1px solid #bbb;
        padding: 50px 60px;
        max-width: 850px;
        margin: 0 auto;
        font-family: 'Times New Roman', Times, serif;
        font-size: 12px;
        color: #000;
        line-height: 1.4;
    }

    /* --- KOP SURAT (rata kiri, garis mengikuti teks terpanjang) --- */
    .kop-wrapper {
        display: inline-block;
        text-align: center;
        margin-bottom: 25px;
        /* inline-block membuat lebar otomatis mengikuti konten terpanjang */
    }
    .kop-logo {
        width: 70px;
        height: auto;
        margin-bottom: 6px;
    }
    .kop-text-line {
        font-family: Arial, sans-serif;
        font-size: 11px;
        white-space: nowrap;
        line-height: 1.5;
    }
    .kop-text-bold {
        font-weight: normal;
    }
    .kop-text-addr {
        font-size: 10px;
    }
    .kop-divider {
        border-top: 2px solid #000;
        margin-top: 6px;
        margin-bottom: 0;
    }

    /* --- JUDUL --- */
    .hps-title {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 16px;
    }

    /* --- INFO --- */
    .info-tbl { border: none; border-collapse: collapse; margin-bottom: 16px; }
    .info-tbl td { border: none; padding: 1px 4px; font-size: 12px; vertical-align: top; }
    .info-lbl { font-weight: 700; width: 145px; white-space: nowrap; }
    .info-sep { width: 10px; font-weight: 700; }
    .info-val { font-weight: 700; }

    /* --- TABEL HPS --- */
    .hps-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-bottom: 0;
    }
    .hps-tbl th, .hps-tbl td {
        border: 1px solid #000;
        padding: 3px 5px;
        color: #000 !important;
    }
    .hps-hdr th {
        text-align: center;
        font-weight: 700;
        font-size: 10px;
        vertical-align: middle;
        background: #D9D9D9;
        color: #000 !important;
    }
    .col-no { width: 30px; }
    .col-nama { }
    .col-satuan { width: 70px; }
    .col-vol { width: 55px; }
    .col-harga { width: 85px; }
    .col-jumlah { width: 115px; }

    .hps-colnum td {
        text-align: center;
        font-weight: 700;
        font-size: 10px;
        padding: 2px;
        background: #D9D9D9;
        color: #000 !important;
    }

    .grp-hdr td {
        font-weight: 700;
        font-size: 11px;
        padding: 3px 5px;
        background: #E8E8E8 !important;
        color: #000 !important;
    }
    .nama { font-size: 11px; }
    .c { text-align: center; }
    .r { text-align: right; }

    .jml-row td {
        border-top: 2px solid #000;
        padding: 4px 5px;
        font-size: 11px;
    }

    /* --- TANDA TANGAN (kanan) --- */
    .ttd-area {
        margin-top: 30px;
        display: flex;
        justify-content: flex-end;
    }
    .ttd-box {
        text-align: center;
        font-size: 12px;
        min-width: 280px;
    }
    .ttd-tanggal { border: none; border-collapse: collapse; margin: 0 auto; }
    .ttd-tanggal td { border: none; padding: 0; font-size: 12px; white-space: nowrap; }
    .ttd-box p { margin: 2px 0; }
    .ttd-jabatan { font-weight: 700; }
    .ttd-ruang { height: 70px; }
    .ttd-nama { font-size: 12px; }
    .ttd-kecil { font-size: 11px; }

    /* ---------- PRINT ---------- */
    @media print {
        .no-print, .sidebar, .topbar, nav, .navbar, header { display: none !important; }
        .main-content, .content-area, .page-content { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        .invoice-doc { border: none; padding: 20px 30px; margin: 0; max-width: 100%; box-shadow: none; }
        body { background: #fff !important; }
        @page { margin: 15mm; }
    }
    @media (max-width: 768px) {
        .settings-grid { grid-template-columns: 1fr; }
        .invoice-doc { padding: 20px; font-size: 10px; }
    }
</style>
@endsection
