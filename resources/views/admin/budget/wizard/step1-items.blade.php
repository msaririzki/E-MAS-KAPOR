@extends('layouts.app')

@section('title', 'Pilih Barang - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">T.A. {{ $budgetPackage->budgetYear->year }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Tahap 1: Pilih Barang</span>
@endsection

@section('content')

{{-- Hero Section --}}
<div class="package-hero">
    <div class="package-hero-inner">
        <div class="package-hero-back">
            <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="btn-back">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
        <div class="package-hero-content">
            <div class="package-title-wrapper" style="justify-content: space-between; width: 100%;">
                <div>
                    <h1 class="package-title">Pilih Barang untuk {{ $budgetPackage->name }}</h1>
                    <p class="package-desc" style="margin-top: 6px;">
                        Klik item untuk menambah/menghapus dari paket. Item yang terpilih akan ditandai.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="selected-counter" id="selectedCounter">
                        <i class="ri-shopping-cart-2-line"></i>
                        <span id="selectedCount" style="font-size: 16px; font-weight: 800; margin: 0 4px;">{{ count($selectedIds) }}</span> <span style="font-weight: 500;">item dipilih</span>
                    </div>
                    <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="btn-action-primary {{ count($selectedIds) == 0 ? 'disabled' : '' }}" id="nextBtn" style="white-space: nowrap;">
                        Lanjut ke Penerima <i class="ri-arrow-right-line" style="margin-left: 6px;"></i>
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
        <div class="wizard-step-card active">
            <div class="wizard-step-header">
                <div class="wizard-step-number">1</div>
                <div class="wizard-step-title">
                    <h3>Pilih Barang</h3>
                    <p>Tentukan item kaporlap</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num" id="statSelectedCount">{{ count($selectedIds) }}</span>
                    <span class="label">Barang Terpilih</span>
                </div>
                <div class="active-indicator">Sedang Berlangsung</div>
            </div>
        </div>

        {{-- Step 2 --}}
        <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}" class="wizard-step-card pending {{ count($selectedIds) == 0 ? 'disabled-link' : '' }}" id="step2Link">
            <div class="wizard-step-header">
                <div class="wizard-step-number">2</div>
                <div class="wizard-step-title">
                    <h3>Tentukan Penerima</h3>
                    <p>Pilih satker & filter personil</p>
                </div>
            </div>
            <div class="wizard-step-body">
                <div class="stat-value">
                    <span class="num">--</span>
                    <span class="label">Satker &bull; -- Personel</span>
                </div>
                <i class="ri-lock-line wizard-step-arrow" id="step2Icon"></i>
            </div>
        </a>

        {{-- Step 3 --}}
        <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="wizard-step-card pending disabled-link">
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

{{-- Search Bar --}}
<div class="search-container" style="margin-bottom: 24px;">
    <div style="position: relative;">
        <i class="ri-search-line" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748B; font-size: 18px;"></i>
        <input type="text" id="searchInput" placeholder="Cari nama barang..." style="width: 100%; padding: 14px 16px 14px 44px; border-radius: 12px; border: 1px solid #E2E8F0; font-size: 14px; outline: none; transition: border-color 0.2s;" onkeyup="filterItems()">
    </div>
</div>

{{-- Items Grid by Category --}}
@foreach($groupedItems as $category => $items)
<div class="category-section">
    <div class="section-title-wrap highlight-title">
        <div class="title-icon">
            @if($category === 'Tutup Kepala')
                <i class="ri-graduation-cap-line"></i>
            @elseif($category === 'Tutup Badan')
                <i class="ri-t-shirt-2-line"></i>
            @else
                <i class="ri-footprint-line"></i>
            @endif
        </div>
        <h2 class="category-title">
            {{ $category }}
            <span class="category-badge">{{ $items->count() }} Barang</span>
        </h2>
    </div>
    
    <div class="items-grid">
        @foreach($items as $item)
        <div class="item-card {{ in_array($item->id, $selectedIds) ? 'selected' : '' }}"
             data-item-id="{{ $item->id }}"
             onclick="toggleItem({{ $item->id }}, this)">
            
            <div class="item-card-check">
                <i class="ri-checkbox-circle-fill"></i>
            </div>
            
            <div class="item-card-body">
                <div class="info-top">
                    @if($item->price)
                        <span class="item-price">{{ $item->formatted_price }} <span class="unit">/ {{ $item->unit ?? 'PCS' }}</span></span>
                    @else
                        <span class="item-price no-price" style="font-size: 12px;"><i class="ri-error-warning-line"></i> Belum ada harga</span>
                    @endif
                </div>
                
                <h4 class="item-name">{{ $item->item_name }}</h4>
                
                <div class="item-meta">
                    @if($item->gender_specific)
                        <span class="badge {{ $item->gender_specific === 'L' ? 'badge-primary' : 'badge-pink' }} badge-sm">
                            <i class="{{ $item->gender_specific === 'L' ? 'ri-men-line' : 'ri-women-line' }}"></i>
                            {{ $item->gender_specific === 'L' ? 'Pria' : 'Wanita' }}
                        </span>
                    @else
                        <span class="badge badge-neutral badge-sm"><i class="ri-user-line"></i> Semua Gender</span>
                    @endif
                    
                    @if($item->invoice_group)
                        <span class="badge badge-neutral badge-sm" style="background: #F1F5F9; color: #64748B;"><i class="ri-folder-2-line"></i> {{ $item->invoice_group }}</span>
                    @endif
                </div>
            </div>
            
            {{-- Loading Overlay --}}
            <div class="item-loading-overlay">
                <i class="ri-loader-4-line spinner"></i>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

