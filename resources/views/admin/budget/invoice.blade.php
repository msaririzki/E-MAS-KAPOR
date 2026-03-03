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
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Invoice HPS</h1>
            <p style="color: #6B7280; font-size: 13px;">{{ $budgetPackage->name }} — {{ $budgetPackage->budgetYear->name }}</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.budget.recap', $budgetPackage) }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali ke Rekapan
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="ri-printer-line"></i> Cetak / PDF
            </button>
        </div>
    </div>
</div>

{{-- Settings Modal Toggle --}}
<details class="settings-panel" style="margin-bottom: 16px;">
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
                    <label>Nama Organisasi</label>
                    <input type="text" name="organization_name" value="{{ $settings->organization_name }}" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 10px;">
                <i class="ri-save-line"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</details>

{{-- Invoice Document --}}
<div class="invoice-document" id="invoiceDocument">
    {{-- Header --}}
    <div class="invoice-header">
        <div class="invoice-logo-area">
            <img src="{{ asset('images/polri-logo.png') }}" alt="Logo" class="invoice-logo" onerror="this.style.display='none'">
        </div>
        <div class="invoice-header-text">
            <h2>{{ $settings->header_title }}</h2>
            <h3>{{ $settings->organization_name }}</h3>
        </div>
    </div>

    <div class="invoice-separator"></div>

    {{-- Document Title --}}
    <div class="invoice-title">
        <h2>HARGA PERKIRAAN SENDIRI (HPS)</h2>
    </div>

    {{-- Info Table --}}
    <table class="invoice-info">
        <tr><td width="180">Satuan Kerja</td><td width="10">:</td><td>{{ $settings->organization_name }}</td></tr>
        <tr><td>Pekerjaan</td><td>:</td><td>{{ $settings->work_type }} {{ $budgetPackage->name }} T.A. {{ $budgetPackage->budgetYear->year }}</td></tr>
        <tr><td>Jenis Pekerjaan</td><td>:</td><td>Pengadaan Barang</td></tr>
        <tr><td>Lokasi</td><td>:</td><td>{{ $settings->location }}</td></tr>
        <tr><td>Tahun Anggaran</td><td>:</td><td>{{ $budgetPackage->budgetYear->year }}</td></tr>
    </table>

    {{-- Main Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 40px;">NO</th>
                <th>JENIS BARANG / JASA</th>
                <th style="width: 70px;">SATUAN</th>
                <th style="width: 80px; text-align: right;">VOLUME</th>
                <th style="width: 120px; text-align: right;">HARGA SATUAN (Rp)</th>
                <th style="width: 140px; text-align: right;">JUMLAH HARGA (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $currentGroup = ''; @endphp
            @foreach($grouped_items as $group => $groupItems)
                {{-- Group Header --}}
                <tr class="group-header">
                    <td colspan="6"><strong>{{ strtoupper($group) }}</strong></td>
                </tr>
                @foreach($groupItems as $item)
                <tr>
                    <td style="text-align: center;">{{ $no++ }}</td>
                    <td>{{ $item['item_name'] }}</td>
                    <td style="text-align: center;">{{ $item['unit'] }}</td>
                    <td style="text-align: right;">{{ number_format($item['qty']) }}</td>
                    <td style="text-align: right;">{{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($item['total'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                {{-- Group Subtotal --}}
                <tr class="subtotal-row">
                    <td colspan="3" style="text-align: right;"><strong>Subtotal {{ $group }}</strong></td>
                    <td style="text-align: right;"><strong>{{ number_format(collect($groupItems)->sum('qty')) }}</strong></td>
                    <td></td>
                    <td style="text-align: right;"><strong>{{ number_format(collect($groupItems)->sum('total'), 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="3" style="text-align: right;"><strong>GRAND TOTAL</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($grand_qty) }}</strong></td>
                <td></td>
                <td style="text-align: right;"><strong>{{ number_format($grand_total, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- Footer / Signature --}}
    <div class="invoice-footer">
        <div class="signature-block">
            <p>{{ $settings->location }}, {{ now()->translatedFormat('d F Y') }}</p>
            <p class="sig-title">{{ $settings->signatory_title }}</p>
            <div class="sig-space"></div>
            <p class="sig-name"><strong><u>{{ $settings->signatory_name ?: '.............................' }}</u></strong></p>
            <p class="sig-rank">{{ $settings->signatory_rank }} {{ $settings->signatory_nrp ? 'NRP. ' . $settings->signatory_nrp : '' }}</p>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

    /* Settings Panel */
    .settings-panel { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; }
    .settings-panel summary { padding: 12px 16px; list-style: none; }
    .settings-panel summary::-webkit-details-marker { display: none; }
    .settings-body { padding: 16px; border-top: 1px solid #F3F4F6; }
    .settings-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .settings-grid .form-group label { font-size: 11px; font-weight: 600; color: #6B7280; text-transform: uppercase; }
    .settings-grid .form-control { width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; }

    /* Invoice Document */
    .invoice-document {
        background: #fff; border: 1px solid #D1D5DB; border-radius: 0;
        padding: 40px 50px; max-width: 900px; margin: 0 auto;
        font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #000;
    }

    .invoice-header { display: flex; align-items: center; gap: 20px; text-align: center; justify-content: center; }
    .invoice-logo { width: 60px; height: 60px; }
    .invoice-header-text h2 { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
    .invoice-header-text h3 { font-size: 13px; font-weight: 600; margin: 4px 0 0; }
    .invoice-separator { border-bottom: 3px double #000; margin: 12px 0 20px; }

    .invoice-title { text-align: center; margin-bottom: 20px; }
    .invoice-title h2 { font-size: 15px; font-weight: 700; text-decoration: underline; }

    .invoice-info { margin-bottom: 20px; border: none; }
    .invoice-info td { padding: 2px 4px; font-size: 12px; vertical-align: top; border: none; }

    .invoice-table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .invoice-table th, .invoice-table td { border: 1px solid #000; padding: 4px 8px; }
    .invoice-table thead { background: #F3F4F6; }
    .invoice-table th { font-size: 10px; font-weight: 700; text-transform: uppercase; text-align: center; }
    .group-header td { background: #F9FAFB; font-size: 11px; padding: 6px 8px; }
    .subtotal-row td { background: #FAFAFA; border-top: 1px solid #666; }
    .grand-total-row td { background: #FEF2F2; font-size: 12px; border-top: 2px solid #000; }

    .invoice-footer { margin-top: 40px; display: flex; justify-content: flex-end; }
    .signature-block { text-align: center; min-width: 250px; }
    .signature-block p { margin: 4px 0; font-size: 12px; }
    .sig-title { font-weight: 600; }
    .sig-space { height: 60px; }
    .sig-name { font-size: 13px; }
    .sig-rank { font-size: 11px; }

    /* Print Styles */
    @media print {
        .page-header, .settings-panel, .sidebar, .topbar, .navbar { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .invoice-document { border: none; padding: 20px; max-width: 100%; box-shadow: none; }
    }

    @media (max-width: 768px) {
        .settings-grid { grid-template-columns: 1fr; }
        .invoice-document { padding: 20px; }
    }
</style>
@endsection
