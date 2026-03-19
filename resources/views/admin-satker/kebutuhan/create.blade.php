@extends('layouts.app')

@section('title', 'Buat Pengajuan Kebutuhan')
@section('breadcrumb', 'Buat Pengajuan')

@section('styles')
<style>
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
    .form-input, .form-textarea, .form-select {
        width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);
        font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); transition: all .15s;
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none; border-color: var(--brand-lighter); box-shadow: 0 0 0 3px rgba(198, 40, 40, .08);
    }
    .form-textarea { resize: vertical; min-height: 60px; }
    .item-row { display: flex; gap: 12px; align-items: flex-start; padding: 14px; background: var(--slate-50); border-radius: var(--radius-sm); margin-bottom: 8px; border: 1px solid var(--border-color); position: relative; }
    .item-row .remove-item { position: absolute; top: 8px; right: 8px; background: var(--danger-bg); color: var(--danger); border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; }
    .item-row .remove-item:hover { background: var(--danger); color: #fff; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Buat Pengajuan Kebutuhan</h1>
            <p>Ajukan kebutuhan item kapor untuk satker Anda.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Kembali</a>
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

<form method="POST" action="{{ route('admin-satker.kebutuhan.store') }}" id="kebutuhanForm">
    @csrf

    <div class="card">
        <div class="card-head"><h3>Informasi Pengajuan</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Judul Pengajuan <span style="color: var(--danger);">*</span></label>
                <input type="text" name="title" class="form-input" value="{{ old('title') }}" placeholder="Contoh: Kebutuhan Seragam Dinas Q1 2026" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tahun Anggaran</label>
                <input type="text" class="form-input" value="{{ $fiscalYear }}" disabled style="background: var(--slate-100);">
            </div>
            <div class="form-group">
                <label class="form-label">Catatan (opsional)</label>
                <textarea name="notes" class="form-textarea" placeholder="Keterangan tambahan...">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Item Kebutuhan</h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                <i class="ri-add-line"></i> Tambah Item
            </button>
        </div>
        <div class="card-body" id="itemsContainer">
            <div id="noItemsMessage" style="text-align: center; padding: 32px; color: var(--text-muted);">
                <i class="ri-shopping-bag-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                Belum ada item. Klik "Tambah Item" untuk memulai.
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 8px; justify-content: flex-end;">
        <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Simpan sebagai Draft</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    const kaporItems = @json($kaporItems);
    let itemIndex = 0;

    function addItem(preselected = null) {
        document.getElementById('noItemsMessage').style.display = 'none';

        const container = document.getElementById('itemsContainer');
        const idx = itemIndex++;

        let optionsHtml = '<option value="">-- Pilih Item --</option>';
        const groups = {};
        kaporItems.forEach(item => {
            const cat = item.category || 'Lainnya';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(item);
        });
        Object.keys(groups).forEach(cat => {
            optionsHtml += `<optgroup label="${cat.replace('_', ' ')}">`;
            groups[cat].forEach(item => {
                const selected = preselected && preselected.kapor_item_id == item.id ? 'selected' : '';
                optionsHtml += `<option value="${item.id}" ${selected}>${item.item_name}</option>`;
            });
            optionsHtml += '</optgroup>';
        });

        const qty = preselected ? preselected.quantity : 1;
        const notes = preselected ? (preselected.notes || '') : '';

        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
            <button type="button" class="remove-item" onclick="removeItem(this)" title="Hapus item"><i class="ri-close-line"></i></button>
            <div style="flex: 2; min-width: 200px;">
                <label class="form-label">Item Kapor</label>
                <select name="items[${idx}][kapor_item_id]" class="form-select" required>${optionsHtml}</select>
            </div>
            <div style="flex: 0 0 100px;">
                <label class="form-label">Jumlah</label>
                <input type="number" name="items[${idx}][quantity]" class="form-input" value="${qty}" min="1" required>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label">Catatan</label>
                <input type="text" name="items[${idx}][notes]" class="form-input" value="${notes}" placeholder="Opsional...">
            </div>
        `;
        container.appendChild(row);
    }

    function removeItem(btn) {
        btn.closest('.item-row').remove();
        const container = document.getElementById('itemsContainer');
        if (!container.querySelector('.item-row')) {
            document.getElementById('noItemsMessage').style.display = 'block';
        }
    }

    // Pre-populate from old input
    @if(old('items'))
        @foreach(old('items') as $item)
            addItem({kapor_item_id: '{{ $item['kapor_item_id'] ?? '' }}', quantity: '{{ $item['quantity'] ?? 1 }}', notes: '{{ $item['notes'] ?? '' }}'});
        @endforeach
    @endif
</script>
@endsection
