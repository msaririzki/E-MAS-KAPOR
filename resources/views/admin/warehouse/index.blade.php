@extends('layouts.app')

@section('title', 'Data Gudang')
@section('breadcrumb', 'Data Gudang')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Data Gudang</h1>
            <p class="page-subtitle">Manajemen penyimpanan barang dan stok</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-import" onclick="openModal('importModal')">
                <div class="btn-content">
                    <i class="ri-file-excel-line"></i>
                    <span>Import Data</span>
                </div>
            </button>
            <div class="dropdown-container d-inline-block" id="exportDropdown">
                <button class="btn btn-export" onclick="document.getElementById('exportDropdown').classList.toggle('open')">
                    <div class="btn-content">
                        <i class="ri-download-line"></i>
                        <span>Export</span>
                        <i class="ri-arrow-down-s-line arrow-icon"></i>
                    </div>
                </button>
                <div class="dropdown-menu">
                    <a href="{{ route('admin.warehouse-items.export-excel') }}" class="dropdown-item">
                        <i class="ri-file-excel-2-line excel-icon"></i>
                        <span>Excel (Sheet 1)</span>
                    </a>
                    <a href="{{ route('admin.warehouse-items.export-pdf') }}" class="dropdown-item">
                        <i class="ri-file-pdf-line pdf-icon"></i>
                        <span>Dokumen PDF</span>
                    </a>
                </div>
            </div>
            <button class="btn btn-primary btn-add" onclick="openModal('addItemModal')">
                <div class="btn-content">
                    <i class="ri-add-line"></i>
                    <span>Tambah Barang</span>
                </div>
            </button>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-archive-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL BARANG</span>
            <span class="stat-number">{{ number_format($stats['total_items'], 0, ',', '.') }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-stack-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL STOK GUDANG</span>
            <span class="stat-number">{{ number_format($stats['total_stock'], 0, ',', '.') }}</span>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.warehouse-items.index') }}" class="filter-form" onsubmit="return false;">
        <div class="search-input-wrapper">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama barang..." class="search-field" autocomplete="off">
        </div>
    </form>
</div>

<script>
    let typingTimer;
    
    // AJAX Fetch Function
    function fetchTable(url) {
        let container = document.getElementById('tableContainer');
        container.style.opacity = '0.5';
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            window.history.pushState({}, '', url);
        })
        .catch(error => {
            console.error('Error:', error);
            container.style.opacity = '1';
            alert('Gagal memuat data.');
        });
    }

    // Intercept Pagination
    document.addEventListener('click', function(e) {
        let link = e.target.closest('.ajax-link');
        if (link) {
            e.preventDefault();
            if (link.getAttribute('href') && !link.classList.contains('disabled')) {
                fetchTable(link.getAttribute('href'));
            }
        }
    });

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
            }, 500);
        }
    });

    // Close Dropdown on outside click
    window.addEventListener('click', function(e) {
        if (!e.target.closest('#exportDropdown')) {
            document.getElementById('exportDropdown')?.classList.remove('open');
        }
    });
</script>

{{-- Table --}}
<div class="table-container" id="tableContainer">
    @include('admin.warehouse.partials.table')
</div>