<script>
    const toggleUrl = "{{ route('admin.budget.wizard.toggle-item', $budgetPackage, false) }}";
    const csrfToken = "{{ csrf_token() }}";

    async function toggleItem(itemId, el) {
        // Prevent multiple clicks while loading
        if(el.classList.contains('loading')) return;
        
        el.classList.add('loading');

        try {
            const resp = await fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ kapor_item_id: itemId })
            });

            if (!resp.ok) {
                let errorMsg = 'Server error: ' + resp.status;
                try {
                    const errData = await resp.json();
                    errorMsg = errData.message || errorMsg;
                } catch(e) {}
                throw new Error(errorMsg);
            }

            const data = await resp.json();

            // Update Class
            if (data.action === 'added') {
                el.classList.add('selected');
                // Add pop animation effect
                el.style.transform = 'scale(0.97)';
                setTimeout(() => el.style.transform = '', 150);
            } else {
                el.classList.remove('selected');
            }

            // Update Counters
            document.getElementById('selectedCount').textContent = data.count;
            document.getElementById('statSelectedCount').textContent = data.count;

            // Update Next & Step 2 Links
            const nextBtn = document.getElementById('nextBtn');
            const step2Link = document.getElementById('step2Link');
            const step2Icon = document.getElementById('step2Icon');
            
            if (data.count > 0) {
                nextBtn.classList.remove('disabled');
                step2Link.classList.remove('disabled-link');
                step2Link.classList.remove('pending');
                step2Link.style.opacity = '1';
                step2Icon.className = 'ri-arrow-right-circle-line wizard-step-arrow';
                step2Icon.style.color = '#C62828';
            } else {
                nextBtn.classList.add('disabled');
                step2Link.classList.add('disabled-link');
                step2Link.classList.add('pending');
                step2Link.style.opacity = '0.6';
                step2Icon.className = 'ri-lock-line wizard-step-arrow';
                step2Icon.style.color = '#CBD5E1';
            }
        } catch (err) {
            console.error('Toggle error:', err);
            alert('Gagal menyimpan perubahan: ' + err.message);
        } finally {
            el.classList.remove('loading');
        }
    }

    // Search Filtering Function
    function filterItems() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const cards = document.querySelectorAll('.item-card');
        const categories = document.querySelectorAll('.category-section');

        cards.forEach(card => {
            const itemName = card.querySelector('.item-name').innerText.toLowerCase();
            if (itemName.includes(filter)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });

        // Hide category header if all items inside are hidden
        categories.forEach(category => {
            const visibleCards = category.querySelectorAll('.item-card[style="display: ;"], .item-card:not([style*="display: none"])');
            if (visibleCards.length === 0) {
                category.style.display = "none";
            } else {
                category.style.display = "";
            }
        });
    }
</script>
@endsection

