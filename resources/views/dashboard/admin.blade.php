@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Tahun Anggaran ' . $stats['fiscal_year'])

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="ri-user-star-line"></i></div>
        <div class="stat-value">{{ number_format($stats['total_personnel']) }}</div>
        <div class="stat-label">Total Personil</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="ri-building-2-line"></i></div>
        <div class="stat-value">{{ number_format($stats['total_satkers']) }}</div>
        <div class="stat-label">Total Satker</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="ri-check-double-line"></i></div>
        <div class="stat-value">{{ number_format($stats['personnel_submitted']) }}</div>
        <div class="stat-label">Sudah Input</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="ri-time-line"></i></div>
        <div class="stat-value">{{ number_format($stats['personnel_pending']) }}</div>
        <div class="stat-label">Belum Input</div>
    </div>
</div>

@php
    $total = $stats['total_personnel'];
    $done  = $stats['personnel_submitted'];
    $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
@endphp

{{-- Overall Progress --}}
<div class="card">
    <div class="card-header">
        <h3><i class="ri-bar-chart-box-line" style="margin-right:8px; color:var(--accent);"></i> Progres Input Keseluruhan</h3>
        <span style="font-size:24px; font-weight:800; color:{{ $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};">{{ $pct }}%</span>
    </div>
    <div class="card-body">
        <div class="progress" style="height:14px; border-radius:7px;">
            <div class="progress-bar {{ $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red') }}" style="width:{{ $pct }}%;"></div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:12px; font-size:13px; color:#64748B;">
            <span>{{ number_format($done) }} dari {{ number_format($total) }} personil sudah input</span>
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
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary"><i class="ri-group-line"></i> Kelola User</a>
        <a href="{{ route('admin.satkers.index') }}" class="btn btn-outline"><i class="ri-building-2-line"></i> Lihat Satker</a>
        <a href="{{ route('admin.reports') }}" class="btn btn-outline"><i class="ri-file-chart-line"></i> Lihat Laporan</a>
    </div>
</div>
@endsection
