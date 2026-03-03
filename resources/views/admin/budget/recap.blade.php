@extends('layouts.app')

@section('title', 'Rekapan - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Rekapan</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Rekapan {{ $budgetPackage->name }}</h1>
            <p style="color: #6B7280; font-size: 13px;">{{ $budgetPackage->budgetYear->name }} — Rekapan seluruh item dan perhitungan</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <a href="{{ route('admin.budget.export-csv', $budgetPackage) }}" class="btn btn-primary">
                <i class="ri-file-excel-line"></i> Export CSV
            </a>
            <a href="{{ route('admin.budget.invoice', $budgetPackage) }}" class="btn btn-primary" style="background: #1D4ED8;">
                <i class="ri-file-text-line"></i> Lihat Invoice HPS
            </a>
        </div>
    </div>
</div>

{{-- Summary Stats --}}
<div class="recap-stats">
    <div class="recap-stat">
        <div class="recap-stat-icon" style="background: #EFF6FF; color: #3B82F6;"><i class="ri-box-3-line"></i></div>
        <div>
            <div class="recap-stat-value">{{ $total_items }}</div>
            <div class="recap-stat-label">Total Item</div>
        </div>
    </div>
    <div class="recap-stat">
        <div class="recap-stat-icon" style="background: #FEF3C7; color: #D97706;"><i class="ri-team-line"></i></div>
        <div>
            <div class="recap-stat-value">{{ number_format($grand_qty) }}</div>
            <div class="recap-stat-label">Total Volume</div>
        </div>
    </div>
    <div class="recap-stat grand">
        <div class="recap-stat-icon" style="background: #FEF2F2; color: #B91C1C;"><i class="ri-money-dollar-circle-line"></i></div>
        <div>
            <div class="recap-stat-value">Rp {{ number_format($grand_total, 0, ',', '.') }}</div>
            <div class="recap-stat-label">Grand Total</div>
        </div>
    </div>
</div>

{{-- Grouped by Invoice Group --}}
@foreach($grouped_items as $group => $groupItems)
<div class="card" style="margin-top: 20px;">
    <div class="card-head">
        <h3><i class="ri-folder-line" style="color: #B91C1C; margin-right: 6px;"></i> {{ $group }}</h3>
        <span style="font-size: 13px; font-weight: 700; color: #6B7280;">{{ count($groupItems) }} item</span>
    </div>
    <div class="card-body flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">NO</th>
                        <th>NAMA BARANG</th>
                        <th>SATUAN</th>
                        <th style="text-align: right;">HARGA SATUAN</th>
                        <th style="text-align: center;">VOLUME</th>
                        <th style="text-align: right;">JUMLAH HARGA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupItems as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $item['item_name'] }}</div>
                            @if(count($item['recipients']) > 0)
                            <div style="font-size: 11px; color: #9CA3AF; margin-top: 2px;">
                                @foreach($item['recipients'] as $r)
                                    {{ $r['satker_name'] }} ({{ $r['matched_count'] }}){{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td>{{ $item['unit'] }}</td>
                        <td style="text-align: right;">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ number_format($item['qty']) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #B91C1C;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #F9FAFB;">
                        <td colspan="4" style="text-align: right; font-weight: 700;">Subtotal {{ $group }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ number_format(collect($groupItems)->sum('qty')) }}</td>
                        <td style="text-align: right; font-weight: 700;">Rp {{ number_format(collect($groupItems)->sum('total'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endforeach

{{-- Grand Total --}}
<div class="card grand-total-card" style="margin-top: 20px;">
    <div class="card-body" style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-size: 14px; font-weight: 700; color: #374151;">GRAND TOTAL</span>
                <span style="font-size: 12px; color: #6B7280; margin-left: 8px;">({{ $total_items }} item, {{ number_format($grand_qty) }} unit)</span>
            </div>
            <div style="font-size: 24px; font-weight: 800; color: #B91C1C;">
                Rp {{ number_format($grand_total, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .recap-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 16px; }
    .recap-stat {
        display: flex; align-items: center; gap: 14px;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px;
    }
    .recap-stat.grand { border-color: #FECACA; background: #FFF5F5; }
    .recap-stat-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
    }
    .recap-stat-value { font-size: 20px; font-weight: 800; color: #111827; }
    .recap-stat-label { font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: 600; }
    .grand-total-card { border: 2px solid #FECACA; background: #FFF5F5; }
    @media (max-width: 768px) { .recap-stats { grid-template-columns: 1fr; } }
</style>
@endsection
