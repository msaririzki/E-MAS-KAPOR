@extends('layouts.app')

@section('title', 'Detail Kebutuhan')
@section('breadcrumb', 'Detail Kebutuhan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Detail Pengajuan</h1>
            <p>{{ $kebutuhan->title }}</p>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 8px;">
            <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Kembali</a>
            <a href="{{ route('admin-satker.kebutuhan.export-excel', $kebutuhan) }}" class="btn btn-outline btn-sm" style="color: #059669; border-color: #059669; background: #ECFDF5;"><i class="ri-file-excel-2-line"></i> Excel</a>
            <a href="{{ route('admin-satker.kebutuhan.export-pdf', $kebutuhan) }}" class="btn btn-outline btn-sm" style="color: #DC2626; border-color: #DC2626; background: #FEF2F2;"><i class="ri-file-pdf-line"></i> PDF</a>
        </div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div style="background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-checkbox-circle-fill"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-error-warning-fill"></i> {{ session('error') }}
    </div>
@endif

{{-- Info Card --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-head"><h3>Informasi Pengajuan</h3></div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Judul</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->title }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Tahun Anggaran</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->fiscal_year }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Satker</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->satker->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Status</div>
                <span class="badge {{ $kebutuhan->status_badge }}">{{ $kebutuhan->status_label }}</span>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Tanggal Pengajuan</div>
                <div style="font-size: 13px;">{{ $kebutuhan->submitted_at ? $kebutuhan->submitted_at->format('d M Y, H:i') : $kebutuhan->created_at->format('d M Y, H:i') }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Total Item</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->items->count() }} barang</div>
            </div>
        </div>
        @if($kebutuhan->notes)
            <div style="margin-top: 16px; padding: 12px 16px; background: var(--info-bg); border: 1px solid var(--info-border); border-radius: var(--radius-sm); font-size: 13px;">
                <strong style="color: var(--info);">Catatan:</strong> {{ $kebutuhan->notes }}
            </div>
        @endif
    </div>
</div>

{{-- Items Table --}}
<div class="card">
    <div class="card-head"><h3>Daftar Item yang Diajukan ({{ $kebutuhan->items->count() }})</h3></div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Nama Item</th>
                    <th>Kategori</th>
                </tr>
            </thead>
            <tbody>
                @php $grouped = $kebutuhan->items->groupBy(fn($item) => $item->identifikasiItem->category ?? 'Lainnya'); $no = 1; @endphp
                @foreach($grouped as $category => $items)
                    <tr style="background: var(--slate-50);">
                        <td colspan="3" style="font-weight: 700; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; padding: 8px 16px; border-bottom: 2px solid var(--border-color);">
                            {{ str_replace('_', ' ', $category) }} ({{ $items->count() }} item)
                        </td>
                    </tr>
                    @foreach($items as $item)
                    <tr>
                        <td style="text-align: center;">{{ $no++ }}</td>
                        <td><div class="cell-name">{{ $item->identifikasiItem->item_name ?? '-' }}</div></td>
                        <td><span class="badge badge-neutral">{{ str_replace('_', ' ', $item->identifikasiItem->category ?? '-') }}</span></td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
