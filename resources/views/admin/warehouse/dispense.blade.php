@extends('layouts.app')

@section('title', 'Pengeluaran Barang')
@section('breadcrumb', 'Pengeluaran Barang')

@section('content')



<div class="dispense-page">
    @if(session('error'))
        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #991B1B; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <i class="ri-error-warning-fill" style="font-size: 18px; color: #EF4444;"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="dispense-card">
        <div class="dispense-card-header">
            <h2><i class="ri-send-plane-line"></i> {{ auth()->user()->hasRole('admin_gudang') ? 'Pengajuan Pengeluaran Barang' : 'Pengeluaran Barang' }}</h2>
            <p>{{ auth()->user()->hasRole('admin_gudang') ? 'Form pengajuan pengeluaran barang — pengajuan akan diproses oleh Super Admin' : 'Form pengeluaran barang gudang — data tersimpan di Laporan Pengeluaran' }}</p>
        </div>

        <form action="{{ route('admin.warehouse-items.dispense') }}" method="POST" id="dispenseForm">
            @csrf
            <input type="hidden" name="dispense_method" id="dispense_method" value="method_1">

            <div class="dispense-card-body">
                <div class="method-tabs">
                    <div class="method-tab active" id="tab_method_1" onclick="switchMethod('method_1')">
                        <i class="ri-user-smile-line"></i> Metode 1: Per Satker
                    </div>
                    <div class="method-tab" id="tab_method_2" onclick="switchMethod('method_2')">
                        <i class="ri-community-line"></i> Metode 2: Per Barang (Multi Satker)
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Surat <span class="req">*</span></label>
                        <input type="date" name="letter_date" class="f-input" value="{{ old('letter_date', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label>Nomor Surat / Referensi <span class="req">*</span></label>
                        <input type="text" name="letter_number" class="f-input" placeholder="Contoh: B/123/III/2026" value="{{ old('letter_number', '-') }}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Pengeluaran <span class="req">*</span></label>
                    <input type="date" name="outflow_date" class="f-input" required value="{{ old('outflow_date', date('Y-m-d')) }}">
                </div>

                <hr class="section-divider">

                {{-- WRAPPER METHOD 1 --}}
                <div id="wrapper_method_1">
                    <div class="section-label"><i class="ri-user-received-line"></i> Satker Penerima</div>
                    <div class="form-group">
                        <div class="ss-wrap" id="satkerSelect_m1">
                            <div class="ss-trigger" onclick="ssToggle('satkerSelect_m1')">
                                <span class="ss-text ss-placeholder" id="satker_text_m1">-- Pilih Satker --</span>
                                <i class="ri-arrow-down-s-line ss-arrow"></i>
                            </div>
                            <div class="ss-dropdown" id="satkerSelect_m1_dd">
                                <div class="ss-search">
                                    <input type="text" placeholder="Cari satker..." oninput="ssFilter(this, 'satkerSelect_m1')">
                                </div>
                                <div class="ss-list">
                                    @foreach($satkers as $satker)
                                        <div class="ss-opt" data-val="{{ $satker->id }}" data-label="{{ $satker->name }}" onclick="ssSelect('satkerSelect_m1', '{{ $satker->id }}', '{{ $satker->name }}')">
                                            {{ $satker->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="satker_id" id="satkerSelect_m1_val">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Nama Penerima Pihak Satker <span class="req">*</span></label>
                        <input type="text" name="recipient_name" id="m1_recipient_name" class="f-input" placeholder="Nama penerima barang" value="{{ old('recipient_name') }}">
                    </div>

                    <div class="section-label"><i class="ri-archive-line"></i> Daftar Barang</div>
                    <div class="item-rows" id="itemRowsContainer_m1"></div>
                    <button type="button" class="btn-add-item" onclick="addItemRowM1()" style="margin-top: 16px;">
                        <i class="ri-add-circle-line"></i> Tambah Barang Lain
                    </button>
                    <p class="stock-warning" id="globalStockWarn_m1" style="margin-top:20px; text-align:center;"><i class="ri-error-warning-line"></i> Ada barang yang melebihi stok!</p>
                </div>

                {{-- WRAPPER METHOD 2 --}}
                <div id="wrapper_method_2" style="display: none;">
                    
                    <div class="section-label"><i class="ri-archive-line"></i> Daftar Barang</div>
                    <div class="m2-checkbox-list-wrapper">
                        <input type="text" class="m2-search-input" placeholder="Cari nama barang..." oninput="filterM2List(this, 'm2_item_list')">
                        <div class="m2-list-container" id="m2_item_list">
                            {{-- Dynamic contents --}}
                        </div>
                    </div>
                    
                    <hr class="section-divider" style="margin-top:30px;">
                    
                    <div class="section-label"><i class="ri-community-line"></i> Daftar Satker Penerima</div>
                    <div class="m2-checkbox-list-wrapper">
                        <div class="m2-action-btns">
                            <button type="button" class="btn-m2-small" onclick="toggleAllM2Checkboxes('m2_satker_list', true)">Pilih Semua</button>
                            <button type="button" class="btn-m2-small" onclick="toggleAllM2Checkboxes('m2_satker_list', false)">Hapus Semua</button>
                        </div>
                        <input type="text" class="m2-search-input" placeholder="Cari satuan kerja..." oninput="filterM2List(this, 'm2_satker_list')">
                        <div class="m2-list-container" id="m2_satker_list">
                            {{-- Dynamic contents --}}
                        </div>
                    </div>

                    <hr class="section-divider" style="margin-top:30px;">
                    
                    <div class="section-label"><i class="ri-equalizer-fill"></i> Mode Pembagian Barang</div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <input type="radio" name="m2_mode" id="m2_mode_acak" value="acak" checked onchange="regenerateGridM2()">
                        <label for="m2_mode_acak" style="display:inline-block; font-size:13px; font-weight:600; cursor:pointer;" title="Sistem akan otomatis memotong ukuran dengan metode FIFO (Acak) saat Anda hanya memasukkan jumlah barang.">
                            Acak (Otomatis)
                        </label>
                        &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="m2_mode" id="m2_mode_ukuran" value="ukuran" onchange="regenerateGridM2()">
                        <label for="m2_mode_ukuran" style="display:inline-block; font-size:13px; font-weight:600; cursor:pointer;" title="Isi form jumlah sesuai dengan spesifikasi ukuran yang diterima masing-masing satker.">
                            Berdasarkan Ukuran
                        </label>
                    </div>

                    <hr class="section-divider">
                    
                    <div class="section-label"><i class="ri-equalizer-line"></i> Jumlah Per Barang</div>
                    <p style="font-size: 13px; color: #6B7280; margin-bottom: 12px; margin-top:-10px;">
                        Isi angka 0 jika satker tersebut tidak menerima barang yang bersangkutan.
                    </p>
                    <div class="qty-grid-wrapper" id="m2_grid_wrapper">
                        <table class="qty-grid-table" id="m2_table">
                            <thead>
                                <tr id="m2_table_head">
                                    <th>Satker Penerima</th>
                                    <th>Pilih barang & satker di atas</th>
                                </tr>
                            </thead>
                            <tbody id="m2_table_body">
                                <tr>
                                    <td colspan="2" style="text-align:center; color:#9CA3AF; padding:20px;">Belum ada data barang dan satker yang dipilih lengkap.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="stock-warning" id="globalStockWarn_m2" style="margin-top:12px; text-align:center;"><i class="ri-error-warning-line"></i> Total kebutuhan dari satker melebihi stok gudang!</p>
                </div>

            </div>

            <div class="submit-section">
                <a href="{{ route('admin.warehouse-items.index') }}" class="btn-back-link">
                    <i class="ri-arrow-left-line"></i> Kembali ke Data Gudang
                </a>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="{{ auth()->user()->hasRole('admin_gudang') ? 'ri-send-plane-fill' : 'ri-save-line' }}"></i> {{ auth()->user()->hasRole('admin_gudang') ? 'Ajukan Pengeluaran' : 'Simpan Pengeluaran' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
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
    
    /* Tabs */
    .method-tabs { display: flex; gap: 10px; margin-bottom: 24px; }
    .method-tab {
        flex: 1; padding: 14px; text-align: center; border: 2px solid var(--border-color, #E5E7EB);
        border-radius: 12px; background: var(--bg-body, #F9FAFB); color: var(--text-muted, #6B7280);
        font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .method-tab.active { border-color: #D97706; background: #FFFBEB; color: #D97706; }

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
    .ss-wrap.disabled { opacity: 0.6; cursor: not-allowed; }
    .ss-wrap.disabled .ss-trigger { pointer-events: none; background: var(--bg-body, #F9FAFB); }
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
    .item-row-fields { display: flex; gap: 16px; align-items: flex-start; }
    .item-row-left { flex: 0 0 280px; }
    .item-row-right { flex: 1; min-width: 0; }
    .size-sub-rows { display: flex; flex-direction: column; gap: 8px; }
    .size-sub-row {
        display: grid; grid-template-columns: 1fr 100px 28px; gap: 8px; align-items: start;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 8px; padding: 8px 10px;
    }
    .btn-remove-size {
        width: 28px; height: 28px; border-radius: 6px; border: 1px solid #FECACA;
        background: #FEF2F2; color: #DC2626; cursor: pointer; display: flex;
        align-items: center; justify-content: center; font-size: 14px; transition: all 0.15s;
        margin-top: 18px;
    }
    .btn-remove-size:hover { background: #FEE2E2; border-color: #F87171; }
    .btn-add-size {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
        border: 1px dashed #D97706; border-radius: 8px; background: transparent;
        color: #D97706; font-size: 11px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; font-family: inherit; margin-top: 4px;
    }
    .btn-add-size:hover { background: #FFFBEB; }
    .stock-remaining { font-size: 10px; color: #D97706; font-weight: 600; margin-top: 2px; }
    .m2-row-fields { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; }
    
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

    /* Checkbox List for M2 */
    .m2-checkbox-list-wrapper {
        border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }
    .m2-search-input {
        width: 100%; padding: 8px 12px; border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 8px; font-size: 13px; margin-bottom: 10px; outline: none;
    }
    .m2-search-input:focus { border-color: #D97706; box-shadow: 0 0 0 2px rgba(217,119,6,0.1); }
    
    .m2-list-container {
        max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;
        padding-right: 4px;
    }
    .m2-list-container::-webkit-scrollbar { width: 5px; }
    .m2-list-container::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
    
    .m2-checkbox-item {
        display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px;
        cursor: pointer; transition: all 0.15s; border: 1px solid transparent;
    }
    .m2-checkbox-item:hover { background: #FFFBEB; border-color: #FEF3C7; }
    .m2-checkbox-item.checked { background: #FEF3C7; border-color: #FDE68A; }
    
    .m2-checkbox-item input[type="checkbox"] {
        width: 18px; height: 18px; cursor: pointer; accent-color: #D97706;
    }
    .m2-checkbox-label { font-size: 13px; font-weight: 600; color: #374151; flex: 1; }
    .m2-checkbox-stock { font-size: 11px; color: #D97706; font-weight: 700; background: #FFFBEB; padding: 2px 8px; border-radius: 10px; }

    .m2-action-btns { display: flex; gap: 8px; margin-bottom: 10px; }
    .btn-m2-small {
        padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px;
        border: 1px solid #E5E7EB; background: #fff; cursor: pointer; transition: all 0.2s;
        text-transform: uppercase;
    }
    .btn-m2-small:hover { background: #F9FAFB; border-color: #D1D5DB; }

    /* Grid Table for M2 */
    .qty-grid-wrapper {
        border-radius: 12px;
        border: 1px solid var(--border-color, #E5E7EB);
        overflow-x: auto;
        background: #fff;
    }
    .qty-grid-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .qty-grid-table th {
        background: var(--bg-body, #F9FAFB); padding: 12px 16px; font-size: 12px;
        text-transform: uppercase; color: var(--text-muted, #6B7280); border-bottom: 2px solid #E5E7EB;
        text-align: left;
    }
    .qty-grid-table td {
        padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 13px; color: var(--text-main, #1F2937);
        vertical-align: middle;
    }
    .qty-grid-table tr:last-child td { border-bottom: none; }
    .f-input-small {
        width: 80px; padding: 8px 10px; border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 6px; font-size: 13px; outline: none; background: #fff;
    }
    .f-input-small:focus { border-color: #D97706; }
    .f-input-mini {
        width: 55px; padding: 6px 8px; border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 6px; font-size: 13px; outline: none; background: #fff; text-align: center;
    }
    .f-input-mini:focus { border-color: #D97706; }

    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .item-row-fields { grid-template-columns: 1fr; }
        .m2-row-fields { grid-template-columns: 1fr; }
        .dispense-card-body { padding: 20px; }
        .submit-section { flex-direction: column; gap: 12px; }
    }
</style>
@endsection

@section('scripts')
<script>
    // Data setup
    const itemsData = {!! $items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'stock' => $i->sizes_sum_stock ?? 0, 'sizes' => $i->sizes->map(fn($s) => ['id' => $s->id, 'size_label' => $s->size_label, 'stock' => $s->stock])])->toJson() !!};
    const satkersData = @json(collect($satkers)->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
    
    // Core Methods Tracking
    let currentMethod = 'method_1';
    let rowCountM1 = 0;
    
    let rowCountM2Item = 0;
    let rowCountM2Satker = 0;

    let stockLimitsM1 = {};
    let stockLimitsM2 = {}; // stores maximum stock per size ID

    document.addEventListener('DOMContentLoaded', () => {
        addItemRowM1(); // Init 1 row for M1
        
        // Init M2 Lists
        initM2Checkboxes();
    });

    function initM2Checkboxes() {
        // Items
        const itemContainer = document.getElementById('m2_item_list');
        itemsData.forEach(item => {
            const div = document.createElement('div');
            div.className = 'm2-checkbox-item';
            div.innerHTML = `
                <input type="checkbox" name="selected_items[]" value="${item.id}" class="m2-item-check" data-name="${item.name}" onchange="handleM2CheckboxChange(this)">
                <span class="m2-checkbox-label">${item.name}</span>
                <span class="m2-checkbox-stock">Stok: ${item.stock}</span>
            `;
            div.onclick = function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = this.querySelector('input');
                    cb.checked = !cb.checked;
                    handleM2CheckboxChange(cb);
                }
            };
            itemContainer.appendChild(div);
        });

        // Satkers
        const satkerContainer = document.getElementById('m2_satker_list');
        satkersData.forEach(sat => {
            const div = document.createElement('div');
            div.className = 'm2-checkbox-item';
            div.innerHTML = `
                <input type="checkbox" name="selected_satkers[]" value="${sat.id}" class="m2-satker-check" data-name="${sat.name}" onchange="handleM2CheckboxChange(this)">
                <span class="m2-checkbox-label">${sat.name}</span>
            `;
            div.onclick = function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = this.querySelector('input');
                    cb.checked = !cb.checked;
                    handleM2CheckboxChange(cb);
                }
            };
            satkerContainer.appendChild(div);
        });
    }

    function filterM2List(input, containerId) {
        const val = input.value.toLowerCase();
        const items = document.getElementById(containerId).querySelectorAll('.m2-checkbox-item');
        items.forEach(item => {
            const text = item.querySelector('.m2-checkbox-label').textContent.toLowerCase();
            item.style.display = text.includes(val) ? 'flex' : 'none';
        });
    }

    function handleM2CheckboxChange(cb) {
        cb.closest('.m2-checkbox-item').classList.toggle('checked', cb.checked);
        
        // Update stock limit map for regenerateGrid
        if (cb.classList.contains('m2-item-check')) {
            const itemObj = itemsData.find(i => i.id == cb.value);
            stockLimitsM2[cb.value] = itemObj ? parseInt(itemObj.stock) : 0;
        }

        regenerateGridM2();
    }

    function toggleAllM2Checkboxes(containerId, state) {
        const container = document.getElementById(containerId);
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            // Only toggle visible ones
            if (cb.closest('.m2-checkbox-item').style.display !== 'none') {
                cb.checked = state;
                cb.closest('.m2-checkbox-item').classList.toggle('checked', state);
            }
        });
        regenerateGridM2();
    }

    function switchMethod(method) {
        currentMethod = method;
        document.getElementById('dispense_method').value = method;
        
        document.getElementById('tab_method_1').classList.remove('active');
        document.getElementById('tab_method_2').classList.remove('active');
        document.getElementById('tab_' + method).classList.add('active');
        
        document.getElementById('wrapper_method_1').style.display = 'none';
        document.getElementById('wrapper_method_2').style.display = 'none';
        
        document.getElementById('wrapper_' + method).style.display = 'block';

        if (method === 'method_1') {
            document.getElementById('satkerSelect_m1_val').required = true;
            document.getElementById('m1_recipient_name').required = true;
        } else {
            document.getElementById('satkerSelect_m1_val').required = false;
            document.getElementById('m1_recipient_name').required = false;
        }
    }

    // ── Searchable Select Core ──
    function ssToggle(id) {
        const wrap = document.getElementById(id);
        if (!wrap || wrap.classList.contains('disabled')) return;

        const dd = document.getElementById(id + '_dd');
        const trigger = wrap.querySelector('.ss-trigger');
        const isOpen = dd.classList.contains('open');
        // Close all
        document.querySelectorAll('.ss-dropdown.open').forEach(d => { 
            d.classList.remove('open'); 
            d.closest('.ss-wrap').querySelector('.ss-trigger').classList.remove('open'); 
        });
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
    function ssSelect(id, value, label, metaData = null) {
        const valInput = document.getElementById(id + '_val');
        if (valInput) valInput.value = value;
        const textEl = document.getElementById(id.replace('Select', '_text'));
        if (textEl) {
            textEl.textContent = label;
            textEl.classList.remove('ss-placeholder');
        }

        // Mark selected
        document.getElementById(id + '_dd').querySelectorAll('.ss-opt').forEach(o => o.classList.toggle('selected', o.dataset.val === value));
        // Close
        document.getElementById(id + '_dd').classList.remove('open');
        document.getElementById(id).querySelector('.ss-trigger').classList.remove('open');

        // Logic based on ID patterns
        if (id.startsWith('itemSelect_m1_')) {
            loadSizesM1(value, id.split('_').pop());
        } 
        else if (id.startsWith('sizeSelect_m1_')) {
            // id format: sizeSelect_m1_{itemIdx}_{subIdx}
            const parts = id.replace('sizeSelect_m1_', '').split('_');
            const itemIdx = parts[0];
            const subIdx = parts[1];
            const opt = document.querySelector(`#${id}_dd .ss-opt[data-val="${value}"]`);
            const stock = parseInt(opt?.dataset?.stock || 0);
            document.getElementById('stockVal_m1_' + itemIdx + '_' + subIdx).textContent = stock;
            document.getElementById('stockBadge_m1_' + itemIdx + '_' + subIdx).style.display = stock > 0 ? 'inline-flex' : 'none';
            recalcRemainingStock(parseInt(itemIdx));
            checkStockM1Sub(parseInt(itemIdx), parseInt(subIdx));
        }
    }
    
    document.addEventListener('click', e => {
        if (!e.target.closest('.ss-wrap')) {
            document.querySelectorAll('.ss-dropdown.open').forEach(d => { 
                d.classList.remove('open'); 
                d.closest('.ss-wrap').querySelector('.ss-trigger').classList.remove('open'); 
            });
        }
    });

    // ── METHOD 1: PER SATKER ──
    function addItemRowM1() {
        const idx = rowCountM1++;
        const container = document.getElementById('itemRowsContainer_m1');
        const div = document.createElement('div');
        div.className = 'item-row';
        div.dataset.index = idx;

        let itemOptsHTML = '';
        itemsData.forEach(i => {
            itemOptsHTML += `<div class="ss-opt" data-val="${i.id}" data-label="${i.name}" onclick="ssSelect('itemSelect_m1_${idx}', '${i.id}', '${i.name}')">${i.name}</div>`;
        });

        div.innerHTML = `
            <div class="item-row-header">
                <div class="item-row-number">${idx + 1}</div>
                <button type="button" class="btn-remove-item" onclick="removeItemRowM1(this)" title="Hapus baris">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="item-row-fields">
                <div class="item-row-left">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Nama Barang <span class="req">*</span></label>
                        <div class="ss-wrap" id="itemSelect_m1_${idx}">
                            <div class="ss-trigger" onclick="ssToggle('itemSelect_m1_${idx}')">
                                <span class="ss-text ss-placeholder" id="item_text_m1_${idx}">-- Pilih Barang --</span>
                                <i class="ri-arrow-down-s-line ss-arrow"></i>
                            </div>
                            <div class="ss-dropdown" id="itemSelect_m1_${idx}_dd" style="z-index:900;">
                                <div class="ss-search"><input type="text" placeholder="Cari barang..." oninput="ssFilter(this, 'itemSelect_m1_${idx}')"></div>
                                <div class="ss-list">${itemOptsHTML}</div>
                            </div>
                            <input type="hidden" class="item-val-m1" id="itemSelect_m1_${idx}_val">
                        </div>
                    </div>
                </div>
                <div class="item-row-right">
                    <div class="size-sub-rows" id="sizeSubRows_m1_${idx}"></div>
                    <button type="button" class="btn-add-size" id="btnAddSize_m1_${idx}" onclick="addSizeSubRow(${idx})" style="display:none;">
                        <i class="ri-add-line"></i> Tambah Ukuran
                    </button>
                </div>
            </div>`;
        container.appendChild(div);
        renumberRows('itemRowsContainer_m1');
    }

    let sizeSubCounters = {}; // track sub-row count per item row
    let loadedSizesCache = {}; // cache loaded sizes per item row idx

    function addSizeSubRow(itemIdx, preselect = null) {
        if (!sizeSubCounters[itemIdx]) sizeSubCounters[itemIdx] = 0;
        const subIdx = sizeSubCounters[itemIdx]++;
        const container = document.getElementById('sizeSubRows_m1_' + itemIdx);
        const cachedSizes = loadedSizesCache[itemIdx] || [];

        let sizeOptsHTML = '';
        cachedSizes.forEach(s => {
            sizeOptsHTML += `<div class="ss-opt" data-val="${s.id}" data-label="${s.size_label}" data-stock="${s.stock}" onclick="ssSelect('sizeSelect_m1_${itemIdx}_${subIdx}', '${s.id}', '${s.size_label} (stok: ${s.stock})')">
                ${s.size_label} <span class="ss-badge">Stok: ${s.stock}</span>
            </div>`;
        });

        const row = document.createElement('div');
        row.className = 'size-sub-row';
        row.dataset.subindex = subIdx;
        row.innerHTML = `
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size:10px;">Ukuran <span class="req">*</span></label>
                <div class="ss-wrap" id="sizeSelect_m1_${itemIdx}_${subIdx}">
                    <div class="ss-trigger" onclick="ssToggle('sizeSelect_m1_${itemIdx}_${subIdx}')" style="min-height:38px; padding:8px 12px;">
                        <span class="ss-text ss-placeholder" id="size_text_m1_${itemIdx}_${subIdx}">-- Pilih Ukuran --</span>
                        <i class="ri-arrow-down-s-line ss-arrow"></i>
                    </div>
                    <div class="ss-dropdown" id="sizeSelect_m1_${itemIdx}_${subIdx}_dd" style="z-index:900;">
                        <div class="ss-search"><input type="text" placeholder="Cari ukuran..." oninput="ssFilter(this, 'sizeSelect_m1_${itemIdx}_${subIdx}')"></div>
                        <div class="ss-list">${sizeOptsHTML}</div>
                    </div>
                    <input type="hidden" name="items[${itemIdx}][sizes][${subIdx}][warehouse_item_size_id]" class="size-val-m1" id="sizeSelect_m1_${itemIdx}_${subIdx}_val">
                </div>
                <div class="stock-badge" id="stockBadge_m1_${itemIdx}_${subIdx}" style="display:none;">
                    <i class="ri-information-line"></i> Stok: <span id="stockVal_m1_${itemIdx}_${subIdx}">0</span>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size:10px;">Jumlah <span class="req">*</span></label>
                <input type="number" name="items[${itemIdx}][sizes][${subIdx}][quantity]" class="f-input qty-input-m1" placeholder="0" min="1" style="padding:8px 10px;" oninput="onQtyChangeM1(${itemIdx})" data-itemindex="${itemIdx}" data-subindex="${subIdx}">
                <p class="stock-warning" id="stockWarnSub_m1_${itemIdx}_${subIdx}"><i class="ri-error-warning-line"></i> Melebihi stok!</p>
            </div>
            <button type="button" class="btn-remove-size" onclick="removeSizeSubRow(this, ${itemIdx})" title="Hapus ukuran">
                <i class="ri-close-line"></i>
            </button>`;
        container.appendChild(row);
        
        // Ensure the newly added row dropdown options have dynamically updated stock
        recalcRemainingStock(itemIdx);
    }

    function removeSizeSubRow(btn, itemIdx) {
        btn.closest('.size-sub-row').remove();
        recalcRemainingStock(itemIdx);
        evaluateGlobalStockM1();
    }

    function removeItemRowM1(btn) {
        const row = btn.closest('.item-row');
        const idx = row.dataset.index;
        row.remove();
        if (stockLimitsM1) delete stockLimitsM1[idx];
        renumberRows('itemRowsContainer_m1');
        evaluateGlobalStockM1();
    }

    function loadSizesM1(itemId, index) {
        const btnAddSize = document.getElementById('btnAddSize_m1_' + index);
        const subRowsContainer = document.getElementById('sizeSubRows_m1_' + index);

        // Clear existing sub-rows
        subRowsContainer.innerHTML = '';
        sizeSubCounters[index] = 0;
        loadedSizesCache[index] = [];
        btnAddSize.style.display = 'none';

        if (!itemId) return;

        fetch(`{{ url('admin/warehouse-items/api/item-sizes') }}/${itemId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(sizes => {
            if (!sizes.length) return;
            loadedSizesCache[index] = sizes;
            btnAddSize.style.display = 'inline-flex';
            // Auto add first sub-row
            addSizeSubRow(index);
        });
    }

    function checkStockM1Sub(itemIdx, subIdx) {
        const qtyInput = document.querySelector(`.qty-input-m1[data-itemindex="${itemIdx}"][data-subindex="${subIdx}"]`);
        if (!qtyInput) return;
        const qty = parseInt(qtyInput.value) || 0;
        const sizeId = document.getElementById('sizeSelect_m1_' + itemIdx + '_' + subIdx + '_val').value;
        
        // Fallback to original stock if no remaining calculated yet
        const stockEl = document.getElementById('stockVal_m1_' + itemIdx + '_' + subIdx);
        let max = stockEl ? parseInt(stockEl.textContent) || 0 : 0;
        
        if (max === 0 && sizeId) {
            const opt = document.querySelector(`#sizeSelect_m1_${itemIdx}_${subIdx}_dd .ss-opt[data-val="${sizeId}"]`);
            max = parseInt(opt?.dataset?.stock || 0);
        }
        
        const warn = document.getElementById('stockWarnSub_m1_' + itemIdx + '_' + subIdx);
        if (max > 0 && qty > max) { warn.style.display = 'block'; } else { warn.style.display = 'none'; }
        evaluateGlobalStockM1();
    }

    // Recalculate remaining stock for all sub-rows with the same size within an item row
    function recalcRemainingStock(itemIdx) {
        const subRows = document.querySelectorAll(`#sizeSubRows_m1_${itemIdx} .size-sub-row`);
        const cachedSizes = loadedSizesCache[itemIdx] || [];
        
        let consumed = {};
        cachedSizes.forEach(s => { consumed[s.id] = 0; });
        
        subRows.forEach(row => {
            const subIdx = row.dataset.subindex;
            const sizeIdInput = document.getElementById('sizeSelect_m1_' + itemIdx + '_' + subIdx + '_val');
            const selectedSizeId = sizeIdInput ? sizeIdInput.value : '';
            const qtyInput = row.querySelector('.qty-input-m1');
            const qty = parseInt(qtyInput?.value || 0);
            
            // 1. Update dropdown options in THIS row based on CURRENT consumed values
            cachedSizes.forEach(s => {
                const avail = s.stock - consumed[s.id];
                const optDiv = document.querySelector(`#sizeSelect_m1_${itemIdx}_${subIdx}_dd .ss-opt[data-val="${s.id}"]`);
                if (optDiv) {
                    const badge = optDiv.querySelector('.ss-badge');
                    if (badge) badge.textContent = `Stok: ${avail}`;
                }
            });
            
            // 2. If a size is selected, update its yellow badge & trigger text, THEN add to consumed
            if (selectedSizeId && consumed[selectedSizeId] !== undefined) {
                const origStock = cachedSizes.find(s => s.id == selectedSizeId)?.stock || 0;
                const sizeLabel = cachedSizes.find(s => s.id == selectedSizeId)?.size_label || '';
                const avail = origStock - consumed[selectedSizeId];
                
                const stockVal = document.getElementById('stockVal_m1_' + itemIdx + '_' + subIdx);
                const stockBadge = document.getElementById('stockBadge_m1_' + itemIdx + '_' + subIdx);
                if (stockVal && stockBadge) {
                    stockVal.textContent = avail;
                    stockBadge.style.display = 'inline-flex';
                    if (avail < 0) {
                        stockBadge.style.background = '#FEE2E2';
                        stockBadge.style.color = '#DC2626';
                    } else {
                        stockBadge.style.background = '#FEF3C7';
                        stockBadge.style.color = '#92400E';
                    }
                }
                
                const sizeTextEl = document.getElementById('size_text_m1_' + itemIdx + '_' + subIdx);
                if (sizeTextEl && !sizeTextEl.classList.contains('ss-placeholder')) {
                    sizeTextEl.textContent = `${sizeLabel} (stok: ${avail})`;
                }
                
                const warn = document.getElementById('stockWarnSub_m1_' + itemIdx + '_' + subIdx);
                if (warn) {
                    if (qty > avail) { warn.style.display = 'block'; } else { warn.style.display = 'none'; }
                }
                
                // Add to consumed for NEXT rows
                consumed[selectedSizeId] += (qty > 0 ? qty : 0);
            } else {
                const stockBadge = document.getElementById('stockBadge_m1_' + itemIdx + '_' + subIdx);
                if (stockBadge) stockBadge.style.display = 'none';
            }
        });

        evaluateGlobalStockM1();
    }

    function onQtyChangeM1(itemIdx) {
        recalcRemainingStock(itemIdx);
    }
    
    function evaluateGlobalStockM1() {
        const warnings = document.querySelectorAll('#wrapper_method_1 .size-sub-row .stock-warning');
        let any = false;
        warnings.forEach(w => { 
            // Hanya aktifkan peringatan global jika teks merah benar-benar sedang tampil di UI
            if(w.style.display === 'block' && w.offsetParent !== null) {
                any = true;
            } 
        });
        const globalWarn = document.getElementById('globalStockWarn_m1');
        if (globalWarn) globalWarn.style.display = any ? 'block' : 'none';
    }

    function regenerateGridM2() {
        // Collect checked items
        const selectedItems = [];
        document.querySelectorAll('.m2-item-check:checked').forEach(cb => {
            selectedItems.push({ id: cb.value, label: cb.dataset.name });
        });

        // Collect checked satkers
        const selectedSatkers = [];
        document.querySelectorAll('.m2-satker-check:checked').forEach(cb => {
            selectedSatkers.push({ id: cb.value, label: cb.dataset.name });
        });

        const tbody = document.getElementById('m2_table_body');
        const thead = document.getElementById('m2_table').querySelector('thead');
        
        const modeInput = document.querySelector('input[name="m2_mode"]:checked');
        const mode = modeInput ? modeInput.value : 'acak';

        // Store old values from inputs currently in DOM
        const oldValues = {};
        document.querySelectorAll('.m2-qty-grid-input').forEach(inp => {
            oldValues[inp.name] = inp.value;
        });

        if (selectedItems.length === 0 || selectedSatkers.length === 0) {
            thead.innerHTML = `<tr id="m2_table_head"><th>Satker Penerima</th><th>Pilih minimal 1 barang & 1 satker</th></tr>`;
            tbody.innerHTML = `<tr><td colspan="2" style="text-align:center; color:#9CA3AF; padding:20px;">Belum ada data barang dan satker yang dipilih lengkap. Centang barang dan satker di atas.</td></tr>`;
            evaluateGridStockM2(mode);
            return;
        }

        // Build header
        if (mode === 'ukuran') {
            let thHtml1 = '<tr><th rowspan="2" style="vertical-align:bottom; border-right:1px solid #E5E7EB;">Satker Penerima</th>';
            let thHtml2 = '<tr>';
            
            selectedItems.forEach(item => {
                const itemObj = itemsData.find(i => i.id == item.id);
                if (itemObj && itemObj.sizes && itemObj.sizes.length > 0) {
                    const colspan = itemObj.sizes.length;
                    thHtml1 += `<th colspan="${colspan}" style="text-align:center; border-bottom:1px solid #E5E7EB; border-right:1px solid #E5E7EB; background:#F9FAFB;"><span style="display:block; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; color:#374151; font-weight:700;" title="${item.label}">${item.label}</span></th>`;
                    
                    itemObj.sizes.forEach(sz => {
                        thHtml2 += `<th style="text-align:center; min-width:60px; padding:12px 6px; border-right:1px solid #E5E7EB;"><strong style="color:#D97706; display:block;">${sz.size_label}</strong> <div style="font-size:10px; color:#D97706; margin-top:4px;">Stok: ${sz.stock}</div></th>`;
                    });
                } else {
                    thHtml1 += `<th rowspan="2" style="text-align:center; border-right:1px solid #E5E7EB;"><span style="display:block; max-width:140px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="${item.label}">${item.label}</span> <div style="font-size:10px; color:#D97706; margin-top:4px;">Stok: 0</div></th>`;
                }
            });
            
            thHtml1 += '</tr>';
            thHtml2 += '</tr>';
            thead.innerHTML = thHtml1 + thHtml2;
        } else {
            let thHtml = '<tr id="m2_table_head"><th style="border-right:1px solid #E5E7EB;">Satker Penerima</th>';
            selectedItems.forEach(item => {
                const max = stockLimitsM2[item.id] || 0;
                thHtml += `<th style="text-align:center; border-right:1px solid #E5E7EB;"><span style="display:block; max-width:140px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; margin:0 auto;" title="${item.label}">${item.label}</span> <div style="font-size:10px; color:#D97706; margin-top:4px;">Stok Max: ${max}</div></th>`;
            });
            thHtml += '</tr>';
            thead.innerHTML = thHtml;
        }

        // Build body
        let tbHtml = '';
        selectedSatkers.forEach(sat => {
            tbHtml += `<tr><td><strong>${sat.label}</strong></td>`;
            selectedItems.forEach(item => {
                const itemObj = itemsData.find(i => i.id == item.id);
                if (mode === 'ukuran' && itemObj && itemObj.sizes && itemObj.sizes.length > 0) {
                    itemObj.sizes.forEach(sz => {
                        const nameAttr = `quantities_size[${sat.id}][${item.id}][${sz.id}]`;
                        const oldVal = oldValues[nameAttr] || '0';
                        tbHtml += `<td style="text-align:center; padding: 8px 6px;"><input type="number" name="${nameAttr}" data-sizeid="${sz.id}" class="f-input-mini m2-qty-grid-input" value="${oldVal}" min="0" oninput="evaluateGridStockM2()"></td>`;
                    });
                } else {
                    const nameAttr = `quantities[${sat.id}][${item.id}]`;
                    const oldVal = oldValues[nameAttr] || '0';
                    tbHtml += `<td><input type="number" name="${nameAttr}" data-itemid="${item.id}" class="f-input-small m2-qty-grid-input" value="${oldVal}" min="0" oninput="evaluateGridStockM2()"></td>`;
                }
            });
            tbHtml += `</tr>`;
        });
        tbody.innerHTML = tbHtml;
        evaluateGridStockM2(mode);
    }

    function evaluateGridStockM2(forcedMode = null) {
        let mode = forcedMode;
        if (!mode) {
            const modeInput = document.querySelector('input[name="m2_mode"]:checked');
            mode = modeInput ? modeInput.value : 'acak';
        }
        
        const inputs = document.querySelectorAll('.m2-qty-grid-input');
        const sums = {};
        
        inputs.forEach(inp => {
            let id = mode === 'ukuran' && inp.dataset.sizeid ? inp.dataset.sizeid : inp.dataset.itemid;
            // Prevent issue if mode was changed but inputs haven't refreshed fully yet
            if (!id && inp.dataset.sizeid) id = inp.dataset.sizeid; 
            if (!id && inp.dataset.itemid) id = inp.dataset.itemid; 
            
            const val = parseInt(inp.value) || 0;
            if (!sums[id]) sums[id] = 0;
            sums[id] += val;
        });

        let anyExceed = false;
        Object.keys(sums).forEach(id => {
            let max = 0;
            if (mode === 'ukuran') {
                // Cari max. berdasarkan id size
                itemsData.forEach(item => {
                   if (item.sizes) {
                       const sz = item.sizes.find(s => s.id == id);
                       if (sz) max = parseInt(sz.stock);
                   } 
                });
            } else {
                max = stockLimitsM2[id] || 0;
            }
            if (sums[id] > max) anyExceed = true;
        });

        document.getElementById('globalStockWarn_m2').style.display = anyExceed ? 'block' : 'none';
    }

    function renumberRows(containerId) {
        document.querySelectorAll(`#${containerId} .item-row`).forEach((row, i) => {
            row.querySelector('.item-row-number').textContent = i + 1;
        });
    }

    // ── SUPER SUBMIT VALIDATION ──
    document.getElementById('dispenseForm').addEventListener('submit', function(e) {
        if (currentMethod === 'method_1') {
            const satker = document.getElementById('satkerSelect_m1_val').value;
            if (!satker) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Pilih Satker', text: 'Harap pilih satker penerima.' }); return; }
            
            const sizeVals = document.querySelectorAll('.size-val-m1');
            if (sizeVals.length === 0) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Data Kosong', text: 'Harap pilih barang!' }); return; }
            for (const s of sizeVals) {
                if (!s.value) { e.preventDefault(); Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: 'Harap lengkapi barang & ukuran.' }); return; }
            }
            if (document.getElementById('globalStockWarn_m1').style.display === 'block') { e.preventDefault(); Swal.fire({ icon: 'error', title: 'Stok Kurang', text: 'Ada barang melebihi stok.' }); return; }
        
        } else {
            const m2Items = document.querySelectorAll('.m2-item-check:checked');
            const m2Satkers = document.querySelectorAll('.m2-satker-check:checked');
            
            if (m2Items.length === 0 || m2Satkers.length === 0) { 
                e.preventDefault(); 
                Swal.fire({ icon: 'warning', title: 'Lengkapi Data', text: 'Pilih minimal 1 barang & 1 satker pada metode ke 2.' }); 
                return; 
            }
            if (document.getElementById('globalStockWarn_m2').style.display === 'block') { e.preventDefault(); Swal.fire({ icon: 'error', title: 'Stok Total Kurang', text: 'Total kebutuhan barang melebihi kuota stok keseluruhan (semua ukuran yang ada)!' }); return; }
        }
    });

</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
