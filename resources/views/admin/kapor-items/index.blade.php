@extends('layouts.app')

@section('title', 'Data Item Kapor')
@section('breadcrumb', 'Data Item Kapor')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Data Item Kapor</h1>
            <p class="page-subtitle">Manajemen master data item kapor dan atribut</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="openModal('addItemModal')">
                <i class="ri-add-line"></i> Tambah Item
            </button>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-shirt-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL KEPA ITEM</span>
            <span class="stat-number">{{ $stats['kepala'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i class="ri-t-shirt-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL BADAN ITEM</span>
            <span class="stat-number">{{ $stats['badan'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="ri-footprint-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL KAKI ITEM</span>
            <span class="stat-number">{{ $stats['kaki'] }}</span>
        </div>
    </div>
     <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-stack-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL SEMUA</span>
            <span class="stat-number">{{ $stats['total'] }}</span>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.kapor-items.index') }}" class="filter-form" onsubmit="return false;">
        <div class="search-input-wrapper">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama item..." class="search-field" autocomplete="off">
        </div>
        
        <div class="filter-divider"></div>

        <div class="custom-select-wrapper" style="width: 200px;">
           <div class="custom-select" onclick="this.classList.toggle('active')">
                <div class="select-trigger">
                    <span>{{ request('category') ? str_replace('_', ' ', request('category')) : 'Semua Kategori' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options">
                    <div class="option {{ !request('category') ? 'selected' : '' }}" onclick="selectCategory('', this)">Semua Kategori</div>
                    @foreach($categories as $key => $label)
                        <div class="option {{ request('category') == $key ? 'selected' : '' }}" onclick="selectCategory('{{ $key }}', this)">{{ $label }}</div>
                    @endforeach
                </div>
            </div>
            <!-- Hidden inputs to preserve other params -->
            <input type="hidden" name="category" id="categoryInput" value="{{ request('category') }}">
            @if(request('per_page')) <input type="hidden" name="per_page" value="{{ request('per_page') }}"> @endif
        </div>
    </form>
</div>

<script>
    let typingTimer;
    
    // AJAX Fetch Function
    function fetchTable(url) {
        let container = document.getElementById('tableContainer');
        container.style.opacity = '0.5'; // Simple loading effect
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            
            // Update URL without reload
            window.history.pushState({}, '', url);
        })
        .catch(error => {
            console.error('Error:', error);
            container.style.opacity = '1';
            alert('Gagal memuat data. Silakan coba lagi.');
        });
    }

    // Intercept Pagination Clicks (using Delegation)
    document.addEventListener('click', function(e) {
        let link = e.target.closest('.ajax-link');
        if (link) {
            e.preventDefault();
            if (link.getAttribute('href') && !link.classList.contains('disabled')) {
                fetchTable(link.getAttribute('href'));
            }
        }
    });

    // Category Filter
    function selectCategory(val, element) {
        document.getElementById('categoryInput').value = val;
        
        // Update UI
        document.querySelectorAll('.filter-bar .option').forEach(el => el.classList.remove('selected'));
        if(element) {
            element.classList.add('selected');
             // Update Trigger Text
            let text = element.innerText;
            document.querySelector('.filter-bar .select-trigger span').innerText = text;
        } else {
             // Fallback if called without element (e.g. direct load)
        }
       
        document.querySelector('.filter-bar .custom-select').classList.remove('active'); // Close dropdown

        let url = new URL(window.location.href);
        url.searchParams.set('category', val);
        url.searchParams.set('page', 1); // Reset to page 1
        
        fetchTable(url.toString());
    }

    // Search Filter with Debounce
    document.addEventListener('input', function(e) {
        if(e.target.classList.contains('search-field')) {
            clearTimeout(typingTimer);
            let val = e.target.value;
            
            typingTimer = setTimeout(() => {
                let url = new URL(window.location.href);
                if(val) {
                    url.searchParams.set('search', val);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.set('page', 1);
                fetchTable(url.toString());
            }, 500); // Debounce 500ms
        }
    });

    // Per Page Change
    function changePerPage(val) {
         let url = new URL(window.location.href);
         url.searchParams.set('per_page', val);
         url.searchParams.set('page', 1);
         fetchTable(url.toString());
    }

    // Handle Browser Back/Forward buttons
    window.onpopstate = function(event) {
        fetchTable(window.location.href);
    };
</script>

{{-- Table --}}
    <div class="table-container" id="tableContainer">
        @include('admin.kapor-items.partials.table')
    </div>

{{-- Add Modal --}}
<div id="addItemModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Item Kapor</h2>
            <button class="modal-close" onclick="closeModal('addItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.kapor-items.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>NAMA ITEM</label>
                    <input type="text" name="item_name" required class="form-input" placeholder="Contoh: Kemeja PDH">
                </div>
                <div class="form-group">
                    <label>KATEGORI</label>
                    <div class="custom-select-wrapper">
                        <select name="category" class="form-input" required style="appearance: auto;">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Tutup_Kepala">Tutup Kepala</option>
                            <option value="Tutup_Badan">Tutup Badan</option>
                            <option value="Tutup_Kaki">Tutup Kaki</option>
                            <option value="Atribut">Atribut</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>KHUSUS GENDER (OPSIONAL)</label>
                    <div class="selection-grid">
                         <label class="selection-card">
                            <input type="radio" name="gender_specific" value="L">
                            <div class="card-content">
                                <span class="card-title">Pria</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                        <label class="selection-card">
                            <input type="radio" name="gender_specific" value="P">
                            <div class="card-content">
                                <span class="card-title">Wanita</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                    </div>
                    <p style="font-size: 11px; color: #6B7280; margin-top: 4px;">Biarkan kosong jika item ini Unisex (Semua Gender)</p>
                </div>
                <div class="form-group">
                    <label>DESKRIPSI (OPSIONAL)</label>
                    <input type="text" name="description" class="form-input" placeholder="Keterangan tambahan...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addItemModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Item</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editItemModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Item Kapor</h2>
            <button class="modal-close" onclick="closeModal('editItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>NAMA ITEM</label>
                    <input type="text" name="item_name" id="edit_item_name" required class="form-input">
                </div>
                <div class="form-group">
                    <label>KATEGORI</label>
                    <div class="custom-select-wrapper">
                        <select name="category" id="edit_category" class="form-input" required style="appearance: auto;">
                            <option value="Tutup_Kepala">Tutup Kepala</option>
                            <option value="Tutup_Badan">Tutup Badan</option>
                            <option value="Tutup_Kaki">Tutup Kaki</option>
                            <option value="Atribut">Atribut</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>KHUSUS GENDER (OPSIONAL)</label>
                    <div class="selection-grid">
                         <label class="selection-card">
                            <input type="radio" name="gender_specific" value="L" id="edit_gender_l">
                            <div class="card-content">
                                <span class="card-title">Pria</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                        <label class="selection-card">
                            <input type="radio" name="gender_specific" value="P" id="edit_gender_p">
                            <div class="card-content">
                                <span class="card-title">Wanita</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>STATUS</label>
                    <div class="selection-grid">
                         <label class="selection-card">
                            <input type="radio" name="is_active" value="1" id="edit_active_1">
                            <div class="card-content">
                                <span class="card-title">Aktif</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                        <label class="selection-card">
                            <input type="radio" name="is_active" value="0" id="edit_active_0">
                            <div class="card-content">
                                <span class="card-title">Non-Aktif</span>
                            </div>
                            <div class="card-check"><i class="ri-check-line"></i></div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editItemModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 style="color: #DC2626; margin: 0;">Hapus Item?</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus item <strong id="deleteItemName"></strong>?</p>
                <p style="font-size: 12px; color: #EF4444; margin-top: 8px;">Perhatian: Semua data ukuran yang terkait dengan item ini juga akan dihapus.</p>
            </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
                <button type="submit" class="btn" style="background: #DC2626; color: white;">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    function openEditModal(item) {
        document.getElementById('edit_item_name').value = item.item_name;
        document.getElementById('edit_category').value = item.category;
        
        // Gender Reset
        document.getElementById('edit_gender_l').checked = false;
        document.getElementById('edit_gender_p').checked = false;
        if(item.gender_specific == 'L') document.getElementById('edit_gender_l').checked = true;
        if(item.gender_specific == 'P') document.getElementById('edit_gender_p').checked = true;

        // Active Status
        if(item.is_active) {
            document.getElementById('edit_active_1').checked = true;
        } else {
             document.getElementById('edit_active_0').checked = true;
        }

        document.getElementById('editForm').action = "/admin/kapor-items/" + item.id;
        openModal('editItemModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteItemName').innerText = name;
        document.getElementById('deleteForm').action = "/admin/kapor-items/" + id;
        openModal('deleteModal');
    }

    // Close on click outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('open');
        }
    }
</script>

@endsection

@section('styles')
{{-- Reuse styles from index.blade.php by keeping structure or defining here --}}
<style>
    /* Add any specific styles if missing from global app.css, but assuming layout provides base styles */
    /* Copied critical styles to ensure look */
    .role-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    
    .stats-grid { display: grid; gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #F3F4F6; display: flex; align-items: center; gap: 16px; }
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; }
    .stat-number { font-size: 20px; font-weight: 700; color: #111827; }
    
    .icon-blue { background: #EFF6FF; color: #3B82F6; }
    .icon-purple { background: #FAF5FF; color: #A855F7; }
    .icon-orange { background: #FFF7ED; color: #F97316; }
    .icon-green { background: #F0FDF4; color: #22C55E; }

    .table-container { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #F9FAFB; padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
    .user-table td { padding: 16px 24px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; color: #374151; font-size: 14px; }
    
    .action-buttons { display: flex; gap: 6px; justify-content: center; }
    .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; cursor: pointer; background: #fff; }
    .btn-icon:hover { background: #F9FAFB; }
    .btn-icon.blue { color: #3B82F6; }
    .btn-icon.red { color: #EF4444; }

    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 50; backdrop-filter: blur(4px); }
    .modal.open { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: #fff; border-radius: 16px; width: 90%; position: relative; animation: zoomIn 0.2s; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 20px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; }
    
    .modal-close { background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6B7280; }
    .modal-close:hover { background: #E5E7EB; color: #111827; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; }
    .form-input:focus { border-color: #3B82F6; ring: 2px solid #3B82F6; }

    /* ── Custom Select UI (Base) ───────────────────── */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
        height: 48px;
        display: flex; align-items: center;
    }
    .custom-select:hover { border-color: #D1D5DB; }
    .custom-select.active {
        border-color: #B91C1C;
        box-shadow: 0 0 0 4px #FEF2F2;
        background: #fff;
    }
    .select-trigger {
        width: 100%;
        padding: 0 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }
    .select-trigger i { 
        color: #9CA3AF; 
        font-size: 20px; 
        transition: transform 0.2s ease; 
    }
    .custom-select.active .select-trigger { color: #111827; }
    .custom-select.active .select-trigger i { 
        transform: rotate(180deg); 
        color: #B91C1C; 
    }
    .custom-options {
        position: absolute;
        top: calc(100% + 8px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 16px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
        z-index: 50;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
        padding: 8px;
        display: flex; flex-direction: column;
    }
    .custom-select.active .custom-options { 
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .option {
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.15s;
        font-size: 14px;
        color: #4B5563;
        border-radius: 8px;
        margin-bottom: 2px;
        font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }
    .option:hover { background-color: #F9FAFB; color: #111827; }
    .option.selected {
        background-color: #FEF2F2;
        color: #B91C1C;
        font-weight: 600;
    }

    /* Refined Filter Bar */
    .filter-bar { 
        background: #fff; 
        border: 1px solid #E5E7EB; 
        border-radius: 12px; 
        padding: 4px 6px; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); 
        margin-bottom: 24px; 
    }
    .filter-form { 
        display: flex; 
        align-items: center; 
        width: 100%; 
    }
    
    .search-input-wrapper { 
        flex: 1; 
        position: relative; 
        display: flex; 
        align-items: center; 
    }
    .search-icon { 
        position: absolute; 
        left: 14px; 
        color: #9CA3AF; 
        font-size: 18px; 
        pointer-events: none; 
    }
    .search-field { 
        width: 100%; 
        height: 44px; 
        border: none; 
        border-radius: 8px; 
        padding: 0 16px 0 44px; 
        font-size: 14px; 
        color: #374151; 
        outline: none; 
        background: transparent; 
    }
    .search-field::placeholder { color: #9CA3AF; }
    
    .filter-divider { 
        width: 1px; 
        height: 24px; 
        background: #E5E7EB; 
        margin: 0 8px; 
    }
    
    /* Override Custom Select for cleaner look inside bar */
    .filter-bar .custom-select-wrapper { margin: 0 4px; }
    .filter-bar .custom-select {
        border: none;
        background: transparent;
        height: 44px;
        padding: 0 12px;
    }
    .filter-bar .custom-select:hover { background: #F9FAFB; border-radius: 8px; }
    .filter-bar .select-trigger {
        justify-content: flex-end;
        padding: 0;
        gap: 8px;
        color: #4B5563;
        font-weight: 500;
        font-size: 13.5px;
    }
    .filter-bar .custom-options {
        top: 100%;
        margin-top: 6px;
        border: 1px solid #E5E7EB;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
        min-width: 200px;
        right: 0;
        left: auto;
        padding: 4px;
        background: #fff;
    }
    .filter-bar .option {
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
    }
    .filter-bar .option:hover { background: #F3F4F6; }
    .filter-bar .option.selected { background: #EFF6FF; color: #2563EB; font-weight: 500; }

    /* Custom Select for Forms (Modals) need to stay default */
    .modal .custom-select {
        border: 1px solid #D1D5DB;
        background: #fff;
        border-radius: 8px;
        height: auto;
        padding: 10px 14px;
    }
    .modal .select-trigger { justify-content: space-between; }
    .modal .custom-options { width: 100%; left: 0; right: auto; margin-top: 4px; }
    
    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    /* Pagination Styles */
    .table-footer { padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #F3F4F6; background: #fff; }
    .footer-left { display: flex; align-items: center; gap: 12px; color: #6B7280; font-size: 13px; }
    .pagination-controls { display: flex; align-items: center; gap: 6px; }
    .page-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; background: #fff; border-radius: 6px; color: #374151; text-decoration: none; transition: all 0.2s; }
    .page-btn:hover:not(.disabled) { background: #F9FAFB; border-color: #D1D5DB; }
    .page-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; background: #F3F4F6; }
    .page-info { font-size: 13px; color: #4B5563; margin: 0 12px; }
    .page-info strong { color: #111827; }
</style>
@endsection
