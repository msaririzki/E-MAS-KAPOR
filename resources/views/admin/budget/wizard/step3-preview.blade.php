@extends('layouts.app')

@section('title', 'Pratinjau - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Pratinjau</span>
@endsection

@section('content')
{{-- Hero Section --}}
<div class="package-hero">
    <div class="package-hero-inner">
        <div class="package-hero-back">
            <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="btn-back">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
        <div class="package-hero-content">
            <div class="package-title-wrapper" style="justify-content: space-between; width: 100%;">
                <div>
                    <h1 class="package-title">Pratinjau & Hitung Anggaran</h1>
                    <p class="package-desc" style="margin-top: 6px;">
                        Ringkasan seluruh item yang dipilih, penerima, dan total anggaran yang dibutuhkan.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn-action-primary">
                        <i class="ri-check-line" style="margin-right: 6px;"></i> Selesai & Simpan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Wizard Steps Container --}}
<div class="wizard-steps-container">
    <div class="wizard-track">
        {{-- Step 1 --}}
        <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="wizard-step-card completed">
            <div class="wizard-step-header">
                <div class="wizard-step-number"><i class="ri-check-line"></i></div>
                <div class="wizard-step-title">
                    <h3>Pilih Barang</h3>
                    <p>Barang berhasil dipilih</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->count() }}</span>
                    <span class="label">Barang</span>
                </div>
                <i class="ri-checkbox-circle-fill wizard-step-arrow" style="color: #10B981; background: transparent;"></i>
            </div>
        </a>

        {{-- Step 2 --}}
        <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="wizard-step-card completed">
            <div class="wizard-step-header">
                <div class="wizard-step-number"><i class="ri-check-line"></i></div>
                <div class="wizard-step-title">
                    <h3>Tentukan Penerima</h3>
                    <p>Satker & filter ditetapkan</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) }}</span>
                    <span class="label">Satker &bull; {{ number_format($budgetPackage->items->sum(fn($i) => $i->recipients->sum('matched_count')), 0, ',', '.') }} Personel</span>
                </div>
                <i class="ri-checkbox-circle-fill wizard-step-arrow" style="color: #10B981; background: transparent;"></i>
            </div>
        </a>

        {{-- Step 3 --}}
        <div class="wizard-step-card active">
            <div class="wizard-step-header">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-title">
                    <h3>Pratinjau & Hitung</h3>
                    <p>Ringkasan total & anggaran</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value highlight">
                    <span class="num" style="color: #C62828; font-size: 20px;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    <span class="label">Total Anggaran (Estimasi)</span>
                </div>
                <div class="active-indicator">Tinjauan Akhir</div>
            </div>
        </div>
    </div>
</div>


