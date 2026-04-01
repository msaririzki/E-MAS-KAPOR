@extends('layouts.app')

@section('title', 'Edit Pengajuan')
@section('breadcrumb', 'Edit Pengajuan')

@section('styles')
<style>
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
    .form-input, .form-textarea {
        width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);
        font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); transition: all .15s;
    }
    .form-input:focus, .form-textarea:focus { outline: none; border-color: var(--brand-lighter); box-shadow: 0 0 0 3px rgba(198, 40, 40, .08); }
    .form-textarea { resize: vertical; min-height: 60px; }
    .category-section { margin-bottom: 24px; }
    .category-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding: 10px 16px; background: var(--slate-50); border-radius: var(--radius-sm); border: 1px solid var(--border-color); }
    .category-header i { font-size: 20px; color: var(--brand); }
    .category-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-main); }
    .category-header .count-badge { background: var(--brand-bg); color: var(--brand); font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 99px; }
    .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
    .item-card { position: relative; padding: 16px; border-radius: 12px; cursor: pointer; border: 2px solid var(--border-color); background: var(--bg-card); transition: all .2s ease; user-select: none; }
    .item-card:hover { border-color: var(--brand-lighter); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.06); }
    .item-card.selected { border-color: var(--brand); background: rgba(198, 40, 40, .04); }
    .item-card.selected::after { content: ''; position: absolute; top: 10px; right: 10px; width: 24px; height: 24px; border-radius: 50%; background: var(--brand); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: center; }
    .item-card .item-name { font-size: 13px; font-weight: 600; color: var(--text-main); line-height: 1.4; }
    .toolbar-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding: 12px 16px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-sm); flex-wrap: wrap; }
    .toolbar-bar .search-box { flex: 1; min-width: 200px; display: flex; align-items: center; gap: 8px; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0 12px; }
    .toolbar-bar .search-box i { color: var(--text-muted); font-size: 16px; }
    .toolbar-bar .search-box input { border: none; outline: none; background: transparent; font-size: 13px; padding: 8px 0; width: 100%; color: var(--text-main); font-family: inherit; }
    .selected-counter { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--brand); white-space: nowrap; }
    .selected-counter .counter-num { background: var(--brand); color: #fff; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Edit Pengajuan</h1>
            <p>{{ $kebutuhan->title }}</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin-satker.kebutuhan.show', $kebutuhan) }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Kembali</a>
        </div>
    </div>
</div>

@if($errors->any())
    <div style="background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
        <ul style="margin: 0; padding-left: 16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin-satker.kebutuhan.update', $kebutuhan) }}">
    @csrf @method('PUT')

    <div class="card">
        <div class="card-head"><h3>Informasi Pengajuan</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Tahun Anggaran <span style="color: var(--danger);">*</span></label>
                <select name="fiscal_year" class="form-input" required>
                    @php $currentYear = date('Y') + 1; @endphp
                    @for($y = $currentYear - 1; $y <= $currentYear + 2; $y++)
                        <option value="{{ $y }}" {{ old('fiscal_year', $kebutuhan->fiscal_year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">*) Judul pengajuan akan di-generate otomatis berdasarkan Tahun Anggaran terpilih.</small>
            </div>
        </div>
    </div>

    <div class="toolbar-bar">
        <div class="search-box">
            <i class="ri-search-line"></i>
            <input type="text" id="searchInput" placeholder="Cari nama barang...">
        </div>
        <div class="selected-counter">
            <div class="counter-num" id="counterNum">0</div>
            <span>Barang terpilih</span>
        </div>
    </div>

    <div id="itemsContainer">
        @php
            $categoryIcons = ['Tutup_Kepala' => 'ri-shirt-line', 'Tutup_Badan' => 'ri-t-shirt-line', 'Tutup_Kaki' => 'ri-footprint-line'];
            $currentSelected = old('items', $selectedIds);
        @endphp

        @foreach($kaporItems as $category => $items)
        <div class="category-section" data-category="{{ $category }}">
            <div class="category-header">
                <i class="{{ $categoryIcons[$category] ?? 'ri-box-3-line' }}"></i>
                <h3>{{ str_replace('_', ' ', $category) }}</h3>
                <span class="count-badge">{{ $items->count() }} Barang</span>
            </div>
            <div class="items-grid">
                @foreach($items as $item)
                <div class="item-card {{ in_array($item->id, $currentSelected) ? 'selected' : '' }}"
                     data-id="{{ $item->id }}" data-name="{{ strtolower($item->item_name) }}" onclick="toggleItem(this)">
                    <div class="item-name">{{ $item->item_name }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div id="hiddenInputs">
        @foreach($currentSelected as $itemId)
            <input type="hidden" name="items[]" value="{{ $itemId }}">
        @endforeach
    </div>

    <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
        <a href="{{ route('admin-satker.kebutuhan.show', $kebutuhan) }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    function toggleItem(card) {
        card.classList.toggle('selected');
        rebuildHiddenInputs();
    }
    function rebuildHiddenInputs() {
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        const selected = document.querySelectorAll('.item-card.selected');
        selected.forEach(card => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'items[]'; input.value = card.dataset.id;
            container.appendChild(input);
        });
        document.getElementById('counterNum').textContent = selected.length;
    }
    document.getElementById('searchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.item-card').forEach(card => {
            card.style.display = card.dataset.name.includes(query) ? '' : 'none';
        });
        document.querySelectorAll('.category-section').forEach(section => {
            section.style.display = section.querySelectorAll('.item-card:not([style*="display: none"])').length > 0 ? '' : 'none';
        });
    });
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('counterNum').textContent = document.querySelectorAll('.item-card.selected').length;
    });
</script>
@endsection
