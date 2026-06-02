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
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
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
                    <span class="label">Satker &bull; {{ number_format($budgetPackage->items->sum(fn($i) => $i->recipients->sum('matched_count')), 0, ',', '.') }} Personel</span>
                </div>
                <div class="active-indicator">Sedang Berlangsung</div>
            </div>
        </div>

        {{-- Step 3 --}}
        <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-step-card pending">
            <div class="wizard-step-header">
                <div class="wizard-step-number">3</div>
                <div class="wizard-step-title">
                    <h3>Pratinjau & Hitung</h3>
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
<div class="items-search-container" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div style="position: relative; flex: 1;">
        <i class="ri-search-line" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 16px;"></i>
        <input type="text" id="global-item-search" class="form-input" style="padding-left: 36px; padding-top: 8px; padding-bottom: 8px; width: 100%; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" placeholder="Cari nama barang kapor..." oninput="filterGlobalItems(this.value)">
    </div>
    
    {{-- Toggle Auto-Saran Filter --}}
    <div class="auto-suggest-toggle" id="auto-suggest-wrap" title="Auto-Saran Filter">
        <span class="toggle-label">Auto-Saran Filter</span>
        <label class="toggle-switch">
            <input type="checkbox" id="auto-suggest-toggle" checked>
            <span class="toggle-track">
                <span class="toggle-thumb"></span>
            </span>
        </label>
        <span class="toggle-state" id="toggle-state-text">Aktif</span>
    </div>
</div>

