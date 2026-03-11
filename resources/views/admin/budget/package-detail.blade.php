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
            <div class="package-title-wrapper" style="justify-content: space-between; width: 100%; align-items: flex-start;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                        <h1 class="package-title" style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">{{ $budgetPackage->name }}</h1>
                        <span class="badge" style="background: {{ $budgetPackage->status_color['bg'] }}; color: {{ $budgetPackage->status_color['text'] }}; border: 1px solid {{ str_replace(')', ', 0.2)', str_replace('rgb', 'rgba', $budgetPackage->status_color['text'])) }}; font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 700; letter-spacing: 0.3px;">
                            {{ $budgetPackage->status_label }}
                        </span>
                    </div>
                    <p class="package-desc" style="color: #64748B; font-size: 14px; margin: 0; line-height: 1.5; display: flex; align-items: center; gap: 6px;">
                        <i class="ri-calendar-event-line" style="font-size: 16px; color: #94A3B8;"></i>
                        Tahun Anggaran {{ $budgetPackage->budgetYear->name }}
                        <span style="color: #CBD5E1;">&bull;</span>
                        {{ $budgetPackage->description ?? 'Tidak ada deskripsi' }}
                    </p>
                </div>
                <div>
                     <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="btn-action-primary">
                        <i class="ri-edit-box-line" style="margin-right: 6px;"></i> Edit Paket
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
        <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="wizard-step-card {{ $budgetPackage->items->count() > 0 ? 'completed' : '' }}">
            <div class="wizard-step-header">
                <div class="wizard-step-number"><i class="ri-check-line"></i></div>
                <div class="wizard-step-title">
                    <h3>Pilih Barang</h3>
                    <p>{{ $budgetPackage->items->count() > 0 ? 'Barang berhasil dipilih' : 'Pilih item kapor yang disertakan' }}</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->count() }}</span>
                    <span class="label">Barang</span>
                </div>
                @if($budgetPackage->items->count() > 0)
                    <i class="ri-checkbox-circle-fill wizard-step-arrow" style="color: #10B981; background: transparent;"></i>
                @else
                    <i class="ri-arrow-right-line wizard-step-arrow"></i>
                @endif
            </div>
        </a>

        {{-- Step 2 --}}
        <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="wizard-step-card {{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) > 0 ? 'completed' : '' }} {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
            <div class="wizard-step-header">
                <div class="wizard-step-number"><i class="ri-check-line"></i></div>
                <div class="wizard-step-title">
                    <h3>Tentukan Penerima</h3>
                    <p>{{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) > 0 ? 'Satker & filter ditetapkan' : 'Pilih satker & filter personil' }}</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->sum(fn($i) => $i->recipients->count()) }}</span>
                    <span class="label">Satker &bull; {{ number_format($budgetPackage->items->sum(fn($i) => $i->recipients->sum('matched_count')), 0, ',', '.') }} Personel</span>
                </div>
                 @if($budgetPackage->items->sum(fn($i) => $i->recipients->count()) > 0)
                    <i class="ri-checkbox-circle-fill wizard-step-arrow" style="color: #10B981; background: transparent;"></i>
                @else
                    <i class="ri-arrow-right-line wizard-step-arrow"></i>
                @endif
            </div>
        </a>

        {{-- Step 3 --}}
        <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-step-card {{ $budgetPackage->items->count() == 0 ? 'disabled' : '' }}">
            <div class="wizard-step-header">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-title">
                    <h3>Pratinjau & Hitung</h3>
                    <p>Ringkasan total & anggaran</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value highlight">
                    <span class="num" style="color: #C62828; font-size: 20px;">{{ $budgetPackage->formatted_budget }}</span>
                    <span class="label">Total Anggaran (Estimasi)</span>
                </div>
                <i class="ri-arrow-right-line wizard-step-arrow"></i>
            </div>
        </a>
    </div>
</div>



