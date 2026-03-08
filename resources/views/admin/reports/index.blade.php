@extends('layouts.app')

@section('title', 'Pusat Laporan')
@section('breadcrumb', 'Laporan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Pusat Laporan</h1>
            <p>Unduh dan kelola laporan data E-MAS KAPOR dalam format Excel.</p>
        </div>
    </div>
</div>

<div class="report-grid">

    {{-- 1. Rekap Personil per Satker --}}
    <div class="report-card">
        <div class="report-icon" style="background: var(--brand-bg); color: var(--brand);">
            <i class="ri-team-line"></i>
        </div>
        <div class="report-body">
            <h3>Rekap Personil per Satker</h3>
            <p>Ringkasan jumlah personil per Satuan Kerja: Total, Sudah Input, Belum Input, dan Persentase kelengkapan data.</p>
            <div class="report-meta">
                <span class="report-badge"><i class="ri-file-excel-2-line"></i> Excel (.xlsx)</span>
            </div>
        </div>
        <div class="report-action">
            <a href="{{ route('admin.reports.export', ['type' => 'personil-satker']) }}" class="report-download-btn">
                <i class="ri-download-2-line"></i> Unduh
            </a>
        </div>
    </div>

    {{-- 2. Rekap Ukuran KAPOR --}}
    <div class="report-card">
        <div class="report-icon" style="background: var(--info-bg); color: var(--info);">
            <i class="ri-t-shirt-line"></i>
        </div>
        <div class="report-body">
            <h3>Rekap Ukuran KAPOR</h3>
            <p>Distribusi ukuran per item KAPOR (Topi, Kemeja, Celana, Sepatu, dsb). Berapa orang per ukuran S, M, L, XL, dan seterusnya.</p>
            <div class="report-meta">
                <span class="report-badge"><i class="ri-file-excel-2-line"></i> Excel (.xlsx)</span>
            </div>
        </div>
        <div class="report-action">
            <a href="{{ route('admin.reports.export', ['type' => 'ukuran-kapor']) }}" class="report-download-btn">
                <i class="ri-download-2-line"></i> Unduh
            </a>
        </div>
    </div>

    {{-- 3. Rekap Anggaran per Paket --}}
    <div class="report-card">
        <div class="report-icon" style="background: var(--success-bg); color: var(--success);">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="report-body">
            <h3>Rekap Anggaran per Paket</h3>
            <p>Daftar seluruh paket anggaran KAPOR beserta total biaya, jumlah item, dan status masing-masing paket (Draft / Final).</p>
            <div class="report-meta">
                <span class="report-badge"><i class="ri-file-excel-2-line"></i> Excel (.xlsx)</span>
            </div>
        </div>
        <div class="report-action">
            <a href="{{ route('admin.reports.export', ['type' => 'anggaran-paket']) }}" class="report-download-btn">
                <i class="ri-download-2-line"></i> Unduh
            </a>
        </div>
    </div>

    {{-- 4. Data Personil Belum Lengkap --}}
    <div class="report-card">
        <div class="report-icon" style="background: var(--warning-bg); color: var(--warning);">
            <i class="ri-error-warning-line"></i>
        </div>
        <div class="report-body">
            <h3>Data Personil Belum Lengkap</h3>
            <p>Daftar lengkap personil yang datanya masih kosong atau bermasalah (belum ada NRP, Pangkat, atau Ukuran KAPOR).</p>
            <div class="report-meta">
                <span class="report-badge"><i class="ri-file-excel-2-line"></i> Excel (.xlsx)</span>
            </div>
        </div>
        <div class="report-action">
            <a href="{{ route('admin.reports.export', ['type' => 'personil-belum-lengkap']) }}" class="report-download-btn">
                <i class="ri-download-2-line"></i> Unduh
            </a>
        </div>
    </div>

    {{-- 5. Riwayat Audit Log --}}
    <div class="report-card">
        <div class="report-icon" style="background: var(--brand-bg); color: var(--brand);">
            <i class="ri-history-line"></i>
        </div>
        <div class="report-body">
            <h3>Riwayat Audit Log</h3>
            <p>Rekap seluruh aktivitas yang tercatat oleh sistem: Login, impor data, perubahan ukuran, dan aksi lainnya.</p>
            <div class="report-meta">
                <span class="report-badge"><i class="ri-file-excel-2-line"></i> Excel (.xlsx)</span>
            </div>
        </div>
        <div class="report-action">
            <a href="{{ route('admin.reports.export', ['type' => 'audit-log']) }}" class="report-download-btn">
                <i class="ri-download-2-line"></i> Unduh
            </a>
        </div>
    </div>

</div>

@endsection

@section('styles')
<style>
    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
        gap: 20px;
    }
    @media (max-width: 500px) {
        .report-grid { grid-template-columns: 1fr; }
    }
    .report-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md, 12px);
        padding: 24px;
        display: flex;
        gap: 16px;
        align-items: flex-start;
        transition: all 0.2s ease;
        position: relative;
    }
    .report-card:hover {
        border-color: var(--brand);
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }
    .report-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .report-body {
        flex: 1;
        min-width: 0;
    }
    .report-body h3 {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
    }
    .report-body p {
        font-size: 12.5px;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 10px;
    }
    .report-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .report-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: var(--success);
        background: var(--success-bg);
        padding: 3px 8px;
        border-radius: 6px;
    }
    .report-action {
        display: flex;
        align-items: center;
        flex-shrink: 0;
        align-self: center;
    }
    .report-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--brand);
        color: #fff;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .report-download-btn:hover {
        background: #b71c1c;
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(198, 40, 40, 0.3);
    }
</style>
@endsection
