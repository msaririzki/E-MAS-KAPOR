@extends('layouts.app')

@section('title', $budgetPackage->name . ' - ' . $budgetPackage->budgetYear->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="sep">/</span>
    <span class="current">{{ $budgetPackage->name }}</span>
@endsection

@section('content')

{{-- Hero Section --}}
<div class="package-hero">
    <div class="package-hero-inner">
        <div class="package-hero-back">
            <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}" class="btn-back">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
        <div class="package-hero-content">
            <div class="package-title-wrapper">
                <h1 class="package-title">{{ $budgetPackage->name }}</h1>
                <span class="badge badge-package" style="background: {{ $budgetPackage->status_color['bg'] }}; color: {{ $budgetPackage->status_color['text'] }}; border: 1px solid {{ str_replace(')', ', 0.2)', str_replace('rgb', 'rgba', $budgetPackage->status_color['text'])) }};">
                    {{ $budgetPackage->status_label }}
                </span>
            </div>
            <p class="package-desc">
                <i class="ri-calendar-event-line"></i> Tahun Anggaran {{ $budgetPackage->budgetYear->name }} &nbsp; <span class="dot-sep">&bull;</span> &nbsp; {{ $budgetPackage->description ?? 'Tidak ada deskripsi' }}
            </p>
        </div>
    </div>
</div>