{{-- ── PERINGATAN UKURAN BELUM DIISI ── --}}
@if(!empty($sizeWarnings))
<div class="size-warning-banner" id="size-warning-panel">
    <div class="swb-header">
        <div class="swb-header-left" onclick="toggleWarningPanel()" style="flex:1; cursor:pointer;">
            <div class="swb-icon"><i class="ri-error-warning-fill"></i></div>
            <div>
                <div class="swb-title">
                    {{ number_format(collect($sizeWarnings)->sum('missing')) }} personel belum mengisi data ukuran kapor
                </div>
                <div class="swb-subtitle">
                    Memengaruhi {{ count($sizeWarnings) }} jenis barang — klik untuk melihat rincian
                </div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('admin.personnel.index', ['status' => 'incomplete']) }}"
               onclick="event.stopPropagation()"
               class="swb-link-btn"
               title="Buka halaman personel yang belum isi ukuran">
                <i class="ri-user-search-line"></i> Lihat &amp; Isi Ukuran
            </a>
            <i class="ri-arrow-down-s-line swb-toggle-icon" id="swb-toggle-icon" onclick="toggleWarningPanel()"></i>
        </div>
    </div>

    <div class="swb-body hidden" id="swb-body">
        @foreach($sizeWarnings as $wIdx => $warn)
        <div class="swb-item">
            <div class="swb-item-header" onclick="toggleWarnDetail({{ $wIdx }})">
                <div class="swb-item-name">
                    <i class="ri-shirt-line"></i>
                    {{ $warn['item_name'] }}
                </div>
                <div class="swb-item-stats">
                    <span class="swb-stat">
                        <span class="swb-stat-label">Total</span>
                        <span class="swb-stat-val">{{ number_format($warn['total']) }}</span>
                    </span>
                    <span class="swb-stat swb-stat-ok">
                        <span class="swb-stat-label">Terisi</span>
                        <span class="swb-stat-val">{{ number_format($warn['valid']) }}</span>
                    </span>
                    <span class="swb-stat swb-stat-miss">
                        <span class="swb-stat-label">Belum</span>
                        <span class="swb-stat-val">{{ number_format($warn['missing']) }}</span>
                    </span>
                    <div class="swb-progress-bar">
                        <div class="swb-progress-fill" style="width: {{ $warn['total'] > 0 ? round(($warn['valid'] / $warn['total']) * 100) : 0 }}%"></div>
                    </div>
                    <i class="ri-arrow-right-s-line swb-item-arrow" id="swb-arrow-{{ $wIdx }}"></i>
                </div>
            </div>

            {{-- Detail per Satker --}}
            <div class="swb-satker-list hidden" id="swb-detail-{{ $wIdx }}">
                <table class="swb-satker-table">
                    <thead>
                        <tr>
                            <th>SATKER</th>
                            <th class="text-right">TOTAL</th>
                            <th class="text-right">TERISI</th>
                            <th class="text-right">BELUM</th>
                            <th class="text-right">PROGRESS</th>
                            <th class="text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($warn['by_satker'] as $sk)
                        <tr>
                            <td>{{ $sk['satker_name'] }}</td>
                            <td class="text-right text-muted">{{ $sk['total'] }}</td>
                            <td class="text-right" style="color:#16A34A; font-weight:600;">{{ $sk['valid'] }}</td>
                            <td class="text-right" style="color:#DC2626; font-weight:700;">{{ $sk['missing'] }}</td>
                            <td class="text-right">
                                <div class="swb-progress-bar" style="width:80px; display:inline-block;">
                                    <div class="swb-progress-fill" style="width: {{ $sk['total'] > 0 ? round(($sk['valid'] / $sk['total']) * 100) : 0 }}%; background: {{ $sk['valid'] === $sk['total'] ? '#16A34A' : '#F59E0B' }};"></div>
                                </div>
                                <span style="font-size:11px; color:#64748B; margin-left:4px;">{{ $sk['total'] > 0 ? round(($sk['valid'] / $sk['total']) * 100) : 0 }}%</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.personnel.index', ['status' => 'incomplete', 'satker_id' => $sk['satker_id']]) }}"
                                   class="swb-satker-link"
                                   title="Lihat personel belum isi ukuran di {{ $sk['satker_name'] }}">
                                    <i class="ri-external-link-line"></i> Isi Ukuran
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($budgetPackage->items->count() > 0)