@section('styles')
<style>
    /* ── Utilities ── */
    .text-brand { color: #C62828; }
    .badge { display: inline-flex; align-items: center; gap: 4px; font-weight: 600; border-radius: 6px; }
    .badge-sm { padding: 4px 8px; font-size: 11px; }
    .badge-primary { background: #EFF6FF; color: #1D4ED8; }
    .badge-pink { background: #FDF2F8; color: #BE185D; }
    .badge-neutral { background: #F3F4F6; color: #4B5563; }
    
    .disabled-link { pointer-events: none; }
    
    /* ── Hero Section ── (Copied from Step 2) */
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
    .package-title-wrapper { display: flex; align-items: flex-start; flex-wrap: wrap; gap: 16px; }
    .package-title { font-size: 22px; font-weight: 800; color: #0F172A; margin: 0; }
    .package-desc { color: #64748B; font-size: 14px; margin: 0; line-height: 1.5; }
    
    /* Selected Counter specific to step 1 */
    .selected-counter {
        display: flex; align-items: center;
        background: #FEF2F2; color: #C62828;
        padding: 8px 16px; border-radius: 12px;
        border: 1px solid #FECACA;
    }
    .selected-counter i { font-size: 20px; }
    
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
    .btn-action-primary.disabled {
        opacity: 0.5; pointer-events: none; background: #94A3B8; border-color: #94A3B8; box-shadow: none;
    }

    /* ── Wizard Steps ── (Copied from Step 2) */
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
    
    /* Active Step */
    .wizard-step-card.active { border-color: #C62828; box-shadow: 0 4px 12px rgba(198, 40, 40, 0.08); transform: translateY(-2px); }
    .wizard-step-card.active::after {
        content: ''; position: absolute; bottom: 0; right: 0; width: 100px; height: 100px;
        background: linear-gradient(135deg, transparent, rgba(198, 40, 40, 0.05));
        border-radius: 100%; transform: translate(10%, 10%);
    }
    .wizard-step-card.active .wizard-step-number { background: #C62828; color: #fff; border-color: #C62828; }
    
    /* Pending Step */
    .wizard-step-card.pending { opacity: 0.6; background: #F8FAFC; border-style: dashed; }
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

    /* ── Category Section ── */
    .category-section { margin-bottom: 32px; }
    
    .highlight-title { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #F1F5F9; }
    .title-icon { width: 40px; height: 40px; border-radius: 10px; background: #FEF2F2; color: #C62828; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .category-title { font-size: 18px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 12px; }
    .category-badge { font-size: 12px; font-weight: 600; background: #F1F5F9; color: #64748B; padding: 4px 10px; border-radius: 20px; }

    /* ── Items Grid ── */
    .items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    /* ── Premium Item Cards ── */
    .item-card {
        background: #ffffff;
        border: 2px solid #E2E8F0;
        border-radius: 16px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .item-card:hover {
        border-color: #CBD5E1;
        box-shadow: 0 8px 16px -4px rgba(0,0,0,0.05);
        transform: translateY(-2px);
    }
    
    /* Selected State */
    .item-card.selected {
        border-color: #C62828;
        background: #FFFBFB;
        box-shadow: 0 8px 16px -4px rgba(198,40,40,0.1);
    }
    
    /* Loading State */
    .item-card.loading {
        pointer-events: none;
    }
    .item-card.loading .item-loading-overlay {
        opacity: 1;
        visibility: visible;
    }
    
    .item-loading-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(2px);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: #C62828;
        opacity: 0; visibility: hidden;
        transition: all 0.2s;
        z-index: 10;
    }

    .item-card-check {
        position: absolute;
        top: 16px; right: 16px;
        width: 24px; height: 24px;
        font-size: 24px; line-height: 1;
        color: #E2E8F0;
        transition: all 0.2s;
        background: #fff;
        border-radius: 50%;
    }
    .item-card.selected .item-card-check {
        color: #C62828;
        transform: scale(1.1);
    }
    .item-card:hover:not(.selected) .item-card-check {
        color: #CBD5E1;
    }

    .item-card-body { position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%; }
    
    .info-top { margin-bottom: 8px; padding-right: 30px; } /* Leave space for check icon */
    .item-price { font-size: 15px; font-weight: 700; color: #0F172A; }
    .item-price .unit { font-size: 12px; font-weight: 500; color: #64748B; }
    .item-price.no-price { color: #EF4444; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    
    .item-name { font-size: 15px; font-weight: 800; color: #1E293B; margin: 0 0 12px 0; line-height: 1.4; letter-spacing: -0.3px; }
    
    .item-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: auto; }

    /* Animations & Responsive */
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .spinner { display: inline-block; animation: spin 1s linear infinite; }

    @media (max-width: 1024px) {
        .wizard-track { grid-template-columns: repeat(3, 1fr); overflow-x: auto; padding-bottom: 8px; }
        .wizard-step-card { min-width: 220px; }
    }
    
    @media (max-width: 768px) {
        .package-title-wrapper { flex-direction: column; align-items: stretch; }
        .package-title-wrapper > div:last-child { justify-content: space-between; }
        .items-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
    }

    /* Search Input Focus */
    #searchInput:focus { border-color: #C62828; box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1); }
</style>
@endsection
