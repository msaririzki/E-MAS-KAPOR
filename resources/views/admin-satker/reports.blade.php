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

@php
    $statusLabel = match (request('status')) {
        'submitted' => 'Sudah Lengkap',
        'pending' => 'Belum Lengkap',
        default => 'Semua Status',
    };
@endphp
<div class="filter-bar no-print monitor-filter-bar">
    <form method="GET" action="{{ route('admin-satker.reports') }}" class="filter-form monitor-filter-form" id="reportFilterForm">
        <div class="search-container monitor-search-container" style="flex: 1;">
            <i class="ri-search-line"></i>
            <input type="text" name="search" id="reportSearchInput" value="{{ request('search') }}" placeholder="Cari berdasarkan nama, NRP/NIP, jabatan, atau bag/fungsi..." oninput="debounceReportSearch()" onkeydown="handleReportSearchKeydown(event)">
            @if(request('search'))
                <button type="button" class="clear-search" onclick="document.getElementById('reportSearchInput').value=''; document.getElementById('reportFilterForm').submit();" style="background: none; border: none; color: #D1D5DB; cursor: pointer; padding: 4px; display: flex; align-items: center; margin-left: 8px;">
                    <i class="ri-close-circle-fill" style="font-size: 18px;"></i>
                </button>
            @endif
        </div>

        <div class="filter-group monitor-filter-group">
            <div class="custom-select-wrapper" style="width: 180px; flex-shrink: 0;">
                <div class="custom-select" onclick="toggleReportDropdown(this, event)">
                    <div class="select-trigger">
                        <span>{{ $statusLabel }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            <div class="option {{ !request('status') ? 'selected' : '' }}" onclick="selectReportOption(this, 'status', '', 'Semua Status')">Semua Status</div>
                            <div class="option {{ request('status') === 'submitted' ? 'selected' : '' }}" onclick="selectReportOption(this, 'status', 'submitted', 'Sudah Lengkap')">Sudah Lengkap</div>
                            <div class="option {{ request('status') === 'pending' ? 'selected' : '' }}" onclick="selectReportOption(this, 'status', 'pending', 'Belum Lengkap')">Belum Lengkap</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="status" value="{{ request('status') }}">
            </div>

            <div class="custom-select-wrapper" style="width: 220px; flex-shrink: 0;">
                <div class="custom-select" onclick="toggleReportDropdown(this, event)">
                    <div class="select-trigger">
                        <span>{{ request('bagian') ?: 'Semua Bag/Fungsi' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="select-search-container">
                            <input type="text" class="select-search-input" placeholder="Cari Bag/Fungsi..." onclick="event.stopPropagation()" onkeyup="filterReportOptions(this)">
                        </div>
                        <div class="options-scroll">
                            <div class="option {{ !request('bagian') ? 'selected' : '' }}" data-label="SEMUA BAG/FUNGSI" onclick="selectReportOption(this, 'bagian', '', 'Semua Bag/Fungsi')">Semua Bag/Fungsi</div>
                            @foreach($bagians as $bagian)
                                <div class="option {{ request('bagian') === $bagian ? 'selected' : '' }}" data-label="{{ $bagian }}" onclick="selectReportOption(this, 'bagian', '{{ $bagian }}', '{{ $bagian }}')">{{ $bagian }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="bagian" value="{{ request('bagian') }}">
            </div>
        </div>

        <div class="monitor-filter-actions" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">
                <i class="ri-filter-3-line"></i> Terapkan
            </button>
            <a href="{{ route('admin-satker.reports') }}" class="btn btn-outline">
                <i class="ri-refresh-line"></i> Reset
            </a>
        </div>
    </form>
</div>

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
                        <th colspan="9" style="text-align: center; background: #F1F5F9; color: #334155;">U K U R A N</th>
                        <th rowspan="3" style="min-width: 70px;">KET</th>
                    </tr>
                    <tr>
                        <th rowspan="2" style="width: 55px; text-align: center; background: #F8FAFC; font-size: 10px;">TUTUP KEPALA</th>
                        <th colspan="3" style="text-align: center; background: #F8FAFC; font-size: 10px;">TUTUP BADAN</th>
                        <th colspan="2" style="text-align: center; background: #F8FAFC; font-size: 10px;">TUTUP KAKI</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #F8FAFC; font-size: 10px;">JAKET</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #F8FAFC; font-size: 10px;">SABUK</th>
                        <th rowspan="2" style="width: 50px; text-align: center; background: #F8FAFC; font-size: 10px;">JILBAB</th>
                    </tr>
                    <tr>
                        <th style="width: 55px; text-align: center; background: #F1F5F9; font-size: 10px;">KEMEJA</th>
                        <th style="width: 55px; text-align: center; background: #F1F5F9; font-size: 10px;">CELANA/ ROK</th>
                        <th style="width: 55px; text-align: center; background: #F1F5F9; font-size: 10px;">T-SHIRT OLHRG</th>
                        <th style="width: 55px; text-align: center; background: #F1F5F9; font-size: 10px;">DINAS</th>
                        <th style="width: 55px; text-align: center; background: #F1F5F9; font-size: 10px;">OLHRG</th>
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
                    <tr style="{{ !$hasData ? 'background: #F4F4F5;' : '' }}">
                        <td style="text-align: center; font-size: 12px; color: #94A3B8;">{{ $no++ }}</td>
                        <td style="font-weight: 600; font-size: 12px; color: #1E293B;">{{ strtoupper($p->full_name) }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->rank->name ?? '-') }}</td>
                        <td style="text-align: center; font-size: 11px; color: #64748B;">{{ $p->golongan ?: '-' }}</td>
                        <td class="nrp-cell" style="font-size: 11px; font-family: 'Courier New', monospace; color: #475569;">{{ $displayNrp }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->jabatan ?: '-') }}</td>
                        <td style="font-size: 12px;">{{ strtoupper($p->bagian ?: '-') }}</td>
                        <td style="text-align: center; font-weight: 700; font-size: 12px; color: #334155;">{{ $p->gender === 'L' ? 'P' : ($p->gender === 'P' ? 'W' : '-') }}</td>

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

                        <td style="font-size: 11px; color: #64748B;"></td>
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
        <div style="width: 14px; height: 14px; border-radius: 3px; background: #F4F4F5; border: 1px solid #D4D4D8;"></div>
        <span>Baris abu-abu = data ukuran belum diisi</span>
    </div>
    <div style="display: flex; align-items: center; gap: 6px;">
        <span style="font-family: 'Courier New', monospace; font-weight: 700;">P</span> = Pria &nbsp;
        <span style="font-family: 'Courier New', monospace; font-weight: 700;">W</span> = Wanita
    </div>
