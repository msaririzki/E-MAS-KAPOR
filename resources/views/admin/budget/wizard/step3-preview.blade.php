@extends('layouts.app')

@section('title', 'Preview - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Preview</span>
@endsection

@section('content')
{{-- Wizard Steps Bar --}}
<div class="wizard-bar">
    <div class="wizard-step done"><span class="step-num"><i class="ri-check-line"></i></span> Pilih Barang</div>
    <div class="wizard-line done-line"></div>
    <div class="wizard-step done"><span class="step-num"><i class="ri-check-line"></i></span> Pilih Penerima</div>
    <div class="wizard-line done-line"></div>
    <div class="wizard-step active"><span class="step-num">3</span> Preview</div>
</div>

<div class="page-header" style="margin-top: 20px;">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Preview {{ $budgetPackage->name }}</h1>
            <p style="color: #6B7280; font-size: 13px;">
                {{ $budgetPackage->budgetYear->name }} — Ringkasan seluruh item, penerima, dan anggaran
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn btn-primary">
                <i class="ri-check-line"></i> Selesai
            </a>
        </div>
    </div>
</div>

{{-- Summary Stats --}}
<div class="preview-stats">
    <div class="preview-stat-card">
        <div class="preview-stat-icon" style="background: #EFF6FF; color: #3B82F6;">
            <i class="ri-box-3-line"></i>
        </div>
        <div class="preview-stat-value">{{ $totalItems }}</div>
        <div class="preview-stat-label">Total Item</div>
    </div>
    <div class="preview-stat-card">
        <div class="preview-stat-icon" style="background: #FEF3C7; color: #D97706;">
            <i class="ri-team-line"></i>
        </div>
        <div class="preview-stat-value">{{ number_format($totalRecipients) }}</div>
        <div class="preview-stat-label">Total Penerima</div>
    </div>
    <div class="preview-stat-card grand">
        <div class="preview-stat-icon" style="background: #FEF2F2; color: #B91C1C;">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="preview-stat-value">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
        <div class="preview-stat-label">Grand Total Anggaran</div>
    </div>
</div>

{{-- Detailed Table --}}
<div class="card" style="margin-top: 24px;">
    <div class="card-head">
        <h3>Rincian Paket</h3>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table>
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
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight: 600; color: #111827;">{{ $item->kaporItem->item_name }}</div>
                            @if($item->recipients->count() > 0)
                            <div style="font-size: 11px; color: #9CA3AF; margin-top: 2px;">
                                @foreach($item->recipients as $r)
                                    <span>{{ $r->satker->name }} ({{ $r->matched_count }})</span>{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td><span class="badge badge-neutral">{{ $item->kaporItem->category }}</span></td>
                        <td>{{ $item->kaporItem->unit ?? 'PCS' }}</td>
                        <td style="text-align: right;">{{ $item->formatted_price }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ number_format($item->calculated_qty) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #B91C1C;">{{ $item->formatted_total }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #FEF2F2;">
                        <td colspan="5" style="text-align: right; font-weight: 800; font-size: 14px; color: #111827;">
                            GRAND TOTAL
                        </td>
                        <td style="text-align: center; font-weight: 800; font-size: 14px;">
                            {{ number_format($totalRecipients) }}
                        </td>
                        <td style="text-align: right; font-weight: 800; font-size: 14px; color: #B91C1C;">
                            Rp {{ number_format($grandTotal, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Per Item Breakdown --}}
@foreach($budgetPackage->items as $item)
@if($item->recipients->count() > 0)
<div class="card">
    <div class="card-head" style="cursor: pointer;" onclick="this.parentElement.querySelector('.card-body').classList.toggle('collapsed')">
        <h3>
            <i class="ri-arrow-down-s-line" style="font-size: 16px; margin-right: 4px;"></i>
            {{ $item->kaporItem->item_name }}
        </h3>
        <span style="font-size: 13px; font-weight: 700; color: #B91C1C;">{{ $item->formatted_total }}</span>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SATKER</th>
                        <th style="text-align: center;">JUMLAH PERSONIL</th>
                        <th style="text-align: right;">HARGA</th>
                        <th style="text-align: right;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->recipients as $r)
                    <tr>
                        <td style="font-weight: 600;">{{ $r->satker->name }}</td>
                        <td style="text-align: center;">{{ $r->matched_count }}</td>
                        <td style="text-align: right;">{{ $item->formatted_price }}</td>
                        <td style="text-align: right; font-weight: 600;">
                            Rp {{ number_format($r->matched_count * $item->effective_price, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #F9FAFB;">
                        <td style="font-weight: 700;">Total</td>
                        <td style="text-align: center; font-weight: 700;">{{ $item->calculated_qty }}</td>
                        <td></td>
                        <td style="text-align: right; font-weight: 700;">{{ $item->formatted_total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection

@section('styles')
<style>
    .wizard-bar {
        display: flex; align-items: center;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;
        padding: 16px 24px;
    }
    .wizard-step {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600; color: #9CA3AF; white-space: nowrap;
    }
    .wizard-step.active { color: #B91C1C; }
    .wizard-step.done { color: #10B981; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; background: #F3F4F6; color: #9CA3AF;
    }
    .wizard-step.active .step-num { background: #B91C1C; color: #fff; }
    .wizard-step.done .step-num { background: #10B981; color: #fff; }
    .wizard-line { flex: 1; height: 2px; background: #E5E7EB; margin: 0 12px; }
    .wizard-line.done-line { background: #10B981; }

    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

    .preview-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px; margin-top: 20px;
    }
    .preview-stat-card {
        background: #fff; border: 1px solid #E5E7EB; border-radius: 14px;
        padding: 20px; text-align: center; transition: all 0.2s;
    }
    .preview-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    .preview-stat-card.grand { border-color: #FECACA; background: #FFF5F5; }
    .preview-stat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; margin: 0 auto 12px;
    }
    .preview-stat-value { font-size: 22px; font-weight: 800; color: #111827; }
    .preview-stat-label { font-size: 12px; color: #6B7280; margin-top: 4px; text-transform: uppercase; font-weight: 600; }

    .card-body.collapsed { display: none; }

    @media (max-width: 768px) {
        .preview-stats { grid-template-columns: 1fr; }
    }
</style>
@endsection