<div class="recipients-container">
    @foreach($budgetPackage->items as $item)
    <div class="card premium-card recipient-card {{ $loop->first ? '' : 'collapsed' }}" id="item-card-{{ $item->id }}">
        
        <div class="card-head recipient-card-header" onclick="toggleCard({{ $item->id }})">
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
                <div class="card-toggle-icon">
                    <i class="ri-arrow-down-s-line"></i>
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
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn-select-all btn-select-polres" onclick="togglePolresSatkers({{ $item->id }})">
                                Polres
                            </button>
                            <button type="button" class="btn-select-all" onclick="toggleAllSatkers({{ $item->id }})">
                                Pilih Semua
                            </button>
                        </div>
                    </div>

                    <div class="satker-search-wrap" style="margin-bottom: 12px; position: relative;">
                        <i class="ri-search-line" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8;"></i>
                        <input type="text" class="form-input" style="padding-left: 36px; padding-top: 8px; padding-bottom: 8px; width: 100%; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px;" placeholder="Cari nama satker..." oninput="filterSatkers({{ $item->id }}, this.value)">
                    </div>
                    
                    <div class="satker-checkboxes custom-scrollbar" id="satker-list-{{ $item->id }}">
                        @foreach($allSatkers as $satker)
                        <label class="satker-checkbox" data-scope="{{ $satker->recipientScope() }}">
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
                        <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                            {{-- Tipe Personil --}}
                            <div class="filter-item">
                                <label class="filter-label">Tipe Personil</label>
                                <div class="filter-pills">
                                    <label class="pill-check">
                                        <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="polri" data-item="{{ $item->id }}"
                                               onchange="toggleRankOptions({{ $item->id }})">
                                        <span>Polri</span>
                                    </label>
                                    <label class="pill-check pill-pns-pppk" for="toggle-pns-pppk-{{ $item->id }}">
                                        <input type="checkbox" class="filter-input pns-hidden" data-filter="personnel_type" data-value="pns" data-item="{{ $item->id }}" style="display:none;">
                                        <input type="checkbox" class="filter-input pppk-hidden" data-filter="personnel_type" data-value="pppk" data-item="{{ $item->id }}" style="display:none;">
                                        <input type="checkbox" class="pill-visual-toggle" id="toggle-pns-pppk-{{ $item->id }}"
                                               onchange="syncPnsPppkVisual(this, {{ $item->id }})">
                                        <span>PNS/PPPK</span>
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
                        </div>

                        {{-- Kategori Pangkat --}}
                        <div class="filter-item" id="rank-group-{{ $item->id }}">
                            <label class="filter-label">Kategori Golongan</label>
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
                                {{-- Golongan PNS/PPPK --}}
                                <label class="pill-check rank-pill pns-rank" data-type="pns">
                                    <input type="checkbox" class="filter-input" data-filter="golongan" data-value="1" data-item="{{ $item->id }}">
                                    <span>GOL 1</span>
                                </label>
                                <label class="pill-check rank-pill pns-rank" data-type="pns">
                                    <input type="checkbox" class="filter-input" data-filter="golongan" data-value="2" data-item="{{ $item->id }}">
                                    <span>GOL 2</span>
                                </label>
                                <label class="pill-check rank-pill pns-rank" data-type="pns">
                                    <input type="checkbox" class="filter-input" data-filter="golongan" data-value="3" data-item="{{ $item->id }}">
                                    <span>GOL 3</span>
                                </label>
                                <label class="pill-check rank-pill pns-rank" data-type="pns">
                                    <input type="checkbox" class="filter-input" data-filter="golongan" data-value="4" data-item="{{ $item->id }}">
                                    <span>GOL 4</span>
                                </label>
                            </div>
                        </div>

                        {{-- Keterangan bertingkat per scope satker --}}
                        <div class="filter-item" id="keterangan-group-{{ $item->id }}">
                            <label class="filter-label">Keterangan (Polda / Polres)</label>
                            <div class="ket-scope-grid">
                                @foreach($keteranganOptions as $scopeKey => $scopeConfig)
                                <div class="ket-scope-card">
                                    <div class="ket-scope-head">
                                        <div class="ket-scope-title">Filter {{ $scopeConfig['label'] }}</div>
                                        <span class="ket-scope-counter" id="ket-scope-counter-{{ $item->id }}-{{ $scopeKey }}">0 filter</span>
                                    </div>
                                    <div class="ket-field-grid">
                                        @foreach($scopeConfig['fields'] as $fieldKey => $fieldConfig)
                                            @php($dropdownKey = $item->id.'-'.$scopeKey.'-'.$fieldKey)
                                            <div class="ket-dropdown ket-dropdown-compact" id="ket-dropdown-{{ $dropdownKey }}">
                                                <button type="button" class="ket-dropdown-trigger" onclick="toggleKetDropdown('{{ $dropdownKey }}')">
                                                    <span class="ket-trigger-text" id="ket-trigger-text-{{ $dropdownKey }}" data-placeholder="{{ $fieldConfig['label'] }}">
                                                        {{ $fieldConfig['label'] }}
                                                    </span>
                                                    <i class="ri-arrow-down-s-line ket-arrow"></i>
                                                </button>
                                                <div class="ket-dropdown-panel" id="ket-panel-{{ $dropdownKey }}">
                                                    <div class="ket-search-wrap">
                                                        <i class="ri-search-line"></i>
                                                        <input type="text" class="ket-search-input" placeholder="Cari {{ strtolower($fieldConfig['label']) }}..." oninput="filterKetOptions('{{ $dropdownKey }}', this.value)">
                                                    </div>
                                                    <div class="ket-options-list custom-scrollbar">
                                                        @forelse($fieldConfig['options'] as $option)
                                                        <label class="ket-option" data-label="{{ strtolower($option['value']) }}">
                                                            <input
                                                                type="checkbox"
                                                                class="filter-input ket-checkbox"
                                                                data-filter="keterangan_scoped"
                                                                data-item="{{ $item->id }}"
                                                                data-scope="{{ $scopeKey }}"
                                                                data-field="{{ $fieldKey }}"
                                                                data-value="{{ $option['value'] }}"
                                                            >
                                                            <div class="ket-option-check"><i class="ri-check-line"></i></div>
                                                            <span class="ket-option-name">{{ $option['value'] }}</span>
                                                            <span class="ket-option-count">{{ $option['count'] }}</span>
                                                        </label>
                                                        @empty
                                                        <div class="ket-empty">Tidak ada data {{ strtolower($fieldConfig['label']) }}.</div>
                                                        @endforelse
                                                    </div>
                                                    <div class="ket-footer">
                                                        <button type="button" class="ket-clear-btn" onclick="clearKetField({{ $item->id }}, '{{ $dropdownKey }}')">Reset</button>
                                                        <button type="button" class="ket-apply-btn" onclick="toggleKetDropdown('{{ $dropdownKey }}')">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="ket-selected-tags" id="ket-tags-{{ $item->id }}"></div>
                            <div class="ket-legacy-note" id="ket-legacy-note-{{ $item->id }}" style="display: none;"></div>
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
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
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

    /* ── Wizard Steps ── */
    .wizard-steps-container { margin-bottom: 20px; }
    .wizard-track { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    
    .wizard-step-card {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 16px;
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

    .wizard-step-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; position: relative; z-index: 2; }
    .wizard-step-number {
        width: 32px; height: 32px; border-radius: 8px;
        font-size: 14px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1px solid transparent;
    }
    .wizard-step-title h3 { font-size: 14px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .wizard-step-title p { font-size: 11px; color: #64748B; margin: 0; line-height: 1.4; }
    
    .wizard-step-body { display: flex; align-items: flex-end; justify-content: space-between; margin-top: auto; position: relative; z-index: 2; }
    .wizard-step-body .stat-value { display: flex; flex-direction: column; }
    .wizard-step-body .stat-value .num { font-size: 18px; font-weight: 800; color: #0F172A; line-height: 1; margin-bottom: 4px; letter-spacing: -0.5px; }
    .wizard-step-body .stat-value .label { font-size: 10px; color: #94A3B8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
    
    .wizard-step-arrow { font-size: 18px; color: #CBD5E1; }
    .active-indicator { font-size: 10px; font-weight: 700; color: #C62828; background: #FEF2F2; padding: 4px 8px; border-radius: 10px; }

    /* ── Recipient Cards ── */
    .premium-card { border-color: #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); border-radius: 12px; margin-bottom: 20px; position: relative; transition: all 0.3s; }
    .premium-card.collapsed { margin-bottom: 12px; }
    
    .recipient-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 20px; background: #fff; border-bottom: 1px solid #F1F5F9;
        border-radius: 12px 12px 0 0; cursor: pointer; user-select: none; transition: background 0.2s;
    }
    .recipient-card-header:hover { background: #F8FAFC; }
    .premium-card.collapsed .recipient-card-header { border-bottom: none; border-radius: 12px; }

    .recipient-item-info .info-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .item-price { font-size: 13px; font-weight: 600; color: #475569; }
    .item-price .unit { color: #94A3B8; font-weight: 400; font-size: 11px; }
    .recipient-item-info h3 { font-size: 16px; font-weight: 800; color: #0F172A; margin: 0; letter-spacing: -0.2px; }
    
    .recipient-summary { display: flex; align-items: center; gap: 12px; text-align: right; }
    .summary-box { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 6px 12px; border-radius: 8px; display: inline-flex; flex-direction: column; align-items: flex-end; }
    .recipient-count { font-size: 20px; font-weight: 800; color: #C62828; line-height: 1; margin-bottom: 2px; }
    .summary-label { font-size: 10px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }

    .card-toggle-icon {
        width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
        color: #94A3B8; font-size: 20px; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .premium-card.collapsed .card-toggle-icon { transform: rotate(-90deg); }

    .recipient-card-body { padding: 0; transition: max-height 0.3s ease; }
    .premium-card.collapsed .recipient-card-body { display: none; }
    
    .body-grid { display: grid; grid-template-columns: 1.15fr 1fr; align-items: stretch; }
    @media (max-width: 900px) { .body-grid { grid-template-columns: 1fr; } }
    
    .satker-section { padding: 16px 20px; border-right: 1px solid #F1F5F9; display: flex; flex-direction: column; }
    .filter-section { padding: 12px 20px; background: #FAFAFA; border-left: 1px solid #F1F5F9; }
    @media (max-width: 900px) { 
        .satker-section { border-right: none; border-bottom: 1px solid #F1F5F9; } 
        .filter-section { border-left: none; }
    }

    .section-title-wrap { display: flex; align-items: center; gap: 6px; margin-bottom: 12px; width: 100%; }
    .section-title-wrap i { font-size: 16px; }
    .section-label { font-size: 13px; font-weight: 700; color: #1E293B; margin: 0; }
    .optional-text { font-size: 11px; font-weight: 400; color: #94A3B8; }
    
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
        display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 6px; overflow-y: auto; padding-right: 6px;
        flex: 1; max-height: 300px;
    }
    @media (max-width: 1024px) { .satker-checkboxes { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } }
    @media (max-width: 600px) { .satker-checkboxes { grid-template-columns: 1fr; } }
    
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #F1F5F9; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

    .satker-checkbox {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 8px 10px; border: 1px solid #E2E8F0; border-radius: 8px;
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
    .filter-groups { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
    .filter-label { font-size: 11px; font-weight: 600; color: #64748B; margin-bottom: 6px; display: block; }
    .filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }
    .pill-check { cursor: pointer; }
    .pill-check input { display: none; }
    .pill-check span {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 600;
        border: 1px solid #E2E8F0; background: #fff; color: #475569; transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .pill-check span i { font-size: 14px; }
    .pill-check input:checked + span { background: #C62828; color: #fff; border-color: #C62828; box-shadow: 0 2px 6px rgba(198,40,40,0.2); }
    .pill-check span:hover { border-color: #CBD5E1; background: #F8FAFC; }
    .pill-check input:checked + span:hover { background: #B91C1C; border-color: #B91C1C; }

    /* Current Recipients Tags - Full Width */
    .current-recipients { background: #FAFAFA; padding: 12px 20px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; }
    .recipient-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .recipient-tag {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 600; padding: 4px 10px;
        background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; border-radius: 6px;
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
    // Global debounce timeouts
    let saveTimeouts = {};
    // Sync PNS/PPPK visual toggle → kedua hidden checkbox
    function syncPnsPppkVisual(visualCb, itemId) {
        const label = visualCb.closest('.pill-pns-pppk');
        const pnsCb = label.querySelector('input[data-value="pns"]');
        const pppkCb = label.querySelector('input[data-value="pppk"]');
        
        pnsCb.checked = visualCb.checked;
        pppkCb.checked = visualCb.checked;
        
        toggleRankOptions(itemId);
        
        // Dispatch 'change' event manually so the auto-save listener triggers
        pnsCb.dispatchEvent(new Event('change'));
    }

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

    // Accordion Toggle
    function toggleCard(itemId) {
        const card = document.getElementById('item-card-' + itemId);
        card.classList.toggle('collapsed');
        
        // Opsional: Jika ingin gaya accordion sejati (satu terbuka pada satu waktu)
        // document.querySelectorAll('.recipient-card').forEach(c => {
        //     if (c.id !== 'item-card-' + itemId) {
        //         c.classList.add('collapsed');
        //     }
        // });
    }

    // Filter pencarian satker
    function filterSatkers(itemId, query) {
        const container = document.getElementById('satker-list-' + itemId);
        const labels = container.querySelectorAll('.satker-checkbox');
        const q = query.toLowerCase().trim();

        labels.forEach(label => {
            const name = label.querySelector('.satker-name')?.textContent?.toLowerCase() || '';
            if (name.includes(q)) {
                label.style.display = 'flex';
            } else {
                label.style.display = 'none';
            }
        });
    }

    // Filter pencarian barang kapor global
    function filterGlobalItems(query) {
        const q = query.toLowerCase().trim();
        const cards = document.querySelectorAll('.recipient-card');

        cards.forEach(card => {
            const itemName = card.querySelector('.recipient-item-info h3')?.textContent?.toLowerCase() || '';
            const categoryName = card.querySelector('.badge-neutral')?.textContent?.toLowerCase() || '';

            if (itemName.includes(q) || categoryName.includes(q)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Toggle Polres Satkers saja
    function togglePolresSatkers(itemId) {
        const container = document.getElementById('satker-list-' + itemId);
        const labels = container.querySelectorAll('.satker-checkbox');
        const btn = document.querySelector(`button[onclick="togglePolresSatkers(${itemId})"]`);

        const polresCheckboxes = [];
        labels.forEach(label => {
            if (label.dataset.scope === 'polres') {
                polresCheckboxes.push(label.querySelector('input[type="checkbox"]'));
            }
        });

        // Cek apakah semua polres sudah tercentang
        const allChecked = polresCheckboxes.every(cb => cb.checked);
        polresCheckboxes.forEach(cb => { cb.checked = !allChecked; });

        // Update tombol
        btn.textContent = allChecked ? 'Polres' : 'Batal Polres';
        btn.classList.toggle('active', !allChecked);

        // Trigger auto-save
        saveRecipients(itemId);
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
        const autoFilters = {};
        const savedFilterState = savedFilters[String(packageItemId)] || {};

        document.querySelectorAll(`.filter-input[data-item="${packageItemId}"]:checked`).forEach(cb => {
            const key = cb.dataset.filter;
            const val = cb.dataset.value;
            if (key === 'keterangan_scoped') return;

            if (!filters[key]) filters[key] = [];
            filters[key].push(val);

            // Cek apakah punya badge AUTO (untuk menandai filter ini murni hasil auto-suggest)
            let isAuto = false;
            const span = cb.nextElementSibling || cb.closest('label')?.querySelector('span');
            if (span && span.querySelector('.auto-badge')) isAuto = true;
            if (cb.classList.contains('pns-hidden') || cb.classList.contains('pppk-hidden')) {
                const pnsBadge = cb.closest('.pill-pns-pppk')?.querySelector('.auto-badge');
                if (pnsBadge) isAuto = true;
            }

            if (isAuto) {
                if (!autoFilters[key]) autoFilters[key] = [];
                autoFilters[key].push(val);
            }
        });

        if (Object.keys(autoFilters).length > 0) {
            filters['_auto'] = autoFilters;
        }

        const scopedKeterangan = collectKeteranganFilters(packageItemId);
        const hasLegacyKeterangan = Array.isArray(savedFilterState.keterangan) && !savedFilterState.keterangan_scoped;

        if (hasLegacyKeterangan && !keteranganTouchedItems.has(String(packageItemId))) {
            filters.keterangan = [...savedFilterState.keterangan];
        } else if (Object.keys(scopedKeterangan).length > 0) {
            filters.keterangan_scoped = scopedKeterangan;
        }

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
                savedFilters[String(packageItemId)] = data.filters || {};

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
    const keteranganTouchedItems = new Set();

    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Auto-Saran Logic
        const autoSuggestToggle = document.getElementById('auto-suggest-toggle');
        const autoSuggestWrap = document.getElementById('auto-suggest-wrap');
        const toggleStateText = document.getElementById('toggle-state-text');
        
        // Load state from localStorage
        let isAutoSuggestEnabled = localStorage.getItem('kapor_auto_suggest') !== 'false';
        autoSuggestToggle.checked = isAutoSuggestEnabled;
        if (!isAutoSuggestEnabled) {
            autoSuggestWrap.classList.add('is-off');
            toggleStateText.textContent = 'Nonaktif';
        }

        // ── Helper: tambahkan badge AUTO ke span ─────────────────────────────────
        function addAutoBadge(span) {
            if (!span || span.querySelector('.auto-badge')) return;
            const badge = document.createElement('span');
            badge.className = 'auto-badge';
            badge.style.cssText = 'font-size:9px;background:rgba(255,255,255,0.3);padding:1px 5px;border-radius:10px;margin-left:4px;font-weight:700;letter-spacing:0.3px;';
            badge.textContent = 'AUTO';
            span.appendChild(badge);
        }

        // ── Fungsi Auto-detect: tipe personil & rank ───────────────────
        function runAutoSuggest(force = false) {
            if (!isAutoSuggestEnabled) return;

            document.querySelectorAll('.recipient-card').forEach(card => {
                const itemId = card.id.replace('item-card-', '');
                const filters = savedFilters[itemId] || {};

                // Jika sudah ada recipients tersimpan → hormati konfigurasi user, 
                // KECUALI jika user memaksa eksekusi auto-suggest lewat toggle ON (force = true)
                const hasSavedRecipients = card.querySelectorAll('input[name^="satker_"]:checked').length > 0;
                if (!force && hasSavedRecipients) return;

                let hasAutoApplied = false;

                const itemNameEl = card.querySelector('.recipient-item-info h3');
                const itemName = itemNameEl ? itemNameEl.textContent.trim() : '';

                // Auto-detect gender
                if (!(filters.gender && filters.gender.length > 0)) {
                    let autoGender = null;
                    const n = itemName.toUpperCase();
                    if (n.includes('WANITA') || n.includes('PEREMPUAN')) autoGender = 'P';
                    else if (n.includes('PRIA') || n.includes('LAKI')) autoGender = 'L';

                    if (autoGender) {
                        const cb = card.querySelector(`input.filter-input[data-filter="gender"][data-value="${autoGender}"]`);
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            addAutoBadge(cb.nextElementSibling);
                            hasAutoApplied = true;
                        }
                    }
                }

                // Auto-detect tipe personil
                if (!(filters.personnel_type && filters.personnel_type.length > 0)) {
                    const detectedType = detectPersonnelTypeFromName(itemName);

                    if (detectedType === 'polri') {
                        const cb = card.querySelector('input.filter-input[data-filter="personnel_type"][data-value="polri"]');
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            addAutoBadge(cb.nextElementSibling);
                            toggleRankOptions(itemId);
                            hasAutoApplied = true;
                        }
                    } else if (detectedType === 'pns') {
                        const visualToggle = card.querySelector('.pill-pns-pppk .pill-visual-toggle');
                        if (visualToggle && !visualToggle.checked) {
                            visualToggle.checked = true;
                            syncPnsPppkVisual(visualToggle, itemId);
                            addAutoBadge(visualToggle.closest('.pill-pns-pppk').querySelector('span'));
                            toggleRankOptions(itemId);
                            hasAutoApplied = true;
                        }
                    }
                }

                // Auto-detect rank
                if (!(filters.rank_categories && filters.rank_categories.length > 0)) {
                    const detectedRanks = detectRankFromName(itemName);
                    detectedRanks.forEach(rank => {
                        const cb = card.querySelector(`input.filter-input[data-filter="rank_categories"][data-value="${rank}"]`);
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            addAutoBadge(cb.nextElementSibling);
                            hasAutoApplied = true;
                        }
                    });
                }

                // Jika di-force (dari tombol toggle ON) dan ada perubahan, trigger save otomatis
                if (force && hasAutoApplied) {
                    if (saveTimeouts[itemId]) clearTimeout(saveTimeouts[itemId]);
                    saveTimeouts[itemId] = setTimeout(() => saveRecipients(itemId), 500);
                }
            });
        }

        autoSuggestToggle.addEventListener('change', async (e) => {
            isAutoSuggestEnabled = e.target.checked;
            localStorage.setItem('kapor_auto_suggest', isAutoSuggestEnabled);
            
            if (!isAutoSuggestEnabled) {
                // Bersihkan filter ber-tag '_auto' dari database secara sinkron sebelum memuat ulang halaman
                const fetchPromises = [];
                Object.keys(savedFilters).forEach(itemId => {
                    let filters = savedFilters[itemId];
                    if (!filters || Object.keys(filters).length === 0) return;

                    if (filters['_auto']) {
                        let changed = false;
                        Object.keys(filters['_auto']).forEach(filterKey => {
                            const autoValues = filters['_auto'][filterKey];
                            if (filters[filterKey] && Array.isArray(filters[filterKey])) {
                                filters[filterKey] = filters[filterKey].filter(v => !autoValues.includes(v));
                                if (filters[filterKey].length === 0) delete filters[filterKey];
                                changed = true;
                            }
                        });
                        delete filters['_auto'];

                        if (changed) {
                            const satkerIds = Array.from(document.querySelectorAll(`#satker-list-${itemId} input[type="checkbox"]:checked`)).map(cb => cb.value);
                            const p = fetch(`/admin/budget/package-items/${itemId}/save-recipients`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    satker_ids: satkerIds,
                                    filters: Object.keys(filters).length > 0 ? filters : null,
                                })
                            });
                            fetchPromises.push(p);
                        }
                    }
                });

                if (fetchPromises.length > 0) {
                    toggleStateText.innerHTML = '<i class="ri-loader-4-line spinner"></i> Sinkr..';
                    autoSuggestToggle.disabled = true;
                    await Promise.all(fetchPromises);
                }

                // Reload halaman untuk merender ulang UI sesuai mode Murni dari DB
                window.location.reload();
            } else {
                // Saat dinyalakan kembali, jalankan auto-suggest secara LIVE tanpa reload
                toggleStateText.textContent = 'Aktif';
                autoSuggestWrap.classList.remove('is-off');
                runAutoSuggest(true); // param force=true mengabaikan check hasSavedRecipients
            }
        });

        // Pre-check filter pills berdasarkan data tersimpan
        Object.keys(savedFilters).forEach(itemId => {
            let filters = savedFilters[itemId];
            if (!filters || Object.keys(filters).length === 0) return;

            // Pre-check personnel_type
            if (filters.personnel_type && Array.isArray(filters.personnel_type)) {
                filters.personnel_type.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="personnel_type"][data-value="${val.toLowerCase()}"]`);
                    if (cb) cb.checked = true;
                });
                // Sync visual toggle PNS/PPPK
                if (filters.personnel_type.some(v => v.toLowerCase() === 'pns' || v.toLowerCase() === 'pppk')) {
                    const visualToggle = document.querySelector(`#item-card-${itemId} .pill-pns-pppk .pill-visual-toggle`);
                    if (visualToggle) visualToggle.checked = true;
                }
            }

            // Pre-check gender dari filter tersimpan
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

        @foreach($budgetPackage->items as $item)
            initializeKeteranganUi({{ $item->id }});
        @endforeach

        // ── Helper: deteksi tipe personil dari nama item ──────────────────────────
        function detectPersonnelTypeFromName(name) {
            const n = name.toUpperCase();
            // Item unisex Polri & PNS — tidak difilter
            if ((n.includes('POLRI') && n.includes('PNS')) || n.includes('JILBAB')) return null;
            if (n.includes('OLAHRAGA') || n.includes('KAOS KAKI') || n.includes('ROMPI')) return null;
            // PNS/PPPK saja
            if (n.includes('PNS') || n.includes('KORPRI')) return 'pns';
            // Polri — kata kunci khas satuan Polri
            const polriKeywords = ['POLRI','POLANTAS','LANTAS','BRIMOB','PROVOS','RESINTEL',
                                   'RESINTELPAM','HUMAS','BARET','PET ','PDL','PDH','TWO TONE','SAMAPTA'];
            if (polriKeywords.some(k => n.includes(k))) return 'polri';
            return null;
        }

        // ── Helper: deteksi rank dari nama item ──────────────────────────────────
        function detectRankFromName(name) {
            const n = name.toUpperCase();
            const ranks = [];
            if (n.includes('PATI'))    ranks.push('PATI');
            if (n.includes('PAMEN'))   ranks.push('PAMEN');
            if (n.includes('PAMA') && !n.includes('PAMINAL')) ranks.push('PAMA');
            if (n.includes('BINTARA')) ranks.push('BINTARA');
            if (n.includes('TAMTAMA')) ranks.push('TAMTAMA');
            return ranks;
        }

        // Eksekusi auto-suggest saat load pertama kali (tidak di-force, hormati status existing)
        runAutoSuggest(false);

        // EVENT LISTENERS FOR AUTO-SAVE
        document.querySelectorAll('.recipient-card').forEach(card => {
            const itemId = card.id.replace('item-card-', '');
            const inputs = card.querySelectorAll('input[type="checkbox"]');
            
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    // Cek jika ini adalah input tipe personil untuk toggle tampilan rank
                    if (input.dataset.filter === 'personnel_type') {
                        toggleRankOptions(itemId);
                    }

                    if (input.dataset.filter === 'keterangan_scoped') {
                        keteranganTouchedItems.add(String(itemId));
                        clearLegacyKeteranganState(itemId);
                        updateKetSummary(itemId);
                    }

                    // Hapus badge AUTO jika user klik filter secara manual
                    const autoFilters = ['gender', 'personnel_type', 'rank_categories', 'golongan'];
                    if (autoFilters.includes(input.dataset.filter)) {
                        // Hapus badge dari span langsung atau dari label terdekat (pill-pns-pppk)
                        const span = input.nextElementSibling || input.closest('label')?.querySelector('span');
                        const badge = span ? span.querySelector('.auto-badge') : null;
                        if (badge) badge.remove();
                        // Juga hapus badge dari pill-visual-toggle (PNS/PPPK)
                        if (input.classList.contains('pns-hidden') || input.classList.contains('pppk-hidden')) {
                            const pnsBadge = input.closest('.pill-pns-pppk')?.querySelector('.auto-badge');
                            if (pnsBadge) pnsBadge.remove();
                        }
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
<script>
    // ── Keterangan Dropdown Functions ──
    const ketScopeLabels = { polda: 'Polda', polres: 'Polres' };
    const ketFieldLabels = {
        keterangan: 'Keterangan 1',
        keterangan_2: 'Keterangan 2',
        keterangan_3: 'Keterangan 3',
        keterangan_4: 'Keterangan 4',
    };

    function scheduleRecipientSave(itemId) {
        if (saveTimeouts[itemId]) clearTimeout(saveTimeouts[itemId]);
        saveTimeouts[itemId] = setTimeout(() => saveRecipients(itemId), 500);
    }

    function findKeteranganCheckbox(itemId, scope, field, value) {
        return Array.from(
            document.querySelectorAll(`input.filter-input[data-item="${itemId}"][data-filter="keterangan_scoped"][data-scope="${scope}"][data-field="${field}"]`)
        ).find(cb => cb.dataset.value === value);
    }

    function collectKeteranganFilters(itemId) {
        const scoped = {};

        document.querySelectorAll(`input.filter-input[data-item="${itemId}"][data-filter="keterangan_scoped"]:checked`).forEach(cb => {
            const scope = cb.dataset.scope;
            const field = cb.dataset.field;
            const value = cb.dataset.value;

            if (!scoped[scope]) scoped[scope] = {};
            if (!scoped[scope][field]) scoped[scope][field] = [];
            scoped[scope][field].push(value);
        });

        Object.keys(scoped).forEach(scope => {
            Object.keys(scoped[scope]).forEach(field => {
                scoped[scope][field] = [...new Set(scoped[scope][field])];
                if (scoped[scope][field].length === 0) delete scoped[scope][field];
            });

            if (Object.keys(scoped[scope]).length === 0) delete scoped[scope];
        });

        return scoped;
    }

    function updateKetDropdownTrigger(dropdownEl) {
        const trigger = dropdownEl.querySelector('.ket-trigger-text');
        if (!trigger) return;

        const checked = dropdownEl.querySelectorAll('input.filter-input[data-filter="keterangan_scoped"]:checked');
        const placeholder = trigger.dataset.placeholder || 'Keterangan';

        if (checked.length === 0) {
            trigger.innerHTML = `${placeholder}`;
            return;
        }

        trigger.innerHTML = `<span style="color:#C62828; font-weight:700;">${placeholder} (${checked.length})</span>`;
    }

    function renderLegacyKeteranganState(itemId, values) {
        const note = document.getElementById('ket-legacy-note-' + itemId);
        const tagsContainer = document.getElementById('ket-tags-' + itemId);

        if (!note || !tagsContainer || !Array.isArray(values) || values.length === 0) return;

        note.style.display = 'block';
        note.dataset.active = 'true';
        note.textContent = `Filter lama aktif: ${values.join(', ')}. Ubah filter keterangan untuk pindah ke mode rinci Polda/Polres.`;
        tagsContainer.innerHTML = values
            .map(value => `<span class="ket-tag is-legacy">Mode lama: ${value}</span>`)
            .join('');
    }

    function clearLegacyKeteranganState(itemId) {
        const note = document.getElementById('ket-legacy-note-' + itemId);
        if (!note) return;

        note.style.display = 'none';
        note.dataset.active = 'false';
        note.textContent = '';
    }

    function updateKetSummary(itemId) {
        const card = document.getElementById('item-card-' + itemId);
        const tagsContainer = document.getElementById('ket-tags-' + itemId);
        const legacyNote = document.getElementById('ket-legacy-note-' + itemId);

        if (!card || !tagsContainer) return;

        card.querySelectorAll('.ket-dropdown').forEach(updateKetDropdownTrigger);

        Object.keys(ketScopeLabels).forEach(scope => {
            const counter = document.getElementById(`ket-scope-counter-${itemId}-${scope}`);
            if (!counter) return;

            const count = card.querySelectorAll(`input.filter-input[data-filter="keterangan_scoped"][data-scope="${scope}"]:checked`).length;
            counter.textContent = count === 0 ? '0 filter' : `${count} filter`;
            if (count > 0) {
                counter.classList.add('active');
            } else {
                counter.classList.remove('active');
            }
        });

        const checked = card.querySelectorAll('input.filter-input[data-filter="keterangan_scoped"]:checked');
        if (checked.length === 0 && legacyNote?.dataset.active === 'true') {
            return;
        }

        if (checked.length === 0) {
            tagsContainer.innerHTML = '';
            return;
        }

        tagsContainer.innerHTML = Array.from(checked).map(cb => {
            const scopeLabel = ketScopeLabels[cb.dataset.scope] || cb.dataset.scope;
            const fieldLabel = ketFieldLabels[cb.dataset.field] || cb.dataset.field;
            const encodedValue = encodeURIComponent(cb.dataset.value);

            return `
                <span class="ket-tag">
                    <span class="ket-tag-meta">${scopeLabel} - ${fieldLabel}</span>
                    ${cb.dataset.value}
                    <button
                        type="button"
                        class="ket-tag-remove"
                        data-item="${itemId}"
                        data-scope="${cb.dataset.scope}"
                        data-field="${cb.dataset.field}"
                        data-value="${encodedValue}"
                    >&times;</button>
                </span>
            `;
        }).join('');
    }

    function initializeKeteranganUi(itemId) {
        const filters = savedFilters[String(itemId)] || {};

        if (filters.keterangan_scoped) {
            Object.entries(filters.keterangan_scoped).forEach(([scope, scopeFilters]) => {
                Object.entries(scopeFilters || {}).forEach(([field, values]) => {
                    (values || []).forEach(value => {
                        const cb = findKeteranganCheckbox(String(itemId), scope, field, value);
                        if (cb) cb.checked = true;
                    });
                });
            });
            clearLegacyKeteranganState(itemId);
        } else if (filters.keterangan && Array.isArray(filters.keterangan)) {
            renderLegacyKeteranganState(itemId, filters.keterangan);
        } else {
            clearLegacyKeteranganState(itemId);
        }

        updateKetSummary(itemId);
    }

    function toggleKetDropdown(dropdownKey) {
        const panel = document.getElementById('ket-panel-' + dropdownKey);
        const dropdown = document.getElementById('ket-dropdown-' + dropdownKey);
        if (!panel || !dropdown) return;

        const isOpen = panel.classList.contains('open');

        document.querySelectorAll('.ket-dropdown-panel.open').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.ket-dropdown.active').forEach(d => d.classList.remove('active'));
        document.querySelectorAll('.premium-card').forEach(c => c.style.zIndex = '1');

        if (!isOpen) {
            panel.classList.add('open');
            dropdown.classList.add('active');
            panel.querySelector('.ket-search-input')?.focus();

            const card = dropdown.closest('.premium-card');
            if (card) card.style.zIndex = '50';
        }
    }

    function filterKetOptions(dropdownKey, query) {
        const panel = document.getElementById('ket-panel-' + dropdownKey);
        if (!panel) return;

        const options = panel.querySelectorAll('.ket-option');
        const q = query.toLowerCase().trim();

        options.forEach(opt => {
            const label = opt.dataset.label || '';
            opt.style.display = label.includes(q) ? 'flex' : 'none';
        });
    }

    function clearKetField(itemId, dropdownKey) {
        const panel = document.getElementById('ket-panel-' + dropdownKey);
        if (!panel) return;

        panel.querySelectorAll('input.filter-input[data-filter="keterangan_scoped"]:checked').forEach(cb => {
            cb.checked = false;
        });

        keteranganTouchedItems.add(String(itemId));
        clearLegacyKeteranganState(itemId);
        updateKetSummary(itemId);
        scheduleRecipientSave(itemId);
    }

    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.ket-tag-remove');
        if (removeBtn) {
            const itemId = removeBtn.dataset.item;
            const scope = removeBtn.dataset.scope;
            const field = removeBtn.dataset.field;
            const value = decodeURIComponent(removeBtn.dataset.value || '');
            const cb = findKeteranganCheckbox(itemId, scope, field, value);

            if (cb) {
                cb.checked = false;
                keteranganTouchedItems.add(String(itemId));
                clearLegacyKeteranganState(itemId);
                updateKetSummary(itemId);
                scheduleRecipientSave(itemId);
            }

            return;
        }

        if (!e.target.closest('.ket-dropdown')) {
            document.querySelectorAll('.ket-dropdown-panel.open').forEach(p => p.classList.remove('open'));
            document.querySelectorAll('.ket-dropdown.active').forEach(d => d.classList.remove('active'));
            document.querySelectorAll('.premium-card').forEach(c => c.style.zIndex = '1');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ket-option input.filter-input[data-filter="keterangan_scoped"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const itemId = this.dataset.item;
                keteranganTouchedItems.add(String(itemId));
                clearLegacyKeteranganState(itemId);
                updateKetSummary(itemId);
            });
        });
    });
</script>
<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* ── Keterangan Dropdown ── */
    .ket-dropdown { position: relative; }
    .ket-dropdown-compact .ket-dropdown-trigger { min-height: 36px; padding: 6px 10px; }
    .ket-scope-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 12px;
    }
    .ket-scope-card {
        border: 1px dashed #CBD5E1;
        border-radius: 8px;
        background: #FDFDFD;
        padding: 10px;
        transition: border-color 0.2s;
    }
    .ket-scope-card:hover { border-color: #94A3B8; }
    .ket-scope-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .ket-scope-title {
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ket-scope-counter {
        white-space: nowrap;
        font-size: 10px;
        font-weight: 700;
        color: #64748B;
        background: #F1F5F9;
        border-radius: 10px;
        padding: 2px 6px;
        transition: all 0.2s;
    }
    .ket-scope-counter.active {
        color: #C62828;
        background: #FEF2F2;
    }
    .ket-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }
    .ket-dropdown-trigger {
        display: flex; align-items: center; justify-content: space-between;
        width: 100%; padding: 6px 10px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 6px;
        font-size: 12px; color: #475569; cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .ket-dropdown-trigger:hover { border-color: #CBD5E1; background: #F8FAFC; }
    .ket-dropdown.active .ket-dropdown-trigger { border-color: #C62828; box-shadow: 0 0 0 3px rgba(198,40,40,0.08); }
    .ket-trigger-text { display: flex; align-items: center; gap: 4px; }
    .ket-arrow { font-size: 16px; color: #94A3B8; transition: transform 0.2s; }
    .ket-dropdown.active .ket-arrow { transform: rotate(180deg); }

    .ket-dropdown-panel {
        display: none; position: absolute; top: calc(100% + 4px); left: 0; 
        width: 260px; /* Cukup lebar agar nama panjang tidak terpotong */
        background: #fff; border: 1px solid #E2E8F0; border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);
        z-index: 50; overflow: hidden;
    }
    .ket-dropdown:nth-child(even) .ket-dropdown-panel {
        left: auto;
        right: 0;
    }
    .ket-dropdown-panel.open { display: block; }

    .ket-search-wrap {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 12px; border-bottom: 1px solid #F1F5F9;
    }
    .ket-search-wrap i { color: #94A3B8; font-size: 15px; }
    .ket-search-input {
        border: none; outline: none; width: 100%;
        font-size: 12.5px; color: #334155; background: transparent;
    }

    .ket-options-list { max-height: 200px; overflow-y: auto; padding: 4px 0; }
    .ket-option {
        display: flex; align-items: center; gap: 8px;
        padding: 7px 12px; cursor: pointer; font-size: 12.5px;
        transition: background 0.15s;
    }
    .ket-option input { display: none; }
    .ket-option-check {
        width: 18px; height: 18px; border-radius: 4px;
        border: 1.5px solid #CBD5E1; display: flex; align-items: center; justify-content: center;
        font-size: 11px; color: transparent; transition: all 0.15s; flex-shrink: 0;
    }
    .ket-option-name { flex: 1; color: #334155; font-weight: 500; }
    .ket-option-count {
        font-size: 11px; color: #94A3B8; background: #F1F5F9;
        padding: 1px 6px; border-radius: 4px; font-weight: 600;
    }
    .ket-empty { padding: 16px; text-align: center; color: #94A3B8; font-size: 12px; }

    .ket-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 12px; border-top: 1px solid #F1F5F9; background: #FAFBFC;
    }
    .ket-clear-btn, .ket-apply-btn {
        padding: 4px 12px; border-radius: 6px; font-size: 11px;
        font-weight: 600; cursor: pointer; border: none; transition: all 0.15s;
    }
    .ket-clear-btn { background: #F1F5F9; color: #64748B; }
    .ket-apply-btn { background: #C62828; color: #fff; }
    .ket-apply-btn:hover { background: #B71C1C; }

    .ket-search-input::placeholder { color: #CBD5E1; }

    .ket-option:hover { background: #F8FAFC; border-color: #E2E8F0; }
    .ket-option input:checked ~ .ket-option-check { background: #C62828 !important; border-color: #C62828 !important; }
    .ket-option input:checked ~ .ket-option-check i { display: block !important; color: #fff; }
    .ket-option input:checked ~ .ket-option-name { color: #C62828 !important; font-weight: 600 !important; }
    
    .ket-clear-btn:hover { background: #E2E8F0 !important; }

    /* Selected tags */
    .ket-selected-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
    .ket-tag {
        display: inline-flex; align-items: center; gap: 4px;
        background: #FEF2F2; color: #C62828; border: 1px solid #FECACA;
        padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
    }
    .ket-tag-meta {
        color: #7F1D1D;
        opacity: 0.85;
    }
    .ket-tag.is-legacy {
        background: #EFF6FF;
        color: #1D4ED8;
        border-color: #BFDBFE;
    }
    .ket-tag button {
        background: none; border: none; color: #C62828; font-size: 14px;
        cursor: pointer; padding: 0 2px; line-height: 1; opacity: 0.6;
    }
    .ket-tag button:hover { opacity: 1; }
    .ket-legacy-note {
        margin-top: 8px;
        font-size: 11px;
        line-height: 1.5;
        color: #92400E;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 8px;
        padding: 8px 10px;
    }

    /* Toggle Auto-Saran */
    .auto-suggest-toggle {
        display: inline-flex; align-items: center; gap: 8px;
        background: #F8FAFC; border: 1px solid #E2E8F0;
        padding: 6px 14px; border-radius: 20px;
        font-size: 13px; font-weight: 600; color: #475569;
        transition: all 0.2s;
    }
    .auto-suggest-toggle.is-off {
        background: #F1F5F9; color: #94A3B8; border-color: #E2E8F0;
    }
    .toggle-label { font-size: 12px; font-weight: 600; }
    .toggle-state { font-size: 11px; font-weight: 700; color: #10B981; min-width: 48px; }
    .auto-suggest-toggle.is-off .toggle-state { color: #94A3B8; }
    .toggle-switch { position: relative; display: inline-block; width: 36px; height: 20px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-track {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: #CBD5E1; border-radius: 20px; cursor: pointer;
        transition: background 0.2s;
    }
    .toggle-switch input:checked + .toggle-track { background: #10B981; }
    .toggle-thumb {
        position: absolute; left: 3px; top: 3px;
        width: 14px; height: 14px; border-radius: 50%;
        background: #fff; transition: transform 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .toggle-switch input:checked + .toggle-track .toggle-thumb { transform: translateX(16px); }
</style>
@endsection
