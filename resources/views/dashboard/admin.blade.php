@extends('layouts.app')

@section('title', 'Dashboard Admin Gudang')
@section('page-title', 'Dashboard Admin Gudang')
@section('page-subtitle', 'Tahun Anggaran ' . $stats['fiscal_year'])

@section('content')
    <div class="stats-row stats-row-5">
        {{-- Total POLRI --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total POLRI</span>
                <div class="stat-icon-sm" style="background:var(--success-bg);color:var(--success);"><i
                        class="ri-team-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_polri']) }}</div>
            <div class="stat-footer">Personil Aktif</div>
        </div>

        {{-- Total PNS --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total PNS/P3K</span>
                <div class="stat-icon-sm" style="background:var(--warning-bg);color:var(--warning);"><i
                        class="ri-user-star-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_pns']) }}</div>
            <div class="stat-footer">Personil Aktif</div>
        </div>

        {{-- Total Personil (Combined) --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Personil</span>
                <div class="stat-icon-sm" style="background:var(--brand-bg);color:var(--brand);"><i
                        class="ri-group-line"></i></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
            <div class="stat-footer">Polri + PNS</div>
        </div>

        {{-- Sudah Isi Ukuran --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Sudah Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:var(--info-bg);color:var(--info);"><i
                        class="ri-check-double-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--info);">{{ number_format($stats['personnel_submitted']) }}</div>
            <div class="stat-footer"><span class="up"><i class="ri-arrow-up-s-fill"></i> {{ $stats['fill_rate'] }}%</span>
                progres</div>
        </div>

        {{-- Belum Isi Ukuran --}}
        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Belum Isi Ukuran</span>
                <div class="stat-icon-sm" style="background:#fef2f2;color:var(--danger);"><i class="ri-time-line"></i></div>
            </div>
            <div class="stat-value" style="color:var(--danger);">{{ number_format($stats['personnel_pending']) }}</div>
            <div class="stat-footer">Menunggu ukuran wajib</div>
        </div>
    </div>

    @php
        $total = $stats['total_personnel'];
        $done = $stats['personnel_submitted'];
        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
    @endphp

    {{-- Overall Progress --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="ri-bar-chart-box-line" style="margin-right:8px; color:var(--accent);"></i> Progres Ukuran
                Keseluruhan</h3>
            <span
                style="font-size:24px; font-weight:800; color:{{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};">{{ $pct }}%</span>
        </div>
        <div class="card-body">
            <div class="progress" style="height:14px; border-radius:7px;">
                <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}"
                    style="width:{{ $pct }}%;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:12px; font-size:13px; color:#64748B;">
                <span>{{ number_format($done) }} dari {{ number_format($total) }} personil sudah isi ukuran wajib</span>
                <span>{{ number_format($stats['total_submissions']) }} total submission</span>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="ri-flashlight-line" style="margin-right:8px; color:var(--accent);"></i> Aksi Cepat</h3>
        </div>
        <div class="card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('admin.warehouse-items.index') }}" class="btn btn-primary"><i class="ri-archive-line"></i> Data Gudang</a>
            <a href="{{ route('admin.warehouse-items.dispense-form') }}" class="btn btn-outline"><i class="ri-inbox-unarchive-line"></i> Pengeluaran Barang</a>
            <a href="{{ route('admin.warehouse-items.reports') }}" class="btn btn-outline"><i class="ri-file-chart-line"></i> Laporan Gudang</a>
        </div>
    </div>
@endsection