</div>

@endsection

@section('styles')
<style>
.monitor-filter-bar {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.monitor-filter-form {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: nowrap;
}

.monitor-filter-group {
    display: flex;
    gap: 12px;
    flex: 0 0 auto;
    flex-wrap: nowrap;
}

.monitor-filter-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.monitor-search-container {
    flex: 1;
    min-width: 260px;
    position: relative;
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 0 16px;
    height: 46px;
    transition: all 0.2s ease;
}

.monitor-search-container:focus-within {
    border-color: #B91C1C;
    box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.05);
}

.monitor-search-container i.ri-search-line {
    color: #64748B;
    font-size: 20px;
    margin-right: 12px;
    flex-shrink: 0;
}

.monitor-search-container input {
    width: 100%;
    height: 100%;
    background: transparent;
    border: none;
    outline: none;
    font-size: 14px;
    color: #1E293B;
    padding: 0;
}

.monitor-search-container input::placeholder {
    color: #94A3B8;
    font-weight: 400;
}

.custom-select-wrapper {
    position: relative;
    width: 100%;
}

.custom-select {
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    height: 48px;
    display: flex;
    align-items: center;
}

.custom-select:hover {
    border-color: #D1D5DB;
}

.custom-select.active {
    border-color: #B91C1C;
    box-shadow: 0 0 0 4px #FEF2F2;
    background: #fff;
}

.select-trigger {
    width: 100%;
    padding: 0 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.select-trigger i {
    color: #9CA3AF;
    font-size: 20px;
    transition: transform 0.2s ease;
}

.custom-select.active .select-trigger {
    color: #111827;
}

.custom-select.active .select-trigger i {
    transform: rotate(180deg);
    color: #B91C1C;
}

.custom-options {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #F3F4F6;
    border-radius: 16px;
    box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1);
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
    padding: 8px;
    display: flex;
    flex-direction: column;
}