{{-- Add Modal --}}
<div id="addItemModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Form Tambah Barang</h2>
            <button class="modal-close" onclick="closeModal('addItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.warehouse-items.store') }}" method="POST">
            @csrf
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label>NAMA BARANG</label>
                    <input type="text" name="name" required class="form-input" placeholder="Contoh: PAKAIAN PDL II PRIA">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>SATUAN</label>
                        <div class="custom-select-wrapper">
                            <select name="unit" class="form-input" style="appearance: auto;">
                                @foreach($unitOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $key == 'STEL' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>HARGA SATUAN (Rp)</label>
                        <input type="number" name="price" class="form-input" placeholder="100000" min="0" step="100">
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px dashed #D1D5DB; padding-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="margin: 0;">DETAIL UKURAN & STOK</label>
                        <button type="button" class="btn btn-sm btn-outline" onclick="addSizeRow()">
                            <i class="ri-add-fill"></i> Tambah Ukuran
                        </button>
                    </div>
                    <div id="sizesContainer">
                        <div class="size-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: end;">
                            <div>
                                <input type="text" name="sizes[]" class="form-input" placeholder="Ukuran (Misal: 14)" required>
                            </div>
                            <div>
                                <input type="number" name="quantities[]" class="form-input" placeholder="Jumlah" min="0" required>
                            </div>
                            <button type="button" class="btn-icon red" onclick="this.closest('.size-row').remove()" style="height: 44px; width: 44px;">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addItemModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editItemModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Master Barang</h2>
            <button class="modal-close" onclick="closeModal('editItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label>NAMA BARANG</label>
                    <input type="text" name="name" id="edit_name" required class="form-input">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>SATUAN</label>
                        <div class="custom-select-wrapper">
                            <select name="unit" id="edit_unit" class="form-input" style="appearance: auto;">
                                @foreach($unitOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>HARGA SATUAN (Rp)</label>
                        <input type="number" name="price" id="edit_price" class="form-input" min="0" step="100">
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
            <h3 style="color: #DC2626; margin: 0;">Hapus Barang?</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus <strong id="deleteItemName"></strong> dari Gudang?</p>
                <p style="font-size: 12px; color: #EF4444; margin-top: 8px;">Semua data stok kelengkapan untuk barang ini juga akan terhapus.</p>
            </div>
             <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
                <button type="submit" class="btn" style="background: #DC2626; color: white;">Hapus Data</button>
            </div>
        </form>
    </div>
</div>

{{-- Import Modal --}}
<div id="importModal" class="modal">
    <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header">
            <h2 class="modal-title">Import Data Gudang</h2>
            <button class="modal-close" onclick="closeModal('importModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.warehouse-items.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="import-area">
                    <div class="template-download-box">
                        <div class="template-info">
                            <i class="ri-file-download-line"></i>
                            <div>
                                <p class="template-title">Belum punya formatnya?</p>
                                <p class="template-desc">Gunakan file contoh agar import data berjalan lancar.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.warehouse-items.download-template') }}" class="btn btn-sm btn-template">
                            <i class="ri-download-2-line"></i> Unduh Template
                        </a>
                    </div>

                    <div class="file-upload-wrapper">
                        <label class="file-drop-zone" id="dropZone">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required id="fileInput" onchange="handleFileSelect(this)">
                            <div class="drop-zone-content">
                                <div class="icon-circle bg-blue-soft">
                                    <i class="ri-upload-cloud-2-line"></i>
                                </div>
                                <p class="drop-text">Klik atau tarik file Excel ke sini</p>
                                <p class="drop-subtext">Maksimal ukuran file 5MB (.xlsx, .xls)</p>
                            </div>
                            <div id="fileSelectedInfo" class="file-info-overlay" style="display:none;">
                                <i class="ri-file-check-line text-green"></i>
                                <span id="fileNameDisplay" class="text-green">Nama-file.xlsx</span>
                                <button type="button" class="btn-remove-file" onclick="resetFileInput(event)"><i class="ri-close-circle-fill"></i></button>
                            </div>
                        </label>
                    </div>

                    <div class="import-instructions">
                        <div class="instruction-header">
                            <i class="ri-information-fill"></i>
                            <span>Panduan Format Kolom (Baris 1)</span>
                        </div>
                        <div class="instruction-badges">
                            <span class="badge">nama_barang</span>
                            <span class="badge">satuan</span>
                            <span class="badge">ukuran</span>
                            <span class="badge">kuantitas</span>
                            <span class="badge">harga_satuan</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('importModal')">Batal</button>
                <button type="submit" class="btn btn-import" style="padding: 0 24px;">
                    <i class="ri-upload-2-line" style="margin-right:8px;"></i> Mulai Import Data
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Kelola Ukuran --}}
<div id="sizeModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">Detail Stok: <span id="sizeModalItemName" style="color:#1D4ED8;"></span></h2>
            <button class="modal-close" onclick="closeModal('sizeModal')"><i class="ri-close-line"></i></button>
        </div>
        <div class="modal-body" style="max-height: 75vh; overflow-y:auto;">

            {{-- Form Tambah Ukuran --}}
            <div style="background:#F0F9FF; border:1px solid #BAE6FD; border-radius:10px; padding:16px; margin-bottom:20px;">
                <p style="font-size:12px; font-weight:700; color:#0369A1; margin:0 0 12px;">TAMBOK STOK / UKURAN BARU</p>
                <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:12px; align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label>UKURAN</label>
                        <input type="text" id="newSizeLabel" class="form-input" placeholder="Misal: 14">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>JUMLAH (Kuantitas)</label>
                        <input type="number" id="newSizeStock" class="form-input" placeholder="0" min="0">
                    </div>
                    <button onclick="submitAddSize()" class="btn btn-primary" style="height:40px; white-space:nowrap;">
                        <i class="ri-add-line"></i> Tambah
                    </button>
                </div>
                <p id="sizeAddError" style="color:#DC2626; font-size:12px; margin:8px 0 0; display:none;"></p>
            </div>

            {{-- Table of Sizes --}}
            <div>
                <p style="font-size:12px; font-weight:700; color:#374151; margin:0 0 10px;">DAFTAR UKURAN & STOK</p>
                <table class="user-table" style="border: 1px solid #E5E7EB;">
                    <thead style="background: #F9FAFB;">
                        <tr>
                            <th style="padding: 10px 16px;">UKURAN</th>
                            <th style="padding: 10px 16px;">JUMLAH STOK</th>
                            <th style="padding: 10px 16px; width: 150px; text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="sizeListTable">
                        <tr><td colspan="3" style="text-align:center; color:#9CA3AF;">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('sizeModal')">Tutup</button>
        </div>
    </div>
