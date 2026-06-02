@extends('layouts.app')

@section('title', 'Pratinjau - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Tahap 3: Pratinjau</span>
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
                        Tinjauan akhir item, kuantitas personil, dan estimasi anggaran sebelum disimpan.
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


{{-- Main Summary Table --}}
<div class="content-panel">
    <div class="panel-header">
        <div class="panel-title-wrap">
            <div class="panel-icon"><i class="ri-file-list-3-line"></i></div>
            <div>
                <h3 class="panel-title">Rincian Barang & Kuantitas</h3>
                <p class="panel-subtitle">Daftar lengkap item yang telah dikonfigurasi penerimanya</p>
            </div>
        </div>
    </div>
    <div class="panel-body flush">
        <div class="table-wrap custom-scrollbar">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">NO</th>
                        <th width="35%">NAMA BARANG</th>
                        <th width="15%">KATEGORI</th>
                        <th width="15%" class="text-right">HARGA (Rp)</th>
                        <th width="10%" class="text-center">QTY</th>
                        <th width="20%" class="text-right">TOTAL (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgetPackage->items as $idx => $item)
                    <tr class="item-row {{ $item->recipients->count() > 0 ? 'has-dropdown' : '' }}">
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar-icon">
                                    <i class="ri-shirt-line"></i>
                                </div>
                                <div>
                                    <div class="item-primary-name">{{ $item->kaporItem->item_name }}</div>
                                    @if($item->recipients->count() > 0)
                                        <div class="item-meta cursor-pointer" onclick="toggleSatkerList({{ $idx }})">
                                            <i class="ri-building-4-line"></i> Diberikan ke <strong>{{ $item->recipients->count() }} Satker</strong>
                                            <i class="ri-arrow-down-s-line" id="icon-satkers-{{ $idx }}" style="font-size: 14px; margin-left: 2px; transition: transform 0.2s;"></i>
                                        </div>
                                    @else
                                        <span class="badge-soft badge-red mt-1">Belum ada penerima</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-soft badge-blue">{{ $item->kaporItem->category }}</span></td>
                        <td class="text-right font-medium text-slate-600">{{ $item->formatted_price }}</td>
                        <td class="text-center">
                            @if($item->calculated_qty > 0)
                                <div class="qty-box">{{ number_format($item->calculated_qty) }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right font-bold text-red-600">
                            {{ $item->formatted_total }}
                        </td>
                    </tr>
                    @if($item->recipients->count() > 0)
                    {{-- Full-width Dropdown Satker List (Hidden by default) --}}
                    <tr id="satkers-{{ $idx }}" class="satker-dropdown-row hidden">
                        <td colspan="6" style="padding: 0; border-bottom: none;">
                            <div class="satker-dropdown-wrapper">
                                <div class="satker-grid">
                                    @foreach($item->recipients as $r)
                                        <div class="satker-chip">
                                            <span class="satker-col" title="{{ $r->satker->name }}">{{ $r->satker->name }}</span>
                                            <span class="qty-col">{{ $r->matched_count }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="tfoot-total">
                        <td colspan="4" class="text-right font-bold text-slate-700">TOTAL KESELURUHAN</td>
                        <td class="text-center font-bold text-slate-900 text-lg">{{ number_format($totalRecipients) }}</td>
                        <td class="text-right font-bold text-red-700 text-lg">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Breakdown Accordion --}}
<div class="mt-8 mb-4">
    <h3 class="text-lg font-bold text-slate-800">Rincian Biaya Per Barang</h3>
    <p class="text-sm text-slate-500">Klik pada setiap barang untuk melihat rincian biaya tiap Satuan Kerja.</p>
</div>

<div class="accordion-container">
    @foreach($budgetPackage->items as $item)
    @if($item->recipients->count() > 0)
    <div class="accordion-item collapsed">
        <div class="accordion-header" onclick="this.parentElement.classList.toggle('collapsed')">
            <div class="accordion-left">
                <div class="acc-icon"><i class="ri-price-tag-3-line"></i></div>
                <div class="acc-title-group">
                    <h4>{{ $item->kaporItem->item_name }}</h4>
                    <span class="acc-subtitle">{{ $item->recipients->count() }} Satker Penerima</span>
                </div>
            </div>
            <div class="accordion-right">
                <div class="acc-total">{{ $item->formatted_total }}</div>
                <div class="acc-chevron"><i class="ri-arrow-down-s-line"></i></div>
            </div>
        </div>
        <div class="accordion-content">
            <div class="table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th width="40%">SATKER PENERIMA</th>
                            <th width="20%" class="text-center">JUMLAH PERSONIL</th>
                            <th width="20%" class="text-right">HARGA (Rp)</th>
                            <th width="20%" class="text-right">SUBTOTAL (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->recipients as $r)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $r->satker->name }}</td>
                            <td class="text-center">
                                <span class="inline-qty">{{ number_format($r->matched_count) }}</span>
                            </td>
                            <td class="text-right text-slate-500">{{ $item->formatted_price }}</td>
                            <td class="text-right font-semibold text-slate-800">
                                Rp {{ number_format($r->matched_count * $item->effective_price, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-right font-bold text-slate-600">TOTAL KESELURUHAN</td>
                            <td class="text-center font-bold text-slate-800">{{ number_format($item->calculated_qty) }}</td>
                            <td></td>
                            <td class="text-right font-bold text-red-600">{{ $item->formatted_total }}</td>
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
    /* ── Hero Section & Wizard (Tetap) ── */
    .package-hero {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
    }
    .package-hero::before {
        content: ''; position: absolute; top: 0; left: 0;
        width: 100%; height: 4px;
        background: linear-gradient(90deg, #C62828, #E53935, #EF5350);
    }
    .package-hero-inner { display: flex; align-items: flex-start; gap: 16px; }
    .btn-back {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; background: #F8FAFC; border: 1px solid #E2E8F0;
        border-radius: 10px; color: #475569; font-size: 18px; transition: all 0.2s; text-decoration: none;
    }
    .btn-back:hover { background: #C62828; color: #ffffff; border-color: #C62828; transform: translateX(-2px); }
    .package-hero-content { flex: 1; }
    .package-title-wrapper { display: flex; align-items: center; flex-wrap: wrap; }
    .package-title { font-size: 20px; font-weight: 800; color: #0F172A; margin: 0; }
    .package-desc { color: #64748B; font-size: 13px; margin: 0; line-height: 1.4; }
    
    .btn-action-primary {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 8px 16px; border-radius: 8px;
        background: #C62828; color: #fff; font-size: 13px; font-weight: 600;
        text-decoration: none; transition: all 0.2s; border: 1px solid #B91C1C;
        box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
    }
    .btn-action-primary:hover {
        background: #B91C1C; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(198, 40, 40, 0.2);
    }

    /* ── Wizard Steps (Tetap) ── */
    .wizard-steps-container { margin-bottom: 20px; }
    .wizard-track { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .wizard-step-card {
        background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;
        text-decoration: none; color: inherit; display: flex; flex-direction: column; justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
    }
    .wizard-step-card.completed { border-color: #BBF7D0; background: #F0FDF4; opacity: 0.8; }
    .wizard-step-card.completed .wizard-step-number { background: #10B981; color: #fff; border-color: #10B981; }
    .wizard-step-card.active { border-color: #C62828; box-shadow: 0 4px 12px rgba(198, 40, 40, 0.08); transform: translateY(-2px); }
    .wizard-step-card.active::after {
        content: ''; position: absolute; bottom: 0; right: 0; width: 100px; height: 100px;
        background: linear-gradient(135deg, transparent, rgba(198, 40, 40, 0.05)); border-radius: 100%; transform: translate(10%, 10%);
    }
    .wizard-step-card.active .wizard-step-number { background: #C62828; color: #fff; border-color: #C62828; }
    .wizard-step-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; position: relative; z-index: 2; }
    .wizard-step-number {
        width: 32px; height: 32px; border-radius: 8px; font-size: 14px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid transparent;
    }
    .wizard-step-title h3 { font-size: 14px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .wizard-step-title p { font-size: 11px; color: #64748B; margin: 0; line-height: 1.4; }
    .wizard-step-body { display: flex; align-items: flex-end; justify-content: space-between; margin-top: auto; position: relative; z-index: 2; }
    .wizard-step-body .stat-value { display: flex; flex-direction: column; }
    .wizard-step-body .stat-value .num { font-size: 18px; font-weight: 800; color: #0F172A; line-height: 1; margin-bottom: 4px; letter-spacing: -0.5px; }
    .wizard-step-body .stat-value .label { font-size: 10px; color: #94A3B8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
    .wizard-step-arrow { font-size: 18px; color: #CBD5E1; }
    .active-indicator { font-size: 10px; font-weight: 700; color: #C62828; background: #FEF2F2; padding: 4px 8px; border-radius: 10px; }

    /* ── Utilities Baru ── */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-muted { color: #94A3B8; }
    .text-slate-500 { color: #64748B; }
    .text-slate-600 { color: #475569; }
    .text-slate-700 { color: #334155; }
    .text-slate-800 { color: #1E293B; }
    .text-slate-900 { color: #0F172A; }
    .text-red-600 { color: #DC2626; }
    .text-red-700 { color: #B91C1C; }
    
    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }
    .text-xs { font-size: 0.75rem; }
    .text-sm { font-size: 0.875rem; }
    .text-lg { font-size: 1.125rem; }
    
    .flex { display: flex; }
    .items-center { align-items: center; }
    .gap-3 { gap: 0.75rem; }
    .mt-1 { margin-top: 0.25rem; }
    .mt-8 { margin-top: 2rem; }
    .mb-4 { margin-bottom: 1rem; }
    .cursor-pointer { cursor: pointer; }
    .hidden { display: none !important; }

    /* ── Stat Cards (Baru) ── */
    .stat-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;
    }
    .stat-card {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 20px; display: flex; align-items: center; gap: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s;
    }
    .stat-card:hover { border-color: #CBD5E1; transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .stat-card.highlight { border-color: #FECACA; background: #FEF2F2; }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
    }
    .stat-info { display: flex; flex-direction: column; }
    .stat-label { font-size: 13px; font-weight: 600; color: #64748B; margin-bottom: 2px; }
    .stat-val { font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; }
    .stat-card.highlight .stat-val { color: #DC2626; }

    /* ── Main Panel & Table (Baru) ── */
    .content-panel {
        background: #ffffff; border-radius: 16px; border: 1px solid #E2E8F0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden;
    }
    .panel-header { padding: 20px 24px; border-bottom: 1px solid #F1F5F9; }
    .panel-title-wrap { display: flex; align-items: center; gap: 14px; }
    .panel-icon {
        width: 36px; height: 36px; border-radius: 10px; background: #FEF2F2; color: #DC2626;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .panel-title { font-size: 16px; font-weight: 700; color: #0F172A; margin: 0; }
    .panel-subtitle { font-size: 13px; color: #64748B; margin: 2px 0 0; }
    
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        background: #F8FAFC; padding: 14px 20px; font-size: 11px; font-weight: 700;
        color: #475569; text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 1px solid #E2E8F0; text-align: left;
    }
    .data-table td {
        padding: 16px 20px; font-size: 13.5px; border-bottom: 1px solid #F1F5F9; vertical-align: top;
    }
    .data-table tbody tr:hover td { background: #F8FAFC; }
    
    .avatar-icon {
        width: 32px; height: 32px; border-radius: 50%; background: #F1F5F9;
        color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 16px;
    }
    .item-primary-name { font-weight: 700; color: #1E293B; font-size: 14px; margin-bottom: 2px; }
    
    .badge-soft { padding: 4px 8px; font-size: 11px; font-weight: 600; border-radius: 6px; display: inline-flex; }
    .badge-blue { background: #EFF6FF; color: #2563EB; }
    .badge-red { background: #FEF2F2; color: #DC2626; }
    
    .item-meta {
        font-size: 12px; color: #64748B; display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px 2px 0; border-radius: 4px; transition: color 0.15s;
    }
    .item-meta:hover { color: #0F172A; }
    .item-meta i.ri-building-4-line { font-size: 14px; color: #94A3B8; }
    
    .qty-box {
        display: inline-block; padding: 4px 12px; background: #F1F5F9; color: #0F172A;
        border-radius: 8px; font-weight: 700; font-size: 13px;
    }
    
    .satker-dropdown-row {
        background: #F8FAFC; transition: all 0.2s;
    }
    .satker-dropdown-row.hidden { display: none; }
    
    .satker-dropdown-wrapper {
        padding: 16px 20px 24px 64px; /* padding-left disesuaikan agar sejajar teks */
        border-top: 1px dashed #E2E8F0;
        border-bottom: 1px solid #E2E8F0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    
    .satker-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;
    }
    
    .satker-chip {
        display: flex; justify-content: space-between; align-items: center; gap: 8px;
        background: #fff; padding: 8px 12px; border-radius: 6px; border: 1px solid #E2E8F0;
        font-size: 12.5px; transition: border-color 0.2s;
    }
    .satker-chip:hover { border-color: #CBD5E1; }
    .satker-col { color: #475569; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .qty-col { font-weight: 700; color: #DC2626; background: #FEF2F2; padding: 2px 8px; border-radius: 4px; font-size: 11.5px; }
    
    .tfoot-total td { background: #F8FAFC; border-top: 2px solid #E2E8F0; padding: 18px 20px; }

    /* ── Accordion Breakdown (Baru) ── */
    .accordion-container { display: flex; flex-direction: column; gap: 12px; }
    .accordion-item {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;
        transition: all 0.25s ease;
    }
    .accordion-item:hover { border-color: #CBD5E1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .accordion-header {
        padding: 18px 20px; display: flex; justify-content: space-between; align-items: center;
        cursor: pointer; user-select: none; background: #ffffff; transition: background 0.15s;
    }
    .accordion-header:hover { background: #F8FAFC; }
    
    .accordion-left { display: flex; align-items: center; gap: 14px; }
    .acc-icon {
        width: 36px; height: 36px; border-radius: 10px; background: #F1F5F9; color: #475569;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .acc-title-group h4 { font-size: 15px; font-weight: 700; color: #0F172A; margin: 0 0 2px 0; }
    .acc-subtitle { font-size: 12.5px; color: #64748B; }
    
    .accordion-right { display: flex; align-items: center; gap: 16px; }
    .acc-total { font-weight: 700; color: #DC2626; font-size: 15px; }
    .acc-chevron {
        width: 28px; height: 28px; border-radius: 50%; background: #F8FAFC;
        display: flex; align-items: center; justify-content: center; color: #94A3B8;
        font-size: 18px; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .accordion-item:not(.collapsed) { border-color: #CBD5E1; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .accordion-item:not(.collapsed) .accordion-header { border-bottom: 1px solid #E2E8F0; background: #F8FAFC; }
    .accordion-item:not(.collapsed) .acc-chevron { transform: rotate(-180deg); background: #E2E8F0; color: #475569; }
    .accordion-item:not(.collapsed) .acc-icon { background: #DC2626; color: #fff; }
    
    .accordion-content { padding: 0; display: block; }
    .accordion-item.collapsed .accordion-content { display: none; }
    
    .acc-table { width: 100%; border-collapse: collapse; }
    .acc-table th {
        background: #fff; padding: 12px 20px; font-size: 11px; font-weight: 600;
        color: #64748B; text-transform: uppercase; border-bottom: 1px solid #E2E8F0;
        text-align: left;
    }
    .acc-table td { padding: 12px 20px; font-size: 13.5px; border-bottom: 1px solid #F1F5F9; }
    .acc-table tr:hover td { background: #F8FAFC; }
    .acc-table tfoot td { background: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 14px 20px; border-bottom: none; }
    
    .inline-qty {
        display: inline-block; padding: 2px 10px; background: #F1F5F9; color: #0F172A;
        border-radius: 6px; font-weight: 600; font-size: 13px;
    }

    @media (max-width: 768px) {
        .stat-grid, .wizard-track { grid-template-columns: 1fr; }
        .data-table, .acc-table { min-width: 600px; }
        .table-wrap { overflow-x: auto; }
        .satker-dropdown-wrapper { padding-left: 16px; }
    }
</style>
@endsection

@section('scripts')
<script>
    function toggleSatkerList(idx) {
        const row = document.getElementById('satkers-' + idx);
        const icon = document.getElementById('icon-satkers-' + idx);
        
        if(row) {
            if(row.classList.contains('hidden')) {
                // Tampilkan baris
                row.classList.remove('hidden');
                
                // Efek animasi icon
                if(icon) icon.style.transform = 'rotate(-180deg)';
                
                // Menghilangkan border bottom pada baris di atasnya agar terlihat menyatu
                const prevRow = row.previousElementSibling;
                if(prevRow) {
                    const tds = prevRow.querySelectorAll('td');
                    tds.forEach(td => td.style.borderBottom = 'none');
                    prevRow.style.backgroundColor = '#F8FAFC';
                }
                
                // Simple fade in untuk wrapper
                const wrapper = row.querySelector('.satker-dropdown-wrapper');
                if(wrapper) {
                    wrapper.style.opacity = '0';
                    wrapper.style.transform = 'translateY(-10px)';
                    wrapper.style.transition = 'all 0.3s ease-out';
                    setTimeout(() => {
                        wrapper.style.opacity = '1';
                        wrapper.style.transform = 'translateY(0)';
                    }, 10);
                }
            } else {
                // Sembunyikan baris
                row.classList.add('hidden');
                
                if(icon) icon.style.transform = 'rotate(0deg)';
                
                // Kembalikan border pada baris aslinya
                const prevRow = row.previousElementSibling;
                if(prevRow) {
                    const tds = prevRow.querySelectorAll('td');
                    tds.forEach(td => td.style.borderBottom = '');
                    prevRow.style.backgroundColor = '';
                }
            }
        }
    }
</script>
@endsection
