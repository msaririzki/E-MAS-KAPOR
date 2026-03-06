@extends('layouts.app')

@section('title', 'Pilih Penerima - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Tahap 2: Penerima</span>
@endsection

@section('content')

{{-- Hero Section --}}
<div class="package-hero">
    <div class="package-hero-inner">
        <div class="package-hero-back">
            <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="btn-back">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
        <div class="package-hero-content">
            <div class="package-title-wrapper" style="justify-content: space-between; width: 100%;">
                <div>
                    <h1 class="package-title">Tentukan Penerima Barang</h1>
                    <p class="package-desc" style="margin-top: 6px;">
                        Pilih satker penerima untuk setiap item. Gunakan filter opsional, atau kosongkan untuk menghitung semua personil aktif.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="btn-action-primary">
                        Lanjut ke Preview <i class="ri-arrow-right-line" style="margin-left: 6px;"></i>
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
                    <p>Item berhasil dipilih</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">{{ $budgetPackage->items->count() }}</span>
                    <span class="label">Item</span>
                </div>
                <i class="ri-checkbox-circle-fill wizard-step-arrow" style="color: #10B981; background: transparent;"></i>
            </div>
        </a>

        {{-- Step 2 --}}
        <div class="wizard-step-card active">
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
                <div class="active-indicator">Sedang Berlangsung</div>
            </div>
        </div>

        {{-- Step 3 --}}
        <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-step-card pending">
            <div class="wizard-step-header">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-title">
                    <h3>Preview & Hitung</h3>
                    <p>Ringkasan total & anggaran</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value highlight">
                    <span class="num">--</span>
                    <span class="label">Total Anggaran</span>
                </div>
                <i class="ri-lock-line wizard-step-arrow"></i>
            </div>
        </a>
    </div>
</div>

{{-- Items with Recipients --}}
<div class="recipients-container">
    @foreach($budgetPackage->items as $item)
    <div class="card premium-card recipient-card" id="item-card-{{ $item->id }}">
        
        <div class="card-head recipient-card-header">
            <div class="recipient-item-info">
                <div class="info-top">
                    <span class="badge badge-neutral badge-sm">{{ $item->kaporItem->category }}</span>
                    <span class="item-price">{{ $item->formatted_price }} <span class="unit">/ {{ $item->kaporItem->unit ?? 'PCS' }}</span></span>
                </div>
                <h3>{{ $item->kaporItem->item_name }}</h3>
            </div>
            <div class="recipient-summary">
                <div class="summary-box">
                    <span class="recipient-count" id="count-{{ $item->id }}">{{ $item->recipients->sum('matched_count') }}</span>
                    <span class="summary-label">Personil Terestimasi</span>
                </div>
            </div>
        </div>

        <div class="card-body flush recipient-card-body">
            <div class="body-grid">
                {{-- Left side: Satker selection --}}
                <div class="satker-section">
                    <div class="section-title-wrap" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="ri-building-4-line text-brand"></i>
                            <h4 class="section-label">Pilih Satker Penerima</h4>
                        </div>
                        <button type="button" class="btn-select-all" onclick="toggleAllSatkers({{ $item->id }})">
                            Pilih Semua
                        </button>
                    </div>
                    
                    <div class="satker-checkboxes custom-scrollbar" id="satker-list-{{ $item->id }}">
                        @foreach($allSatkers as $satker)
                        <label class="satker-checkbox">
                            <input type="checkbox" name="satker_{{ $item->id }}[]"
                                   value="{{ $satker->id }}"
                                   {{ $item->recipients->pluck('satker_id')->contains($satker->id) ? 'checked' : '' }}>
                            <div class="checkbox-visual"></div>
                            <span class="satker-name">{{ $satker->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Right side: Filters & Current --}}
                <div class="filter-section" style="padding: 16px;">
                    <div class="section-title-wrap" style="margin-bottom: 12px;">
                        <i class="ri-filter-3-line text-brand"></i>
                        <h4 class="section-label">Filter Personil <span class="optional-text">(Kosongkan = Semua)</span></h4>
                    </div>
                    
                    <div class="filter-groups" style="gap: 12px;">
                        {{-- Tipe Personil --}}
                        <div class="filter-item">
                            <label class="filter-label">Tipe Personil</label>
                            <div class="filter-pills">
                                <label class="pill-check">
                                    <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="polri" data-item="{{ $item->id }}"
                                           onchange="toggleRankOptions({{ $item->id }})">
                                    <span>Polri</span>
                                </label>
                                <label class="pill-check">
                                    <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="pns" data-item="{{ $item->id }}"
                                           onchange="toggleRankOptions({{ $item->id }})">
                                    <span>PNS</span>
                                </label>
                                <label class="pill-check">
                                    <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="pppk" data-item="{{ $item->id }}"
                                           onchange="toggleRankOptions({{ $item->id }})">
                                    <span>PPPK</span>
                                </label>
                            </div>
                        </div>

                        {{-- Gender --}}
                        <div class="filter-item">
                            <label class="filter-label">Gender</label>
                            <div class="filter-pills">
                                <label class="pill-check">
                                    <input type="checkbox" class="filter-input" data-filter="gender" data-value="L" data-item="{{ $item->id }}">
                                    <span><i class="ri-men-line"></i> Pria</span>
                                </label>
                                <label class="pill-check">
                                    <input type="checkbox" class="filter-input" data-filter="gender" data-value="P" data-item="{{ $item->id }}">
                                    <span><i class="ri-women-line"></i> Wanita</span>
                                </label>
                            </div>
                        </div>

                        {{-- Kategori Pangkat --}}
                        <div class="filter-item" id="rank-group-{{ $item->id }}">
                            <label class="filter-label">Kategori Pangkat</label>
                            <div class="filter-pills" id="rank-pills-{{ $item->id }}">
                                {{-- Pangkat Polri --}}
                                <label class="pill-check rank-pill polri-rank" data-type="polri">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PATI" data-item="{{ $item->id }}">
                                    <span>PATI</span>
                                </label>
                                <label class="pill-check rank-pill polri-rank" data-type="polri">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PAMEN" data-item="{{ $item->id }}">
                                    <span>PAMEN</span>
                                </label>
                                <label class="pill-check rank-pill polri-rank" data-type="polri">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PAMA" data-item="{{ $item->id }}">
                                    <span>PAMA</span>
                                </label>
                                <label class="pill-check rank-pill polri-rank" data-type="polri">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="BINTARA" data-item="{{ $item->id }}">
                                    <span>BINTARA</span>
                                </label>
                                <label class="pill-check rank-pill polri-rank" data-type="polri">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="TAMTAMA" data-item="{{ $item->id }}">
                                    <span>TAMTAMA</span>
                                </label>
                                {{-- Pangkat PNS/PPPK --}}
                                <label class="pill-check rank-pill pns-rank" data-type="pns">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PNS" data-item="{{ $item->id }}">
                                    <span>PNS</span>
                                </label>
                                <label class="pill-check rank-pill pns-rank" data-type="pppk">
                                    <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PPPK" data-item="{{ $item->id }}">
                                    <span>PPPK</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Current Recipients (Auto-updated) FULL WIDTH --}}
            <div class="current-recipients" id="recipients-container-{{ $item->id }}" style="display: {{ $item->recipients->count() > 0 ? 'block' : 'none' }}; border-top: 1px solid #F1F5F9;">
                <label class="filter-label">Satker Terpilih Berdasarkan Filter Terakhir</label>
                <div class="recipient-tags" id="recipient-tags-{{ $item->id }}">
                    @if($item->recipients->count() > 0)
                        @foreach($item->recipients as $recipient)
                        <span class="recipient-tag {{ $recipient->matched_count == 0 ? 'zero-count' : '' }}">
                            {{ $recipient->satker->name }}
                            <span class="r-count">{{ $recipient->matched_count }} org</span>
                        </span>
                        @endforeach
                    @else
                        <span style="font-size:12px; color:#94A3B8; font-style:italic;">Belum ada satker penerima yang dipilih.</span>
                    @endif
                </div>
            </div>
            
        </div>
        
        {{-- Area for auto-save notification floating --}}
        <div id="save-status-{{ $item->id }}" class="auto-save-status">
            <i class="ri-check-line"></i> Tersimpan
        </div>
    </div>
    @endforeach
