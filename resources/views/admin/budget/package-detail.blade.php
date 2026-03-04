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
<div class="page-header">
    <div class="page-header-row">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}" class="btn btn-ghost btn-sm" style="padding: 4px 8px;">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <h1 style="font-size: 22px; font-weight: 700;">{{ $budgetPackage->name }}</h1>
                <span class="badge" style="background: {{ $budgetPackage->status_color['bg'] }}; color: {{ $budgetPackage->status_color['text'] }};">
                    {{ $budgetPackage->status_label }}
                </span>
            </div>
            <p style="color: #6B7280; font-size: 14px; margin-left: 40px;">
                {{ $budgetPackage->budgetYear->name }} — {{ $budgetPackage->description ?? 'Belum ada deskripsi' }}
            </p>
        </div>
    </div>
</div>

{{-- Wizard Navigation --}}
<div class="wizard-actions">
    <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="wizard-action-card">
        <div class="wizard-step-number">1</div>
        <div class="wizard-step-info">
            <h3>Pilih Barang</h3>
            <p>Pilih item kapor yang akan disertakan dalam paket ini</p>
        </div>
        <div class="wizard-step-stat">
            <span class="stat-num">{{ $budgetPackage->items->count() }}</span>
            <span class="stat-label">Item Dipilih</span>
        </div>
        <i class="ri-arrow-right-s-line wizard-arrow"></i>
    </a>

    <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="wizard-action-card {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
        <div class="wizard-step-number">2</div>
        <div class="wizard-step-info">
            <h3>Tentukan Penerima</h3>
            <p>Pilih satker & filter personil per item</p>
        </div>
        <div class="wizard-step-stat">
            <span class="stat-num">{{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) }}</span>
            <span class="stat-label">Satker Terpilih</span>
        </div>
        <i class="ri-arrow-right-s-line wizard-arrow"></i>
    </a>

    <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-action-card {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
        <div class="wizard-step-number">3</div>
        <div class="wizard-step-info">
            <h3>Preview & Hitung</h3>
            <p>Ringkasan total barang, penerima, dan anggaran</p>
        </div>
        <div class="wizard-step-stat">
            <span class="stat-num">{{ $budgetPackage->formatted_budget }}</span>
            <span class="stat-label">Total Anggaran</span>
        </div>
        <i class="ri-arrow-right-s-line wizard-arrow"></i>
    </a>
</div>

{{-- Export Actions --}}
@if($budgetPackage->items->count() > 0)
<div class="export-actions" style="margin-top: 20px;">
    <a href="{{ route('admin.budget.recap', $budgetPackage) }}" class="export-card">
        <div class="export-icon" style="background: #EFF6FF; color: #3B82F6;">
            <i class="ri-file-list-3-line"></i>
        </div>
        <div class="export-info">
            <h4>Rekapan</h4>
            <p>Lihat rekapan lengkap per item & satker</p>
        </div>
        <i class="ri-arrow-right-s-line" style="color: #D1D5DB; font-size: 18px;"></i>
    </a>
    <a href="{{ route('admin.budget.invoice', $budgetPackage) }}" class="export-card">
        <div class="export-icon" style="background: #FEF3C7; color: #D97706;">
            <i class="ri-file-text-line"></i>
        </div>
        <div class="export-info">
            <h4>Invoice HPS</h4>
            <p>Generate Harga Perkiraan Sendiri (HPS)</p>
        </div>
        <i class="ri-arrow-right-s-line" style="color: #D1D5DB; font-size: 18px;"></i>
    </a>
    <a href="{{ route('admin.budget.export-csv', $budgetPackage) }}" class="export-card">
        <div class="export-icon" style="background: #D1FAE5; color: #059669;">
            <i class="ri-file-excel-line"></i>
        </div>
        <div class="export-info">
            <h4>Export Excel Rekapan</h4>
            <p>Download format Excel (.xlsx)</p>
        </div>
        <i class="ri-download-line" style="color: #D1D5DB; font-size: 18px;"></i>
    </a>
</div>
@endif

{{-- Summary Table if items exist --}}
@if($budgetPackage->items->count() > 0)
<div class="card" style="margin-top: 24px;">
    <div class="card-head">
        <h3>Item dalam Paket</h3>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>NAMA ITEM</th>
                        <th>KATEGORI</th>
                        <th>HARGA</th>
                        <th>QTY</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgetPackage->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td style="font-weight: 600;">{{ $item->kaporItem->item_name }}</td>
                        <td><span class="badge badge-neutral">{{ $item->kaporItem->category }}</span></td>
                        <td>{{ $item->formatted_price }}</td>
                        <td>{{ $item->calculated_qty }}</td>
                        <td style="font-weight: 600;">{{ $item->formatted_total }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #F9FAFB;">
                        <td colspan="4" style="font-weight: 700; text-align: right;">GRAND TOTAL</td>
                        <td style="font-weight: 700;">{{ $budgetPackage->items->sum('calculated_qty') }}</td>
                        <td style="font-weight: 700; color: #B91C1C;">{{ $budgetPackage->formatted_budget }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@section('styles')
<style>
    .wizard-actions {
        display: flex; flex-direction: column; gap: 12px;
    }
    .wizard-action-card {
        display: flex; align-items: center; gap: 16px;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 14px;
        padding: 20px 24px; text-decoration: none; color: inherit;
        transition: all 0.2s; cursor: pointer;
    }
    .wizard-action-card:hover {
        border-color: #B91C1C; box-shadow: 0 4px 12px rgba(185,28,28,0.08);
        transform: translateY(-1px);
    }
    .wizard-action-card.disabled {
        opacity: 0.5; pointer-events: none;
    }
    .wizard-step-number {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #B91C1C, #DC2626);
        color: #fff; font-size: 16px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .wizard-step-info { flex: 1; }
    .wizard-step-info h3 { font-size: 15px; font-weight: 700; color: #111827; }
    .wizard-step-info p { font-size: 13px; color: #6B7280; margin-top: 2px; }
    .wizard-step-stat { text-align: right; min-width: 100px; }
    .wizard-step-stat .stat-num { display: block; font-size: 18px; font-weight: 700; color: #111827; }
    .wizard-step-stat .stat-label { font-size: 11px; color: #9CA3AF; text-transform: uppercase; font-weight: 600; }
    .wizard-arrow { font-size: 20px; color: #D1D5DB; flex-shrink: 0; }

    .export-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .export-card {
        display: flex; align-items: center; gap: 12px;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;
        padding: 14px 16px; text-decoration: none; color: inherit; transition: all 0.2s;
    }
    .export-card:hover { border-color: #B91C1C; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .export-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
    }
    .export-info { flex: 1; }
    .export-info h4 { font-size: 13px; font-weight: 700; color: #111827; }
    .export-info p { font-size: 11px; color: #9CA3AF; margin-top: 1px; }
    @media (max-width: 768px) { .export-actions { grid-template-columns: 1fr; } }
</style>
@endsection
