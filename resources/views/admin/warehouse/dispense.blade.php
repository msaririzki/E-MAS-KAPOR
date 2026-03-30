@extends('layouts.app')

@section('title', 'Pengeluaran Barang')
@section('breadcrumb', 'Pengeluaran Barang')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .dispense-page { max-width: 860px; margin: 0 auto; }
    .dispense-card {
        background: var(--bg-card, #fff);
        border-radius: 16px;
        border: 1px solid var(--border-color, #E5E7EB);
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .dispense-card-header {
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%);
        padding: 24px 28px;
        color: white;
    }
    .dispense-card-header h2 { font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
    .dispense-card-header p { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }
    .dispense-card-body { padding: 28px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.5px; color: var(--text-muted, #6B7280); margin-bottom: 6px;
    }
    .form-group label .req { color: #EF4444; }
    .f-input {
        width: 100%; padding: 10px 14px; border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 8px; font-size: 14px; font-family: inherit;
        background: var(--bg-card, #fff); color: var(--text-main, #1F2937);
        transition: border-color 0.2s, box-shadow 0.2s; outline: none; box-sizing: border-box;
    }
    .f-input:focus { border-color: #D97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }
    .f-input::placeholder { color: #9CA3AF; }

    /* Native select styled */
    .f-select {
        width: 100%; padding: 10px 14px; border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 8px; font-size: 14px; font-family: inherit;
        background: var(--bg-card, #fff); color: var(--text-main, #1F2937);
        transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        cursor: pointer; appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }
    .f-select:focus { border-color: #D97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }

    /* Searchable select */
    .ss-wrap { position: relative; }
    .ss-trigger {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; border: 1px solid var(--border-color, #D1D5DB); border-radius: 8px;
        background: var(--bg-card, #fff); font-size: 14px; color: var(--text-main, #1F2937);
        cursor: pointer; transition: border-color 0.2s, box-shadow 0.2s; min-height: 42px;
    }
    .ss-trigger:hover { border-color: #9CA3AF; }
    .ss-trigger.open { border-color: #D97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }
    .ss-trigger .ss-arrow { color: #6B7280; font-size: 18px; transition: transform 0.2s; flex-shrink: 0; }
    .ss-trigger.open .ss-arrow { transform: rotate(180deg); color: #D97706; }
    .ss-trigger .ss-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ss-trigger .ss-placeholder { color: #9CA3AF; }
    .ss-dropdown {
        display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.12); z-index: 100;
        overflow: hidden;
    }
    .ss-dropdown.open { display: block; }
    .ss-search { padding: 10px; border-bottom: 1px solid var(--border-color, #F3F4F6); }
    .ss-search input {
        width: 100%; padding: 8px 12px; border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 6px; font-size: 13px; outline: none; background: var(--bg-body, #F9FAFB);
        color: var(--text-main, #1F2937); font-family: inherit; box-sizing: border-box;
    }
    .ss-search input:focus { border-color: #D97706; background: var(--bg-card, #fff); }
    .ss-list { max-height: 220px; overflow-y: auto; padding: 4px; }
    .ss-list::-webkit-scrollbar { width: 4px; }
    .ss-list::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 10px; }
    .ss-opt {
        padding: 9px 12px; cursor: pointer; font-size: 13px; color: #4B5563;
        border-radius: 6px; margin-bottom: 2px; transition: background 0.1s;
        display: flex; align-items: center; justify-content: space-between;
    }
    .ss-opt:hover { background: #FEF3C7; color: #92400E; }
    .ss-opt.selected { background: #FEF3C7; color: #92400E; font-weight: 600; }
    .ss-opt .ss-badge {
        font-size: 11px; background: #F3F4F6; color: #6B7280;
        padding: 2px 8px; border-radius: 10px; font-weight: 500;
    }
    .ss-opt.selected .ss-badge { background: #FDE68A; color: #92400E; }

    .section-divider { border: none; border-top: 1px dashed var(--border-color, #E5E7EB); margin: 8px 0 20px; }
    .section-label {
        font-size: 13px; font-weight: 700; color: var(--text-main, #1F2937);
        margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    }
    .section-label i { font-size: 16px; color: #D97706; }

    /* Item rows */
    .item-rows { display: flex; flex-direction: column; gap: 16px; }
    .item-row {
        background: var(--bg-body, #F9FAFB); border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 12px; padding: 16px 20px; position: relative;
    }
    .item-row-header {
        display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
    }
    .item-row-number {
        font-size: 12px; font-weight: 700; color: white; background: #D97706;
        width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }
    .btn-remove-item {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #FECACA;
        background: #FEF2F2; color: #DC2626; cursor: pointer; display: flex;
        align-items: center; justify-content: center; font-size: 16px; transition: all 0.15s;
    }
    .btn-remove-item:hover { background: #FEE2E2; border-color: #F87171; }
    .item-row-fields { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; }
    .stock-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px;
        border-radius: 20px; font-size: 11px; font-weight: 600; background: #FEF3C7; color: #92400E; margin-top: 4px;
    }
    .stock-warning { color: #DC2626; font-size: 12px; margin-top: 4px; font-weight: 600; display: none; }
    .btn-add-item {
        width: 100%; padding: 12px; border: 2px dashed #D97706; border-radius: 12px;
        background: transparent; color: #D97706; font-size: 14px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: all 0.2s; font-family: inherit;
    }
    .btn-add-item:hover { background: #FFFBEB; border-color: #B45309; }

    .submit-section {
        padding: 20px 28px; background: var(--bg-body, #F9FAFB);
        border-top: 1px solid var(--border-color, #E5E7EB);
        display: flex; justify-content: space-between; align-items: center;
    }
    .btn-submit {
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%); color: white;
        padding: 12px 32px; border: none; border-radius: 10px; font-size: 14px;
        font-weight: 700; cursor: pointer; display: inline-flex; align-items: center;
        gap: 8px; transition: all 0.2s; font-family: inherit;
    }
    .btn-submit:hover { box-shadow: 0 4px 12px rgba(217,119,6,0.3); transform: translateY(-1px); }
    .btn-back-link {
        display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted, #6B7280);
        text-decoration: none; font-size: 13px; font-weight: 600;
    }
    .btn-back-link:hover { color: var(--text-main, #1F2937); }

    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .item-row-fields { grid-template-columns: 1fr; }
        .dispense-card-body { padding: 20px; }
        .submit-section { flex-direction: column; gap: 12px; }
    }
</style>

<div class="dispense-page">
    @if(session('error'))
        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #991B1B; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <i class="ri-error-warning-fill" style="font-size: 18px; color: #EF4444;"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="dispense-card">
        <div class="dispense-card-header">
            <h2><i class="ri-send-plane-line"></i> Pengeluaran Barang</h2>
            <p>Form pengeluaran barang gudang — data tersimpan di Laporan Pengeluaran</p>
        </div>

        <form action="{{ route('admin.warehouse-items.dispense') }}" method="POST" id="dispenseForm">
            @csrf
            <div class="dispense-card-body">
                {{-- Section: Info Surat (Disembunyikan) --}}
                <div style="display: none;">
                    <div class="section-label"><i class="ri-file-text-line"></i> Informasi Surat</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nomor Surat <span class="req">*</span></label>
                            <input type="text" name="letter_number" class="f-input" placeholder="Contoh: B/123/III/2026" value="-">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Surat <span class="req">*</span></label>
                            <input type="date" name="letter_date" class="f-input" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <hr class="section-divider">
                </div>

                {{-- Section: Penerima --}}
                <div class="section-label"><i class="ri-user-received-line"></i> Informasi Penerima</div>

                <div class="form-group">
                    <label>Satker Penerima <span class="req">*</span></label>
                    <div class="ss-wrap" id="satkerSelect">
                        <div class="ss-trigger" onclick="ssToggle('satkerSelect')">
                            <span class="ss-text ss-placeholder" id="satker_text">-- Pilih Satker --</span>
                            <i class="ri-arrow-down-s-line ss-arrow"></i>
                        </div>
                        <div class="ss-dropdown" id="satkerSelect_dd">
                            <div class="ss-search">
                                <input type="text" placeholder="Cari satker..." oninput="ssFilter(this, 'satkerSelect')">
                            </div>
                            <div class="ss-list">
                                @foreach($satkers as $satker)
                                    <div class="ss-opt" data-val="{{ $satker->id }}" data-label="{{ $satker->name }}" onclick="ssSelect('satkerSelect', '{{ $satker->id }}', '{{ $satker->name }}')">
                                        {{ $satker->name }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="satker_id" id="satkerSelect_val" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Penerima <span class="req">*</span></label>
                        <input type="text" name="recipient_name" class="f-input" placeholder="Nama penerima barang" required value="{{ old('recipient_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Pengeluaran <span class="req">*</span></label>
                        <input type="date" name="outflow_date" class="f-input" required value="{{ old('outflow_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <hr class="section-divider">

                {{-- Section: Barang (Multi) --}}
                <div class="section-label"><i class="ri-archive-line"></i> Detail Barang</div>

                <div class="item-rows" id="itemRowsContainer">
                    {{-- Row 1 (default) --}}
                    <div class="item-row" data-index="0">
                        <div class="item-row-header">
                            <div class="item-row-number">1</div>
                        </div>
                        <div class="item-row-fields">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Nama Barang <span class="req">*</span></label>
                                <select class="f-select item-select" onchange="loadSizes(this, 0)" data-index="0">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-stock="{{ $item->sizes_sum_stock ?? 0 }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Ukuran <span class="req">*</span></label>
                                <select class="f-select size-select" name="items[0][warehouse_item_size_id]" data-index="0" disabled>
                                    <option value="">-- Pilih Barang Dulu --</option>
                                </select>
                                <div class="stock-badge" id="stockBadge_0" style="display:none;">
                                    <i class="ri-information-line"></i> Stok: <span id="stockVal_0">0</span>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Jumlah <span class="req">*</span></label>
                                <input type="number" name="items[0][quantity]" class="f-input qty-input" placeholder="0" min="1" required oninput="checkStock(0)" data-index="0">
                                <p class="stock-warning" id="stockWarn_0"><i class="ri-error-warning-line"></i> Melebihi stok!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-add-item" onclick="addItemRow()" style="margin-top: 16px;">
                    <i class="ri-add-circle-line"></i> Tambah Barang Lain
                </button>
            </div>

            <div class="submit-section">
                <a href="{{ route('admin.warehouse-items.index') }}" class="btn-back-link">
                    <i class="ri-arrow-left-line"></i> Kembali ke Data Gudang
                </a>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="ri-save-line"></i> Simpan Pengeluaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Items data for dynamic use
    const itemsData = @json($items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'stock' => $i->sizes_sum_stock ?? 0]));
    let rowCount = 1;
    let stockLimits = {};

    // ── Searchable Select ──
    function ssToggle(id) {
        const wrap = document.getElementById(id);
        const dd = document.getElementById(id + '_dd');
        const trigger = wrap.querySelector('.ss-trigger');
        const isOpen = dd.classList.contains('open');
        // Close all others
        document.querySelectorAll('.ss-dropdown.open').forEach(d => { d.classList.remove('open'); d.closest('.ss-wrap').querySelector('.ss-trigger').classList.remove('open'); });
        if (!isOpen) {
            dd.classList.add('open');
            trigger.classList.add('open');
            const si = dd.querySelector('.ss-search input');
            if (si) { si.value = ''; si.focus(); ssFilter(si, id); }
        }
    }
    function ssFilter(input, id) {
        const val = input.value.toLowerCase();
        const opts = document.getElementById(id + '_dd').querySelectorAll('.ss-opt');
        opts.forEach(o => { o.style.display = (o.dataset.label || o.textContent).toLowerCase().includes(val) ? '' : 'none'; });
    }
    function ssSelect(id, value, label) {
        document.getElementById(id + '_val').value = value;
        const textEl = document.getElementById(id.replace('Select', '') + '_text');
        textEl.textContent = label;
        textEl.classList.remove('ss-placeholder');
        // Mark selected
        document.getElementById(id + '_dd').querySelectorAll('.ss-opt').forEach(o => o.classList.toggle('selected', o.dataset.val === value));
        // Close
        document.getElementById(id + '_dd').classList.remove('open');
        document.getElementById(id).querySelector('.ss-trigger').classList.remove('open');
    }
    document.addEventListener('click', e => {
        if (!e.target.closest('.ss-wrap')) {
            document.querySelectorAll('.ss-dropdown.open').forEach(d => { d.classList.remove('open'); d.closest('.ss-wrap').querySelector('.ss-trigger').classList.remove('open'); });
        }
    });

    // ── Load sizes via AJAX ──
    function loadSizes(selectEl, index) {
        const itemId = selectEl.value;
        const sizeSelect = document.querySelector(`.size-select[data-index="${index}"]`);
        const stockBadge = document.getElementById('stockBadge_' + index);
        const stockWarn = document.getElementById('stockWarn_' + index);

        sizeSelect.innerHTML = '<option value="">Memuat...</option>';
        sizeSelect.disabled = true;
        if (stockBadge) stockBadge.style.display = 'none';
        if (stockWarn) stockWarn.style.display = 'none';
        stockLimits[index] = 0;

        if (!itemId) {
            sizeSelect.innerHTML = '<option value="">-- Pilih Barang Dulu --</option>';
            return;
        }

        fetch(`{{ url('admin/warehouse-items/api/item-sizes') }}/${itemId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(sizes => {
            if (!sizes.length) {
                sizeSelect.innerHTML = '<option value="">Tidak ada stok</option>';
                return;
            }
            let html = '<option value="">-- Pilih Ukuran --</option>';
            sizes.forEach(s => { html += `<option value="${s.id}" data-stock="${s.stock}">${s.size_label} (stok: ${s.stock})</option>`; });
            sizeSelect.innerHTML = html;
            sizeSelect.disabled = false;
            sizeSelect.onchange = function() {
                const opt = this.options[this.selectedIndex];
                const stock = parseInt(opt.dataset.stock || 0);
                stockLimits[index] = stock;
                document.getElementById('stockVal_' + index).textContent = stock;
                document.getElementById('stockBadge_' + index).style.display = stock > 0 ? 'inline-flex' : 'none';
                checkStock(index);
            };
        })
        .catch(() => { sizeSelect.innerHTML = '<option value="">Gagal memuat</option>'; });
    }

    function checkStock(index) {
        const qtyInput = document.querySelector(`.qty-input[data-index="${index}"]`);
        const qty = parseInt(qtyInput.value) || 0;
        const max = stockLimits[index] || 0;
        const warn = document.getElementById('stockWarn_' + index);
        if (max > 0 && qty > max) { warn.style.display = 'block'; } else { warn.style.display = 'none'; }
    }

    // ── Add/Remove item rows ──
    function addItemRow() {
        const idx = rowCount;
        rowCount++;
        const container = document.getElementById('itemRowsContainer');
        const div = document.createElement('div');
        div.className = 'item-row';
        div.dataset.index = idx;

        let itemOptions = '<option value="">-- Pilih Barang --</option>';
        itemsData.forEach(i => { itemOptions += `<option value="${i.id}" data-stock="${i.stock}">${i.name}</option>`; });

        div.innerHTML = `
            <div class="item-row-header">
                <div class="item-row-number">${idx + 1}</div>
                <button type="button" class="btn-remove-item" onclick="removeItemRow(this)" title="Hapus baris">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="item-row-fields">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Nama Barang <span class="req">*</span></label>
                    <select class="f-select item-select" onchange="loadSizes(this, ${idx})" data-index="${idx}">
                        ${itemOptions}
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Ukuran <span class="req">*</span></label>
                    <select class="f-select size-select" name="items[${idx}][warehouse_item_size_id]" data-index="${idx}" disabled>
                        <option value="">-- Pilih Barang Dulu --</option>
                    </select>
                    <div class="stock-badge" id="stockBadge_${idx}" style="display:none;">
                        <i class="ri-information-line"></i> Stok: <span id="stockVal_${idx}">0</span>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Jumlah <span class="req">*</span></label>
                    <input type="number" name="items[${idx}][quantity]" class="f-input qty-input" placeholder="0" min="1" required oninput="checkStock(${idx})" data-index="${idx}">
                    <p class="stock-warning" id="stockWarn_${idx}"><i class="ri-error-warning-line"></i> Melebihi stok!</p>
                </div>
            </div>`;
        container.appendChild(div);
        renumberRows();
    }

    function removeItemRow(btn) {
        const row = btn.closest('.item-row');
        row.remove();
        renumberRows();
    }

    function renumberRows() {
        document.querySelectorAll('.item-row').forEach((row, i) => {
            row.querySelector('.item-row-number').textContent = i + 1;
        });
    }

    // ── Form submit validation ──
    document.getElementById('dispenseForm').addEventListener('submit', function(e) {
        const satker = document.getElementById('satkerSelect_val').value;
        if (!satker) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Pilih Satker', text: 'Harap pilih satker penerima.' }); return; }

        const sizeSelects = document.querySelectorAll('.size-select');
        for (const s of sizeSelects) {
            if (!s.value) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: 'Harap pilih barang & ukuran untuk semua baris.' }); return; }
        }

        // Check stock warnings
        const warnings = document.querySelectorAll('.stock-warning');
        for (const w of warnings) {
            if (w.style.display === 'block') { e.preventDefault(); Swal.fire({ icon: 'error', title: 'Stok Tidak Cukup', text: 'Ada barang yang jumlahnya melebihi stok.' }); return; }
        }
    });
</script>
@endsection