.custom-select.active .custom-options {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.select-search-container {
    padding: 8px;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
    border-bottom: 1px solid #F3F4F6;
    margin-bottom: 4px;
}

.select-search-input {
    width: 100%;
    height: 36px;
    padding: 0 12px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    background-color: #F9FAFB;
    transition: all 0.2s;
}

.select-search-input:focus {
    border-color: #B91C1C;
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.1);
}

.options-scroll {
    max-height: 240px;
    overflow-y: auto;
    padding-right: 2px;
}

.options-scroll::-webkit-scrollbar {
    width: 4px;
}

.options-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.options-scroll::-webkit-scrollbar-thumb {
    background-color: #E5E7EB;
    border-radius: 10px;
}

.options-scroll::-webkit-scrollbar-thumb:hover {
    background-color: #D1D5DB;
}

.option {
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 14px;
    color: #4B5563;
    border-radius: 8px;
    margin-bottom: 2px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.option:last-child {
    margin-bottom: 0;
}

.option:hover {
    background-color: #F9FAFB;
    color: #111827;
}

.option.selected {
    background-color: #FEF2F2;
    color: #B91C1C;
    font-weight: 600;
}

@media (max-width: 1024px) {
    .monitor-filter-form {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .monitor-filter-form {
        align-items: stretch;
        flex-direction: column;
    }

    .monitor-filter-group {
        width: 100%;
        flex-direction: column;
    }

    .monitor-filter-group .custom-select-wrapper {
        width: 100% !important;
    }

    .monitor-filter-actions {
        width: 100%;
    }

    .monitor-filter-actions .btn {
        flex: 1;
        justify-content: center;
    }
}

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

    tr[style*="background: #F4F4F5"] {
        background-color: #f4f4f5 !important;
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

@section('scripts')
<script>
    document.addEventListener('click', function (event) {
        if (! event.target.closest('.monitor-filter-bar .custom-select')) {
            closeReportDropdowns();
        }
    });

    document.addEventListener('scroll', function (event) {
        if (event.target.classList && (event.target.classList.contains('options-scroll') || event.target.closest('.options-scroll'))) {
            return;
        }

        closeReportDropdowns();
    }, true);

    function closeReportDropdowns() {
        document.querySelectorAll('.monitor-filter-bar .custom-options').forEach(function (options) {
            options.style.display = 'none';
        });

        document.querySelectorAll('.monitor-filter-bar .custom-select').forEach(function (select) {
            select.classList.remove('active');
        });
    }

    function toggleReportDropdown(element, event) {
        const options = element.querySelector('.custom-options');
        const isOpen = element.classList.contains('active');

        closeReportDropdowns();

        if (! isOpen) {
            options.style.display = 'block';
            element.classList.add('active');
        }

        event.stopPropagation();
    }

    function filterReportOptions(input) {
        const filter = input.value.toLowerCase();
        const options = input.closest('.custom-options').querySelectorAll('.option');

        options.forEach(function (option) {
            const text = (option.dataset.label || option.textContent || '').toLowerCase();
            option.style.display = text.includes(filter) ? 'flex' : 'none';
        });
    }

    function selectReportOption(element, inputName, value, label) {
        const wrapper = element.closest('.custom-select-wrapper');
        const trigger = wrapper.querySelector('.select-trigger span');
        const input = wrapper.querySelector('input[type="hidden"]');

        trigger.innerText = label;
        input.value = value;

        wrapper.querySelectorAll('.option').forEach(function (option) {
            option.classList.remove('selected');
        });

        element.classList.add('selected');
        document.getElementById('reportFilterForm').submit();
    }

    let reportSearchTimeout;

    function debounceReportSearch() {
        clearTimeout(reportSearchTimeout);

        const searchInput = document.getElementById('reportSearchInput');
        if (! searchInput) {
            return;
        }

        reportSearchTimeout = setTimeout(function () {
            if (/\s$/.test(searchInput.value)) {
                return;
            }

            document.getElementById('reportFilterForm').submit();
        }, 500);
    }

    function handleReportSearchKeydown(event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        clearTimeout(reportSearchTimeout);
        document.getElementById('reportFilterForm').submit();
    }
</script>
@endsection
