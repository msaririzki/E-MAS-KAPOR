@extends('layouts.app')

@section('title', 'Monitoring Satker')
@section('breadcrumb', 'Monitoring')

@section('content')
{{-- Print-only Header (KOP) --}}
<div class="print-only print-header" style="display: none; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
    <div style="text-align: center;">
        <h2 style="margin: 0; font-size: 18px; text-transform: uppercase; font-weight: 800;">MONITORING DATA PERSONEL & UKURAN KAPOR</h2>
        <h3 style="margin: 5px 0 0 0; font-size: 16px; font-weight: 700;">{{ strtoupper($stats['satker_name']) }} — TA {{ $stats['fiscal_year'] }}</h3>
        <p style="margin: 5px 0 0 0; font-size: 11px; color: #333;">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</div>

<div class="page-header no-print">
    <div class="page-header-row">
        <div>
            <h1>
                <i class="ri-file-list-3-line no-print" style="margin-right: 6px; color: var(--brand);"></i> Monitoring Data Personel & Ukuran Kapor
            </h1>
            <p>
                {{ $stats['satker_name'] }} — TA {{ $stats['fiscal_year'] }}
            </p>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-outline">
                <i class="ri-printer-line"></i> Cetak
            </button>
            <a href="{{ route('admin.personnel.export-personnel', request()->only(['search', 'status', 'bagian'])) }}" class="btn btn-success">
                <i class="ri-file-excel-2-line"></i> Export Excel
            </a>
        </div>
    </div>
</div>

{{-- Summary Bar --}}
<div class="summary-bar no-print" style="background: #fff; border: 1px solid var(--slate-200); border-radius: 10px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--brand);"></div>
        <span style="font-size: 13px; color: #64748B;">Total:</span>
        <span style="font-size: 14px; font-weight: 800; color: #0F172A;">{{ number_format($stats['total_personnel']) }}</span>
    </div>
    <div style="width: 1px; height: 20px; background: var(--slate-200);"></div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 13px; color: #64748B;">Pria:</span>
        <span style="font-size: 14px; font-weight: 700; color: #334155;">{{ number_format($stats['pria']) }}</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 13px; color: #64748B;">Wanita:</span>
        <span style="font-size: 14px; font-weight: 700; color: #334155;">{{ number_format($stats['wanita']) }}</span>
    </div>
    <div style="width: 1px; height: 20px; background: var(--slate-200);"></div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <i class="ri-check-double-line" style="color: var(--success); font-size: 16px;"></i>
        <span style="font-size: 13px; color: #64748B;">Sudah Input:</span>
        <span style="font-size: 14px; font-weight: 700; color: var(--success);">{{ number_format($stats['personnel_submitted']) }}</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <i class="ri-time-line" style="color: var(--danger); font-size: 16px;"></i>
        <span style="font-size: 13px; color: #64748B;">Belum Input:</span>
        <span style="font-size: 14px; font-weight: 700; color: var(--danger);">{{ number_format($stats['personnel_pending']) }}</span>
    </div>
    <div style="width: 1px; height: 20px; background: var(--slate-200);"></div>
    <div style="display: flex; align-items: center; gap: 8px;">
        @php $pct = $stats['fill_rate']; @endphp
        <span style="font-size: 13px; color: #64748B;">Progres:</span>
        <div class="progress" style="width: 80px; height: 8px; border-radius: 4px;">
            <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}" style="width:{{ $pct }}%;"></div>
        </div>
        <span style="font-size: 13px; font-weight: 700; color: {{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};">{{ $pct }}%</span>
    </div>
</div>

<form method="GET" action="{{ route('admin-satker.reports') }}" class="no-print" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 20px; background: #fff; border: 1px solid var(--slate-200); border-radius: 10px; padding: 16px;">
    <div style="flex: 1 1 240px;">
        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Cari Personel</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NRP/NIP, jabatan, bag/fungsi" class="form-input" style="width: 100%;">
    </div>
    <div style="flex: 0 1 180px;">
        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Status Ukuran</label>
        <select name="status" class="form-input" style="width: 100%; appearance: auto;">
            <option value="">Semua Status</option>
            <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Sudah Lengkap</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Belum Lengkap</option>
        </select>
    </div>
    <div style="flex: 0 1 220px;">
        <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Bag / Fungsi</label>
        <select name="bagian" class="form-input" style="width: 100%; appearance: auto;">
            <option value="">Semua Bag / Fungsi</option>
            @foreach($bagians as $bagian)
                <option value="{{ $bagian }}" {{ request('bagian') === $bagian ? 'selected' : '' }}>{{ $bagian }}</option>
            @endforeach
        </select>
    </div>
    <div style="display: flex; gap: 8px; align-items: center;">
        <button type="submit" class="btn btn-primary">
            <i class="ri-filter-3-line"></i> Terapkan
        </button>
        <a href="{{ route('admin-satker.reports') }}" class="btn btn-outline">
            <i class="ri-refresh-line"></i> Reset
        </a>
    </div>