{{-- Detailed Table --}}
<div class="premium-card">
    <div class="card-header border-b">
        <div>
            <h3 class="card-title">Rincian Paket</h3>
            <p class="card-subtitle">Daftar item dan estimasi kuantitas yang dibutuhkan</p>
        </div>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">NO</th>
                        <th>NAMA BARANG</th>
                        <th>KATEGORI</th>
                        <th>SATUAN</th>
                        <th style="text-align: right;">HARGA</th>
                        <th style="text-align: center;">QTY</th>
                        <th style="text-align: right;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgetPackage->items as $idx => $item)
                    <tr>
                        <td class="text-secondary">{{ $idx + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->kaporItem->item_name }}</div>
                            @if($item->recipients->count() > 0)
                            <div class="item-satker-list">
                                @foreach($item->recipients as $r)
                                    <span>{{ $r->satker->name }} ({{ $r->matched_count }})</span>{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td><span class="badge badge-neutral badge-sm">{{ $item->kaporItem->category }}</span></td>
                        <td class="text-secondary">{{ $item->kaporItem->unit ?? 'PCS' }}</td>
                        <td style="text-align: right;" class="font-medium">{{ $item->formatted_price }}</td>
                        <td style="text-align: center;">
                            <span class="qty-badge">{{ number_format($item->calculated_qty) }}</span>
                        </td>
                        <td style="text-align: right;" class="text-red font-bold">{{ $item->formatted_total }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="5" class="text-right">GRAND TOTAL</td>
                        <td class="text-center">{{ number_format($totalRecipients) }}</td>
                        <td class="text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Per Item Breakdown --}}
<div class="section-title-wrap" style="margin-top: 32px; margin-bottom: 16px;">
    <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0;">Breakdown Per Barang</h3>
</div>

<div class="breakdown-list">
    @foreach($budgetPackage->items as $item)
    @if($item->recipients->count() > 0)
    <div class="premium-accordion">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="accordion-title">
                <i class="ri-arrow-down-s-line chevron"></i>
                <div class="title-text">
                    <span class="name">{{ $item->kaporItem->item_name }}</span>
                    <span class="sub" style="font-size: 12px; font-weight: 400; color: #64748B; margin-left: 8px;">
                        {{ $item->recipients->count() }} Satker
                    </span>
                </div>
            </div>
            <div class="accordion-meta text-red font-bold">
                {{ $item->formatted_total }}
            </div>
        </div>
        <div class="accordion-body flush">
            <div class="table-wrap">
                <table class="modern-table detail-table">
                    <thead>
                        <tr>
                            <th>SATKER KESATUAN</th>
                            <th style="text-align: center;">PERSONIL</th>
                            <th style="text-align: right;">HARGA</th>
                            <th style="text-align: right;">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->recipients as $r)
                        <tr>
                            <td class="font-medium text-dark">{{ $r->satker->name }}</td>
                            <td style="text-align: center;">
                                <span class="qty-badge neutral">{{ $r->matched_count }}</span>
                            </td>
                            <td style="text-align: right;" class="text-secondary">{{ $item->formatted_price }}</td>
                            <td style="text-align: right;" class="font-medium text-dark">
                                Rp {{ number_format($r->matched_count * $item->effective_price, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="subtotal-row">
                            <td class="font-bold text-dark text-right">TOTAL</td>
                            <td style="text-align: center; font-weight: 700;">{{ $item->calculated_qty }}</td>
                            <td></td>
                            <td style="text-align: right; font-weight: 700;" class="text-red">{{ $item->formatted_total }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>
@endsection

@section('styles')
<style>
    /* ── Hero Section & Wizard (Copied from Step 2) ── */
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
    .package-hero-content { flex: 1; }
    .package-title-wrapper { display: flex; align-items: center; flex-wrap: wrap; }
    .package-title { font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; }
    .package-desc { color: #64748B; font-size: 14px; margin: 0; line-height: 1.5; }
    
    .btn-action-primary {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 20px; border-radius: 10px;
        background: #C62828; color: #fff; font-size: 14px; font-weight: 600;
        text-decoration: none; transition: all 0.2s; border: 1px solid #B91C1C;
        box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
    }
    .btn-action-primary:hover {
        background: #B91C1C; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(198, 40, 40, 0.2);
    }

    /* ── Wizard Steps ── */
    .wizard-steps-container { margin-bottom: 24px; }
    .wizard-track { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    
    .wizard-step-card {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        display: flex; flex-direction: column; justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative; overflow: hidden;
    }
    .wizard-step-card:hover:not(.pending):not(.active) {
        border-color: #C62828; box-shadow: 0 4px 12px rgba(198, 40, 40, 0.08); transform: translateY(-2px);
    }
    
    /* Completed Step */
    .wizard-step-card.completed { border-color: #BBF7D0; background: #F0FDF4; opacity: 0.8; }
    .wizard-step-card.completed .wizard-step-number { background: #10B981; color: #fff; border-color: #10B981; }
    .wizard-step-card.completed:hover { opacity: 1; border-color: #22C55E; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34, 197, 94, 0.1); }
    
    /* Active Step */
    .wizard-step-card.active { border-color: #C62828; box-shadow: 0 4px 12px rgba(198, 40, 40, 0.08); transform: translateY(-2px); }
    .wizard-step-card.active::after {
        content: ''; position: absolute; bottom: 0; right: 0; width: 100px; height: 100px;
        background: linear-gradient(135deg, transparent, rgba(198, 40, 40, 0.05));
        border-radius: 100%; transform: translate(10%, 10%);
    }
    .wizard-step-card.active .wizard-step-number { background: #C62828; color: #fff; border-color: #C62828; }
    
    /* Pending Step */
    .wizard-step-card.pending { opacity: 0.6; pointer-events: none; background: #F8FAFC; border-style: dashed; }
    .wizard-step-card.pending .wizard-step-number { background: #F1F5F9; color: #94A3B8; border-color: #E2E8F0; }

    .wizard-step-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px; position: relative; z-index: 2; }
    .wizard-step-number {
        width: 36px; height: 36px; border-radius: 10px;
        font-size: 16px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1px solid transparent;
    }
    .wizard-step-title h3 { font-size: 15px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .wizard-step-title p { font-size: 12px; color: #64748B; margin: 0; line-height: 1.4; }
    
    .wizard-step-body { display: flex; align-items: flex-end; justify-content: space-between; margin-top: auto; position: relative; z-index: 2; }
    .wizard-step-body .stat-value { display: flex; flex-direction: column; }
    .wizard-step-body .stat-value .num { font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1; margin-bottom: 4px; letter-spacing: -0.5px; }
    .wizard-step-body .stat-value .label { font-size: 11px; color: #94A3B8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
    
    .wizard-step-arrow { font-size: 20px; color: #CBD5E1; }
    .active-indicator { font-size: 11px; font-weight: 700; color: #C62828; background: #FEF2F2; padding: 4px 10px; border-radius: 12px; }

    /* ── Summary Stats ── */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px; margin-top: 20px;
    }
    .stat-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03); border-color: #CBD5E1; }
    .stat-card.grand-total-card { border: 1px solid #FECACA; background: #FEF2F2; }
    .stat-card.grand-total-card:hover { border-color: #FCA5A5; }
    
    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
    }
    .stat-label { font-size: 13px; font-weight: 600; color: #64748B; margin-bottom: 2px; }
    .stat-value { font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1.2; }

    /* ── Utilities ── */
    .text-secondary { color: #64748B; }
    .text-dark { color: #0F172A; }
    .text-red { color: #C62828; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-medium { font-weight: 500; }
    .font-bold { font-weight: 700; }
    
    .badge-sm { padding: 2px 8px; font-size: 10px; border-radius: 6px; }

    /* ── Premium Cards & Tables ── */
    .premium-card {
        background: #ffffff; border-radius: 16px; margin-bottom: 24px;
        border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow: hidden;
    }
    .card-header { padding: 20px 24px; }
    .card-header.border-b { border-bottom: 1px solid #F1F5F9; }
    .card-title { font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 4px 0; }
    .card-subtitle { font-size: 13px; color: #64748B; margin: 0; }
    
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .modern-table th {
        background: #F8FAFC; color: #64748B; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 24px;
        border-bottom: 1px solid #E2E8F0; text-align: left;
    }
    .modern-table td {
        padding: 16px 24px; font-size: 13px; color: #334155;
        border-bottom: 1px solid #F1F5F9; vertical-align: middle;
    }
    .modern-table tbody tr:hover { background: #F8FAFC; }
    .modern-table tbody tr:last-child td { border-bottom: none; }
    
    .item-name { font-weight: 600; color: #0F172A; font-size: 14px; }
    .item-satker-list { font-size: 11px; color: #94A3B8; margin-top: 4px; line-height: 1.4; }
    .qty-badge {
        display: inline-block; padding: 4px 12px; background: #FEF2F2; color: #C62828;
        border-radius: 20px; font-weight: 700; font-size: 12px;
    }
    .qty-badge.neutral { background: #F1F5F9; color: #475569; }

    /* Footer Grand Total */
    .grand-total-row td {
        background: #FEF2F2; color: #C62828; font-weight: 800; font-size: 14px;
        border-top: 1px solid #FECACA; padding: 18px 24px;
    }

    /* ── Accordion Breakdown ── */
    .breakdown-list { display: flex; flex-direction: column; gap: 12px; }
    .premium-accordion {
        background: #ffffff; border-radius: 12px; border: 1px solid #E2E8F0; transition: all 0.2s;
    }
    .premium-accordion:hover { border-color: #CBD5E1; }
    .accordion-header {
        padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;
        cursor: pointer; user-select: none;
    }
    .accordion-title { display: flex; align-items: center; gap: 12px; }
    .accordion-title .chevron {
        font-size: 20px; color: #94A3B8; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .accordion-title .name { font-size: 14px; font-weight: 600; color: #0F172A; }
    
    .premium-accordion:not(.collapsed) .accordion-title .chevron { transform: rotate(-180deg); }
    .premium-accordion:not(.collapsed) .accordion-header { border-bottom: 1px solid #F1F5F9; }
    
    .accordion-body { padding: 0; }
    .premium-accordion.collapsed .accordion-body { display: none; }
    
    .detail-table th { background: transparent; padding: 12px 20px; border-bottom: 1px solid #F1F5F9; }
    .detail-table td { padding: 12px 20px; }
    .subtotal-row td { background: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 16px 20px; }

    @media (max-width: 768px) {
        .wizard-track { grid-template-columns: 1fr; }
        .stat-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection
