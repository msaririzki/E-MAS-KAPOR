@extends('layouts.app')

@section('title', 'Buat Pengajuan Kebutuhan')
@section('breadcrumb', 'Buat Pengajuan')



@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Buat Pengajuan Kebutuhan</h1>
            <p>Pilih item kapor yang dibutuhkan oleh satker Anda.</p>
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
                <label class="form-label">Tahun Anggaran <span style="color: var(--danger);">*</span></label>
                @php $nextYear = (int) date('Y') + 1; @endphp
                <input type="hidden" name="fiscal_year" value="{{ $nextYear }}">
                <div class="form-input" style="background: var(--slate-50); cursor: default; font-weight: 600;">
                    {{ $nextYear }}
                </div>
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">*) Tahun anggaran otomatis ditetapkan ke tahun depan ({{ date('Y') }} + 1). Judul pengajuan akan di-generate otomatis.</small>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
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

    {{-- Item Cards by Category --}}
    <div id="itemsContainer">
        @php
            $categoryIcons = [
                'Tutup_Kepala' => 'ri-shirt-line',
                'Tutup_Badan' => 'ri-t-shirt-line',
                'Tutup_Kaki' => 'ri-footprint-line',
            ];
            $oldItems = old('items', []);
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
                <div class="item-card {{ in_array($item->id, $oldItems) ? 'selected' : '' }}"
                     data-id="{{ $item->id }}"
                     data-name="{{ strtolower($item->item_name) }}"
                     onclick="toggleItem(this)">
                    <div class="item-name">{{ $item->item_name }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- Hidden inputs container --}}
    <div id="hiddenInputs">
        @foreach($oldItems as $itemId)
            <input type="hidden" name="items[]" value="{{ $itemId }}">
        @endforeach
    </div>

    <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px;">
        <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-ghost">Batal</a>
        <button type="button" class="btn btn-primary" onclick="openPreview()"><i class="ri-eye-line"></i> Preview & Kirim</button>
    </div>
</form>

{{-- PREVIEW MODAL --}}
<div class="preview-overlay" id="previewModal">
    <div class="preview-modal">
        <div class="preview-header">
            <div class="preview-header-icon">
                <i class="ri-file-search-line"></i>
            </div>
            <div>
                <h3>Preview Pengajuan Kebutuhan</h3>
                <p>Periksa kembali data pengajuan Anda sebelum mengirim.</p>
            </div>
        </div>
        <div class="preview-body" id="previewBody">
            {{-- Filled dynamically by JS --}}
        </div>
        <div class="preview-footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="closePreview()"><i class="ri-arrow-left-line"></i> Kembali Edit</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnConfirmSubmit" onclick="confirmSubmit()">
                <i class="ri-send-plane-fill"></i> Konfirmasi & Kirim
            </button>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
    .form-input, .form-textarea {
        width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);
        font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); transition: all .15s;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none; border-color: var(--brand-lighter); box-shadow: 0 0 0 3px rgba(198, 40, 40, .08);
    }
    .form-textarea { resize: vertical; min-height: 60px; }

    /* ── Card Grid ── */
    .category-section { margin-bottom: 24px; }
    .category-header {
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
        padding: 10px 16px; background: var(--slate-50); border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
    }
    .category-header i { font-size: 20px; color: var(--brand); }
    .category-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-main); }
    .category-header .count-badge {
        background: var(--brand-bg); color: var(--brand); font-size: 11px; font-weight: 700;
        padding: 2px 10px; border-radius: 99px;
    }

    .items-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;
    }

    .item-card {
        position: relative; padding: 16px; border-radius: 12px; cursor: pointer;
        border: 2px solid var(--border-color); background: var(--bg-card);
        transition: all .2s ease; user-select: none;
    }
    .item-card:hover { border-color: var(--brand-lighter); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.06); }
    .item-card.selected { border-color: var(--brand); background: rgba(198, 40, 40, .04); }
    .item-card.selected::after {
        content: ''; position: absolute; top: 10px; right: 10px;
        width: 24px; height: 24px; border-radius: 50%; background: var(--brand);
        display: flex; align-items: center; justify-content: center;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: center;
    }
    .item-card .item-name { font-size: 13px; font-weight: 600; color: var(--text-main); line-height: 1.4; }

    /* ── Search & Counter ── */
    .toolbar-bar {
        display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
        padding: 12px 16px; background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius-sm); flex-wrap: wrap;
    }
    .toolbar-bar .search-box {
        flex: 1; min-width: 200px; display: flex; align-items: center; gap: 8px;
        background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-sm);
        padding: 0 12px;
    }
    .toolbar-bar .search-box i { color: var(--text-muted); font-size: 16px; }
    .toolbar-bar .search-box input {
        border: none; outline: none; background: transparent; font-size: 13px; padding: 8px 0;
        width: 100%; color: var(--text-main); font-family: inherit;
    }
    .selected-counter {
        display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700;
        color: var(--brand); white-space: nowrap;
    }
    .selected-counter .counter-num {
        background: var(--brand); color: #fff; width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800;
    }

    /* ── Preview Modal ── */
    .preview-overlay {
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.6); backdrop-filter: blur(4px);
        align-items: center; justify-content: center;
        animation: pvFadeIn .2s ease;
    }
    .preview-overlay.active { display: flex; }
    .preview-modal {
        background: #ffffff; border-radius: 16px; width: 100%; max-width: 640px;
        max-height: 85vh; display: flex; flex-direction: column;
        box-shadow: 0 24px 80px rgba(0,0,0,.4); overflow: hidden;
        animation: pvSlideUp .25s ease;
    }
    .preview-header {
        padding: 20px 24px 16px; border-bottom: 1px solid #e2e8f0;
        display: flex; align-items: center; gap: 12px;
    }
    .preview-header-icon {
        width: 44px; height: 44px; border-radius: 50%; background: #fef3c7; color: #d97706;
        display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
    }
    .preview-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .preview-header p { margin: 2px 0 0; font-size: 12px; color: #64748b; }
    .preview-body {
        padding: 16px 24px; overflow-y: auto; flex: 1;
    }
    .preview-info-row {
        display: flex; align-items: center; gap: 10px; padding: 10px 14px;
        background: #f1f5f9; border-radius: 8px; margin-bottom: 16px;
    }
    .preview-info-row i { font-size: 18px; color: #475569; }
    .preview-info-row .info-label { font-size: 12px; color: #64748b; }
    .preview-info-row .info-value { font-size: 14px; font-weight: 700; color: #1e293b; }
    .preview-category { margin-bottom: 14px; }
    .preview-category-title {
        font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;
        letter-spacing: .5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;
    }
    .preview-category-title i { font-size: 16px; color: var(--brand); }
    .preview-item-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .preview-item-tag {
        padding: 5px 12px; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 6px; font-size: 12px; font-weight: 500; color: #334155;
    }
    .preview-footer {
        padding: 14px 24px; border-top: 1px solid #e2e8f0;
        display: flex; gap: 10px; justify-content: flex-end; background: #f8fafc;
    }
    .preview-footer .btn { min-width: 130px; justify-content: center; font-weight: 600; }
    .preview-warning {
        display: flex; align-items: flex-start; gap: 8px; padding: 10px 14px;
        background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px;
        margin-top: 12px; font-size: 12px; color: #92400e; line-height: 1.5;
    }
    .preview-warning i { font-size: 16px; margin-top: 1px; flex-shrink: 0; }
    @keyframes pvFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes pvSlideUp { from { opacity: 0; transform: translateY(20px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>
@endsection

@section('scripts')
<script>
    const categoryIcons = {
        'Tutup_Kepala': 'ri-shirt-line',
        'Tutup_Badan': 'ri-t-shirt-line',
        'Tutup_Kaki': 'ri-footprint-line',
    };

    function toggleItem(card) {
        const id = card.dataset.id;
        card.classList.toggle('selected');
        rebuildHiddenInputs();
    }

    function rebuildHiddenInputs() {
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        const selected = document.querySelectorAll('.item-card.selected');
        selected.forEach(card => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = card.dataset.id;
            container.appendChild(input);
        });
        document.getElementById('counterNum').textContent = selected.length;
    }

    // Search
    document.getElementById('searchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.item-card').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(query) ? '' : 'none';
        });
        document.querySelectorAll('.category-section').forEach(section => {
            const visibleCards = section.querySelectorAll('.item-card:not([style*="display: none"])');
            section.style.display = visibleCards.length > 0 ? '' : 'none';
        });
    });

    // Init counter
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('counterNum').textContent = document.querySelectorAll('.item-card.selected').length;
    });

    // ── Preview Modal ──
    function openPreview() {
        const selected = document.querySelectorAll('.item-card.selected');
        if (selected.length === 0) {
            alert('Minimal pilih 1 item kebutuhan sebelum mengirim.');
            return;
        }

        // Group selected items by category
        const grouped = {};
        selected.forEach(card => {
            const section = card.closest('.category-section');
            const category = section ? section.dataset.category : 'Lainnya';
            if (!grouped[category]) grouped[category] = [];
            grouped[category].push(card.querySelector('.item-name').textContent.trim());
        });

        const fiscalYear = document.querySelector('input[name="fiscal_year"]').value;

        // Build preview HTML
        let html = '';

        // Info row
        html += '<div class="preview-info-row">';
        html += '  <i class="ri-calendar-line"></i>';
        html += '  <div>';
        html += '    <div class="info-label">Tahun Anggaran</div>';
        html += '    <div class="info-value">TA ' + fiscalYear + '</div>';
        html += '  </div>';
        html += '</div>';

        html += '<div class="preview-info-row" style="margin-bottom: 20px;">';
        html += '  <i class="ri-shopping-bag-line"></i>';
        html += '  <div>';
        html += '    <div class="info-label">Total Item Dipilih</div>';
        html += '    <div class="info-value">' + selected.length + ' Barang</div>';
        html += '  </div>';
        html += '</div>';

        // Items by category
        for (const [cat, items] of Object.entries(grouped)) {
            const icon = categoryIcons[cat] || 'ri-box-3-line';
            const catName = cat.replace(/_/g, ' ');
            html += '<div class="preview-category">';
            html += '  <div class="preview-category-title"><i class="' + icon + '"></i> ' + catName + ' (' + items.length + ')</div>';
            html += '  <div class="preview-item-list">';
            items.forEach(name => {
                html += '<span class="preview-item-tag">' + name + '</span>';
            });
            html += '  </div>';
            html += '</div>';
        }

        // Warning
        html += '<div class="preview-warning">';
        html += '  <i class="ri-alert-line"></i>';
        html += '  <div><strong>Perhatian:</strong> Pengajuan hanya dapat dilakukan <strong>1 kali</strong> per tahun anggaran. Pastikan semua item yang dipilih sudah benar sebelum mengirim.</div>';
        html += '</div>';

        document.getElementById('previewBody').innerHTML = html;
        document.getElementById('previewModal').classList.add('active');
    }

    function closePreview() {
        document.getElementById('previewModal').classList.remove('active');
    }

    function confirmSubmit() {
        document.getElementById('btnConfirmSubmit').disabled = true;
        document.getElementById('btnConfirmSubmit').innerHTML = '<i class="ri-loader-4-line"></i> Mengirim...';
        document.getElementById('kebutuhanForm').submit();
    }

    // Close modal on overlay click or Escape
    document.getElementById('previewModal').addEventListener('click', function(e) {
        if (e.target === this) closePreview();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePreview();
    });
</script>
@endsection
