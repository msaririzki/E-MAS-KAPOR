@extends('layouts.app')

@section('title', 'Personel')
@section('breadcrumb', 'Personel')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Personel</h1>
            <p class="page-subtitle">Direktorii personel dan informasi kapor</p>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 10px;">
            {{-- Hapus per Satker --}}
            <button class="btn" onclick="openModal('bulkDeleteModal')" style="background: var(--danger); color: white;">
                <i class="ri-delete-bin-line"></i> Hapus Satker
            </button>

            {{-- Impor Excel --}}
            <button class="btn" onclick="openModal('importModal')" style="background: var(--slate-600); color: white;">
                <i class="ri-file-upload-line"></i> Impor Excel
            </button>

            {{-- Custom Dropdown Rekap --}}
            <div class="dropdown-container" id="rekapDropdown" style="position: relative;">
                <button type="button" class="btn btn-primary" onclick="this.parentElement.classList.toggle('open')">
                    <span style="display: flex; align-items: center; gap: 6px;"><i class="ri-file-excel-2-line"></i> Rekap</span>
                    <i class="ri-arrow-down-s-line" style="margin-left: 4px;"></i>
                </button>
                <div class="dropdown-menu" style="width: 240px; right: 0; left: auto; max-height: 480px; overflow-y: auto;">
                    {{-- Section: Tutup Kepala --}}
                    <div class="px-3 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        Tutup Kepala
                    </div>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kepala', 'TOPI LAPANGAN')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Topi Lapangan
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kepala', 'PET')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Pet
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kepala', 'BARET')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Baret
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kepala', 'PECI')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Peci
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kepala', 'JILBAB')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Jilbab
                    </button>

                    {{-- Section: Tutup Badan --}}
                    <div class="px-3 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50 border-y border-gray-100 mt-1">
                        Tutup Badan
                    </div>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Badan', 'Kemeja')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Kemeja
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Badan', 'Celana')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Celana/Rok
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Badan', 'Jaket')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Jaket
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Badan', 'Olahraga')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> T-Shirt/Olahraga
                    </button>

                    {{-- Section: Tutup Kaki --}}
                    <div class="px-3 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider bg-gray-50 border-y border-gray-100 mt-1">
                        Tutup Kaki
                    </div>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kaki', 'Sepatu Dinas')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Sepatu Dinas
                    </button>
                    <button class="dropdown-item" onclick="exportKapor('Tutup_Kaki', 'Sepatu Olahraga')">
                        <i class="ri-checkbox-blank-circle-line" style="font-size: 12px;"></i> Sepatu Olahraga
                    </button>
                </div>
            </div>

            {{-- Cetak Satker --}}
            <button class="btn btn-success" onclick="openModal('printSatkerModal')">
                <i class="ri-printer-line"></i> Cetak Satker
            </button>

            {{-- Tambah Personel --}}
            <button class="btn btn-primary" onclick="openModal('addPersonnelModal')">
                <i class="ri-user-add-line"></i> Tambah
            </button>
        </div>
    </div>
</div>

<script>
    // Close rekap dropdown when clicking outside
    document.addEventListener('click', function(e) {
        var dd = document.getElementById('rekapDropdown');
        if (dd && !dd.contains(e.target)) dd.classList.remove('open');
    });

    function exportKapor(category, item) {
        // Construct the URL
        const url = "{{ route('admin.personnel.export-rekap') }}?category=" + category + "&item=" + encodeURIComponent(item);
        
        // Trigger download
        window.location.href = url;
        
        // Close dropdown
        document.getElementById('rekapDropdown').classList.remove('open');
    }
