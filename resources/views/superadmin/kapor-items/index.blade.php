@extends('layouts.app')

@section('title', 'Item Identifikasi Kebutuhan')
@section('breadcrumb', 'Item Identifikasi Kebutuhan')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Item Identifikasi Kebutuhan</h1>
            <p class="page-subtitle">Manajemen data item kapor khusus yang tersedia untuk form identifikasi kebutuhan</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="openModal('addItemModal')">
                <i class="ri-add-line"></i> Tambah Item Baru
            </button>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-file-list-3-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL ITEM KAPOR</span>
            <span class="stat-number">{{ number_format($stats['total']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TERSEDIA IDENTIFIKASI</span>
            <span class="stat-number">{{ number_format($stats['for_identifikasi']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-check-double-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL AKTIF</span>
            <span class="stat-number">{{ number_format($stats['aktif']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange" style="background: #FEE2E2; color: #DC2626;">
            <i class="ri-close-circle-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL NON-AKTIF</span>
            <span class="stat-number">{{ number_format($stats['nonaktif']) }}</span>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px;">
        <i class="ri-checkbox-circle-line" style="margin-right: 6px;"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px;">
        <i class="ri-error-warning-line" style="margin-right: 6px;"></i> {{ session('error') }}
    </div>
@endif

{{-- Filter Form --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('superadmin.kapor-items.index') }}" class="filter-form">
        <div class="search-input-wrapper" style="flex: 2;">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama item..." class="search-field" autocomplete="off">
        </div>
        
        <div class="filter-divider"></div>

        <div class="custom-select-wrapper" style="flex: 1;">
            <select name="category" class="form-input" style="height: 44px; border: none; background: transparent; padding-left: 10px; appearance: auto;" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-divider"></div>

        <div class="custom-select-wrapper" style="flex: 1;">
            <select name="status" class="form-input" style="height: 44px; border: none; background: transparent; padding-left: 10px; appearance: auto;" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="identifikasi" {{ request('status') == 'identifikasi' ? 'selected' : '' }}>Tersedia Identifikasi</option>
                <option value="non_identifikasi" {{ request('status') == 'non_identifikasi' ? 'selected' : '' }}>Tdk Tersedia Identifikasi</option>
                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Master Aktif</option>
                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Master Non-Aktif</option>
            </select>
        </div>
        
        @if(request('search'))
            <div style="padding: 0 10px;">
                <a href="{{ route('superadmin.kapor-items.index') }}" class="btn btn-ghost" style="color: #6B7280;"><i class="ri-close-line"></i> Reset</a>
            </div>
        @endif
        
        <button type="submit" style="display: none;"></button>
    </form>
</div>

{{-- Table --}}
<div class="table-container">
    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    <th style="width: 60px;">NO</th>
                    <th>NAMA ITEM & DESKRIPSI</th>
                    <th>KATEGORI</th>
                    <th>IDENTIFIKASI KEBUTUHAN</th>
                    <th>MASTER AKTIF</th>
                    <th style="text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                <tr style="{{ !$item->is_active ? 'opacity: 0.6; background: #F9FAFB;' : '' }}">
                    <td>{{ $items->firstItem() + $index }}</td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 600; color: #111827;">{{ $item->item_name }}</span>
                            @if($item->description)
                                <span style="font-size: 12px; color: #6B7280;">{{ $item->description }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="role-pill" style="
                            background: {{ $item->category == 'Tutup_Kepala' ? '#DBEAFE' : ($item->category == 'Tutup_Badan' ? '#F3E8FF' : ($item->category == 'Tutup_Kaki' ? '#FFEDD5' : '#F0FDF4')) }};
                            color: {{ $item->category == 'Tutup_Kepala' ? '#1E40AF' : ($item->category == 'Tutup_Badan' ? '#6B21A8' : ($item->category == 'Tutup_Kaki' ? '#9A3412' : '#166534')) }};
                        ">
                            {{ str_replace('_', ' ', $item->category) }}
                        </span>
                    </td>
                    <td>
                        <form action="{{ route('superadmin.kapor-items.toggle-identifikasi', $item->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" title="{{ $item->for_identifikasi ? 'Matikan untuk identifikasi' : 'Aktifkan untuk identifikasi' }}" style="border: none; background: none; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                @if($item->for_identifikasi)
                                    <i class="ri-toggle-fill" style="font-size: 24px; color: #10B981; line-height: 1;"></i>
                                    <span style="font-size: 12px; color: #10B981; font-weight: 600;">Tersedia</span>
                                @else
                                    <i class="ri-toggle-line" style="font-size: 24px; color: #9CA3AF; line-height: 1;"></i>
                                    <span style="font-size: 12px; color: #6B7280; font-weight: 500;">Tidak Tersedia</span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td>
                        <span class="role-pill" style="
                            background: {{ $item->is_active ? '#DCFCE7' : '#FEE2E2' }};
                            color: {{ $item->is_active ? '#166534' : '#991B1B' }};
                        ">
                            {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon blue" onclick="openEditModal({{ json_encode($item) }})" title="Edit Item">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="btn-icon red" onclick="confirmDelete({{ $item->id }}, '{{ addslashes($item->item_name) }}')" title="Hapus Item">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 48px; color: #9CA3AF;">
                        <i class="ri-inbox-line" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                        Belum ada data item.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($items->total() > 0)
        <div class="table-footer">
            <div class="footer-left">
                Menampilkan {{ $items->firstItem() ?? 0 }} hingga {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
            </div>
            
            <div class="footer-right">
                <div class="pagination-controls">
                    <a href="{{ $items->url(1) }}" class="page-btn {{ $items->onFirstPage() ? 'disabled' : '' }}" title="Halaman Pertama">
                        <i class="ri-double-left-line"></i>
                    </a>
                    <a href="{{ $items->previousPageUrl() }}" class="page-btn {{ $items->onFirstPage() ? 'disabled' : '' }}" title="Halaman Sebelumnya">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <span class="page-info">Halaman <strong>{{ $items->currentPage() }}</strong> dari <strong>{{ $items->lastPage() }}</strong></span>
                    <a href="{{ $items->nextPageUrl() }}" class="page-btn {{ !$items->hasMorePages() ? 'disabled' : '' }}" title="Halaman Selanjutnya">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <a href="{{ $items->url($items->lastPage()) }}" class="page-btn {{ !$items->hasMorePages() ? 'disabled' : '' }}" title="Halaman Terakhir">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Add Modal --}}
<div id="addItemModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Item Baru</h2>
            <button class="modal-close" onclick="closeModal('addItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('superadmin.kapor-items.store') }}" method="POST">
            @csrf
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label>NAMA ITEM</label>
                    <input type="text" name="item_name" required class="form-input" placeholder="Contoh: Baju PDL">
                </div>
                <div class="form-group">
                    <label>KATEGORI</label>
                    <div class="custom-select-wrapper">
                        <select name="category" class="form-input" required style="appearance: auto;">
                            <option value="">-- Pilih --</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>DESKRIPSI (opsional)</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Deskripsi item..."></textarea>
                </div>
                
                <div class="form-group" style="margin-top: 16px; background: #F3F4F6; padding: 12px; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; text-transform: none; font-size: 13px;">
                        <input type="checkbox" name="for_identifikasi" value="1" checked style="width: 16px; height: 16px;">
                        <span style="font-weight: 600; color: #374151;">Langsung sediakan untuk form Identifikasi Kebutuhan</span>
                    </label>
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
            <h2 class="modal-title">Edit Item</h2>
            <button class="modal-close" onclick="closeModal('editItemModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label>NAMA ITEM</label>
                    <input type="text" name="item_name" id="edit_item_name" required class="form-input">
                </div>
                <div class="form-group">
                    <label>KATEGORI</label>
                    <div class="custom-select-wrapper">
                        <select name="category" id="edit_category" class="form-input" required style="appearance: auto;">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>DESKRIPSI</label>
                    <textarea name="description" id="edit_description" class="form-input" rows="3"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                    <div class="form-group" style="background: #F3F4F6; padding: 12px; border-radius: 8px; margin: 0;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; text-transform: none; font-size: 13px;">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width: 16px; height: 16px;">
                            <span style="font-weight: 600; color: #374151;">Master Item Aktif</span>
                        </label>
                    </div>
                    
                    <div class="form-group" style="background: #F3F4F6; padding: 12px; border-radius: 8px; margin: 0;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; text-transform: none; font-size: 13px;">
                            <input type="checkbox" name="for_identifikasi" id="edit_for_identifikasi" value="1" style="width: 16px; height: 16px;">
                            <span style="font-weight: 600; color: #374151;">Gunakan utk Identifikasi</span>
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
                <p style="font-size: 12px; color: #EF4444; margin-top: 8px;">Perhatian: Item ini akan terhapus. Item yang sudah dipakai dalam pengajuan tidak dapat dihapus.</p>
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
        document.getElementById('edit_description').value = item.description || '';
        document.getElementById('edit_is_active').checked = item.is_active;
        document.getElementById('edit_for_identifikasi').checked = item.for_identifikasi;

        document.getElementById('editForm').action = "{{ url('superadmin/kapor-items') }}/" + item.id;
        openModal('editItemModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteItemName').innerText = name;
        document.getElementById('deleteForm').action = "{{ url('superadmin/kapor-items') }}/" + id;
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
<style>
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

    .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 4px 6px; margin-bottom: 24px; }
    .filter-form { display: flex; align-items: center; width: 100%; }
    .search-input-wrapper { display: flex; align-items: center; position: relative; }
    .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
    .search-field { width: 100%; height: 44px; border: none; padding: 0 16px 0 44px; font-size: 14px; outline: none; background: transparent; }
    .filter-divider { width: 1px; height: 24px; background: #E5E7EB; margin: 0 8px; }
    
    .table-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; background: #fff; }
    .footer-left { font-size: 13px; color: #6B7280; display: flex; align-items: center; }
    .footer-right { display: flex; align-items: center; }
    .pagination-controls { display: flex; align-items: center; gap: 8px; background: #F9FAFB; padding: 6px; border-radius: 10px; border: 1px solid #F3F4F6; }
    .page-btn { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #fff; border: 1px solid #E5E7EB; color: #374151; text-decoration: none; transition: all 0.2s; font-size: 14px; }
    .page-btn:hover:not(.disabled) { background: #3B82F6; color: #fff; border-color: #3B82F6; }
    .page-btn.disabled { opacity: 0.5; pointer-events: none; background: #F3F4F6; }
    .page-info { font-size: 13px; color: #4B5563; padding: 0 12px; }
    
    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endsection