<div class="layout-stack">

    {{-- Top Section: Export Actions --}}
    <div class="action-panel" style="margin-bottom: 24px; padding: 16px;">
        <div style="margin-bottom: 12px;">
            <h3 style="font-size: 13px; font-weight: 700; color: #64748B; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="ri-printer-line" style="margin-right: 4px;"></i> Opsi Cetak & Unduh
            </h3>
        </div>
        <div class="export-actions-grid-horizontal" style="gap: 12px;">
            
            {{-- Excel / Detail Download --}}
            <div class="export-card-group-horizontal" style="gap: 12px;">
                
                <a href="{{ route('admin.budget.export-csv', $budgetPackage) }}" class="export-btn export-green" data-download data-estimate="10" style="padding: 12px;">
                    <div class="export-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="ri-file-excel-line"></i></div>
                    <div class="export-info">
                        <h4 style="font-size: 12px;">Export Rekapan</h4>
                        <p style="font-size: 11px;">Unduh file .xlsx</p>
                    </div>
                    <div class="export-loading">
                        <i class="ri-loader-4-line spinner"></i>
                    </div>
                </a>

                <a href="{{ route('admin.budget.export-pdf', $budgetPackage) }}" class="export-btn export-red" data-download data-estimate="15" style="padding: 12px;">
                    <div class="export-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="ri-file-pdf-line"></i></div>
                    <div class="export-info">
                        <h4 style="font-size: 12px;">Export Rekapan PDF</h4>
                        <p style="font-size: 11px;">Unduh file .pdf</p>
                    </div>
                    <div class="export-loading">
                        <i class="ri-loader-4-line spinner"></i>
                    </div>
                </a>
                
                <a href="{{ route('admin.budget.export-detail', $budgetPackage) }}" class="export-btn export-purple" data-download data-estimate="20" style="padding: 12px;">
                    <div class="export-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="ri-team-line"></i></div>
                    <div class="export-info">
                        <h4 style="font-size: 12px;">Export Nominatif</h4>
                        <p style="font-size: 11px;">Detail per personil</p>
                    </div>
                    <div class="export-loading">
                        <i class="ri-loader-4-line spinner"></i>
                    </div>
                </a>
            </div>


            {{-- HPS & Analysis --}}
            <div class="export-card-group-horizontal" style="gap: 12px;">

                <a href="{{ route('admin.budget.recap', $budgetPackage) }}" class="export-btn export-blue" style="padding: 12px;">
                    <div class="export-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="ri-user-shared-line"></i></div>
                    <div class="export-info">
                        <h4 style="font-size: 12px;">Analisis Duplikasi</h4>
                        <p style="font-size: 11px;">Cek personil ganda</p>
                    </div>
                </a>
                
                <a href="{{ route('admin.budget.invoice', $budgetPackage) }}" class="export-btn export-orange" style="padding: 12px;">
                    <div class="export-icon" style="width: 32px; height: 32px; font-size: 16px;"><i class="ri-file-text-line"></i></div>
                    <div class="export-info">
                        <h4 style="font-size: 12px;">Invoice HPS</h4>
                        <p style="font-size: 11px;">Generate format HPS</p>
                    </div>
                </a>
            </div>

        </div>
    </div>

    {{-- Bottom Section: Summary Table --}}
    <div class="layout-main">
        <div class="content-panel">
            <div class="panel-header">
                <div class="panel-title-wrap">
                    <div class="panel-icon"><i class="ri-list-check-3"></i></div>
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
                                <td class="text-center font-bold text-slate-900 text-lg">{{ number_format($budgetPackage->items->sum('calculated_qty'), 0, ',', '.') }}</td>
                                <td class="text-right font-bold text-red-700 text-lg">{{ $budgetPackage->formatted_budget }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('styles')
<style>
     /* ── Main Layout Stack ── */
     .layout-stack {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    
    /* ── Hero Section & Wizard ── */
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
        content: ''; position: absolute; top: 0; left: 0;
        width: 100%; height: 4px;
        background: linear-gradient(90deg, #C62828, #E53935, #EF5350);
    }
    .package-hero-inner { display: flex; align-items: flex-start; gap: 16px; }
    .btn-back {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; background: #F8FAFC; border: 1px solid #E2E8F0;
        border-radius: 12px; color: #475569; font-size: 20px; transition: all 0.2s; text-decoration: none;
    }
    .btn-back:hover { background: #C62828; color: #ffffff; border-color: #C62828; transform: translateX(-2px); }
    .package-hero-content { flex: 1; }
    .package-title-wrapper { display: flex; align-items: center; flex-wrap: wrap; }
    
    .btn-action-primary {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 20px; border-radius: 10px;
        background: #ffffff; color: #475569; font-size: 14px; font-weight: 600;
        text-decoration: none; transition: all 0.2s; border: 1px solid #E2E8F0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .btn-action-primary:hover {
        background: #F8FAFC; transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-color: #CBD5E1; color: #0F172A;
    }

    /* ── Wizard Steps ── */
    .wizard-steps-container { margin-bottom: 24px; }
    .wizard-track { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .wizard-step-card {
        background: #ffffff; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px;
        text-decoration: none; color: inherit; display: flex; flex-direction: column; justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
    }
    .wizard-step-card:hover:not(.disabled) { border-color: #CBD5E1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .wizard-step-card.completed { border-color: #BBF7D0; background: #F0FDF4; opacity: 0.9; }
    .wizard-step-card.completed .wizard-step-number { background: #10B981; color: #fff; border-color: #10B981; }
    .wizard-step-card.disabled { opacity: 0.6; pointer-events: none; background: #F8FAFC; filter: grayscale(1); }
    .wizard-step-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px; position: relative; z-index: 2; }
    .wizard-step-number {
        width: 36px; height: 36px; border-radius: 10px; font-size: 16px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0;
    }
    .wizard-step-title h3 { font-size: 15px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .wizard-step-title p { font-size: 12px; color: #64748B; margin: 0; line-height: 1.4; }
    .wizard-step-body { display: flex; align-items: flex-end; justify-content: space-between; margin-top: auto; position: relative; z-index: 2; }
    .wizard-step-body .stat-value { display: flex; flex-direction: column; }
    .wizard-step-body .stat-value .num { font-size: 22px; font-weight: 800; color: #0F172A; line-height: 1; margin-bottom: 4px; letter-spacing: -0.5px; }
    .wizard-step-body .stat-value .label { font-size: 11px; color: #94A3B8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
    .wizard-step-arrow { font-size: 20px; color: #CBD5E1; }

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
    .mt-0 { margin-top: 0; }
    .mb-3 { margin-bottom: 0.75rem; }
    .cursor-pointer { cursor: pointer; }
    .hidden { display: none !important; }

    /* ── Size Warning Banner ── */
    .size-warning-banner {
        background: #FFFBEB;
        border: 1px solid #FCD34D;
        border-radius: 16px;
        margin-bottom: 24px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.15);
    }
    .swb-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; cursor: pointer; user-select: none;
        transition: background 0.15s;
    }
    .swb-header:hover { background: #FEF3C7; }
    .swb-header-left { display: flex; align-items: center; gap: 14px; }
    .swb-icon {
        width: 40px; height: 40px; border-radius: 12px; background: #FDE68A;
        color: #D97706; display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .swb-title { font-size: 14px; font-weight: 700; color: #92400E; margin-bottom: 2px; }
    .swb-subtitle { font-size: 12px; color: #B45309; }
    .swb-toggle-icon { font-size: 22px; color: #D97706; transition: transform 0.25s; cursor:pointer; }
    .swb-toggle-icon.open { transform: rotate(180deg); }
    .swb-link-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700;
        background: #D97706; color: #fff; text-decoration: none;
        transition: background 0.15s, transform 0.15s;
        white-space: nowrap; flex-shrink: 0;
    }
    .swb-link-btn:hover { background: #B45309; transform: translateY(-1px); color: #fff; }
    .swb-link-btn i { font-size: 14px; }


    .swb-body { border-top: 1px solid #FCD34D; }
    .swb-item { border-bottom: 1px solid #FEF3C7; }
    .swb-item:last-child { border-bottom: none; }

    .swb-item-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 20px; cursor: pointer; transition: background 0.15s;
    }
    .swb-item-header:hover { background: #FEF9E7; }
    .swb-item-name {
        display: flex; align-items: center; gap: 8px;
        font-size: 13.5px; font-weight: 600; color: #1E293B;
    }
    .swb-item-name i { color: #D97706; font-size: 16px; }
    .swb-item-stats {
        display: flex; align-items: center; gap: 16px;
    }
    .swb-stat {
        display: flex; flex-direction: column; align-items: center;
        padding: 4px 10px; border-radius: 8px; background: #fff;
        border: 1px solid #E2E8F0; min-width: 52px;
    }
    .swb-stat-label { font-size: 9px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.4px; }
    .swb-stat-val { font-size: 14px; font-weight: 800; color: #0F172A; }
    .swb-stat-ok .swb-stat-val { color: #16A34A; }
    .swb-stat-miss .swb-stat-val { color: #DC2626; }
    .swb-item-arrow { font-size: 20px; color: #D97706; transition: transform 0.2s; }
    .swb-item-arrow.open { transform: rotate(90deg); }

    .swb-progress-bar {
        width: 80px; height: 6px; background: #FDE68A; border-radius: 99px; overflow: hidden;
    }
    .swb-progress-fill {
        height: 100%; background: #F59E0B; border-radius: 99px;
        transition: width 0.4s ease;
    }

    .swb-satker-list { background: #fff; border-top: 1px solid #FEF3C7; padding: 0 20px 16px; }
    .swb-satker-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 12px; }
    .swb-satker-table th {
        font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase;
        letter-spacing: 0.4px; padding: 6px 10px; text-align: left;
        border-bottom: 1px solid #F1F5F9;
    }
    .swb-satker-table td {
        font-size: 12.5px; color: #475569; padding: 8px 10px; border-bottom: 1px solid #F8FAFC;
    }
    .swb-satker-table tbody tr:hover td { background: #FFFBEB; }
    .swb-satker-table tbody tr:last-child td { border-bottom: none; }
    .swb-satker-link {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 11px; font-weight: 700; color: #D97706;
        text-decoration: none; padding: 3px 8px; border-radius: 6px;
        border: 1px solid #FCD34D; background: #FFFBEB;
        transition: all 0.15s; white-space: nowrap;
    }
    .swb-satker-link:hover { background: #D97706; color: #fff; border-color: #D97706; }
    .swb-satker-link i { font-size: 12px; }



    /* ── Main Panel & Table ── */
    .content-panel {
        background: #ffffff; border-radius: 16px; border: 1px solid #E2E8F0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02); overflow: hidden;
    }
    .panel-header { padding: 20px 24px; border-bottom: 1px solid #F1F5F9; }
    .panel-title-wrap { display: flex; align-items: center; gap: 14px; }
    .panel-icon {
        width: 36px; height: 36px; border-radius: 10px; background: #F8FAFC; color: #64748B;
        display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #E2E8F0;
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

    /* ── Export Action Panel ── */
    .action-panel {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
    }
    .export-actions-grid-horizontal {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .export-card-group-horizontal {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .export-divider {
        display: flex; align-items: center; text-align: center;
        position: relative;
    }
    .export-divider::before {
        content: ''; position: absolute; left: 0; right: 0; top: 50%;
        border-top: 1px dashed #E2E8F0; z-index: 1;
    }
    .export-divider span { position: relative; z-index: 2; margin: 0 auto; }

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
        transform: translateY(-2px);
    }
    .export-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        transition: transform 0.2s;
    }
    .export-btn:hover .export-icon { transform: scale(1.1); }
    .export-info { flex: 1; }
    .export-info h4 { font-size: 13px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .export-info p { font-size: 12px; color: #64748B; margin: 0; }

    /* Color Variants for Export Cards */
    .export-blue { border-color: #E0E7FF; }
    .export-blue:hover { background: #EEF2FF; border-color: #C7D2FE; }
    .export-blue .export-icon { background: #E0E7FF; color: #4F46E5; }
    
    .export-orange { border-color: #FFEDD5; }
    .export-orange:hover { background: #FFF7ED; border-color: #FED7AA; }
    .export-orange .export-icon { background: #FFEDD5; color: #F97316; }

    .export-green { border-color: #DCFCE7; }
    .export-green:hover { background: #F0FDF4; border-color: #BBF7D0; }
    .export-green .export-icon { background: #DCFCE7; color: #16A34A; }

    .export-purple { border-color: #F3E8FF; }
    .export-purple:hover { background: #FAF5FF; border-color: #E9D5FF; }
    .export-purple .export-icon { background: #F3E8FF; color: #9333EA; }

    .export-red { border-color: #FECACA; }
    .export-red:hover { background: #FEF2F2; border-color: #FCA5A5; }
    .export-red .export-icon { background: #FEE2E2; color: #DC2626; }

    /* Loading overlay */
    .export-btn .export-loading {
        position: absolute; inset: 0; background: rgba(255,255,255,0.85); backdrop-filter: blur(2px);
        display: none; align-items: center; justify-content: center; border-radius: 12px; z-index: 5;
    }
    .export-btn.is-loading { pointer-events: none; }
    .export-btn.is-loading .export-loading { display: flex; }
    .spinner { font-size: 24px; color: #C62828; animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* Responsive */
    @media (min-width: 1600px) {
        .package-hero { padding: 28px; }
        .package-hero h1.package-title { font-size: 24px !important; }
        .package-desc { font-size: 15px !important; }
        .btn-action-primary { font-size: 15px; padding: 12px 24px; }
        .wizard-step-card { padding: 24px; }
        .wizard-step-title h3 { font-size: 16px; }
        .wizard-step-title p { font-size: 13px; }
        .wizard-step-body .stat-value .num { font-size: 26px; }
        .wizard-step-body .stat-value .label { font-size: 12px; }
        .panel-title { font-size: 18px; }
        .panel-subtitle { font-size: 14px; }
        .panel-header { padding: 24px 28px; }
        .data-table th { padding: 16px 24px; font-size: 12px; }
        .data-table td { padding: 18px 24px; font-size: 14.5px; }
        .item-primary-name { font-size: 15px; }
        .export-info h4 { font-size: 14px; }
        .export-info p { font-size: 13px; }
        .action-panel { padding: 24px; }
        .qty-box { font-size: 14px; }
        .satker-chip { font-size: 13.5px; }
    }
    @media (min-width: 1920px) {
        .package-hero h1.package-title { font-size: 26px !important; }
        .wizard-step-body .stat-value .num { font-size: 28px; }
        .data-table td { font-size: 15px; }
        .panel-title { font-size: 19px; }
    }
    @media (max-width: 1024px) {
        .layout-grid { grid-template-columns: 1fr; }
        .wizard-track { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .export-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .export-card-group { display: contents; }
        .export-actions-grid-horizontal { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .wizard-track { grid-template-columns: 1fr; }
        .package-title-wrapper { flex-direction: column; align-items: flex-start; gap: 4px; }
        .data-table { min-width: 600px; }
        .table-wrap { overflow-x: auto; }
        .satker-dropdown-wrapper { padding-left: 16px; }
        .export-card-group-horizontal { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Download Loading State tersinkronisasi murni via Fetch Blob
    document.querySelectorAll('.export-btn[data-download]').forEach(function(btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault(); // Cegah redirect bawaan
            
            if (btn.classList.contains('is-loading')) return; // Cegah double klik
            btn.classList.add('is-loading');
            
            try {
                // Lakukan request file ke backend (browser mutar bola salju sambil menunggu)
                const response = await fetch(btn.href);
                if (!response.ok) throw new Error('Download request failed');
                
                // Coba petik/ekstrak nama file asli dari Header backend (jika ada Content-Disposition)
                const disposition = response.headers.get('Content-Disposition');
                let filename = 'Download_Report'; 
                
                // Fallback default extension
                if (btn.href.includes('pdf')) filename += '.pdf';
                else if (btn.href.includes('excel') || btn.href.includes('export')) filename += '.xlsx';

                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) { 
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                
                // Ambil bungkus file biner nya langsung
                const blob = await response.blob();
                
                // Bangun jembatan virtual agar user bisa mendownloadnya ke HDD
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click(); // Klik hantu untuk trigger simpan file
                
                // Bersihkan ceceran memory
                window.URL.revokeObjectURL(url);
                a.remove();
            } catch (error) {
                console.error("Export error:", error);
                alert("Gagal melakukan export atau sesi habis. Silakan refresh halaman dan coba lagi.");
            } finally {
                // Apapun yang terjadi, stop mutarnya 100% presisi sinkron waktu server!
                btn.classList.remove('is-loading');
            }
        });
    });
});

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

<script>
// ── Toggle Panel Peringatan Utama ──
function toggleWarningPanel() {
    const body = document.getElementById('swb-body');
    const icon = document.getElementById('swb-toggle-icon');
    if (!body) return;

    if (body.classList.contains('hidden')) {
        body.classList.remove('hidden');
        if (icon) icon.classList.add('open');
    } else {
        body.classList.add('hidden');
        if (icon) icon.classList.remove('open');
        // Tutup semua detail satker
        document.querySelectorAll('[id^="swb-detail-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="swb-arrow-"]').forEach(el => el.classList.remove('open'));
    }
}

// ── Toggle Detail per Satker ──
function toggleWarnDetail(idx) {
    const detail = document.getElementById('swb-detail-' + idx);
    const arrow  = document.getElementById('swb-arrow-' + idx);
    if (!detail) return;

    if (detail.classList.contains('hidden')) {
        detail.classList.remove('hidden');
        if (arrow) arrow.classList.add('open');
    } else {
        detail.classList.add('hidden');
        if (arrow) arrow.classList.remove('open');
    }
}
</script>
@endsection