</div>

{{-- Modal Keluarkan Barang --}}
<div id="dispenseModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Keluarkan <span id="dispenseModalItemName" style="color:#D97706;"></span></h2>
            <button class="modal-close" onclick="closeModal('dispenseModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.warehouse-items.dispense') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>PILIH UKURAN</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="dispense_size_label">-- Pilih Ukuran --</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="options-scroll" id="dispenseSizeOptions">
                                    <!-- Options injected via JS -->
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="warehouse_item_size_id" id="dispenseSizeId" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>TANGGAL PENGELUARAN</label>
                    <input type="date" name="outflow_date" value="{{ date('Y-m-d') }}" required class="form-input">
                </div>
                <div class="form-group">
                    <label>JUMLAH KELUAR</label>
                    <input type="number" name="quantity" id="dispenseQuantity" min="1" placeholder="0" required class="form-input" oninput="validateDispenseStock()">
                    <p id="dispenseStockWarning" style="color: #DC2626; font-size: 12px; margin-top: 4px; display: none; font-weight: 600;">
                        <i class="ri-error-warning-line"></i> Jumlah melebihi stok tersedia! (Stok: <span id="currentMaxStock">0</span>)
                    </p>
                </div>
                <div class="form-group">
                    <label>SATKER PENERIMA (OPSIONAL)</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="dispense_satker_label">-- Pilih Satker --</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="select-search-container">
                                    <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterCustomOptions(this)">
                                </div>
                                <div class="options-scroll">
                                    <div class="option" data-label="Tanpa Satker" onclick="selectCustomOption(this, 'satker_id', '', '-- Pilih Satker --', 'dispense_satker_label')"><i class="ri-close-circle-line" style="margin-right: 6px; color: #9CA3AF;"></i> Kosongkan Pilihan</div>
                                    @foreach($satkers as $satker)
                                        <div class="option" data-label="{{ $satker->name }}" onclick="selectCustomOption(this, 'satker_id', '{{ $satker->id }}', '{{ $satker->name }}', 'dispense_satker_label')">{{ $satker->name }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="satker_id" id="satker_id">
                    </div>
                </div>
                <div class="form-group">
                    <label>NAMA PENERIMA (OPSIONAL)</label>
                    <input type="text" name="recipient_name" placeholder="Nama Personel Penerima" class="form-input">
                </div>
                <div class="form-group">
                    <label>STATUS SPPM</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="dispense_sppm_label">Belum Ada</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="options-scroll">
                                    <div class="option selected" onclick="selectCustomOption(this, 'reference_note', 'Belum Ada', 'Belum Ada', 'dispense_sppm_label')">Belum Ada</div>
                                    <div class="option" onclick="selectCustomOption(this, 'reference_note', 'Sudah Ada', 'Sudah Ada', 'dispense_sppm_label')">Sudah Ada</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="reference_note" id="reference_note" value="Belum Ada">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('dispenseModal')">Batal</button>
                <button type="submit" id="dispenseSubmitBtn" class="btn" style="background:#D97706; color:white;"><i class="ri-upload-cloud-2-line"></i> Keluarkan Data</button>
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

    function addSizeRow() {
        const row = `
            <div class="size-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: end;">
                <div>
                    <input type="text" name="sizes[]" class="form-input" placeholder="Ukuran" required>
                </div>
                <div>
                    <input type="number" name="quantities[]" class="form-input" placeholder="Jumlah" min="0" required>
                </div>
                <button type="button" class="btn-icon red" onclick="this.closest('.size-row').remove()" style="height: 44px; width: 44px;">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;
        document.getElementById('sizesContainer').insertAdjacentHTML('beforeend', row);
    }

    function openEditModal(item) {
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_unit').value = item.unit || 'PCS';
        document.getElementById('edit_price').value = parseInt(item.price) || 0;
        document.getElementById('editForm').action = "/admin/warehouse-items/" + item.id;
        openModal('editItemModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteItemName').innerText = name;
        document.getElementById('deleteForm').action = "/admin/warehouse-items/" + id;
        openModal('deleteModal');
    }

    // Modal Size Scripts
    let curWarehouseId = null;

    function openSizeModal(id, name) {
        curWarehouseId = id;
        document.getElementById('sizeModalItemName').textContent = name;
        document.getElementById('sizeAddError').style.display = 'none';
        document.getElementById('newSizeLabel').value = '';
        document.getElementById('newSizeStock').value = '';
        openModal('sizeModal');
        loadSizes();
    }

    function loadSizes() {
        let tbody = document.getElementById('sizeListTable');
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#9CA3AF;">Memuat...</td></tr>';
        
        fetch(`/admin/warehouse-items/${curWarehouseId}/sizes`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(sizes => {
            if(!sizes.length) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#6B7280;">Stok kosong. Silakan tambahkan ukuran.</td></tr>';
                return;
            }
            let html = '';
            sizes.forEach(s => {
                html += `
                    <tr>
                        <td style="padding: 10px 16px; font-weight: 600;">${s.size_label}</td>
                        <td style="padding: 10px 16px;">
                            <input type="number" id="stock_${s.id}" value="${s.stock}" class="form-input" style="width: 100px; padding: 6px 12px;">
                        </td>
                        <td style="padding: 10px 16px; text-align: right;">
                            <button onclick="updateSize(${s.id})" class="btn-icon blue d-inline-block" style="display:inline-flex; width:34px; height:34px;" title="Simpan Update">
                                <i class="ri-save-line"></i>
                            </button>
                            <button onclick="deleteSize(${s.id})" class="btn-icon red d-inline-block" style="display:inline-flex; width:34px; height:34px;" title="Hapus">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        });
    }

    function submitAddSize() {
        const label = document.getElementById('newSizeLabel').value.trim();
        const stock = document.getElementById('newSizeStock').value;
        const errEl = document.getElementById('sizeAddError');

        if (!label || !stock) {
            errEl.textContent = 'Harap isi Ukuran dan Kuantitas.';
            errEl.style.display = 'block';
            return;
        }
        
        errEl.style.display = 'none';
        
        fetch(`/admin/warehouse-items/${curWarehouseId}/sizes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ size_label: label, stock: stock })
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) { errEl.textContent = data.error || 'Gagal menyimpan.'; errEl.style.display='block'; return; }
            document.getElementById('newSizeLabel').value = '';
            document.getElementById('newSizeStock').value = '';
            loadSizes();
            // Refresh table behind implicitly or explicitly
            window.location.reload(); 
        })
        .catch(() => { errEl.textContent = 'Kesalahan server.'; errEl.style.display='block'; });
    }

    function updateSize(sizeId) {
        const newStock = document.getElementById(`stock_${sizeId}`).value;
        fetch(`/admin/warehouse-items/${curWarehouseId}/sizes/${sizeId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ stock: newStock })
        }).then(r => {
            if(r.ok) { window.location.reload(); } else { alert('Gagal update.'); }
        });
    }

    function deleteSize(sizeId) {
        if(!confirm('Hapus ukuran ini permanen?')) return;
        fetch(`/admin/warehouse-items/${curWarehouseId}/sizes/${sizeId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => {
            if(r.ok) { window.location.reload(); } else { alert('Gagal hapus.'); }
        });
    }

    let dispenseSizesData = [];

    function openDispenseModal(id, name) {
        document.getElementById('dispenseModalItemName').textContent = name;
        let optionsContainer = document.getElementById('dispenseSizeOptions');
        optionsContainer.innerHTML = '<div class="option" style="justify-content:center; color:#9ca3af">Memuat...</div>';
        document.getElementById('dispense_size_label').innerText = 'Memuat...';
        document.getElementById('dispenseStockWarning').style.display = 'none';
        document.getElementById('dispenseQuantity').value = '';
        document.getElementById('dispenseSizeId').value = '';
        openModal('dispenseModal');

        fetch(`/admin/warehouse-items/${id}/sizes`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(sizes => {
            dispenseSizesData = sizes;
            if(!sizes.length) {
                optionsContainer.innerHTML = '<div class="option" style="justify-content:center; color:#9ca3af">Tidak ada stok / ukuran</div>';
                document.getElementById('dispense_size_label').innerText = 'Tidak ada stok / ukuran';
                return;
            }
            let html = ``;
            sizes.forEach(s => {
                let dis = s.stock < 1 ? 'pointer-events: none; opacity: 0.5;' : '';
                html += `<div class="option" style="${dis}" onclick="selectDispenseSize('${s.id}', '${s.size_label}', ${s.stock}, this)">${s.size_label} <span style="font-size:12px;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:10px;">Stok: ${s.stock}</span></div>`;
            });
            optionsContainer.innerHTML = html;
            document.getElementById('dispense_size_label').innerText = '-- Pilih Ukuran --';
        });
    }

    function validateDispenseStock() {
        const inputSizeId = document.getElementById('dispenseSizeId');
        const qtyInput = document.getElementById('dispenseQuantity');
        const warning = document.getElementById('dispenseStockWarning');
        const maxStockLabel = document.getElementById('currentMaxStock');
        
        if (!inputSizeId || !inputSizeId.value) {
            warning.style.display = 'none';
            qtyInput.classList.remove('border-red');
            return;
        }

        const stock = parseInt(inputSizeId.getAttribute('data-stock')) || 0;
        const inputQty = parseInt(qtyInput.value) || 0;
        const submitBtn = document.getElementById('dispenseSubmitBtn');

        if (inputQty > stock || inputQty < 1) {
            maxStockLabel.textContent = stock;
            warning.style.display = 'block';
            qtyInput.style.borderColor = '#DC2626';
            qtyInput.style.backgroundColor = '#FEF2F2';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            warning.style.display = 'none';
            qtyInput.style.borderColor = '';
            qtyInput.style.backgroundColor = '';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    }

    // Custom Select Handlers
    function toggleDropdown(el) {
        const options = el.querySelector('.custom-options');
        const isOpen = options.style.display === 'block';

        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
        document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));

        if (!isOpen) {
            options.style.display = 'block';
            el.classList.add('active');
        } 
        event.stopPropagation();
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-options').forEach(opt => {
                opt.style.display = 'none';
            });
            document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));
        }
    });

    function selectCustomOption(el, inputId, value, label, labelId) {
        document.getElementById(inputId).value = value;
        document.getElementById(labelId).innerText = label;
        
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        if(event) event.stopPropagation();
    }

    function selectDispenseSize(value, label, stock, el = null) {
        document.getElementById('dispenseSizeId').value = value;
        document.getElementById('dispense_size_label').innerText = label + (stock > 0 ? ` (Stok: ${stock})` : '');
        document.getElementById('dispenseSizeId').setAttribute('data-stock', stock);
        
        if(el) {
            const wrapper = el.closest('.custom-select-wrapper');
            wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
            el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
            el.closest('.custom-select').classList.remove('active');
        } else {
             document.querySelectorAll('#dispenseSizeOptions .option').forEach(opt => opt.classList.remove('selected'));
        }
        
        validateDispenseStock();
        if(event) event.stopPropagation();
    }

    function filterCustomOptions(input) {
        const filter = input.value.toLowerCase();
        const optionsContainer = input.closest('.custom-options');
        const options = optionsContainer.querySelectorAll('.option');
        
        options.forEach(opt => {
            const text = (opt.dataset.label || '').toLowerCase();
            if (text.includes(filter)) {
                opt.style.display = 'flex';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    // File Input Handlers
    function handleFileSelect(input) {
        const file = input.files[0];
        const overlay = document.getElementById('fileSelectedInfo');
        const nameDisplay = document.getElementById('fileNameDisplay');
        
        if (file) {
            nameDisplay.textContent = file.name;
            overlay.style.display = 'flex';
        }
    }

    function resetFileInput(event) {
        event.preventDefault();
        event.stopPropagation();
        const input = document.getElementById('fileInput');
        const overlay = document.getElementById('fileSelectedInfo');
        input.value = '';
        overlay.style.display = 'none';
    }
</script>

@endsection

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    
    .stats-grid { display: grid; gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #F3F4F6; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; }
    .stat-number { font-size: 24px; font-weight: 800; color: #111827; }
    
    .icon-blue { background: #EFF6FF; color: #3B82F6; }
    .icon-green { background: #F0FDF4; color: #22C55E; }

    .table-container { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #F9FAFB; padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
    .user-table td { padding: 16px 24px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; color: #374151; font-size: 14px; }
    
    .action-buttons { display: flex; gap: 6px; justify-content: center; }
    .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; cursor: pointer; background: #fff; color:#4B5563;}
    .btn-icon:hover { background: #F9FAFB; }
    .btn-icon.blue:hover { color: #3B82F6; background: #EFF6FF; border-color: #BFDBFE;}
    .btn-icon.red:hover { color: #EF4444; background: #FEF2F2; border-color: #FECACA;}

    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 100; backdrop-filter: blur(4px); }
    .modal.open { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: #fff; border-radius: 16px; width: 90%; position: relative; animation: zoomIn 0.2s; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
    .modal-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0;}
    .modal-body { padding: 24px; }
    .modal-footer { padding: 20px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; }
    
    .modal-close { background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6B7280; }
    .modal-close:hover { background: #E5E7EB; color: #111827; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; transition: border-color .15s;}
    .form-input:focus { border-color: #3B82F6; ring: 2px solid #3B82F6; }

    /* Import Modal Improvements */
    .import-area { display: flex; flex-direction: column; gap: 20px; }
    .template-download-box { background: #F0F9FF; border: 2px dashed #BAE6FD; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; }
    .template-info { display: flex; align-items: center; gap: 12px; }
    .template-info i { font-size: 24px; color: #0EA5E9; background: white; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.1); }
    .template-title { font-weight: 700; color: #0369A1; margin: 0; font-size: 14px; }
    .template-desc { font-size: 12px; color: #0EA5E9; margin: 2px 0 0; }
    .btn-template { background: white; color: #0369A1; border: 1px solid #BAE6FD; height: 36px; font-size: 12px; padding: 0 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .btn-template:hover { background: #E0F2FE; border-color: #7DD3FC; transform: translateY(-1px); }

    .file-upload-wrapper { width: 100%; }
    .file-drop-zone { display: block; background: #F9FAFB; border: 2px dashed #D1D5DB; border-radius: 16px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.2s; position: relative; }
    .file-drop-zone:hover { background: #F3F4F6; border-color: #9CA3AF; }
    .file-drop-zone input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    
    .drop-zone-content { pointer-events: none; }
    .icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px; }
    .bg-blue-soft { background: #EFF6FF; color: #3B82F6; }
    .drop-text { font-weight: 700; color: #374151; font-size: 16px; margin: 0; }
    .drop-subtext { color: #6B7280; font-size: 12px; margin: 6px 0 0; }

    .file-info-overlay { position: absolute; inset: 0; background: #F0FDF4; border: 2px solid #22C55E; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 12px; z-index: 10; padding: 0 40px; }
    .text-green { color: #15803D; font-weight: 700; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .btn-remove-file { background: none; border: none; color: #EF4444; font-size: 20px; cursor: pointer; display: flex; align-items: center; transition: transform 0.2s; }
    .btn-remove-file:hover { transform: scale(1.1); }

    .import-instructions { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px; }
    .instruction-header { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 10px; }
    .instruction-header i { color: #64748B; font-size: 16px; }
    .instruction-badges { display: flex; flex-wrap: wrap; gap: 6px; }
    .badge { background: white; border: 1px solid #CBD5E1; color: #64748B; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px; font-family: monospace; }

    /* Filter Bar */
    .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 4px 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .filter-form { display: flex; align-items: center; width: 100%; }
    .search-input-wrapper { flex: 1; position: relative; display: flex; align-items: center; padding-right:12px;}
    .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
    .search-field { width: 100%; height: 44px; border: none; border-radius: 8px; padding: 0 16px 0 44px; font-size: 14px; color: #374151; outline: none; background: transparent; }
    .search-field::placeholder { color: #9CA3AF; }

    /* Custom Select */
    .custom-select-wrapper { position: relative; width: 100%; }
    
    .custom-select {
        background: #fff;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
        height: 48px;
        display: flex; align-items: center;
    }
    .custom-select:hover { border-color: #9CA3AF; }
    .custom-select.active {
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
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
    .select-trigger i { color: #6B7280; font-size: 20px; transition: transform 0.2s ease; }
    .custom-select.active .select-trigger { color: #111827; }
    .custom-select.active .select-trigger i { transform: rotate(180deg); color: #3B82F6; }

    /* Dropdown UI */
    .custom-options {
        position: absolute; top: calc(100% + 8px); left: 0; right: 0;
        background: #fff; border: 1px solid #F3F4F6; border-radius: 12px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); z-index: 2000;
        display: none; flex-direction: column; padding: 8px;
    }
    .custom-select.dropup .custom-options {
        top: auto; bottom: calc(100% + 8px);
        box-shadow: 0 -10px 40px -10px rgba(0,0,0,0.1);
    }
    .options-scroll { max-height: 240px; overflow-y: auto; padding-right: 2px; }
    .options-scroll::-webkit-scrollbar { width: 4px; }
    .options-scroll::-webkit-scrollbar-track { background: transparent; }
    .options-scroll::-webkit-scrollbar-thumb { background-color: #E5E7EB; border-radius: 10px; }
    .options-scroll::-webkit-scrollbar-thumb:hover { background-color: #D1D5DB; }

    /* Option Item UI */
    .option {
        padding: 10px 12px; cursor: pointer; transition: all 0.15s; font-size: 14px;
        color: #4B5563; border-radius: 8px; margin-bottom: 2px; font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }
    .option:last-child { margin-bottom: 0; }
    .option:hover { background-color: #F9FAFB; color: #111827; }
    .option.selected { background-color: #FEF2F2; color: #B91C1C; }

    /* Select Search UI */
    .select-search-container {
        padding: 4px; position: sticky; top: 0; background: #fff; z-index: 10;
        border-bottom: 1px solid #F3F4F6; margin-bottom: 4px;
    }
    .select-search-input {
        width: 100%; height: 36px; padding: 0 12px; border: 1px solid #E5E7EB;
        border-radius: 8px; font-size: 13px; outline: none; background: #F9FAFB; transition: all 0.2s;
    }
    .select-search-input:focus { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 3px rgba(185,28,28,0.1); }
    
    /* New Interactive Buttons */
    .page-header-actions { display: flex; gap: 10px; align-items: center; }
    .btn-content { display: flex; align-items: center; gap: 8px; }
    
    .btn { cursor: pointer; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; height: 42px; padding: 0 18px; }
    .btn::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.1); opacity: 0; transition: opacity 0.2s; }
    .btn:hover::after { opacity: 1; }
    .btn:active { transform: scale(0.96); }

    .btn-import { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2), 0 2px 4px -2px rgba(16, 185, 129, 0.1); }
    .btn-import:hover { box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3), 0 4px 6px -4px rgba(16, 185, 129, 0.2); transform: translateY(-2px); }

    .btn-export { background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); color: white; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2), 0 2px 4px -2px rgba(99, 102, 241, 0.1); }
    .btn-export:hover { box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3), 0 4px 6px -4px rgba(99, 102, 241, 0.2); transform: translateY(-2px); }
    .btn-export .arrow-icon { transition: transform 0.2s; }
    .dropdown-container.open .btn-export .arrow-icon { transform: rotate(180deg); }

    .btn-add { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: white; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2), 0 2px 4px -2px rgba(239, 68, 68, 0.1); }
    .btn-add:hover { box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3), 0 4px 6px -4px rgba(239, 68, 68, 0.2); transform: translateY(-2px); }

    /* Dropdown Enhancements */
    .dropdown-container { position: relative; }
    .dropdown-menu { position: absolute; top: calc(100% + 8px); right: 0; background: white; border-radius: 12px; min-width: 200px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #F3F4F6; z-index: 50; display: none; opacity: 0; transform: translateY(-10px); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; }
    .dropdown-container.open .dropdown-menu { display: block; opacity: 1; transform: translateY(0); pointer-events: auto; }

    .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: #4B5563; font-size: 14px; text-decoration: none; transition: all 0.15s; border-radius: 8px; margin: 4px 8px; }
    .dropdown-item:hover { background: #F9FAFB; color: #111827; }
    .dropdown-item i { font-size: 18px; }
    .excel-icon { color: #10B981; }
    .pdf-icon { color: #EF4444; }

    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .dropdown-container.d-inline-block { display: inline-block; position: relative; }
</style>
@endsection