</div>

@endsection

@section('styles')
<style>
    /* ── Utilities ── */
    .text-brand { color: #C62828; }
    .badge-sm { padding: 3px 8px; font-size: 10.5px; border-radius: 6px; }
    
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

    /* ── Recipient Cards ── */
    .premium-card { border-color: #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
    .recipient-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 24px; background: #fff; border-bottom: 1px solid #F1F5F9;
    }
    .recipient-item-info .info-top { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .item-price { font-size: 14px; font-weight: 600; color: #475569; }
    .item-price .unit { color: #94A3B8; font-weight: 400; font-size: 12px; }
    .recipient-item-info h3 { font-size: 18px; font-weight: 800; color: #0F172A; margin: 0; letter-spacing: -0.3px; }
    
    .recipient-summary { text-align: right; }
    .summary-box { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px 16px; border-radius: 12px; display: inline-flex; flex-direction: column; align-items: flex-end; }
    .recipient-count { font-size: 26px; font-weight: 800; color: #C62828; line-height: 1; margin-bottom: 4px; }
    .summary-label { font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }

    .recipient-card-body { padding: 0; }
    .body-grid { display: grid; grid-template-columns: 2fr 1fr; }
    @media (max-width: 900px) { .body-grid { grid-template-columns: 1fr; } }
    
    .satker-section { padding: 24px; border-right: 1px solid #F1F5F9; }
    .filter-section { padding: 16px 24px; background: #FAFAFA; border-left: 1px solid #F1F5F9; }
    @media (max-width: 900px) { 
        .satker-section { border-right: none; border-bottom: 1px solid #F1F5F9; } 
        .filter-section { border-left: none; }
    }

    .section-title-wrap { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; width: 100%; }
    .section-title-wrap i { font-size: 18px; }
    .section-label { font-size: 14px; font-weight: 700; color: #1E293B; margin: 0; }
    .optional-text { font-size: 12px; font-weight: 400; color: #94A3B8; }
    
    .btn-select-all {
        background: #F1F5F9; border: 1px solid #E2E8F0; color: #475569; padding: 4px 10px;
        border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s;
    }
    .btn-select-all.active {
        background: #FEF2F2; color: #C62828; border-color: #FECACA;
    }
    .btn-select-all:hover { filter: brightness(0.95); }

    /* Satker Select UI */
    .satker-checkboxes {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 8px; max-height: 280px; overflow-y: auto; padding-right: 8px;
    }
    @media (max-width: 1024px) { .satker-checkboxes { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .satker-checkboxes { grid-template-columns: 1fr; } }
    
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #F1F5F9; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

    .satker-checkbox {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px; border: 1px solid #E2E8F0; border-radius: 10px;
        cursor: pointer; transition: all 0.2s; background: #fff;
    }
    .satker-checkbox:hover { border-color: #CBD5E1; background: #F8FAFC; }
    .satker-checkbox input[type="checkbox"] { display: none; }
    .checkbox-visual {
        width: 18px; height: 18px; border: 2px solid #CBD5E1; border-radius: 5px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all 0.2s; background: #fff; margin-top: 1px;
    }
    .checkbox-visual::after {
        content: '\EB7A'; font-family: 'RemixIcon'; color: #fff;
        font-size: 12px; font-weight: bold; opacity: 0; transform: scale(0.5); transition: all 0.2s;
    }
    .satker-checkbox input[type="checkbox"]:checked + .checkbox-visual { background: #C62828; border-color: #C62828; }
    .satker-checkbox input[type="checkbox"]:checked + .checkbox-visual::after { opacity: 1; transform: scale(1); }
    .satker-checkbox input[type="checkbox"]:checked ~ .satker-name { color: #0F172A; font-weight: 600; }
    .satker-name { font-size: 13px; color: #475569; font-weight: 500; line-height: 1.4; transition: all 0.2s; }

    /* Filter UI */
    .filter-groups { display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; }
    .filter-label { font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 8px; display: block; }
    .filter-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .pill-check { cursor: pointer; }
    .pill-check input { display: none; }
    .pill-check span {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
        border: 1px solid #E2E8F0; background: #fff; color: #475569; transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .pill-check span i { font-size: 14px; }
    .pill-check input:checked + span { background: #C62828; color: #fff; border-color: #C62828; box-shadow: 0 2px 6px rgba(198,40,40,0.2); }
    .pill-check span:hover { border-color: #CBD5E1; background: #F8FAFC; }
    .pill-check input:checked + span:hover { background: #B91C1C; border-color: #B91C1C; }

    /* Current Recipients Tags - Full Width */
    .current-recipients { background: #FAFAFA; padding: 20px 24px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; }
    .recipient-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .recipient-tag {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 12px; font-weight: 600; padding: 6px 12px;
        background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; border-radius: 8px;
    }
    .recipient-tag.zero-count {
        background: #F1F5F9; color: #64748B; border-color: #E2E8F0;
    }
    .recipient-tag.zero-count .r-count {
        color: #64748B; border-color: #E2E8F0; background: #F8FAFC;
    }
    .r-count { background: #fff; color: #15803D; padding: 2px 6px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #BBF7D0; }

    /* Auto-save status feedback as floating notification */
    .auto-save-status {
        position: absolute;
        bottom: 24px; right: 24px;
        background: #fff; padding: 8px 16px; border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #E2E8F0;
        z-index: 10; font-size: 13px; font-weight: 600;
        display: flex; align-items: center; gap: 6px;
        opacity: 0; visibility: hidden; transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .auto-save-status.saving { opacity: 1; visibility: visible; transform: translateY(0); color: #64748B; border-color: #CBD5E1; }
    .auto-save-status.success { opacity: 1; visibility: visible; transform: translateY(0); color: #10B981; border-color: #BBF7D0; background: #F0FDF4; }
    .auto-save-status.error { opacity: 1; visibility: visible; transform: translateY(0); color: #EF4444; border-color: #FECACA; background: #FEF2F2; }
    
    .spinner { display: inline-block; animation: spin 1s linear infinite; }
</style>
@endsection

@section('scripts')
<script>
    // Dinamis: tampilkan/sembunyikan rank options berdasarkan tipe personil
    function toggleRankOptions(itemId) {
        const polriChecked = document.querySelector(`input[data-item="${itemId}"][data-value="polri"]`)?.checked;
        const pnsChecked = document.querySelector(`input[data-item="${itemId}"][data-value="pns"]`)?.checked;
        const pppkChecked = document.querySelector(`input[data-item="${itemId}"][data-value="pppk"]`)?.checked;

        const rankGroup = document.getElementById('rank-pills-' + itemId);
        if (!rankGroup) return;

        const polriRanks = rankGroup.querySelectorAll('.polri-rank');
        const pnsRanks = rankGroup.querySelectorAll('.pns-rank[data-type="pns"]');
        const pppkRanks = rankGroup.querySelectorAll('.pns-rank[data-type="pppk"]');

        const anySelected = polriChecked || pnsChecked || pppkChecked;

        // Jika tidak ada tipe yg dipilih, tampilkan semua rank
        if (!anySelected) {
            rankGroup.querySelectorAll('.rank-pill').forEach(p => { p.style.display = ''; });
            return;
        }

        // Sembunyikan/tampilkan berdasarkan pilihan
        polriRanks.forEach(p => { p.style.display = polriChecked ? '' : 'none'; });
        pnsRanks.forEach(p => { p.style.display = pnsChecked ? '' : 'none'; });
        pppkRanks.forEach(p => { p.style.display = pppkChecked ? '' : 'none'; });

        // Uncheck yang disembunyikan
        rankGroup.querySelectorAll('.rank-pill').forEach(pill => {
            if (pill.style.display === 'none') {
                pill.querySelector('input').checked = false;
            }
        });
    }

    // Toggle Semua Satker (Check/Uncheck All)
    function toggleAllSatkers(itemId) {
        const container = document.getElementById('satker-list-' + itemId);
        const checkboxes = container.querySelectorAll('input[type="checkbox"]');
        const btn = document.querySelector(`button[onclick="toggleAllSatkers(${itemId})"]`);
        
        // Cek apakan semua checkbox sudah tercentang
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        
        // Jika sudah semua tercentang, maka batalkan semua. Jika belum, centang semua.
        const newState = !allChecked;
        
        checkboxes.forEach(cb => {
            cb.checked = newState;
        });
        
        // Update tombol teks & warna
        if (newState) {
            btn.textContent = 'Batalkan Pilih Semua';
            btn.classList.add('active');
        } else {
            btn.textContent = 'Pilih Semua';
            btn.classList.remove('active');
        }
        
        // Trigger auto-save immediately
        saveRecipients(itemId);
    }

    // Auto-save function
    async function saveRecipients(packageItemId) {
        const card = document.getElementById('item-card-' + packageItemId);
        const countElement = document.getElementById('count-' + packageItemId);
        const statusElement = document.getElementById('save-status-' + packageItemId);
        
        // UI Feedback: Loading state
        statusElement.className = 'auto-save-status saving';
        statusElement.innerHTML = '<i class="ri-loader-4-line spinner"></i> Menyimpan...';
        card.style.opacity = '0.7';
        card.style.pointerEvents = 'none';

        // Kumpulkan satker terpilih
        const satkerList = document.getElementById('satker-list-' + packageItemId);
        const satkerIds = [];
        satkerList.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            satkerIds.push(cb.value);
        });

        // Kumpulkan filter
        const filters = {};
        document.querySelectorAll(`.filter-input[data-item="${packageItemId}"]:checked`).forEach(cb => {
            const key = cb.dataset.filter;
            if (!filters[key]) filters[key] = [];
            filters[key].push(cb.dataset.value);
        });

        try {
            const resp = await fetch(`/admin/budget/package-items/${packageItemId}/save-recipients`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    satker_ids: satkerIds, // Now allows empty array
                    filters: Object.keys(filters).length > 0 ? filters : null,
                })
            });

            const data = await resp.json();

            // Check if backend rejected validation
            if (resp.status === 422) {
                statusElement.className = 'auto-save-status error';
                statusElement.innerHTML = '<i class="ri-error-warning-line"></i> Pilih minimal 1 satker';
                countElement.textContent = '0';
                
                // Show empty state visually
                const container = document.getElementById('recipients-container-' + packageItemId);
                const tagsWrapper = document.getElementById('recipient-tags-' + packageItemId);
                tagsWrapper.innerHTML = `<span style="font-size:12px; color:#94A3B8; font-style:italic;">Belum ada satker penerima yang dipilih.</span>`;
                container.style.display = 'block';
                return;
            }

            if (data.success) {
                // Update count with animation
                countElement.textContent = data.total_recipients;
                
                // Update current recipients UI dynamically
                const container = document.getElementById('recipients-container-' + packageItemId);
                const tagsWrapper = document.getElementById('recipient-tags-' + packageItemId);

                if (satkerIds.length === 0) {
                     tagsWrapper.innerHTML = `<span style="font-size:12px; color:#94A3B8; font-style:italic;">Belum ada satker penerima yang dipilih.</span>`;
                     container.style.display = 'block';
                } else if (data.recipients_detail && data.recipients_detail.length > 0) {
                    tagsWrapper.innerHTML = '';
                    let hasRealRecipients = false;
                    
                    data.recipients_detail.forEach(r => {
                        hasRealRecipients = true;
                        tagsWrapper.innerHTML += `
                            <span class="recipient-tag ${r.count === 0 ? 'zero-count' : ''}">
                                ${r.satker_name}
                                <span class="r-count">${r.count} org</span>
                            </span>
                        `;
                    });
                    
                    container.style.display = hasRealRecipients ? 'block' : 'none';
                } else {
                    container.style.display = 'none';
                }
                
                statusElement.className = 'auto-save-status success';
                statusElement.innerHTML = '<i class="ri-check-line"></i> Tersimpan otomatis';
            } else {
                throw new Error('Save failed');
            }
        } catch (err) {
            console.error(err);
            statusElement.className = 'auto-save-status error';
            statusElement.innerHTML = '<i class="ri-close-circle-line"></i> Gagal menyimpan';
        } finally {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            
            // Hide status after a few seconds if it's success
            if(statusElement.classList.contains('success')) {
                setTimeout(() => {
                    statusElement.classList.remove('success'); // fade out
                }, 2000);
            }
        }
    }

    // Pre-populate filters from database
    const savedFilters = @json(
        $budgetPackage->items->mapWithKeys(function($item) {
            // Ambil filter dari recipient pertama (semua recipients punya filter sama per item)
            $firstRecipient = $item->recipients->first();
            return [$item->id => $firstRecipient ? ($firstRecipient->recipient_filters ?? []) : []];
        })
    );

    document.addEventListener('DOMContentLoaded', function() {
        // Pre-check filter pills berdasarkan data tersimpan
        Object.keys(savedFilters).forEach(itemId => {
            const filters = savedFilters[itemId];
            if (!filters || Object.keys(filters).length === 0) return;

            // Pre-check personnel_type
            if (filters.personnel_type && Array.isArray(filters.personnel_type)) {
                filters.personnel_type.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="personnel_type"][data-value="${val.toLowerCase()}"]`);
                    if (cb) cb.checked = true;
                });
            }

            // Pre-check gender
            if (filters.gender && Array.isArray(filters.gender)) {
                filters.gender.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="gender"][data-value="${val}"]`);
                    if (cb) cb.checked = true;
                });
            }

            // Pre-check rank_categories
            if (filters.rank_categories && Array.isArray(filters.rank_categories)) {
                filters.rank_categories.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="rank_categories"][data-value="${val}"]`);
                    if (cb) cb.checked = true;
                });
            }
        });

        // Toggle rank visibility berdasarkan tipe personel yang tercheck
        @foreach($budgetPackage->items as $item)
            toggleRankOptions({{ $item->id }});
        @endforeach

        // EVENT LISTENERS FOR AUTO-SAVE
        let saveTimeouts = {};

        document.querySelectorAll('.recipient-card').forEach(card => {
            const itemId = card.id.replace('item-card-', '');
            const inputs = card.querySelectorAll('input[type="checkbox"]');
            
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    // Cek jika ini adalah input tipe personil untuk toggle tampilan rank
                    if (input.dataset.filter === 'personnel_type') {
                        toggleRankOptions(itemId);
                    }
                    
                    // Clear existing delay
                    if(saveTimeouts[itemId]) {
                        clearTimeout(saveTimeouts[itemId]);
                    }
                    
                    // Set new delay (debounce)
                    saveTimeouts[itemId] = setTimeout(() => {
                        saveRecipients(itemId);
                    }, 500); // Wait 500ms after last interaction before saving
                });
            });
        });
    });
</script>
<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
@endsection
