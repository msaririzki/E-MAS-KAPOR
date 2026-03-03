@extends('layouts.app')

@section('title', 'Pilih Barang - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">T.A. {{ $budgetPackage->budgetYear->year }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Pilih Barang</span>
@endsection

@section('content')
{{-- Wizard Steps Bar --}}
<div class="wizard-bar">
    <div class="wizard-step active"><span class="step-num">1</span> Pilih Barang</div>
    <div class="wizard-line"></div>
    <div class="wizard-step"><span class="step-num">2</span> Pilih Penerima</div>
    <div class="wizard-line"></div>
    <div class="wizard-step"><span class="step-num">3</span> Preview</div>
</div>

<div class="page-header" style="margin-top: 20px;">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Pilih Barang untuk {{ $budgetPackage->name }}</h1>
            <p style="color: #6B7280; font-size: 13px;">Klik item untuk menambah/menghapus dari paket. Item yang terpilih akan ditandai.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="selected-counter" id="selectedCounter">
                <i class="ri-shopping-cart-2-line"></i>
                <span id="selectedCount">{{ count($selectedIds) }}</span> item dipilih
            </div>
            <a href="{{ route('admin.budget.wizard.step2', $budgetPackage) }}"
               class="btn btn-primary {{ count($selectedIds) == 0 ? 'disabled' : '' }}" id="nextBtn">
                Lanjut ke Penerima <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>

{{-- Items Grid by Category --}}
@foreach($groupedItems as $category => $items)
<div class="category-section">
    <h2 class="category-title">
        @if($category === 'Tutup Kepala')
            <i class="ri-graduation-cap-line"></i>
        @elseif($category === 'Tutup Badan')
            <i class="ri-t-shirt-2-line"></i>
        @else
            <i class="ri-footprint-line"></i>
        @endif
        {{ $category }}
        <span class="category-count">{{ $items->count() }} item</span>
    </h2>
    <div class="items-grid">
        @foreach($items as $item)
        <div class="item-card {{ in_array($item->id, $selectedIds) ? 'selected' : '' }}"
             data-item-id="{{ $item->id }}"
             onclick="toggleItem({{ $item->id }}, this)">
            <div class="item-card-check">
                <i class="ri-checkbox-circle-fill"></i>
            </div>
            <div class="item-card-body">
                <h4 class="item-name">{{ $item->item_name }}</h4>
                <div class="item-meta">
                    @if($item->gender_specific)
                        <span class="item-badge gender">
                            <i class="{{ $item->gender_specific === 'L' ? 'ri-men-line' : 'ri-women-line' }}"></i>
                            {{ $item->gender_specific === 'L' ? 'Pria' : 'Wanita' }}
                        </span>
                    @else
                        <span class="item-badge unisex"><i class="ri-user-line"></i> Unisex</span>
                    @endif
                    <span class="item-badge unit">{{ $item->unit ?? 'PCS' }}</span>
                </div>
                @if($item->price)
                    <div class="item-price">{{ $item->formatted_price }}</div>
                @else
                    <div class="item-price no-price">Belum ada harga</div>
                @endif
                @if($item->invoice_group)
                    <div class="item-group">{{ $item->invoice_group }}</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

<script>
    const toggleUrl = "{{ route('admin.budget.wizard.toggle-item', $budgetPackage) }}";
    const csrfToken = "{{ csrf_token() }}";

    async function toggleItem(itemId, el) {
        el.classList.toggle('loading');

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
            const data = await resp.json();

            if (data.action === 'added') {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }

            document.getElementById('selectedCount').textContent = data.count;

            const nextBtn = document.getElementById('nextBtn');
            if (data.count > 0) {
                nextBtn.classList.remove('disabled');
            } else {
                nextBtn.classList.add('disabled');
            }
        } catch (err) {
            console.error('Error:', err);
        } finally {
            el.classList.remove('loading');
        }
    }
</script>
@endsection

@section('styles')
<style>
    .wizard-bar {
        display: flex; align-items: center; gap: 0;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;
        padding: 16px 24px;
    }
    .wizard-step {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600; color: #9CA3AF;
        white-space: nowrap;
    }
    .wizard-step.active { color: #B91C1C; }
    .wizard-step.done { color: #10B981; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800;
        background: #F3F4F6; color: #9CA3AF;
    }
    .wizard-step.active .step-num { background: #B91C1C; color: #fff; }
    .wizard-step.done .step-num { background: #10B981; color: #fff; }
    .wizard-line { flex: 1; height: 2px; background: #E5E7EB; margin: 0 12px; }

    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

    .selected-counter {
        display: flex; align-items: center; gap: 6px;
        font-size: 13px; font-weight: 600; color: #B91C1C;
        background: #FEF2F2; padding: 8px 14px; border-radius: 8px;
    }
    .selected-counter i { font-size: 16px; }

    .btn.disabled { opacity: 0.5; pointer-events: none; }

    .category-section { margin-bottom: 28px; }
    .category-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 16px; font-weight: 700; color: #111827;
        margin-bottom: 14px; padding-bottom: 8px;
        border-bottom: 2px solid #F3F4F6;
    }
    .category-title i { font-size: 20px; color: #B91C1C; }
    .category-count { font-size: 12px; font-weight: 500; color: #9CA3AF; margin-left: 4px; }

    .items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }

    .item-card {
        background: #fff; border: 2px solid #E5E7EB; border-radius: 12px;
        padding: 16px; cursor: pointer; transition: all 0.2s;
        position: relative; overflow: hidden;
    }
    .item-card:hover { border-color: #D1D5DB; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .item-card.selected { border-color: #B91C1C; background: #FFF5F5; }
    .item-card.loading { opacity: 0.6; pointer-events: none; }

    .item-card-check {
        position: absolute; top: 10px; right: 10px;
        font-size: 22px; color: #D1D5DB; transition: all 0.2s;
    }
    .item-card.selected .item-card-check { color: #B91C1C; }

    .item-name { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 8px; line-height: 1.3; }
    .item-meta { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
    .item-badge {
        font-size: 10px; font-weight: 700; padding: 2px 7px;
        border-radius: 20px; display: inline-flex; align-items: center; gap: 2px;
    }
    .item-badge.gender { background: #EFF6FF; color: #3B82F6; }
    .item-badge.unisex { background: #F3F4F6; color: #6B7280; }
    .item-badge.unit { background: #FEF3C7; color: #92400E; }

    .item-price { font-size: 14px; font-weight: 700; color: #B91C1C; }
    .item-price.no-price { font-size: 12px; color: #D1D5DB; font-weight: 500; }
    .item-group { font-size: 11px; color: #6B7280; margin-top: 4px; }

    @media (max-width: 768px) {
        .items-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .wizard-bar { overflow-x: auto; }
    }
</style>
@endsection