</form>

{{-- Main Data Table --}}
<div class="card">
    <div class="card-header no-print" style="padding: 14px 20px; border-bottom: 1px solid var(--slate-100); display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 14px; font-weight: 700; color: #1E293B; display: flex; align-items: center; gap: 8px;">
            <i class="ri-table-line" style="color: var(--brand);"></i>
            Data Personel & Ukuran Kapor — {{ $stats['satker_name'] }}
        </h3>
        <span style="font-size: 12px; color: #94A3B8; background: #F1F5F9; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
            {{ $personnels->count() }} personel
        </span>
    </div>
    <div class="card-body flush">
        <div class="table-wrap" style="overflow-x: auto;">
            <table style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th rowspan="3" style="width: 40px; text-align: center;">NO</th>
                        <th rowspan="3" style="min-width: 140px;">NAMA LENGKAP</th>
                        <th rowspan="3" style="min-width: 90px;">PANGKAT</th>
                        <th rowspan="3" style="width: 45px; text-align: center;">GOL</th>
                        <th rowspan="3" style="min-width: 100px;">NRP / NIP</th>
                        <th rowspan="3" style="min-width: 90px;">JABATAN</th>
                        <th rowspan="3" style="min-width: 80px;">BAG / FUNGSI</th>
                        <th rowspan="3" style="width: 35px; text-align: center;">JK</th>
                        <th colspan="9" style="text-align: center; background: #FEF2F2; color: #991B1B;">U K U R A N</th>
                        <th rowspan="3" style="min-width: 70px;">KET</th>
                    </tr>
                    <tr>
                        <th rowspan="2" style="width: 55px; text-align: center; background: #FFF7ED; font-size: 10px;">TUTUP KEPALA</th>
                        <th colspan="3" style="text-align: center; background: #FFF7ED; font-size: 10px;">TUTUP BADAN</th>
                        <th colspan="2" style="text-align: center; background: #FFF7ED; font-size: 10px;">TUTUP KAKI</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #FFF7ED; font-size: 10px;">JAKET</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #FFF7ED; font-size: 10px;">SABUK</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #FFF7ED; font-size: 10px;">JILBAB</th>
                    </tr>
                    <tr>
                        <th style="width: 55px; text-align: center; background: #FFFBEB; font-size: 10px;">KEMEJA</th>
                        <th style="width: 55px; text-align: center; background: #FFFBEB; font-size: 10px;">CELANA/ ROK</th>
                        <th style="width: 55px; text-align: center; background: #FFFBEB; font-size: 10px;">T-SHIRT OLHRG</th>
                        <th style="width: 55px; text-align: center; background: #FFFBEB; font-size: 10px;">DINAS</th>
                        <th style="width: 55px; text-align: center; background: #FFFBEB; font-size: 10px;">OLHRG</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @forelse($personnels as $p)
                    @php
                        $kaporSizes = is_array($p->kapor_sizes) ? $p->kapor_sizes : [];
                        $getSz = function($key) use ($kaporSizes) {
                            $v = trim($kaporSizes[$key] ?? '');
                            return ($v !== '' && $v !== '-' && $v !== '0') ? $v : '-';
                        };
                        $displayNrp = $p->nrp ?: '-';
                        $hasData = (bool) ($p->is_size_complete ?? false);
                    @endphp
                    <tr style="{{ !$hasData ? 'background: #FFFBEB;' : '' }}">
                        <td style="text-align: center; font-size: 12px; color: #94A3B8;">{{ $no++ }}</td>
                        <td style="font-weight: 600; font-size: 12px; color: #1E293B;">{{ strtoupper($p->full_name) }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->rank->name ?? '-') }}</td>
                        <td style="text-align: center; font-size: 11px; color: #64748B;">{{ $p->golongan ?: '-' }}</td>
                        <td class="nrp-cell" style="font-size: 11px; font-family: 'Courier New', monospace; color: #475569;">{{ $displayNrp }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->jabatan ?: '-') }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->bagian ?: '-') }}</td>
                        <td style="text-align: center; font-weight: 700; font-size: 12px; color: {{ $p->gender === 'L' ? '#3B82F6' : '#EC4899' }};">{{ $p->gender === 'L' ? 'P' : ($p->gender === 'P' ? 'W' : '-') }}</td>

                        {{-- 9 Kolom Ukuran --}}
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('topi') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('kemeja') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('celana') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('olahraga') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('sepatu_dinas') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('sepatu_olahraga') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('jaket') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('sabuk') }}</td>
                        <td style="text-align: center; font-size: 11px;">{{ $getSz('jilbab') }}</td>

                        <td style="font-size: 11px; color: #64748B;">{{ $p->keterangan ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="18" style="text-align: center; padding: 48px; color: #94A3B8;">
                            <i class="ri-file-list-3-line" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            Belum ada data personel.
                        </td>
                    </tr>
                    @endforelse

                    {{-- Row Total --}}
                    @if($personnels->count() > 0)
                    <tr style="background: #F1F5F9; font-weight: 700;">
                        <td colspan="2" style="text-align: right; padding-right: 12px; font-size: 12px; color: #334155;">JUMLAH</td>
                        <td colspan="16" style="font-size: 12px; color: #334155;">
                            Total: {{ $personnels->count() }} &nbsp;|&nbsp;
                            Pria: {{ $stats['pria'] }} &nbsp;|&nbsp;
                            Wanita: {{ $stats['wanita'] }} &nbsp;|&nbsp;
                            Sudah Input: {{ $stats['personnel_submitted'] }} &nbsp;|&nbsp;
                            Belum Input: {{ $stats['personnel_pending'] }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
{{-- Legend --}}
<div class="legend no-print" style="margin-top: 12px; padding: 12px 16px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; display: flex; align-items: center; gap: 20px; font-size: 12px; color: #64748B;">
    <span style="font-weight: 700; color: #334155;">Keterangan:</span>
    <div style="display: flex; align-items: center; gap: 6px;">
        <div style="width: 14px; height: 14px; border-radius: 3px; background: #FFFBEB; border: 1px solid #FDE68A;"></div>
        <span>Baris kuning = data ukuran belum diisi</span>
    </div>
    <div style="display: flex; align-items: center; gap: 6px;">
        <span style="font-family: 'Courier New', monospace; font-weight: 700;">P</span> = Pria &nbsp;
        <span style="font-family: 'Courier New', monospace; font-weight: 700;">W</span> = Wanita
    </div>
</div>

@endsection

@section('styles')
<style>
@media print {
    /* 1. GLOBAL UI HIDING - Menghilangkan elemen navigasi & UI web */
    nav, aside, header, footer, 
    #sidebar, .sidebar, .header, .topbar, .overlay,
    .no-print, .breadcrumb, .breadcrumb-container, .page-header,
    .page-header-actions, .mobile-nav-toggle, .btn-menu-toggle,
    .summary-bar, .legend, .card-header, .header-right {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        overflow: hidden !important;
    }

    /* Memaksa semua font menjadi warna hitam murni di cetakan */
    * {
        color: #000000 !important;
    }

    /* 2. LAYOUT RESET - Gunakan seluruh lebar kertas */
    html, body {
        background-color: #ffffff !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        left: 0 !important;
        position: static !important;
        display: block !important;
    }

    .content {
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }

    /* 3. PRINT HEADER (KOP) */
    .print-only {
        display: block !important;
        visibility: visible !important;
        text-align: center !important;
        margin-bottom: 20px !important;
    }

    .print-header {
        border-bottom: 3px double #000 !important;
        padding-bottom: 15px !important;
    }

    /* 4. TABLE OPTIMIZATION - Agar muat di A4 Landscape */
    .card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .card-body {
        padding: 0 !important;
    }

    .table-wrap {
        overflow: visible !important;
        width: 100% !important;
        zoom: 75% !important; /* Perkecil keseluruhan tabel 25% agar pasti pas di kertas */
    }

    table {
        width: 100% !important;
        min-width: auto !important;
        border-collapse: collapse !important;
        border: 1.5px solid #000 !important;
        font-family: Arial, sans-serif !important;
        font-size: 6.5pt !important; /* Font diperkecil lagi agar pasti muat */
        color: #000 !important;
        table-layout: auto !important;
    }

    th, td {
        border: 1px solid #000 !important;
        padding: 2px 1px !important; /* Padding diminimalkan */
        text-align: center !important; /* Rata tengah secara default */
        vertical-align: middle !important;
        word-wrap: break-word !important;
        width: auto !important;      /* TImpa inline width */
        min-width: 0 !important;     /* TImpa inline min-width */
        max-width: none !important;
    }

    /* Kolom Nama Lengkap (kolom ke-2) tetap rata kiri */
    table tr td:nth-child(2) {
        text-align: left !important;
        padding-left: 5px !important;
    }

    thead th {
        background-color: #d1d5db !important;
        font-weight: bold !important;
        text-align: center !important;
        text-transform: uppercase !important;
        color: #000 !important;
    }

    /* Row Backgrounds */
    tr[style*="background: #F1F5F9"] {
        background-color: #e5e7eb !important;
        font-weight: bold !important;
    }

    tr[style*="background: #FFFBEB"] {
        background-color: #ffffca !important;
    }

    /* Spefisikasi Cell NRP agar lebih terlihat proporsional */
    .nrp-cell {
        font-weight: 700 !important;
        font-family: Arial, sans-serif !important;
        font-size: 7pt !important;
        letter-spacing: 0px !important;
    }

    /* Page Breaks */
    tr {
        page-break-inside: avoid !important;
    }

    @page {
        size: A4 landscape;
        margin: 5mm; /* Margin dikecilkan semaksimal mungkin */
    }
}
</style>
@endsection