</script>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-group-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL RILL PERSONEL</span>
            <span class="stat-number">{{ number_format($stats['total_real']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">SUDAH INPUT DATA</span>
            <span class="stat-number">{{ number_format($stats['submitted']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-red">
            <i class="ri-close-circle-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">BELUM INPUT DATA</span>
            <span class="stat-number">{{ number_format($stats['pending']) }}</span>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.personnel.index') }}" class="filter-form" id="filterForm">
        <div class="search-container" style="flex: 2;">
            <i class="ri-search-line"></i>
            <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari berdasarkan nama, NRP/NIP, atau golongan..." oninput="debounceSearch()">
            @if(request('search'))
                <button type="button" class="clear-search" onclick="document.getElementById('searchInput').value=''; document.getElementById('filterForm').submit();" style="background: none; border: none; color: #D1D5DB; cursor: pointer; padding: 4px; display: flex; align-items: center; margin-left: 8px;">
                    <i class="ri-close-circle-fill" style="font-size: 18px;"></i>
                </button>
            @endif
        </div>

        <div class="filter-group">
            <div class="custom-select-wrapper" style="flex: 1;">
                <div class="custom-select" onclick="toggleDropdown(this)">
                    <div class="select-trigger">
                        <span>{{ request('rank_id') ? $ranks->firstWhere('id', request('rank_id'))->name : 'Semua Pangkat' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            <div class="option {{ !request('rank_id') ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'rank_id', '', 'Semua Pangkat')">Semua Pangkat</div>
                            @foreach($ranks as $rank)
                                <div class="option {{ request('rank_id') == $rank->id ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'rank_id', '{{ $rank->id }}', '{{ $rank->name }}')">{{ $rank->name }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="rank_id" value="{{ request('rank_id') }}">
            </div>

            <div class="custom-select-wrapper" style="flex: 1;">
                <div class="custom-select" onclick="toggleDropdown(this)">
                    <div class="select-trigger">
                        <span>{{ request('satker_id') ? $satkers->firstWhere('id', request('satker_id'))->name : 'Semua Satker' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="select-search-container">
                            <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterSatkerOptions(this)">
                        </div>
                        <div class="options-scroll">
                            <div class="option {{ !request('satker_id') ? 'selected' : '' }}" data-label="SEMUA SATKER" onclick="selectOptionSearch(this, 'satker_id', '', 'SEMUA SATKER')">SEMUA SATKER</div>
                            @foreach($satkers as $satker)
                                <div class="option {{ request('satker_id') == $satker->id ? 'selected' : '' }}" data-label="{{ $satker->name }}" onclick="selectOptionSearch(this, 'satker_id', '{{ $satker->id }}', '{{ $satker->name }}')">{{ $satker->name }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="satker_id" value="{{ request('satker_id') }}">
            </div>
        </div>

        <div>
            <button type="button" class="btn" style="background: var(--slate-600); color: white;" onclick="window.location.href=updateQueryStringParameter(window.location.href, 'export', '1')">
                <i class="ri-download-line"></i> Ekspor
            </button>
        </div>
    </form>
</div>

{{-- Data Table --}}
<div class="table-container">
    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    @php
                        $sort = request('sort');
                        $dir = request('direction', 'desc');
                        $nextDir = $dir === 'asc' ? 'desc' : 'asc';
                        $iconClass = $dir === 'asc' ? 'ri-sort-asc' : 'ri-sort-desc';
                    @endphp
                    <th style="border-top-left-radius: 12px; cursor: pointer;" onclick="window.location.href=updateSort('full_name', '{{ $sort == 'full_name' ? $nextDir : 'asc' }}')">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            PERSONEL 
                            @if($sort == 'full_name')
                                <i class="{{ $iconClass }}" style="color: var(--accent); font-size: 16px;"></i>
                            @else
                                <i class="ri-expand-up-down-line" style="opacity: 0.3; font-size: 14px;"></i>
                            @endif
                        </div>
                    </th>
                    <th style="cursor: pointer;" onclick="window.location.href=updateSort('rank', '{{ $sort == 'rank' ? $nextDir : 'asc' }}')">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            PANGKAT / GOL
                            @if($sort == 'rank')
                                <i class="{{ $iconClass }}" style="color: var(--accent); font-size: 16px;"></i>
                            @else
                                <i class="ri-expand-up-down-line" style="opacity: 0.3; font-size: 14px;"></i>
                            @endif
                        </div>
                    </th>
                    <th style="cursor: pointer;" onclick="window.location.href=updateSort('jabatan', '{{ $sort == 'jabatan' ? $nextDir : 'asc' }}')">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            JABATAN / BAGIAN
                            @if($sort == 'jabatan')
                                <i class="{{ $iconClass }}" style="color: var(--accent); font-size: 16px;"></i>
                            @else
                                <i class="ri-expand-up-down-line" style="opacity: 0.3; font-size: 14px;"></i>
                            @endif
                        </div>
                    </th>
                    <th style="cursor: pointer;" onclick="window.location.href=updateSort('satker', '{{ $sort == 'satker' ? $nextDir : 'asc' }}')">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            SATUAN KERJA
                            @if($sort == 'satker')
                                <i class="{{ $iconClass }}" style="color: var(--accent); font-size: 16px;"></i>
                            @else
                                <i class="ri-expand-up-down-line" style="opacity: 0.3; font-size: 14px;"></i>
                            @endif
                        </div>
                    </th>
                    <th style="border-top-right-radius: 12px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnels as $p)
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar" style="background-color: {{ ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'][ord($p->full_name[0]) % 7] }};">
                                {{ strtoupper(substr($p->full_name, 0, 1)) }}
                            </div>
                            <div class="details">
                                <span class="name">{{ $p->full_name }}</span>
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <span class="nrp">{{ $p->nrp }}</span>
                                    <i class="ri-file-copy-line icon-copy" title="Salin NRP" onclick="copyToClipboard('{{ $p->nrp }}')"></i>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <span style="font-weight: 500; color: #111827; font-size: 13px;">{{ $p->rank->name ?? '—' }}</span>
                            <span style="font-size: 12px; color: #6B7280;">{{ $p->golongan ?? $p->rank->category ?? '—' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <span style="font-weight: 500; color: #111827; font-size: 13px;">{{ $p->jabatan ?? '—' }}</span>
                            <span style="font-size: 12px; color: #6B7280;">{{ $p->bagian ?? '—' }}</span>
                        </div>
                    </td>
                    <td>
                        <span style="font-size: 13px; color: #4B5563; font-weight: 500;">{{ $p->satker->name ?? '—' }}</span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon green" onclick="openDetailModal({{ json_encode($p) }})" title="Lihat Detail & Ukuran">
                                <i class="ri-eye-line"></i>
                            </button>
                            <button class="btn-icon blue" onclick="openEditModal({{ json_encode($p) }})" title="Edit Data">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="btn-icon red" title="Hapus Data" onclick="confirmDelete({{ $p->id }}, '{{ $p->full_name }}')">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 48px; color: #9CA3AF;">
                        <i class="ri-user-unfollow-line" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                        Belum ada data personel ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($personnels->total() > 0)
        <div class="table-footer">
            <div class="footer-left">
                Menampilkan {{ $personnels->firstItem() ?? 0 }} hingga {{ $personnels->lastItem() ?? 0 }} dari {{ $personnels->total() }} data
                <div class="per-page-selector" style="margin-left: 12px;">
                    <div class="custom-select-wrapper" style="min-width: 80px;">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger" style="height: 34px; padding: 0 10px; font-size: 13px;">
                                <span>{{ $perPage }}</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options" style="background: #fff !important; bottom: calc(100% + 8px); top: auto;">
                                <div class="options-scroll">
                                    <div class="option {{ $perPage == 10 ? 'selected' : '' }}" onclick="window.location.href=updateQueryStringParameter(window.location.href, 'per_page', '10')">10</div>
                                    <div class="option {{ $perPage == 25 ? 'selected' : '' }}" onclick="window.location.href=updateQueryStringParameter(window.location.href, 'per_page', '25')">25</div>
                                    <div class="option {{ $perPage == 50 ? 'selected' : '' }}" onclick="window.location.href=updateQueryStringParameter(window.location.href, 'per_page', '50')">50</div>
                                    <div class="option {{ $perPage == 100 ? 'selected' : '' }}" onclick="window.location.href=updateQueryStringParameter(window.location.href, 'per_page', '100')">100</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="pagination-controls">
                    <a href="{{ $personnels->url(1) }}" class="page-btn {{ $personnels->onFirstPage() ? 'disabled' : '' }}">
                        <i class="ri-double-left-line"></i>
                    </a>
                    <a href="{{ $personnels->previousPageUrl() }}" class="page-btn {{ $personnels->onFirstPage() ? 'disabled' : '' }}">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    <span class="page-info">Halaman <strong>{{ $personnels->currentPage() }}</strong> dari <strong>{{ $personnels->lastPage() }}</strong></span>
                    <a href="{{ $personnels->nextPageUrl() }}" class="page-btn {{ !$personnels->hasMorePages() ? 'disabled' : '' }}">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <a href="{{ $personnels->url($personnels->lastPage()) }}" class="page-btn {{ !$personnels->hasMorePages() ? 'disabled' : '' }}">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Bulk Delete Personnel Modal --}}
<div id="bulkDeleteModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #DC2626;">Hapus Seluruh Data per Satker</h3>
            <button class="modal-close" onclick="closeModal('bulkDeleteModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form action="{{ route('admin.personnel.bulk-delete') }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-body" style="padding: 24px;">
                <div style="background: #FEF2F2; border: 1px solid #FEE2E2; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 12px;">
                        <i class="ri-error-warning-line" style="font-size: 24px; color: #DC2626;"></i>
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #991B1B; margin-bottom: 4px;">Peringatan Keamanan</h4>
                            <p style="font-size: 13px; color: #B91C1C; line-height: 1.5;">Tindakan ini akan menghapus <strong>SELURUH</strong> data personil, akun login, dan riwayat ukuran kapor pada Satker yang dipilih. Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 700; color: #374151;">Pilih Satuan Kerja (Satker) <span style="color: #EF4444;">*</span></label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="bulk_delete_satker_label">-- Pilih Satker --</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="select-search-container">
                                    <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterSatkerOptions(this)">
                                </div>
                                <div class="options-scroll">
                                    @foreach($satkers as $s)
                                        <div class="option" data-value="{{ $s->id }}" data-label="{{ $s->name }}" onclick="selectSatkerOptionSimple(this, 'bulk_delete')">{{ $s->name }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="satker_id" id="bulk_delete_satker_id" required>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; color: #374151;">Konfirmasi Penghapusan <span style="color: #EF4444;">*</span></label>
                    <p style="font-size: 12px; color: #6B7280; margin-bottom: 8px;">Ketik kata <strong>HAPUS</strong> di bawah ini untuk mengonfirmasi.</p>
                    <input type="text" name="confirm_text" required placeholder="Ketik HAPUS" class="form-input" style="padding: 10px;">
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: #F9FAFB; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('bulkDeleteModal')">Batal</button>
                <button type="submit" class="btn" style="background: #DC2626; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <i class="ri-delete-bin-line"></i> Hapus Semua Data
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Import Personnel Modal --}}
<div id="importModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #111827;">Impor Data Personel & Ukuran Kapor</h3>
            <button class="modal-close" onclick="closeModal('importModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form action="{{ route('admin.personnel.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 700; color: #374151;">PILIH SATUAN KERJA (SATKER) TUJUAN <span style="color: #EF4444;">*</span></label>
                    
                    <div class="custom-search-select" style="position: relative; margin-top: 8px;">
                        <input type="text" id="import_satker_input" class="form-input" placeholder="-- Ketik atau Pilih Satker --" autocomplete="off" style="width: 100%; padding: 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-weight: 500; color: #374151; cursor: pointer; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394A3B8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 14px center; background-size: 10px;" onfocus="openCustomSatkerDropdown()" onkeyup="filterCustomSatkerDropdown()" onclick="openCustomSatkerDropdown()">
                        
                        <div id="import_satker_dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; margin-top: 4px; max-height: 250px; overflow-y: auto; z-index: 1050; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                            @foreach($satkers as $s)
                                <div class="custom-dropdown-option" data-id="{{ $s->id }}" data-name="{{ $s->name }}" onclick="selectCustomSatker('{{ $s->id }}', '{{ $s->name }}')" style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #F8FAFC; font-size: 13px; font-weight: 500; color: #475569; transition: background 0.15s, color 0.15s;" onmouseover="this.style.background='#F1F5F9'; this.style.color='#0F172A'" onmouseout="this.style.background='white'; this.style.color='#475569'">{{ $s->name }}</div>
                            @endforeach
                        </div>
                        
                        <input type="hidden" name="satker_id" id="import_satker_id" required>
                    </div>

                    <p style="font-size: 12px; color: #6B7280; margin-top: 4px;">Pilih satker yang sesuai dengan isi file yang akan diimport.</p>
                </div>
                
                <script>
                    function openCustomSatkerDropdown() {
                        const dropdown = document.getElementById('import_satker_dropdown');
                        dropdown.style.display = 'block';
                        filterCustomSatkerDropdown();
                    }
                    
                    function filterCustomSatkerDropdown() {
                        const input = document.getElementById('import_satker_input');
                        const filterVal = input.value.toUpperCase();
                        const dropdown = document.getElementById('import_satker_dropdown');
                        const options = dropdown.querySelectorAll('.custom-dropdown-option');
                        let hasVisible = false;

                        options.forEach(opt => {
                            const txtValue = opt.getAttribute('data-name').toUpperCase();
                            if (txtValue.indexOf(filterVal) > -1) {
                                opt.style.display = 'block';
                                hasVisible = true;
                            } else {
                                opt.style.display = 'none';
                            }
                        });

                        dropdown.style.display = hasVisible ? 'block' : 'none';
                        
                        const hiddenId = document.getElementById('import_satker_id');
                        if(input.value.trim() === "") {
                            hiddenId.value = '';
                        }
                    }

                    function selectCustomSatker(id, name) {
                        document.getElementById('import_satker_input').value = name;
                        document.getElementById('import_satker_id').value = id;
                        document.getElementById('import_satker_dropdown').style.display = 'none';
                    }

                    document.addEventListener('click', function(e) {
                        const container = document.querySelector('.custom-search-select');
                        const dropdown = document.getElementById('import_satker_dropdown');
                        if (container && !container.contains(e.target) && dropdown) {
                            dropdown.style.display = 'none';
                        }
                    });
                </script>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="font-weight: 700; color: #374151;">Pilih File Excel/CSV Hasil Pengisian <span style="color: #EF4444;">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input" style="padding: 12px; border: 2px dashed #E5E7EB; background: #F9FAFB;">
                    <p style="font-size: 12px; color: #6B7280; margin-top: 8px;">Format yang didukung: .xlsx, .xls, atau .csv</p>
                </div>

                <div style="background: #FFF7ED; border: 1px solid #FFEDD5; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 12px;">
                        <i class="ri-information-line" style="font-size: 24px; color: #F97316;"></i>
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #9A3412; margin-bottom: 4px;">Instruksi Format Import</h4>
                            <p style="font-size: 13px; color: #C2410C; line-height: 1.5;">Setelah upload, sistem akan menampilkan <strong>halaman preview</strong> untuk memeriksa data sebelum disimpan. Pangkat yang mengandung typo akan dikoreksi otomatis dan dapat diedit manual jika diperlukan.</p>
                            <a href="{{ route('admin.personnel.template') }}" class="btn btn-primary" style="background: #B91C1C; margin-top: 12px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="ri-download-cloud-2-line"></i> Unduh Tabel Format Import (Excel)
                            </a>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <h5 style="font-size: 14px; font-weight: 700; color: #374151; margin-bottom: 12px;">Struktur Tabel Excel (Data mulai baris 11):</h5>
                    <div style="overflow-x: auto; border: 1px solid #E5E7EB; border-radius: 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                            <thead>
                                <tr style="background: #F9FAFB;">
                                    <th style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Kolom</th>
                                    <th style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Wajib</th>
                                    <th style="padding: 10px; border-bottom: 1px solid #E5E7EB;">Isi / Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">NAMA LENGKAP</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Tanpa gelar (Contoh: BUDI SANTOSO) - <strong>Kolom B</strong>.</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">PANGKAT</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Gunakan singkatan standar (Contoh: BRIPDA, AKP, KOMBES POL, PENATA) - <strong>Kolom C</strong>.</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">GOLONGAN</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Isi kategori pangkat (Contoh: PATI, PAMEN, PAMA, BINTARA, IV/a, III/b) - <strong>Kolom D</strong>.</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">NRP / NIP</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Identitas unik. Wajib diisi benar karena akan menjadi <strong>username & password</strong> login personel - <strong>Kolom E</strong>.</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">JABATAN</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Nama jabatan resmi (Contoh: KANIT, BANUM, dsb) - <strong>Kolom F</strong>.</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">BAG / FUNGSI</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; color: #EF4444;">Ya</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">
                                        Satuan fungsi (Contoh: BAG PAL, BAG ADA, dsb) - <strong>Kolom G</strong>. 
                                        <div style="margin-top: 4px; font-size: 11px; color: #6B7280; font-style: italic;">Note: Diisi sesuai KEP Penempatan karena berpengaruh pada jenis kapor yang diterima.</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">JENIS KELAMIN</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Tidak</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Isi dengan <strong>P</strong> (Pria/Laki-laki) atau <strong>W</strong> (Wanita/Perempuan) - <strong>Kolom H</strong>.</td>
                                </tr>
                                <tr style="background: #FEF2F2;">
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">DATA UKURAN</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Tidak</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">
                                        <div style="font-weight: 700; margin-bottom: 4px; color: #111827;">PANDUAN ISI UKURAN (Kolom I s/d Q):</div>
                                        <ul style="margin: 0; padding-left: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 11px;">
                                            <li><strong>T. Kepala:</strong> 54 s/d 60</li>
                                            <li><strong>Kemeja:</strong> Pria: 14-22 | Wan: K, SD, B, EB, EEB, EEEB, EEEEB</li>
                                            <li><strong>Celana/Rok:</strong> Pria: 27-50 | Wan: K, SD, B, EB, EEB, EEEB, EEEEB</li>
                                            <li><strong>T-Shirt/Jaket:</strong> K, SD, B, EB, EEB, EEEB, EEEEB</li>
                                            <li><strong>Sepatu:</strong> 36 s/d 48</li>
                                            <li><strong>Sabuk:</strong> 36, 38, ..., 60 (Genap)</li>
                                            <li><strong>Jilbab:</strong> K, SD, B</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6; font-weight: 700; color: #374151;">KETERANGAN</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Tidak</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #F3F4F6;">Catatan tambahan - <strong>Kolom R</strong>.</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding: 12px; background: #F8FAFC; font-size: 11px; color: #475569;">
                                        <strong>KHUSUS POLRES, ISI BAG/FUNGSI SEBAGAI BERIKUT:</strong><br>
                                        BAG OPS, BAG REN, BAG SDM, BA POLRES (BAG SDM), BAG LOG, SI WAS, SIHUMAS, SIKUM, SI TIK, SIUM, SIKEU, SI DOKKES, SI PROPAM, SAT BINMAS, SAT SAMAPTA, SAT PAMOBVIT, SAT LANTAS, SAT POLAIRUD, SAT TAHTI, SPKT, SAT INTELKAM, SAT RESKRIM, SAT RESNARKOBA.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: #F9FAFB; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('importModal')">Tutup</button>
                <button type="submit" class="btn btn-primary" style="background: #B91C1C; padding-left: 30px; padding-right: 30px;">
                    <i class="ri-eye-line"></i> Proses &amp; Lihat Preview
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Personnel Modal --}}
<div id="addPersonnelModal" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="ri-user-add-line" style="color: #B91C1C; margin-right: 10px;"></i> Tambah Personel
            </h2>
            <button class="modal-close" onclick="closeModal('addPersonnelModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form action="{{ route('admin.personnel.store') }}" method="POST">
            @csrf
            <input type="hidden" name="modal_type" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <!-- Row 1 -->
                    <div class="form-group">
                        <label>JENIS PERSONIL</label>
                        <div class="selection-grid">
                            <label class="selection-card">
                                <input type="radio" name="personnel_type" value="Polri" {{ old('personnel_type', 'Polri') == 'Polri' ? 'checked' : '' }} onclick="filterRanks('Polri')">
                                <div class="card-content">
                                    <span class="card-title">Polri</span>
                                    <span class="card-desc">Anggota Kepolisian</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                            <label class="selection-card">
                                <input type="radio" name="personnel_type" value="PNS" {{ old('personnel_type') == 'PNS' ? 'checked' : '' }} onclick="filterRanks('PNS')">
                                <div class="card-content">
                                    <span class="card-title">PNS</span>
                                    <span class="card-desc">Pegawai Negeri</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>NRP / NIP</label>
                        <input type="text" name="nrp" value="{{ old('modal_type') == 'add' ? old('nrp') : '' }}" required placeholder="Masukkan Nomor Identitas" class="form-input @error('nrp') {{ old('modal_type') == 'add' ? 'has-error' : '' }} @enderror" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                        @if(old('modal_type') == 'add')
                            @error('nrp')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>

                    <!-- Row 2 -->
                    <div class="form-group">
                        <label>NAMA LENGKAP</label>
                        <input type="text" name="full_name" value="{{ old('modal_type') == 'add' ? old('full_name') : '' }}" required placeholder="Nama Tanpa Gelar" class="form-input" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="form-group">
                        <label>PANGKAT</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="add_rank_label">{{ old('modal_type') == 'add' && old('rank_id') ? $ranks->firstWhere('id', old('rank_id'))->name : '— Pilih Pangkat —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @php
                                            $pnsGrades = [
                                                'Pembina Utama' => 'IV/e',
                                                'Pembina Utama Madya' => 'IV/d',
                                                'Pembina Utama Muda' => 'IV/c',
                                                'Pembina Tingkat I' => 'IV/b',
                                                'Pembina' => 'IV/a',
                                                'Penata Tingkat I' => 'III/d',
                                                'Penata' => 'III/c',
                                                'Penata Muda Tingkat I' => 'III/b',
                                                'Penata Muda' => 'III/a',
                                                'Pengatur Tingkat I' => 'II/d',
                                                'Pengatur' => 'II/c',
                                                'Pengatur Muda Tingkat I' => 'II/b',
                                                'Pengatur Muda' => 'II/a',
                                                'Juru Tingkat I' => 'I/d',
                                                'Juru' => 'I/c',
                                                'Juru Muda Tingkat I' => 'I/b',
                                                'Juru Muda' => 'I/a',
                                            ];
                                        @endphp
                                        @foreach($ranks as $rank)
                                            @php
                                                $fillValue = ($rank->category == 'PNS' && isset($pnsGrades[$rank->name])) ? $pnsGrades[$rank->name] : $rank->category;
                                            @endphp
                                            <div class="option" 
                                                 data-value="{{ $rank->id }}" 
                                                 data-label="{{ $rank->name }}" 
                                                 data-category="{{ $rank->category }}" 
                                                 data-fill="{{ $fillValue }}"
                                                 onclick="selectRank(this)">
                                                {{ $rank->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="rank_id" required id="add_rank_id" value="{{ old('modal_type') == 'add' ? old('rank_id') : '' }}">
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="form-group">
                        <label>GOLONGAN (POLRI/PNS)</label>
                        <input type="text" name="golongan" value="{{ old('modal_type') == 'add' ? old('golongan') : '' }}" placeholder="Contoh: III/A" class="form-input" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="form-group">
                        <label>JABATAN</label>
                        <input type="text" name="jabatan" value="{{ old('modal_type') == 'add' ? old('jabatan') : '' }}" placeholder="Contoh: BANIT, KASUBNIT" class="form-input" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Row 4 -->
                    <div class="form-group">
                        <label>SATKER / SATWIL</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="add_satker_label">{{ old('modal_type') == 'add' && old('satker_id') ? $satkers->firstWhere('id', old('satker_id'))->name : '— Pilih Satker —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="select-search-container">
                                        <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterSatkerOptions(this)">
                                    </div>
                                    <div class="options-scroll">
                                        @foreach($satkers as $satker)
                                            <div class="option" 
                                                 data-value="{{ $satker->id }}" 
                                                 data-label="{{ $satker->name }}"
                                                 onclick="selectSatkerOption(this, 'add')">
                                                {{ $satker->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="satker_id" required id="add_satker_id" value="{{ old('modal_type') == 'add' ? old('satker_id') : '' }}">
                        </div>
                    </div>
                    <div class="form-group" id="add_bagian_manual_wrapper">
                        <label>BAGIAN / FUNGSI</label>
                        <input type="text" name="bagian" id="add_bagian_manual" value="{{ old('modal_type') == 'add' ? old('bagian') : '' }}" placeholder="Contoh: RESKRIM, INTEL" class="form-input" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="form-group" id="add_bagian_select_wrapper" style="display: none;">
                        <label>BAGIAN / FUNGSI</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="add_bagian_select_label">{{ old('modal_type') == 'add' && old('bagian') ? old('bagian') : '— Pilih Bagian —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['PIMPINAN', 'BAG OPS', 'BAG REN', 'BAG SDM', 'BAG LOG', 'SIUM', 'SIKEU', 'SIPROPAM', 'SIWAS', 'SIKUM', 'SIHUMAS', 'SIDOKKES', 'SITIK', 'SPKT', 'SAT INTELKAM', 'SAT RESKRIM', 'SAT RESNARKOBA', 'SAT SAMAPTA', 'SAT BINMAS', 'SAT LANTAS', 'SAT POLAIRUD', 'SAT TAHTI', 'SAT OBVIT', 'POLSEK'] as $opt)
                                            <div class="option" onclick="selectBagianDropdown(this, 'add', '{{ $opt }}')">{{ $opt }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="bagian" id="add_bagian_select" value="{{ old('modal_type') == 'add' ? old('bagian') : '' }}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>KETERANGAN</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="add_keterangan_label">{{ old('modal_type') == 'add' && old('keterangan') ? old('keterangan') : '— Pilih Keterangan —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['STAF', 'SAMAPTA', 'LANTAS', 'PROVOS', 'RESKRIM', 'INTEL', 'PAMINAL', 'SIKUM', 'HUMAS', 'TIK'] as $opt)
                                            <div class="option" onclick="selectOptionManual(this, 'keterangan', '{{ $opt }}', '{{ $opt }}', 'add_keterangan_label')">{{ $opt }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="keterangan" id="add_keterangan" value="{{ old('modal_type') == 'add' ? old('keterangan') : '' }}">
                        </div>
                    </div>

                    <!-- Row 5 -->
                    <div class="form-group">
                        <label>NO HP (WHATSAPP)</label>
                        <div class="input-with-icon">
                            <i class="ri-whatsapp-line"></i>
                            <input type="text" name="phone" value="{{ old('modal_type') == 'add' ? old('phone') : '' }}" placeholder="Contoh: 08123456789" class="form-input pl-10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>JENIS KELAMIN</label>
                        <div class="selection-grid">
                            <label class="selection-card">
                                <input type="radio" name="gender" value="L" {{ old('modal_type') == 'add' && old('gender') == 'L' ? 'checked' : (old('modal_type') != 'add' ? 'checked' : '') }} onclick="filterMeasurements('L', 'addPersonnelModal')">
                                <div class="card-content">
                                    <span class="card-title">Laki-laki</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                            <label class="selection-card">
                                <input type="radio" name="gender" value="P" {{ old('modal_type') == 'add' && old('gender') == 'P' ? 'checked' : '' }} onclick="filterMeasurements('P', 'addPersonnelModal')">
                                <div class="card-content">
                                    <span class="card-title">Perempuan</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                        </div>
                    </div>

                    <!-- Row 6 -->
                    <div class="form-group col-span-2-desktop">
                        <label>AGAMA</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select dropup" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="add_religion_label">{{ old('modal_type') == 'add' && old('religion') ? old('religion') : '— Pilih Agama —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['ISLAM', 'PROTESTAN', 'KATOLIK', 'HINDU', 'BUDHA', 'KHONGHUCU'] as $rel)
                                            <div class="option" onclick="selectOptionManual(this, 'religion', '{{ $rel }}', '{{ $rel }}', 'add_religion_label')">{{ $rel }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="religion" id="add_religion" value="{{ old('modal_type') == 'add' ? old('religion') : '' }}">
                        </div>
                    </div>
                    
                    <!-- Measurements Section -->
                    <div class="col-span-2-desktop" style="margin-top: 10px; padding-top: 20px; border-top: 1px dashed #E5E7EB;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px;">DATA UKURAN (KAPOR)</h4>
                        @php
                            $s_head = range(54, 60);
                            $s_shirt_m = ['14', '14.5', '15', '15.5', '16', '16.5', '17', '17.5', '18', '18.5', '19', '19.5', '20', '21', '22'];
                            $s_wom = ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];
                            $s_pants_m = range(27, 50);
                            $s_shoes = range(36, 48);
                            $s_belt = range(36, 60, 2);
                            $s_jilbab = ['K', 'SD', 'B'];
                            
                            $items = [
                                'topi' => ['label'=>'TUTUP KEPALA', 'opts'=>$s_head],
                                'olahraga' => ['label'=>'T-SHIRT/OLAHRAGA', 'opts'=>$s_wom],
                                'jaket' => ['label'=>'JAKET', 'opts'=>$s_wom],
                                'sepatu_dinas' => ['label'=>'SEPATU DINAS', 'opts'=>$s_shoes],
                                'sepatu_olahraga' => ['label'=>'SEPATU OLAHRAGA', 'opts'=>$s_shoes],
                                'sabuk' => ['label'=>'SABUK', 'opts'=>$s_belt],
                                'jilbab' => ['label'=>'JILBAB', 'opts'=>$s_jilbab],
                            ];
                        @endphp
                        <div class="form-grid">
                            {{-- 1. TUTUP KEPALA --}}
                            <div class="form-group">
                                <label>TUTUP KEPALA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_topi">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_head as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[topi]', '{{ $s }}', '{{ $s }}', 'add_size_label_topi')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[topi]" id="add_size_topi">
                                </div>
                            </div>

                            {{-- 2. KEMEJA (Gendered) --}}
                            <div class="form-group">
                                <label>KEMEJA (PDH/PDL)</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_kemeja">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shirt_m as $s)
                                                    <div class="option" data-gender="L" onclick="selectOptionManual(this, 'kapor_sizes[kemeja]', '{{ $s }}', '{{ $s }}', 'add_size_label_kemeja')">{{ $s }}</div>
                                                @endforeach
                                                @foreach($s_wom as $s)
                                                    <div class="option" data-gender="P" onclick="selectOptionManual(this, 'kapor_sizes[kemeja]', '{{ $s }}', '{{ $s }}', 'add_size_label_kemeja')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[kemeja]" id="add_size_kemeja">
                                </div>
                            </div>

                            {{-- 3. CELANA/ROK (Gendered) --}}
                            <div class="form-group">
                                <label>CELANA/ROK</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_celana">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_pants_m as $s)
                                                    <div class="option" data-gender="L" onclick="selectOptionManual(this, 'kapor_sizes[celana]', '{{ $s }}', '{{ $s }}', 'add_size_label_celana')">{{ $s }}</div>
                                                @endforeach
                                                @foreach($s_wom as $s)
                                                    <div class="option" data-gender="P" onclick="selectOptionManual(this, 'kapor_sizes[celana]', '{{ $s }}', '{{ $s }}', 'add_size_label_celana')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[celana]" id="add_size_celana">
                                </div>
                            </div>

                            {{-- 4. T-SHIRT/OLAHRAGA --}}
                            <div class="form-group">
                                <label>T-SHIRT/OLAHRAGA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_olahraga">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_wom as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[olahraga]', '{{ $s }}', '{{ $s }}', 'add_size_label_olahraga')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[olahraga]" id="add_size_olahraga">
                                </div>
                            </div>

                            {{-- 5. JAKET --}}
                            <div class="form-group">
                                <label>JAKET</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_jaket">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_wom as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[jaket]', '{{ $s }}', '{{ $s }}', 'add_size_label_jaket')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[jaket]" id="add_size_jaket">
                                </div>
                            </div>

                            {{-- 6. SEPATU DINAS --}}
                            <div class="form-group">
                                <label>SEPATU DINAS</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_sepatu_dinas">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shoes as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sepatu_dinas]', '{{ $s }}', '{{ $s }}', 'add_size_label_sepatu_dinas')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sepatu_dinas]" id="add_size_sepatu_dinas">
                                </div>
                            </div>

                            {{-- 7. SEPATU OLAHRAGA --}}
                            <div class="form-group">
                                <label>SEPATU OLAHRAGA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_sepatu_olahraga">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shoes as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sepatu_olahraga]', '{{ $s }}', '{{ $s }}', 'add_size_label_sepatu_olahraga')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sepatu_olahraga]" id="add_size_sepatu_olahraga">
                                </div>
                            </div>

                            {{-- 8. SABUK --}}
                            <div class="form-group">
                                <label>SABUK</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_sabuk">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_belt as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sabuk]', '{{ $s }}', '{{ $s }}', 'add_size_label_sabuk')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sabuk]" id="add_size_sabuk">
                                </div>
                            </div>

                            {{-- 9. JILBAB --}}
                            <div class="form-group">
                                <label>JILBAB</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="add_size_label_jilbab">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_jilbab as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[jilbab]', '{{ $s }}', '{{ $s }}', 'add_size_label_jilbab')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[jilbab]" id="add_size_jilbab">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addPersonnelModal')" style="border-radius: 10px; padding: 10px 24px; font-weight: 600; border-color: #E5E7EB; color: #374151;">Batal</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px; padding: 10px 24px; font-weight: 700;">Simpan Personel</button>
            </div>
        </form>
    </div>
</div>

{{-- Detail Personnel Modal --}}
<div id="detailPersonnelModal" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="ri-file-user-line" style="color: #10B981; margin-right: 10px;"></i> DETAIL PERSONEL
            </h2>
            <button class="modal-close" onclick="closeModal('detailPersonnelModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 24px;">
                <div style="width: 80px; height: 80px; background: #E5E7EB; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: #6B7280; flex-shrink: 0;" id="detail_avatar">
                    <!-- Initials -->
                </div>
                <div>
                    <h3 id="detail_name" style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;"></h3>
                    <div style="font-size: 14px; color: #6B7280; margin-bottom: 2px;" id="detail_nrp"></div>
                    <div style="display: flex; gap: 8px; margin-top: 8px;">
                        <span id="detail_rank" class="role-pill" style="background: #F3F4F6; color: #374151; border-color: #E5E7EB;"></span>
                        <span id="detail_type" class="role-pill"></span>
                    </div>
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 24px;">
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">SATKER</label>
                    <div id="detail_satker" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">JABATAN</label>
                    <div id="detail_jabatan" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">BAGIAN</label>
                    <div id="detail_bagian" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">KETERANGAN</label>
                    <div id="detail_keterangan" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                 <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">GOLONGAN</label>
                    <div id="detail_golongan" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">AGAMA</label>
                    <div id="detail_religion" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">JENIS KELAMIN</label>
                    <div id="detail_gender" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
                <div>
                    <label style="font-weight: 800; color: #000; font-size: 14px; display: block; margin-bottom: 2px;">NO HP</label>
                    <div id="detail_phone" style="font-size: 14px; font-weight: 500; color: #4B5563;"></div>
                </div>
            </div>

            <h4 style="font-size: 14px; font-weight: 700; color: #111827; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px; margin-bottom: 16px;">
                DATA UKURAN (KAPOR)
            </h4>
            
            <div id="detail_measurements" class="form-grid" style="gap: 12px;">
                <!-- Populated by JS -->
                <div class="text-sm text-gray-500 italic col-span-2">Belum ada data ukuran.</div>
            </div>
            
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; padding: 16px 24px;">
            <button type="button" class="btn btn-outline" style="border-radius: 10px; padding: 8px 20px; font-weight: 600; border-color: #E5E7EB; color: #374151; font-size: 14px;" onclick="closeModal('detailPersonnelModal')">Tutup</button>
        </div>
    </div>
</div>
<div class="modal" id="deleteModal">
    <div class="modal-content" style="max-width: 360px; border-radius: 24px; padding: 8px;">
        <div class="modal-body" style="padding: 24px; text-align: center;">
            <div style="width: 64px; height: 64px; background: #FEF2F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="ri-error-warning-fill" style="font-size: 32px; color: #EF4444;"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 800; color: #111827; margin-bottom: 8px;">Hapus Personel?</h3>
            <p style="font-size: 15px; color: #6B7280; line-height: 1.5; margin-bottom: 24px;">
                Apakah Anda yakin ingin menghapus <strong id="delete_person_name" style="color: #374151;"></strong>? Data yang telah dihapus tidak dapat dikembalikan.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-outline" style="flex: 1; height: 44px; padding: 0; border-radius: 12px; font-weight: 700; border-color: #E5E7EB; color: #374151; font-size: 14px; display: flex; align-items: center; justify-content: center;" onclick="closeModal('deleteModal')">Batal</button>
                <form id="deleteForm" method="POST" style="flex: 1;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; padding: 0; background: #EF4444; border-color: #EF4444; border-radius: 12px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Edit Personnel Modal --}}
<div id="editPersonnelModal" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="ri-user-settings-line" style="color: #B91C1C; margin-right: 10px;"></i> Edit Data Personel
            </h2>
            <button class="modal-close" onclick="closeModal('editPersonnelModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit_personnel_id" value="{{ old('modal_type') == 'edit' ? old('id') : '' }}">
            <div class="modal-body">
                <div class="form-grid">
                    <!-- Row 1: Personnel Type -->
                    <div class="form-group col-span-2-desktop">
                        <label>JENIS PERSONIL</label>
                        <div class="selection-grid">
                            <label class="selection-card">
                                <input type="radio" name="personnel_type" id="edit_type_polri" value="Polri" {{ old('modal_type') == 'edit' && old('personnel_type') == 'Polri' ? 'checked' : '' }} onclick="filterRanksEdit('Polri')">
                                <div class="card-content">
                                    <span class="card-title">Polri</span>
                                    <span class="card-desc">Anggota Kepolisian</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                            <label class="selection-card">
                                <input type="radio" name="personnel_type" id="edit_type_pns" value="PNS" {{ old('modal_type') == 'edit' && old('personnel_type') == 'PNS' ? 'checked' : '' }} onclick="filterRanksEdit('PNS')">
                                <div class="card-content">
                                    <span class="card-title">PNS</span>
                                    <span class="card-desc">Pegawai Negeri</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="form-group">
                        <label>NRP / NIP</label>
                        <input type="text" name="nrp" id="edit_nrp" value="{{ old('modal_type') == 'edit' ? old('nrp') : '' }}" class="form-input @error('nrp') {{ old('modal_type') == 'edit' ? 'has-error' : '' }} @enderror" required placeholder="Contoh: 12345678" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                        @if(old('modal_type') == 'edit')
                            @error('nrp')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="form-group">
                        <label>NAMA LENGKAP</label>
                        <input type="text" name="full_name" id="edit_full_name" value="{{ old('modal_type') == 'edit' ? old('full_name') : '' }}" class="form-input" required placeholder="Nama Lengkap tanpa gelar" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Row 3 -->
                    <div class="form-group">
                        <label>PANGKAT</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="edit_rank_label">{{ old('modal_type') == 'edit' && old('rank_id') ? $ranks->firstWhere('id', old('rank_id'))->name : '— Pilih Pangkat —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll" id="edit_rank_options">
                                        @php
                                            $pnsGrades = [
                                                'Pembina Utama' => 'IV/e', 'Pembina Utama Madya' => 'IV/d', 'Pembina Utama Muda' => 'IV/c',
                                                'Pembina Tingkat I' => 'IV/b', 'Pembina' => 'IV/a', 'Penata Tingkat I' => 'III/d',
                                                'Penata' => 'III/c', 'Penata Muda Tingkat I' => 'III/b', 'Penata Muda' => 'III/a',
                                                'Pengatur Tingkat I' => 'II/d', 'Pengatur' => 'II/c', 'Pengatur Muda Tingkat I' => 'II/b',
                                                'Pengatur Muda' => 'II/a', 'Juru Tingkat I' => 'I/d', 'Juru' => 'I/c',
                                                'Juru Muda Tingkat I' => 'I/b', 'Juru Muda' => 'I/a',
                                            ];
                                        @endphp
                                        @foreach($ranks as $rank)
                                            @php
                                                $fillValue = ($rank->category == 'PNS' && isset($pnsGrades[$rank->name])) ? $pnsGrades[$rank->name] : $rank->category;
                                            @endphp
                                            <div class="option" 
                                                 data-value="{{ $rank->id }}" 
                                                 data-label="{{ $rank->name }}"
                                                 data-category="{{ $rank->category }}"
                                                 data-fill="{{ $fillValue }}"
                                                 onclick="selectRankEdit(this)">
                                                {{ $rank->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="rank_id" id="edit_rank_id" value="{{ old('modal_type') == 'edit' ? old('rank_id') : '' }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                         <label>GOLONGAN (POLRI/PNS)</label>
                        <input type="text" name="golongan" id="edit_golongan" value="{{ old('modal_type') == 'edit' ? old('golongan') : '' }}" class="form-input" placeholder="Contoh: III/A" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Row 4 -->
                    <div class="form-group">
                        <label>JABATAN</label>
                        <input type="text" name="jabatan" id="edit_jabatan" value="{{ old('modal_type') == 'edit' ? old('jabatan') : '' }}" class="form-input" placeholder="Contoh: BANIT, KASUBNIT" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="form-group">
                        <label>SATKER / SATWIL</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="edit_satker_label">{{ old('modal_type') == 'edit' && old('satker_id') ? $satkers->firstWhere('id', old('satker_id'))->name : '— Pilih Satker —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="select-search-container">
                                        <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterSatkerOptions(this)">
                                    </div>
                                    <div class="options-scroll">
                                        @foreach($satkers as $satker)
                                            <div class="option" 
                                                 data-value="{{ $satker->id }}" 
                                                 data-label="{{ $satker->name }}"
                                                 onclick="selectSatkerOption(this, 'edit')">
                                                {{ $satker->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="satker_id" id="edit_satker_id" value="{{ old('modal_type') == 'edit' ? old('satker_id') : '' }}" required>
                        </div>
                    </div>

                    <!-- Row 5 -->
                    <div class="form-group" id="edit_bagian_manual_wrapper">
                        <label>BAGIAN / FUNGSI</label>
                        <input type="text" name="bagian" id="edit_bagian_manual" value="{{ old('modal_type') == 'edit' ? old('bagian') : '' }}" class="form-input" placeholder="Contoh: RESKRIM, INTEL" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="form-group" id="edit_bagian_select_wrapper" style="display: none;">
                        <label>BAGIAN / FUNGSI</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="edit_bagian_select_label">{{ old('modal_type') == 'edit' && old('bagian') ? old('bagian') : '— Pilih Bagian —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['PIMPINAN', 'BAG OPS', 'BAG REN', 'BAG SDM', 'BAG LOG', 'SIUM', 'SIKEU', 'SIPROPAM', 'SIWAS', 'SIKUM', 'SIHUMAS', 'SIDOKKES', 'SITIK', 'SPKT', 'SAT INTELKAM', 'SAT RESKRIM', 'SAT RESNARKOBA', 'SAT SAMAPTA', 'SAT BINMAS', 'SAT LANTAS', 'SAT POLAIRUD', 'SAT TAHTI', 'SAT OBVIT', 'POLSEK'] as $opt)
                                            <div class="option" onclick="selectBagianDropdown(this, 'edit', '{{ $opt }}')">{{ $opt }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="bagian" id="edit_bagian_select" value="{{ old('modal_type') == 'edit' ? old('bagian') : '' }}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>KETERANGAN</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="edit_keterangan_label">{{ old('modal_type') == 'edit' && old('keterangan') ? old('keterangan') : '— Pilih Keterangan —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['STAF', 'SAMAPTA', 'LANTAS', 'PROVOS', 'RESKRIM', 'INTEL', 'PAMINAL', 'SIKUM', 'HUMAS', 'TIK'] as $opt)
                                            <div class="option" onclick="selectOptionManual(this, 'keterangan', '{{ $opt }}', '{{ $opt }}', 'edit_keterangan_label')">{{ $opt }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="keterangan" id="edit_keterangan" value="{{ old('modal_type') == 'edit' ? old('keterangan') : '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>NO HP (WHATSAPP)</label>
                        <div class="input-with-icon">
                            <i class="ri-whatsapp-line"></i>
                            <input type="text" name="phone" id="edit_phone" value="{{ old('modal_type') == 'edit' ? old('phone') : '' }}" class="form-input pl-10" placeholder="Contoh: 08123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <!-- Row 6 -->
                    <div class="form-group">
                        <label>JENIS KELAMIN</label>
                        <div class="selection-grid">
                            <label class="selection-card">
                                <input type="radio" name="gender" id="edit_gender_l" value="L" {{ old('modal_type') == 'edit' && old('gender') == 'L' ? 'checked' : '' }} onclick="filterMeasurements('L', 'editPersonnelModal')">
                                <div class="card-content">
                                    <span class="card-title">Laki-laki</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                            <label class="selection-card">
                                <input type="radio" name="gender" id="edit_gender_p" value="P" {{ old('modal_type') == 'edit' && old('gender') == 'P' ? 'checked' : '' }} onclick="filterMeasurements('P', 'editPersonnelModal')">
                                <div class="card-content">
                                    <span class="card-title">Perempuan</span>
                                </div>
                                <div class="card-check"><i class="ri-check-line"></i></div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Row 7 -->
                     <div class="form-group col-span-2-desktop">
                        <label>AGAMA</label>
                        <div class="custom-select-wrapper">
                            <div class="custom-select dropup" onclick="toggleDropdown(this)">
                                <div class="select-trigger"><span id="edit_religion_label">{{ old('modal_type') == 'edit' && old('religion') ? old('religion') : '— Pilih Agama —' }}</span><i class="ri-arrow-down-s-line"></i></div>
                                <div class="custom-options">
                                    <div class="options-scroll">
                                        @foreach(['ISLAM', 'PROTESTAN', 'KATOLIK', 'HINDU', 'BUDHA', 'KHONGHUCU'] as $rel)
                                            <div class="option" onclick="selectOptionManual(this, 'religion', '{{ $rel }}', '{{ $rel }}', 'edit_religion_label')">{{ $rel }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="religion" id="edit_religion" value="{{ old('modal_type') == 'edit' ? old('religion') : '' }}">
                        </div>
                    </div>
                    
                    <!-- Measurements Section -->
                    <div class="col-span-2-desktop" style="margin-top: 10px; padding-top: 20px; border-top: 1px dashed #E5E7EB;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px;">DATA UKURAN (KAPOR)</h4>
                        <div class="form-grid">
                            {{-- 1. TUTUP KEPALA --}}
                            <div class="form-group">
                                <label>TUTUP KEPALA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_topi">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_head as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[topi]', '{{ $s }}', '{{ $s }}', 'edit_size_label_topi')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[topi]" id="edit_size_topi">
                                </div>
                            </div>

                            {{-- 2. KEMEJA (Gendered) --}}
                            <div class="form-group">
                                <label>KEMEJA (PDH/PDL)</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_kemeja">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shirt_m as $s)
                                                    <div class="option" data-gender="L" onclick="selectOptionManual(this, 'kapor_sizes[kemeja]', '{{ $s }}', '{{ $s }}', 'edit_size_label_kemeja')">{{ $s }}</div>
                                                @endforeach
                                                @foreach($s_wom as $s)
                                                    <div class="option" data-gender="P" onclick="selectOptionManual(this, 'kapor_sizes[kemeja]', '{{ $s }}', '{{ $s }}', 'edit_size_label_kemeja')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[kemeja]" id="edit_size_kemeja">
                                </div>
                            </div>

                            {{-- 3. CELANA/ROK (Gendered) --}}
                            <div class="form-group">
                                <label>CELANA/ROK</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_celana">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_pants_m as $s)
                                                    <div class="option" data-gender="L" onclick="selectOptionManual(this, 'kapor_sizes[celana]', '{{ $s }}', '{{ $s }}', 'edit_size_label_celana')">{{ $s }}</div>
                                                @endforeach
                                                @foreach($s_wom as $s)
                                                    <div class="option" data-gender="P" onclick="selectOptionManual(this, 'kapor_sizes[celana]', '{{ $s }}', '{{ $s }}', 'edit_size_label_celana')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[celana]" id="edit_size_celana">
                                </div>
                            </div>

                            {{-- 4. T-SHIRT/OLAHRAGA --}}
                            <div class="form-group">
                                <label>T-SHIRT/OLAHRAGA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_olahraga">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_wom as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[olahraga]', '{{ $s }}', '{{ $s }}', 'edit_size_label_olahraga')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[olahraga]" id="edit_size_olahraga">
                                </div>
                            </div>

                            {{-- 5. JAKET --}}
                            <div class="form-group">
                                <label>JAKET</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_jaket">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_wom as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[jaket]', '{{ $s }}', '{{ $s }}', 'edit_size_label_jaket')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[jaket]" id="edit_size_jaket">
                                </div>
                            </div>

                            {{-- 6. SEPATU DINAS --}}
                            <div class="form-group">
                                <label>SEPATU DINAS</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_sepatu_dinas">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shoes as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sepatu_dinas]', '{{ $s }}', '{{ $s }}', 'edit_size_label_sepatu_dinas')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sepatu_dinas]" id="edit_size_sepatu_dinas">
                                </div>
                            </div>

                            {{-- 7. SEPATU OLAHRAGA --}}
                            <div class="form-group">
                                <label>SEPATU OLAHRAGA</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_sepatu_olahraga">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_shoes as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sepatu_olahraga]', '{{ $s }}', '{{ $s }}', 'edit_size_label_sepatu_olahraga')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sepatu_olahraga]" id="edit_size_sepatu_olahraga">
                                </div>
                            </div>

                            {{-- 8. SABUK --}}
                            <div class="form-group">
                                <label>SABUK</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_sabuk">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_belt as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[sabuk]', '{{ $s }}', '{{ $s }}', 'edit_size_label_sabuk')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[sabuk]" id="edit_size_sabuk">
                                </div>
                            </div>

                            {{-- 9. JILBAB --}}
                            <div class="form-group">
                                <label>JILBAB</label>
                                <div class="custom-select-wrapper">
                                    <div class="custom-select" onclick="toggleDropdown(this)">
                                        <div class="select-trigger"><span id="edit_size_label_jilbab">— Pilih —</span><i class="ri-arrow-down-s-line"></i></div>
                                        <div class="custom-options">
                                            <div class="options-scroll">
                                                @foreach($s_jilbab as $s)
                                                    <div class="option" onclick="selectOptionManual(this, 'kapor_sizes[jilbab]', '{{ $s }}', '{{ $s }}', 'edit_size_label_jilbab')">{{ $s }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kapor_sizes[jilbab]" id="edit_size_jilbab">
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

            <div class="modal-footer" style="gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editPersonnelModal')" style="border-radius: 10px; padding: 10px 24px; font-weight: 600; border-color: #E5E7EB; color: #374151;">Batal</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px; padding: 10px 24px; font-weight: 700;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>



<div id="toastContainer" class="toast-container"></div>

{{-- Modal Cetak Satker --}}
<div id="printSatkerModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Cetak Laporan Satker</h2>
            <button class="modal-close" onclick="closeModal('printSatkerModal')">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size: 14px; color: #64748B; margin-bottom: 24px;">Pilih Satker yang ingin dicetak laporannya ke dalam format PDF.</p>
            
            <form action="{{ route('admin.personnel.print-satker') }}" method="GET" target="_blank" id="printSatkerForm">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>SATKER / SATWILL</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="print_satker_label">Pilih Satker</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="select-search-container">
                                    <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterSatkerOptions(this)">
                                </div>
                                <div class="options-scroll">
                                    @foreach($satkers as $satker)
                                        <div class="option" data-value="{{ $satker->id }}" data-label="{{ $satker->name }}" onclick="selectPrintSatker(this)">
                                            {{ $satker->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="satker_id" id="print_satker_id" required>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label>TAHUN ANGGARAN</label>
                        <input type="text" name="fiscal_year" value="{{ \App\Models\Setting::getValue('fiscal_year', date('Y')) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>TEMPAT CETAK</label>
                        <input type="text" name="location" value="Mataram" class="form-input">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label>JABATAN PENANDA TANGAN</label>
                    <input type="text" name="signatory_role" value="KASUBBAG RENMIN KABAG LOG" class="form-input">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label>NAMA PEJABAT</label>
                    <input type="text" name="signatory_name" placeholder="Nama Lengkap Pejabat" class="form-input">
                </div>

                <div class="form-group">
                    <label>PANGKAT / NRP</label>
                    <input type="text" name="signatory_nrp" placeholder="Pangkat & NRP/NIP" class="form-input">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('printSatkerModal')">Batal</button>
            <button class="btn" style="background: #3B82F6; color: white; border: none; padding: 0 20px; height: 46px; border-radius: 10px; font-weight: 600; cursor: pointer;" onclick="previewPrintSatker()">
                <i class="ri-eye-line" style="margin-right: 6px;"></i> Lihat Preview
            </button>
            <button class="btn-save" style="background: #059669; border: none;" onclick="downloadPrintSatker()">
                <i class="ri-download-line" style="margin-right: 6px;"></i> Download PDF
            </button>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    /* ── Header ─────────────────────────────────────────── */
    .btn-maroon {
        background: #B91C1C !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(185, 28, 28, 0.2) !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
        color: #fff !important;
        display: flex; align-items: center; gap: 8px; cursor: pointer;
    }
    .btn-maroon:hover { background: #991B1B !important; }

    .btn-outline {
        background: #fff;
        border: 1px solid #E5E7EB;
        color: #374151;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        cursor: pointer;
        display: flex; align-items: center; gap: 8px;
        transition: all 0.2s;
    }
    .btn-outline:hover { background: #F9FAFB; border-color: #D1D5DB; }

    /* ── Stats ──────────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-4px); border-color: #E5E7EB; }

    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .icon-blue { background: #EBF5FF; color: #3B82F6; }
    .icon-purple { background: #F5F3FF; color: #8B5CF6; }
    .icon-orange { background: #FFF7ED; color: #F97316; }
    .icon-green { background: #F0FDF4; color: #22C55E; }
    .icon-red { background: #FEF2F2; color: #EF4444; }
    
    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 13px; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
    .stat-number { font-size: 22px; font-weight: 700; color: #111827; }

    /* ── Filters ────────────────────────────────────────── */
    .filter-bar {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
    }
    .filter-form { display: flex; gap: 16px; align-items: center; }
    .filter-group { display: flex; gap: 12px; flex: 2; }
    
    .search-container {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 0 16px;
        height: 46px;
        transition: all 0.2s ease;
    }
    .search-container:focus-within { 
        border-color: #B91C1C; 
        box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.05); 
    }
    .search-container i.ri-search-line { 
        color: #64748B; 
        font-size: 20px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .search-container input {
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        font-size: 14px;
        color: #1E293B;
        padding: 0;
    }
    .search-container input::placeholder { color: #94A3B8; font-weight: 400; }

    /* ── Custom Select UI (Refined) ───────────────────── */
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
    
    /* Active State: Red Border, Shadow, Text Color */
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

    /* Dropdown Menu */
    .custom-options {
        position: absolute;
        top: calc(100% + 8px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 16px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s cubic-bezier(0.165, 0.84, 0.44, 1);
        padding: 8px; /* Padding inside the box */
        display: flex; flex-direction: column;
    }
    
    /* Dropup Variant (Open Upwards) */
    .custom-select.dropup .custom-options {
        top: auto;
        bottom: calc(100% + 8px);
        transform: translateY(10px);
        box-shadow: 0 -10px 40px -10px rgba(0,0,0,0.1);
    }
    
    .custom-select.active .custom-options { 
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    /* Scrollable Area */
    .options-scroll {
        max-height: 240px; /* Fixed height for scrolling */
        overflow-y: auto;
        padding-right: 2px; /* Slight padding for visual balance */
    }
    
    /* Custom Scrollbar Styling */
    .options-scroll::-webkit-scrollbar { width: 4px; }
    .options-scroll::-webkit-scrollbar-track { background: transparent; }
    .options-scroll::-webkit-scrollbar-thumb { 
        background-color: #E5E7EB; 
        border-radius: 10px; 
    }
    .options-scroll::-webkit-scrollbar-thumb:hover { background-color: #D1D5DB; }
    
    /* Option Item */
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
    .option:last-child { margin-bottom: 0; }
    
    .option:hover {
        background-color: #F9FAFB;
        color: #111827;
    }
    
    /* Selected Option */
    .option.selected {
        background-color: #F3F4F6; /* Grey background */
        color: #111827; /* Dark text */
        font-weight: 600;
    }
    /* Add checkmark for selected (optional but nice) */
    .option.selected::after {
        content: ""; /* Can add check icon here if needed via background-image or ::after content */
    }
    /* If you want the specific Red style from image */
    .option.selected {
        background-color: #FEF2F2;
        color: #B91C1C;
    }
        color: #4B5563;
        cursor: pointer;
        transition: all 0.1s;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 2px;
    }
    .option:last-child { margin-bottom: 0; }
    .option:hover { background: #F3F4F6; color: #111827; }
    .option.selected { 
        background: #FEF2F2; 
        color: #B91C1C; 
        font-weight: 600; 
    }

    /* Select Search */
    .select-search-container {
        padding: 8px;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 10;
        border-bottom: 1px solid #F3F4F6;
        margin-bottom: 4px;
    }
    .select-search-input {
        width: 100%;
        height: 36px;
        padding: 0 12px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        background-color: #F9FAFB;
        transition: all 0.2s;
    }
    .select-search-input:focus {
        border-color: #B91C1C;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.1);
    }

    /* ── Table ──────────────────────────────────────────── */
    .table-container {
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th {
        background: #FAFAFA;
        padding: 12px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6B7280;
        text-transform: uppercase;
        border-bottom: 1px solid #F3F4F6;
        transition: background-color 0.2s;
    }
    .user-table th[onclick]:hover {
        background-color: #F3F4F6;
    }
    .user-table th i { font-size: 14px; margin-left: 4px; color: #D1D5DB; }
    .user-table td { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    
    .user-info { display: flex; align-items: center; gap: 12px; }
    .avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 14px;
    }
    .details { display: flex; flex-direction: column; }
    .name { font-weight: 600; color: #111827; }
    .nrp { font-size: 12px; color: #9CA3AF; }

    .role-pill {
        background: #EFF6FF;
        color: #3B82F6;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #DBEAFE;
    }

    .action-buttons { display: flex; gap: 8px; justify-content: center; }
    .btn-icon {
        width: 32px; height: 32px;
        border-radius: 6px;
        border: 1px solid #E5E7EB;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s; font-size: 16px;
    }
    .btn-icon.blue { color: #3B82F6; }
    .btn-icon.blue:hover { background: #3B82F6; color: #fff; border-color: #3B82F6; }
    
    .btn-icon.green { color: #10B981; }
    .btn-icon.green:hover { background: #10B981; color: #fff; border-color: #10B981; }

    .btn-icon.red { color: #EF4444; }
    .btn-icon.red:hover { background: #EF4444; color: #fff; border-color: #EF4444; }

    /* ── Table Footer (Pagination) ────────────────────── */
    .table-footer {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #F3F4F6;
        background: #fff;
    }
    .footer-left { display: flex; align-items: center; gap: 12px; color: #6B7280; font-size: 13px; }
    
    .per-page-selector { display: flex; align-items: center; margin-left: 12px; }

    .pagination-controls { display: flex; align-items: center; gap: 4px; }
    .page-btn {
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #E5E7EB;
        background: #fff;
        border-radius: 6px;
        color: #374151;
        text-decoration: none;
        transition: all 0.2s;
    }
    .page-btn:hover:not(.disabled) { background: #F9FAFB; border-color: #D1D5DB; }
    .page-btn.disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }
    .page-info { font-size: 13px; color: #4B5563; margin: 0 12px; }
    .page-info strong { color: #111827; }

    /* ── Modal (Updated for Overflow) ────────────────── */
    .modal {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(17, 24, 39, 0.4); backdrop-filter: blur(4px);
        display: none; 
        z-index: 1000; 
        overflow-y: auto; /* Scroll on the wrapper */
        padding: 20px 0; /* Add padding for vertical spacing */
    }
    .modal.open { display: block; } /* Use block instead of flex for scroll */
    
    .modal-content {
        background: #fff;
        border-radius: 20px;
        width: 100%;
        max-width: 650px;
        margin: 20px auto; /* Center with margin */
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        animation: zoomIn 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        overflow: visible; /* Allow dropdowns to go outside */
    }
    
    .modal-header {
        padding: 20px 28px;
        border-bottom: 1px solid #F3F4F6;
        display: flex; justify-content: space-between; align-items: center;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        background: #fff;
    }
    .modal-title { font-size: 20px; font-weight: 800; color: #111827; letter-spacing: -0.025em; }
    .modal-close {
        background: #F3F4F6; border: none; width: 32px; height: 32px; 
        border-radius: 50%; font-size: 20px; color: #6B7280; 
        cursor: pointer; display: flex; align-items: center; justify-content: center; 
        transition: all 0.2s;
    }
    .modal-close:hover { background: #E5E7EB; color: #111827; }
    /* ── Page Header ────────────────────────────────────── */
    .page-header { margin-bottom: 24px; }
    .page-header-row {
        display: flex; justify-content: space-between; align-items: flex-end;
    }
    .page-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header-actions { display: flex; gap: 12px; }

    /* ── Responsive Design ─────────────────────────────── */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr); /* 2 columns on tablet */
        }
    }

    @media (max-width: 768px) {
        .page-header-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .page-header-actions {
            width: 100%;
        }
        .page-header-actions button {
            flex: 1;
            justify-content: center;
        }

        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .header-actions {
            width: 100%;
            justify-content: space-between;
        }
        .btn-maroon, .btn-outline {
            /* flex: 1; removed to avoid conflict with above specific selector */
            justify-content: center;
        }
        
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group {
            width: 100%;
            flex-direction: column;
        }
        .search-input {
            width: 100%;
        }
        
        .table-container {
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        
        /* Table Responsive Scroll */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .user-table {
            min-width: 800px; /* Ensure table doesn't squish */
        }
        
        /* Pagination */
        .table-footer {
            flex-direction: column;
            gap: 16px;
            align-items: center;
            text-align: center;
        }
        .footer-left {
            flex-direction: column;
            gap: 8px;
        }
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr; /* Stack on mobile */
        }
        
        .modal-content {
            width: 95%; /* Use almost full width on mobile */
            margin: 10px auto;
            border-radius: 16px;
        }
        .form-grid {
            grid-template-columns: 1fr !important; /* Stack columns */
            gap: 16px !important;
        }
        .selection-grid {
            grid-template-columns: 1fr; /* Stack selection cards */
        }
        
        .modal-body { padding: 20px; }
        .modal-footer { flex-direction: column-reverse; gap: 10px; }
        .btn-save, .btn-cancel { width: 100%; }
        
        /* Adjust font sizes for mobile */
        .page-title { font-size: 20px; }
        .stat-number { font-size: 18px; }
        .stat-label { font-size: 11px; }
    }

    .modal-body {
        padding: 24px 28px 40px; 
        overflow: visible; /* DO NOT CLIP */
    }

    /* Modal Footer rounded corners */
    .modal-footer {
        padding: 20px 28px;
        border-top: 1px solid #F3F4F6;
        display: flex; justify-content: flex-end; gap: 14px;
        background: #F9FAFB;
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
    }

    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-group input { width: 100%; height: 46px; padding: 0 16px; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #1F2937; outline: none; transition: all 0.2s; background: #F9FAFB; }
    .form-group input:focus { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08); }
    .form-group input::placeholder { color: #9CA3AF; }
    
    .btn-save { background: #B91C1C; color: #fff; border: 1px solid #B91C1C; padding: 0 24px; height: 46px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-save:hover { background: #991B1B; }
    
    .btn-cancel { background: #fff; color: #4B5563; border: 1px solid #E5E7EB; padding: 0 24px; height: 46px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-cancel:hover { background: #F9FAFB; border-color: #D1D5DB; }

    /* ── Responsive Modal ──────────────────────── */
    @media (max-width: 640px) {
        .modal-content {
            width: 95%; /* Use almost full width on mobile */
            max-height: 90vh; /* slightly taller on mobile */
        }
        .form-grid {
            grid-template-columns: 1fr !important; /* Stack columns */
            gap: 16px !important;
        }
        .modal-body { padding: 20px; }
        .modal-footer { flex-direction: column-reverse; gap: 10px; }
        .btn-save, .btn-cancel { width: 100%; }
    }

    /* ── Toast ─────────────────────────────────── */
    .toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; }
    .toast { min-width: 320px; background: #fff; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-left: 4px solid #10B981; animation: toast-slide-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    .toast.success { border-left-color: #10B981; }
    .toast.error { border-left-color: #EF4444; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes zoomIn { from { transform: scale(0.95) translateY(10px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
    @keyframes toast-slide-in { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    /* ── Elegant Form Styles ───────────────────────────── */
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-input {
        width: 100%;
        height: 44px;
        padding: 0 14px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        color: #1F2937;
        transition: all 0.2s;
        background: #F9FAFB;
        outline: none;
        font-family: inherit;
    }
    .form-input:focus {
        background: #fff;
        border-color: #B91C1C;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.1);
    }
    .input-with-icon { position: relative; }
    .input-with-icon i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 18px; color: #9CA3AF;
        pointer-events: none;
    }
    .form-input.pl-10 { padding-left: 42px; }

    /* Selection Cards (Radio/Checkbox) */
    .selection-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .selection-card {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        user-select: none;
    }
    .selection-card:hover {
        border-color: #D1D5DB;
        background: #F9FAFB;
    }
    .selection-card input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0; width: 0;
    }
    
    /* Checked State */
    .selection-card:has(input:checked) {
        border-color: #B91C1C;
        background: #FEF2F2;
        box-shadow: 0 0 0 1px #B91C1C inset; /* subtle border emphasis */
    }
    .selection-card:has(input:checked) .card-title {
        color: #B91C1C;
        font-weight: 700;
    }
    .selection-card:has(input:checked) .card-desc {
        color: #B91C1C;
        opacity: 0.8;
    }
    .selection-card:has(input:checked) .card-check {
        transform: scale(1);
        opacity: 1;
    }
    
    .card-content { display: flex; flex-direction: column; }
    .card-title {
        font-size: 14px;
        color: #374151;
        font-weight: 600;
    }
    .card-desc {
        font-size: 11px;
        color: #6B7280;
        font-weight: 400;
    }
    .card-check {
        width: 20px; height: 20px;
        background: #B91C1C;
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px;
        transform: scale(0.5);
        opacity: 0;
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    /* Form Grid Layout */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .col-span-2-desktop {
        grid-column: span 2;
    }
    
    @media (max-width: 640px) {
        .col-span-2-desktop { grid-column: auto; }
    }
    .form-input.has-error {
        border-color: #EF4444 !important;
        background-color: #FEF2F2 !important;
    }
    .error-message {
        color: #EF4444;
        font-size: 11px;
        margin-top: 4px;
        font-weight: 500;
        text-transform: none;
    }


</style>
@endsection

@section('scripts')
<script>
    // Global click listener to close dropdowns
    document.addEventListener('click', (e) => {
        // If click is not inside a custom-select, close all
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-options').forEach(opt => {
                opt.style.display = 'none';
            });
        }
    });

    // Handle scroll to close dropdowns preventing detachment
    document.addEventListener('scroll', (e) => {
        // Ignore scroll events coming from inside the dropdown options
        if (e.target.classList && (e.target.classList.contains('options-scroll') || e.target.closest('.options-scroll'))) {
            return;
        }

        document.querySelectorAll('.custom-options').forEach(opt => {
            if (opt.style.display === 'block') {
                // Determine if we should close: 
                // If it's a fixed position dropdown (previous logic), we might want to close on outer scroll.
                // But with our current CSS-based (absolute) dropdowns inside overflow-visible modal, 
                // we technically don't need to close on modal scroll unless we really want to.
                // However, preserving the behavior for window/modal scroll is fine, just NOT inner scroll.
                
                // Close it
                opt.style.display = 'none';
                opt.closest('.custom-select').classList.remove('active');
            }
        });
    }, true); // Capture phase

    function exportTutupKepala(type) {
        // Legacy support, redirects to new route
        const url = "{{ route('admin.personnel.export-rekap') }}?category=Tutup_Kepala&item=" + encodeURIComponent(type);
        window.location.href = url;
    }

    function toggleDropdown(el) {
        // Toggle this dropdown
        const options = el.querySelector('.custom-options');
        const isOpen = options.style.display === 'block';

        // Close all others
        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
        document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));

        if (!isOpen) {
            options.style.display = 'block';
            // We rely on CSS for positioning (top, left, width, z-index)
            // Do NOT overwrite with inline styles here
            el.classList.add('active');
        } 
        
        event.stopPropagation();
    }

    function selectSatkerOption(el, type) {
        const value = el.dataset.value;
        const label = el.dataset.label;
        
        // Update Satker
        document.getElementById(type + '_satker_id').value = value;
        document.getElementById(type + '_satker_label').innerText = label;
        
        // Handle Bagian Visibility
        updateBagianVisibility(label, type);
        
        // Visual feedback
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        event.stopPropagation();
    }

    function filterSatkerOptions(input) {
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

    function selectSatkerOptionSimple(el, type) {
        const value = el.dataset.value;
        const label = el.dataset.label;
        
        // Update Satker
        document.getElementById(type + '_satker_id').value = value;
        document.getElementById(type + '_satker_label').innerText = label;
        
        // Visual feedback
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        if (event) event.stopPropagation();
    }

    function updateBagianVisibility(satkerName, type, currentBagian = '') {
        const isPolres = satkerName.toUpperCase().includes('POLRES') || satkerName.toUpperCase().includes('POLRESTA');
        const manualWrapper = document.getElementById(type + '_bagian_manual_wrapper');
        const selectWrapper = document.getElementById(type + '_bagian_select_wrapper');
        const manualInput = document.getElementById(type + '_bagian_manual');
        const selectInput = document.getElementById(type + '_bagian_select');
        
        if (isPolres) {
            manualWrapper.style.display = 'none';
            selectWrapper.style.display = 'block';
            manualInput.disabled = true;
            selectInput.disabled = false;
            
            if (currentBagian) {
                selectInput.value = currentBagian;
                document.getElementById(type + '_bagian_select_label').innerText = currentBagian;
            } else {
                selectInput.value = '';
                document.getElementById(type + '_bagian_select_label').innerText = '— Pilih Bagian —';
            }
        } else {
            manualWrapper.style.display = 'block';
            selectWrapper.style.display = 'none';
            manualInput.disabled = false;
            selectInput.disabled = true;
            
            if (currentBagian) {
                manualInput.value = currentBagian;
            }
        }
    }

    function selectBagianDropdown(el, type, value) {
        const wrapper = el.closest('.custom-select-wrapper');
        const label = document.getElementById(type + '_bagian_select_label');
        const input = document.getElementById(type + '_bagian_select');
        
        label.innerText = value;
        input.value = value;
        
        // Visual feedback
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        if (event) event.stopPropagation();
    }

    function selectOptionSearch(el, inputName, value, label) {
        const wrapper = el.closest('.custom-select-wrapper');
        const trigger = wrapper.querySelector('.select-trigger span');
        const input = wrapper.querySelector('input[type="hidden"]');
        
        trigger.innerText = label;
        input.value = value;
        
        // Submit form
        document.getElementById('filterForm').submit();
    }

    function selectOptionManual(el, inputName, value, label, triggerId, category = null) {
        const wrapper = el.closest('.custom-select-wrapper');
        const trigger = document.getElementById(triggerId);
        const input = wrapper.querySelector('input[type="hidden"]');
        
        trigger.innerText = label;
        input.value = value;

        // Auto-fill Golongan if category is provided (for Rank)
        if (category && inputName === 'rank_id') {
            const golonganInput = document.querySelector('input[name="golongan"]');
            if (golonganInput) {
                golonganInput.value = category;
                // Add a visual flash effect to indicate auto-fill
                golonganInput.style.transition = 'background-color 0.3s';
                golonganInput.style.backgroundColor = '#ecfdf5'; // Light green
                setTimeout(() => {
                    golonganInput.style.backgroundColor = '#F9FAFB'; // Back to default
                }, 500);
            }
        }
        
        // Visual feedback
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        event.stopPropagation();
    }


    function selectRank(el) {
        const value = el.dataset.value;
        const label = el.dataset.label;
        const category = el.dataset.category;
        const fill = el.dataset.fill;
        
        // Update Hidden Input (Rank ID)
        document.getElementById('add_rank_id').value = value;
        // Update Label
        document.getElementById('add_rank_label').innerText = label;
        
        // Auto-fill Golongan (PNS & Polri)
        const golonganInput = document.querySelector('input[name="golongan"]');
        if (golonganInput) {
            // Use the data-fill attribute which contains either the PNS Grade (IV/a) or Polri Category (PATI/PAMEN/etc)
            golonganInput.value = fill; 
            
            // Visual flash effect (green background)
            golonganInput.style.transition = 'background-color 0.3s';
            golonganInput.style.backgroundColor = '#ecfdf5'; 
            setTimeout(() => { golonganInput.style.backgroundColor = '#F9FAFB'; }, 500);
        }
    
        // UI Feedback (highlight selected option)
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close Dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        event.stopPropagation();
    }

    function filterRanks(type) {
        // Select all rank options inside the add personnel modal
        const optionsWrapper = document.querySelector('#addPersonnelModal .custom-select-wrapper .options-scroll');
        if (!optionsWrapper) return;
        
        const ranks = optionsWrapper.querySelectorAll('.option');
        const rankLabel = document.getElementById('add_rank_label');
        const rankId = document.getElementById('add_rank_id');

        // Reset selection if changing personnel type (optional logic)
        rankLabel.innerText = '— Pilih Pangkat —';
        rankId.value = '';

        ranks.forEach(rank => {
            // Updated to use data attributes which is much robust
            const category = rank.dataset.category || '';

            if (type === 'Polri') {
                // Show only non-PNS ranks (PATI, PAMEN, PAMA, BINTARA)
                if (category !== 'PNS') {
                    rank.style.display = 'flex'; // Restore flex display
                } else {
                    rank.style.display = 'none';
                }
            } else {
                // Show only PNS ranks
                if (category === 'PNS') {
                    rank.style.display = 'flex';
                } else {
                    rank.style.display = 'none';
                }
            }
        });
    }

    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
        
        if (id === 'addPersonnelModal') {
            // Default to Polri on open
            filterRanks('Polri');
            document.querySelector('input[name="personnel_type"][value="Polri"]').checked = true;
        }
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    function openDetailModal(p) {
        document.getElementById('detail_name').innerText = p.full_name;
        document.getElementById('detail_nrp').innerText = (p.nrp || '—');
        document.getElementById('detail_rank').innerText = p.rank ? p.rank.name : '—';
        document.getElementById('detail_satker').innerText = p.satker ? p.satker.name : '—';
        document.getElementById('detail_jabatan').innerText = p.jabatan || '—';
        document.getElementById('detail_bagian').innerText = p.bagian || '—';
        document.getElementById('detail_keterangan').innerText = p.keterangan || '—';
        document.getElementById('detail_golongan').innerText = p.golongan || '—';
        document.getElementById('detail_religion').innerText = p.religion || '—';
        document.getElementById('detail_gender').innerText = p.gender || '—';
        document.getElementById('detail_phone').innerText = p.phone || '—';
        
        // Type Badge Style
        const typeEl = document.getElementById('detail_type');
        typeEl.innerText = p.personnel_type;
        if(p.personnel_type === 'Polri') {
             typeEl.style.background = '#EFF6FF'; typeEl.style.color = '#3B82F6'; typeEl.style.borderColor = '#DBEAFE';
        } else {
             typeEl.style.background = '#F9FAFB'; typeEl.style.color = '#374151'; typeEl.style.borderColor = '#E5E7EB';
        }

        // Avatar Initials
        let initials = '';
        if (p.full_name) {
            const names = p.full_name.split(' ');
            if (names.length > 0) initials += names[0][0];
            if (names.length > 1) initials += names[names.length - 1][0];
        }
        document.getElementById('detail_avatar').innerText = initials.toUpperCase();

        // Measurements
        const mContainer = document.getElementById('detail_measurements');
        mContainer.innerHTML = '';
        
        // Hardcoded list to ensure decoupling from KaporItem database
        const displayItems = [
            { label: 'TUTUP KEPALA', key: 'topi' },
            { label: 'KEMEJA (PDH/PDL)', key: 'kemeja' },
            { label: 'CELANA/ROK', key: 'celana' },
            { label: 'T-SHIRT/OLAHRAGA', key: 'olahraga' },
            { label: 'JAKET', key: 'jaket' },
            { label: 'SEPATU DINAS', key: 'sepatu_dinas' },
            { label: 'SEPATU OLAHRAGA', key: 'sepatu_olahraga' },
            { label: 'SABUK', key: 'sabuk' },
            { label: 'JILBAB', key: 'jilbab' }
        ];

        const sizes = p.kapor_sizes || {};
        let hasData = false;

        displayItems.forEach(item => {
            const val = sizes[item.key] || '—';
            if (val !== '—') hasData = true;

            const div = document.createElement('div');
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #F9FAFB; border-radius: 8px; border: 1px solid #F3F4F6; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 500; color: #6B7280;">${item.label}</span>
                    <span style="font-size: 14px; font-weight: 700; color: #111827;">${val}</span>
                </div>
            `;
            mContainer.appendChild(div);
        });

        if (!hasData) {
            // Optional: Message if no data, but we show dashes above anyway
        }

        openModal('detailPersonnelModal');
    }

    function selectRankEdit(el) {
        const value = el.dataset.value;
        const label = el.dataset.label;
        const category = el.dataset.category;
        const fill = el.dataset.fill;
        
        // Update Hidden Input (Rank ID)
        document.getElementById('edit_rank_id').value = value;
        // Update Label
        document.getElementById('edit_rank_label').innerText = label;
        
        // Auto-fill Golongan (PNS & Polri)
        const golonganInput = document.getElementById('edit_golongan');
        if (golonganInput) {
            golonganInput.value = fill; 
            
            // Visual flash effect
            golonganInput.style.transition = 'background-color 0.3s';
            golonganInput.style.backgroundColor = '#ecfdf5'; 
            setTimeout(() => { golonganInput.style.backgroundColor = '#F9FAFB'; }, 500);
        }
    
        // UI Feedback
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close Dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        event.stopPropagation();
    }

    function filterRanksEdit(type) {
        const optionsWrapper = document.getElementById('edit_rank_options');
        if (!optionsWrapper) return;
        
        const ranks = optionsWrapper.querySelectorAll('.option');
        const rankLabel = document.getElementById('edit_rank_label');
        const rankId = document.getElementById('edit_rank_id');

        // Reset selection if needed (optional logic, kept simple here to avoid clearing existing data on load)
        // typically we only clear if the user *changes* the type manually, not on initial load
        // But for simplicity, we let the user re-select if they change type.
        
        ranks.forEach(rank => {
            const category = rank.dataset.category || '';
            if (type === 'Polri') {
                if (category !== 'PNS') rank.style.display = 'flex';
                else rank.style.display = 'none';
            } else {
                if (category === 'PNS') rank.style.display = 'flex';
                else rank.style.display = 'none';
            }
        });
    }

    function filterMeasurements(gender, modalId = 'editPersonnelModal') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const options = modal.querySelectorAll('.option[data-gender]');
        options.forEach(opt => {
            const sizeGender = opt.getAttribute('data-gender');
            if (!sizeGender || sizeGender === gender) {
                opt.style.display = 'flex';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function openEditModal(p) {
        const editForm = document.getElementById('editForm');
        editForm.action = '/admin/personnel/' + p.id;
        document.getElementById('edit_personnel_id').value = p.id;
        document.getElementById('edit_nrp').value = p.nrp;
        document.getElementById('edit_full_name').value = p.full_name;
        
        // Set Rank
        document.getElementById('edit_rank_id').value = p.rank_id;
        document.getElementById('edit_rank_label').innerText = p.rank ? p.rank.name : '— Pilih Pangkat —';
        
        // Set Satker
        document.getElementById('edit_satker_id').value = p.satker_id;
        const satkerName = p.satker ? p.satker.name : '';
        document.getElementById('edit_satker_label').innerText = satkerName || '— Pilih Satker —';
        
        // Initialize Bagian Visibility
        updateBagianVisibility(satkerName, 'edit', p.bagian);
        
        document.getElementById('edit_jabatan').value = p.jabatan || '';
        document.getElementById('edit_keterangan').value = p.keterangan || '';
        document.getElementById('edit_keterangan_label').innerText = p.keterangan || '— Pilih Keterangan —';
        
        document.getElementById('edit_phone').value = p.phone || '';
        document.getElementById('edit_golongan').value = p.golongan || '';
        
        // Set Religion
        document.getElementById('edit_religion').value = p.religion || '';
        document.getElementById('edit_religion_label').innerText = p.religion || '— Pilih Agama —';

        // Set Radio Buttons & Trigger Filter
        if (p.personnel_type === 'Polri') {
            document.getElementById('edit_type_polri').checked = true;
            filterRanksEdit('Polri');
        } else if (p.personnel_type === 'PNS') {
            document.getElementById('edit_type_pns').checked = true;
            filterRanksEdit('PNS');
        }

        if (p.gender === 'L') {
            document.getElementById('edit_gender_l').checked = true;
            filterMeasurements('L', 'editPersonnelModal');
        }
        else if (p.gender === 'P') {
            document.getElementById('edit_gender_p').checked = true;
            filterMeasurements('P', 'editPersonnelModal');
        }
        
        // Reset Measurements inputs
        document.querySelectorAll('[id^="edit_size_"]').forEach(el => el.value = '');

        // Reset Measurements inputs
        document.querySelectorAll('[id^="edit_size_"]').forEach(el => el.value = '');
        document.querySelectorAll('[id^="edit_size_label_"]').forEach(el => el.innerText = '— Pilih —');

        // Populate Measurements
        if (p.kapor_sizes) {
            const sizes = p.kapor_sizes;
            const keys = ['topi','jilbab','kemeja','celana','jaket','olahraga','sepatu_dinas','sepatu_olahraga','sabuk'];
            
            keys.forEach(key => {
                if(sizes[key]) {
                    const input = document.getElementById('edit_size_' + key);
                    const label = document.getElementById('edit_size_label_' + key);
                    if(input && label) {
                        input.value = sizes[key];
                        // Try to find the matching option label (especially for Pria/Wanita prefixed options)
                        // If not found, just use the value logic
                         label.innerText = sizes[key];
                         // Highlight selected
                         const wrapper = input.closest('.custom-select-wrapper');
                         if(wrapper) {
                            // We can try to find the actual .option element to highlight it, 
                            // but finding by text/value in filtered list is tricky.
                            // Basic feedback:
                         }
                    }
                }
            });
        }

        openModal('editPersonnelModal');
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_person_name').innerText = name;
        document.getElementById('deleteForm').action = '/admin/personnel/' + id;
        openModal('deleteModal');
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('NRP berhasil disalin: ' + text);
        });
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div style="color: ${type === 'success' ? '#10B981' : '#EF4444'}; font-size: 20px;"><i class="ri-${type === 'success' ? 'checkbox-circle' : 'error-warning'}-fill"></i></div>
            <div style="font-size: 14px; color: #374151; font-weight: 500;">${message}</div>
        `;
        container.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 3000);
    }

    @if(session('success')) showToast("{{ session('success') }}"); @endif
    @if(session('error')) showToast("{{ session('error') }}", 'error'); @endif
    @if($errors->has('nrp')) 
        showToast("NRP/NIP sudah terdaftar dalam sistem.", 'error'); 
    @elseif($errors->any()) 
        showToast("Terjadi kesalahan input data.", 'error'); 
    @endif

    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
    });

    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    }

    function updateSort(sort, direction) {
        let url = window.location.href;
        url = updateQueryStringParameter(url, 'sort', sort);
        url = updateQueryStringParameter(url, 'direction', direction);
        return url;
    }

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    function selectMeasurementSize(el, itemId, sizeId, label) {
        // Update hidden input
        const wrapper = el.closest('.custom-select-wrapper');
        const input = wrapper.querySelector('input[type="hidden"]');
        input.value = sizeId;
        
        // Update Trigger Label
        const trigger = wrapper.querySelector('.select-trigger span');
        trigger.innerText = label;
        
        // Visual Feedback
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close Dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        event.stopPropagation();
    }

    window.onload = function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput && "{{ request('search') }}") {
            const val = searchInput.value;
            searchInput.value = '';
            searchInput.focus();
            searchInput.value = val;
        }

        // Initialize Add Modal Filter (Default L)
        filterMeasurements('L', 'addPersonnelModal');

        @if($errors->any())
            @if(old('modal_type') == 'add')
                openModal('addPersonnelModal');
                // Re-initialize visibility if Satker was selected
                @if(old('satker_id'))
                    const satkerNameAdd = "{{ $satkers->firstWhere('id', old('satker_id'))->name ?? '' }}";
                    updateBagianVisibility(satkerNameAdd, 'add', "{{ old('bagian') }}");
                @endif
            @elseif(old('modal_type') == 'edit' && old('id'))
                document.getElementById('editForm').action = '/admin/personnel/' + "{{ old('id') }}";
                openModal('editPersonnelModal');
                // Re-initialize visibility if Satker was selected
                @if(old('satker_id'))
                    const satkerNameEdit = "{{ $satkers->firstWhere('id', old('satker_id'))->name ?? '' }}";
                    updateBagianVisibility(satkerNameEdit, 'edit', "{{ old('bagian') }}");
                @endif
                // Trigger Rank filter for Edit
                @if(old('personnel_type'))
                    filterRanksEdit("{{ old('personnel_type') }}");
                @endif
            @endif
        @endif
    }
    function selectPrintSatker(el) {
        const value = el.dataset.value;
        const label = el.dataset.label;
        
        document.getElementById('print_satker_id').value = value;
        document.getElementById('print_satker_label').innerText = label;
        
        // Visual feedback
        const wrapper = el.closest('.custom-select-wrapper');
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Close dropdown
        el.closest('.custom-select').querySelector('.custom-options').style.display = 'none';
        el.closest('.custom-select').classList.remove('active');
        
        if (event) event.stopPropagation();
    }
    function previewPrintSatker() {
        const form = document.getElementById('printSatkerForm');
        const satkerId = document.getElementById('print_satker_id').value;
        
        if (!satkerId) {
            showToast('Silakan pilih satker terlebih dahulu', 'error');
            return;
        }

        // Ensure download parameter is NOT present for preview
        let downloadInput = form.querySelector('input[name="download"]');
        if (downloadInput) downloadInput.remove();

        form.target = "_blank";
        form.submit();
        closeModal('printSatkerModal');
    }

    function downloadPrintSatker() {
        const form = document.getElementById('printSatkerForm');
        const satkerId = document.getElementById('print_satker_id').value;
        
        if (!satkerId) {
            showToast('Silakan pilih satker terlebih dahulu', 'error');
            return;
        }

        // Add download parameter for actual download
        let downloadInput = form.querySelector('input[name="download"]');
        if (!downloadInput) {
            downloadInput = document.createElement('input');
            downloadInput.type = 'hidden';
            downloadInput.name = 'download';
            downloadInput.value = '1';
            form.appendChild(downloadInput);
        }

        form.target = "_blank";
        form.submit();
        closeModal('printSatkerModal');
    }
</script>
@endsection