{{-- Wizard Steps Container --}}
<div class="wizard-steps-container">
    <div class="wizard-track">
        {{-- Step 1 --}}
        <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="wizard-step-card">
            <div class="wizard-step-header">
                <div class="wizard-step-number">1</div>
                <div class="wizard-step-title">
                    <h3>Pilih Barang</h3>
                    <p>Pilih item kapor yang disertakan</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->count() }}</span>
                    <span class="label">Item</span>
                </div>
                <i class="ri-arrow-right-line wizard-step-arrow"></i>
            </div>
        </a>

        {{-- Step 2 --}}
        <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="wizard-step-card {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
            <div class="wizard-step-header">
                <div class="wizard-step-number">2</div>
                <div class="wizard-step-title">
                    <h3>Tentukan Penerima</h3>
                    <p>Pilih satker & filter personil</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) }}</span>
                    <span class="label">Satker</span>
                </div>
                <i class="ri-arrow-right-line wizard-step-arrow"></i>
            </div>
        </a>

        {{-- Step 3 --}}
        <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-step-card {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
            <div class="wizard-step-header">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-title">
                    <h3>Preview & Hitung</h3>
                    <p>Ringkasan total & anggaran</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value highlight">
                    <span class="num">{{ $budgetPackage->formatted_budget }}</span>
                    <span class="label">Total Anggaran</span>
                </div>
                <i class="ri-arrow-right-line wizard-step-arrow"></i>
            </div>
        </a>
    </div>
</div>

@if($budgetPackage->items->count() > 0)
<div class="layout-grid">
    {{-- Left Column: Summary Table --}}
    <div class="layout-main">
        <div class="card premium-card">
            <div class="card-head">
                <div class="card-title-with-icon">
                    <div class="title-icon"><i class="ri-list-check-3"></i></div>
                    <h3>Item dalam Paket</h3>
                </div>
            </div>
            <div class="card-body flush">
                <div class="table-wrap custom-scrollbar">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th width="5%">NO</th>
                                <th>NAMA ITEM</th>
                                <th>KATEGORI</th>
                                <th class="text-right">HARGA (Rp)</th>
                                <th class="text-center">QTY</th>
                                <th class="text-right">TOTAL (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budgetPackage->items as $idx => $item)
                            <tr>
                                <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="item-name">{{ $item->kaporItem->item_name }}</span>
                                </td>
                                <td><span class="badge badge-neutral badge-sm">{{ $item->kaporItem->category }}</span></td>
                                <td class="text-right text-muted">{{ str_replace('Rp ', '', $item->formatted_price) }}</td>
                                <td class="text-center font-semibold">{{ number_format($item->calculated_qty, 0, ',', '.') }}</td>
                                <td class="text-right font-bold text-dark">{{ str_replace('Rp ', '', $item->formatted_total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-total-row">
                                <td colspan="4" class="text-right">GRAND TOTAL KESELURUHAN</td>
                                <td class="text-center qty-total">{{ number_format($budgetPackage->items->sum('calculated_qty'), 0, ',', '.') }}</td>
                                <td class="text-right grand-total">{{ $budgetPackage->formatted_budget }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Export Actions --}}
    <div class="layout-sidebar">
        <div class="action-panel">
            <div class="panel-header">
                <h3>Aksi & Ekspor</h3>
                <p>Opsi cetak dan unduh data paket</p>
            </div>
            <div class="export-actions-grid">
                <a href="{{ route('admin.budget.recap', $budgetPackage) }}" class="export-btn export-blue">
                    <div class="export-icon"><i class="ri-file-list-3-line"></i></div>
                    <div class="export-info">
                        <h4>Rekapan HTML</h4>
                        <p>Lihat di browser</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.budget.invoice', $budgetPackage) }}" class="export-btn export-orange">
                    <div class="export-icon"><i class="ri-file-text-line"></i></div>
                    <div class="export-info">
                        <h4>Invoice HPS</h4>
                        <p>Generate format HPS</p>
                    </div>
                </a>

                <div class="export-divider"><span>Format Excel</span></div>

                <a href="{{ route('admin.budget.export-csv', $budgetPackage) }}" class="export-btn export-green" data-download data-estimate="10">
                    <div class="export-icon"><i class="ri-file-excel-line"></i></div>
                    <div class="export-info">
                        <h4>Export Rekapan</h4>
                        <p>Unduh file .xlsx</p>
                    </div>
                    <div class="export-loading">
                        <i class="ri-loader-4-line spinner"></i>
                    </div>
                </a>
                
                <a href="{{ route('admin.budget.export-detail', $budgetPackage) }}" class="export-btn export-purple" data-download data-estimate="20">
                    <div class="export-icon"><i class="ri-team-line"></i></div>
                    <div class="export-info">
                        <h4>Export Nominatif</h4>
                        <p>Detail penerima per personil</p>
                    </div>
                    <div class="export-loading">
                        <i class="ri-loader-4-line spinner"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('styles')
<style>
    /* ── Utilities ── */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-muted { color: #64748B; }
    .text-dark { color: #0F172A; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }
    .dot-sep { color: #94A3B8; }

    /* ── Hero Section ── */
    .package-hero {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
    }
    .package-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 4px;
        background: linear-gradient(90deg, #C62828, #E53935, #EF5350);
    }
    .package-hero-inner {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .btn-back {
        display: flex;
        align-items: center; justify-content: center;
        width: 40px; height: 40px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        color: #475569;
        font-size: 20px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-back:hover {
        background: #C62828;
        color: #ffffff;
        border-color: #C62828;
        transform: translateX(-2px);
    }
    .package-hero-content {
        flex: 1;
    }
    .package-title-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .package-title {
        font-size: 24px;
        font-weight: 800;
        color: #0F172A;
        letter-spacing: -0.5px;
        margin: 0;
    }
    .badge-package {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .package-desc {
        color: #64748B;
        font-size: 14px;
        display: flex;
        align-items: center;
        margin: 0;
    }
    .package-desc i {
        margin-right: 6px;
        color: #94A3B8;
        font-size: 16px;
    }

    /* ── Wizard Steps ── */
    .wizard-steps-container {
        margin-bottom: 24px;
    }
    .wizard-track {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .wizard-step-card {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .wizard-step-card::after {
        content: '';
        position: absolute;
        bottom: 0; right: 0;
        width: 100px; height: 100px;
        background: linear-gradient(135deg, transparent, rgba(198, 40, 40, 0.03));
        border-radius: 100%;
        transform: translate(30%, 30%);
        transition: transform 0.3s;
    }
    .wizard-step-card:hover {
        border-color: #C62828;
        box-shadow: 0 10px 15px -3px rgba(198, 40, 40, 0.08), 0 4px 6px -4px rgba(198, 40, 40, 0.04);
        transform: translateY(-2px);
    }
    .wizard-step-card:hover::after {
        transform: translate(10%, 10%);
        background: linear-gradient(135deg, transparent, rgba(198, 40, 40, 0.08));
    }
    .wizard-step-card.disabled {
        opacity: 0.6;
        pointer-events: none;
        background: #F8FAFC;
    }
    .wizard-step-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 24px;
    }
    .wizard-step-number {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: #FEF2F2;
        color: #C62828;
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #FECACA;
    }
    .wizard-step-card:hover .wizard-step-number {
        background: #C62828;
        color: #ffffff;
        border-color: #C62828;
    }
    .wizard-step-title h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1E293B;
        margin: 0 0 2px 0;
    }
    .wizard-step-title p {
        font-size: 12px;
        color: #64748B;
        margin: 0;
        line-height: 1.4;
    }
    .wizard-step-body {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: auto;
    }
    .wizard-step-body .stat-value {
        display: flex;
        flex-direction: column;
    }
    .wizard-step-body .stat-value .num {
        font-size: 22px;
        font-weight: 800;
        color: #0F172A;
        line-height: 1;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }
    .wizard-step-body .stat-value.highlight .num {
        color: #C62828;
    }
    .wizard-step-body .stat-value .label {
        font-size: 11px;
        color: #94A3B8;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .wizard-step-arrow {
        font-size: 20px;
        color: #CBD5E1;
        background: #F8FAFC;
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .wizard-step-card:hover .wizard-step-arrow {
        color: #C62828;
        background: #FEF2F2;
        transform: translateX(3px);
    }

    /* ── Layout Grid (Table + Sidebar) ── */
    .layout-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
    }

    /* ── Premium Card & Table ── */
    .premium-card {
        border-color: #E2E8F0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        border-radius: 16px;
        overflow: hidden;
    }
    .card-title-with-icon {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .title-icon {
        width: 32px; height: 32px;
        background: #EFF6FF;
        color: #3B82F6;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    .premium-card .card-head h3 {
        font-size: 16px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .modern-table thead th {
        background: #F8FAFC;
        padding: 12px 16px;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #E2E8F0;
        border-top: 1px solid #E2E8F0;
    }
    .modern-table tbody td {
        padding: 14px 16px;
        font-size: 13.5px;
        border-bottom: 1px solid #F1F5F9;
        color: #334155;
        vertical-align: middle;
    }
    .modern-table tbody tr:hover td {
        background: #F8FAFC;
    }
    .item-name {
        font-weight: 600;
        color: #1E293B;
    }
    .badge-sm {
        padding: 2px 6px;
        font-size: 10.5px;
        border-radius: 4px;
    }

    .table-total-row {
        background: #F8FAFC;
    }
    .table-total-row td {
        padding: 16px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        border-top: 2px solid #E2E8F0;
    }
    .qty-total {
        color: #0F172A;
        font-size: 14px;
    }
    .grand-total {
        color: #C62828;
        font-size: 16px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    /* ── Export Action Panel ── */
    .action-panel {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .panel-header {
        margin-bottom: 16px;
    }
    .panel-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: #0F172A;
        margin: 0 0 4px 0;
    }
    .panel-header p {
        font-size: 12px;
        color: #64748B;
        margin: 0;
    }
    .export-actions-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .export-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 8px 0;
        color: #94A3B8;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .export-divider::before, .export-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px dashed #E2E8F0;
    }
    .export-divider span {
        padding: 0 10px;
    }

    .export-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    .export-btn:hover {
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transform: translateY(-1px);
    }
    .export-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        transition: transform 0.2s;
    }
    .export-btn:hover .export-icon {
        transform: scale(1.1);
    }
    .export-info {
        flex: 1;
    }
    .export-info h4 {
        font-size: 13px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0;
    }
    .export-info p {
        font-size: 12px; color: #64748B; margin: 0;
    }

    /* Color Variants for Export Cards */
    .export-blue:hover { background: #EFF6FF; border-color: #BFDBFE; }
    .export-blue .export-icon { background: #DBEAFE; color: #3B82F6; }
    
    .export-orange:hover { background: #FFF7ED; border-color: #FED7AA; }
    .export-orange .export-icon { background: #FFEDD5; color: #F97316; }

    .export-green:hover { background: #F0FDF4; border-color: #BBF7D0; }
    .export-green .export-icon { background: #DCFCE7; color: #22C55E; }

    .export-purple:hover { background: #FAF5FF; border-color: #E9D5FF; }
    .export-purple .export-icon { background: #F3E8FF; color: #A855F7; }

    /* Loading overlay */
    .export-btn .export-loading {
        position: absolute; inset: 0;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(2px);
        display: none; align-items: center; justify-content: center;
        border-radius: 12px;
        z-index: 5;
    }
    .export-btn.is-loading { pointer-events: none; }
    .export-btn.is-loading .export-loading { display: flex; }
    .spinner {
        font-size: 24px; color: #C62828;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* Responsive */
    @media (max-width: 1024px) {
        .layout-grid { grid-template-columns: 1fr; }
        .wizard-track { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .action-panel { display: flex; flex-direction: column; }
        .export-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .export-divider { grid-column: 1 / -1; }
    }
    @media (max-width: 768px) {
        .wizard-track { grid-template-columns: 1fr; }
        .package-title-wrapper { flex-direction: column; align-items: flex-start; gap: 4px; }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Download Loading State
    document.querySelectorAll('.export-btn[data-download]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            btn.classList.add('is-loading');
            var estimatedSeconds = parseInt(btn.dataset.estimate || '5');
            setTimeout(function() {
                btn.classList.remove('is-loading');
            }, estimatedSeconds * 1000);
        });
    });
});
</script>
@endsection


