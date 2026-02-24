@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('breadcrumb', 'Manajemen Pengguna')

@section('content')
{{-- Page Header --}}
<div class="page-header" style="margin-bottom: 24px;">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: #111827;">Manajemen Pengguna</h1>
            <p style="color: #6B7280; font-size: 14px; margin-top: 4px;">Kelola akun pengguna, peran, dan hak akses</p>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 12px;">
            <button class="btn btn-outline" onclick="openModal('importModal')" style="border-radius: 10px; padding: 10px 18px; font-weight: 600; border-color: #E5E7EB; color: #374151;">
                <i class="ri-file-upload-line" style="color: #B91C1C;"></i> Impor CSV
            </button>
            <button id="addBtn" class="btn btn-primary btn-maroon" onclick="toggleAddForm()" style="border-radius: 10px; padding: 10px 18px; font-weight: 700;">
                @if($errors->any())
                    <i class="ri-close-line"></i> Batal
                @else
                    <i class="ri-user-add-line"></i> Tambah Akun
                @endif
            </button>
        </div>
    </div>
</div>

{{-- Inline Add User Form --}}
<div id="addUserSection" class="inline-card" style="display: {{ $errors->any() ? 'block' : 'none' }}; margin-bottom: 24px;">
    <div class="card-header-simple">
        <h3 style="font-size: 18px; font-weight: 700; color: #111827;">Tambah Pengguna Baru</h3>
    </div>
    
    @if($errors->any())
        {{-- Top warning removed for cleaner granular errors --}}
    @endif
    
    <form id="addUserForm" method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="card-body-simple">
            <div class="form-grid-2">
                <div class="form-group-simple">
                    <label>Username (NRP/NIP)</label>
                    <input type="text" name="nrp_nip" value="{{ old('nrp_nip') }}" 
                        class="form-input-simple @error('nrp_nip') is-invalid @enderror" 
                        placeholder="Contoh: 123456" required>
                    @error('nrp_nip') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
                <div class="form-group-simple">
                     <label>Nama Lengkap</label>
                     <input type="text" name="name" value="{{ old('name') }}" 
                         class="form-input-simple @error('name') is-invalid @enderror" 
                         placeholder="Masukkan nama lengkap" required>
                     @error('name') <span class="error-msg">{{ $message }}</span> @enderror
                 </div>
                 <div class="form-group-simple">
                     <label>Kata Sandi</label>
                     <div class="password-wrapper-simple">
                         <input type="password" name="password" id="inline_password" 
                             class="form-input-simple @error('password') is-invalid @enderror" 
                             placeholder="••••••••" required>
                         <button type="button" class="password-toggle-simple" onclick="togglePassword('inline_password', this)">
                             <i class="ri-eye-line"></i>
                         </button>
                     </div>
                     @error('password') <span class="error-msg">{{ $message }}</span> @enderror
                 </div>
                 <div class="form-group-simple">
                     <label>Peran</label>
                     <div class="custom-select form-input-simple @error('role') is-invalid @enderror" 
                          onclick="toggleDropdown(this)" id="roleSelect">
                         <div class="select-trigger">
                             <span id="roleLabel">
                                 @if(old('role'))
                                     {{ ucfirst(old('role')) }}
                                 @else
                                     — Pilih Peran —
                                 @endif
                             </span>
                             <i class="ri-arrow-down-s-line"></i>
                         </div>
                         <div class="custom-options" style="background: #fff !important;">
                             <div class="options-scroll">
                                 <div class="option" onclick="setSelectValue('role', '', '— Pilih Peran —', this)">— Pilih Peran —</div>
                                 @foreach($roles as $role)
                                     <div class="option {{ old('role') == $role->name ? 'selected' : '' }}" 
                                          onclick="setSelectValue('role', '{{ $role->name }}', '{{ ucfirst($role->name) }}', this)">
                                         {{ ucfirst($role->name) }}
                                     </div>
                                 @endforeach
                             </div>
                         </div>
                         <input type="hidden" name="role" id="role_input" value="{{ old('role') }}" required>
                     </div>
                     @error('role') <span class="error-msg">{{ $message }}</span> @enderror
                 </div>
             </div>
             
             {{-- Optional Fields Row --}}
             <div class="form-grid-2" style="margin-top: 16px;">
                 <div class="form-group-simple">
                     <label>No. HP (WhatsApp)</label>
                     <input type="text" name="phone" value="{{ old('phone') }}" 
                         class="form-input-simple @error('phone') is-invalid @enderror" 
                         placeholder="Contoh: 08123456789">
                     @error('phone') <span class="error-msg">{{ $message }}</span> @enderror
                 </div>
             </div>
 
             <div class="form-actions-simple">
                 <button type="button" class="btn-simple btn-outline-simple" onclick="toggleAddForm()">Batal</button>
                 <button type="submit" class="btn-simple btn-maroon-simple">Simpan</button>
             </div>
        </div>
    </form>
</div>

{{-- Toast Notification Container --}}
<div id="toastContainer" class="toast-container"></div>

@if(session('success') || session('error') || $errors->any())
<script>
    window.addEventListener('DOMContentLoaded', () => {
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('warning'))
            showToast("{{ session('warning') }}", 'warning');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif
        @if($errors->any())
            showToast("Gagal menyimpan! Mohon periksa kembali inputan Anda.", 'error');
        @endif
    });
</script>
@endif

{{-- Stats Row --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-shield-user-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Admin Satker</span>
            <span class="stat-number">{{ $stats['total_admin_satker'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i class="ri-admin-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Admin Polda</span>
            <span class="stat-number">{{ $stats['total_admin_polda'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="ri-group-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Personil</span>
            <span class="stat-number">{{ $stats['total_personil'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pengguna Aktif</span>
            <span class="stat-number">{{ $stats['active_users'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-red">
            <i class="ri-lock-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Tidak Aktif</span>
            <span class="stat-number">{{ $stats['inactive_users'] }}</span>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form" id="filterForm">
        <div class="search-input">
            <i class="ri-search-line"></i>
            <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari berdasarkan nama atau username..." autocomplete="off" oninput="debounceSearch()">
            @if(request('search'))
                <button type="button" class="clear-search" onclick="document.getElementById('searchInput').value=''; document.getElementById('filterForm').submit();">
                    <i class="ri-close-circle-fill"></i>
                </button>
            @endif
        </div>
        
        <div class="custom-select-wrapper">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>{{ request('role') ? ucfirst(request('role')) : 'Semua Peran' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options" style="background: #fff !important;">
                    <div class="options-scroll">
                        <div class="option {{ !request('role') ? 'selected' : '' }}" onclick="selectOption(this, 'role', '', 'Semua Peran')">Semua Peran</div>
                        @foreach($roles as $role)
                            <div class="option {{ request('role') == $role->name ? 'selected' : '' }}" 
                                 onclick="selectOption(this, 'role', '{{ $role->name }}', '{{ ucfirst($role->name) }}')">
                                {{ ucfirst($role->name) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="role" id="filter_role" value="{{ request('role') }}">
        </div>

        <div class="custom-select-wrapper">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>
                        @if(request('status') == 'active') Aktif 
                        @elseif(request('status') == 'inactive') Tidak Aktif 
                        @else Semua Status @endif
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options" style="background: #fff !important;">
                    <div class="options-scroll">
                        <div class="option {{ !request('status') ? 'selected' : '' }}" onclick="selectOption(this, 'status', '', 'Semua Status')">Semua Status</div>
                        <div class="option {{ request('status') == 'active' ? 'selected' : '' }}" onclick="selectOption(this, 'status', 'active', 'Aktif')">Aktif</div>
                        <div class="option {{ request('status') == 'inactive' ? 'selected' : '' }}" onclick="selectOption(this, 'status', 'inactive', 'Tidak Aktif')">Tidak Aktif</div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="status" id="filter_status" value="{{ request('status') }}">
        </div>

        <input type="hidden" name="per_page" value="{{ $perPage }}">
    </form>
</div>

{{-- Users Table --}}
<div class="table-container">
    <div class="table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th style="border-top-left-radius: 12px;">PENGGUNA <i class="ri-arrow-up-down-line" style="font-size: 10px; opacity: 0.5;"></i></th>
                    <th>PERAN <i class="ri-arrow-up-down-line" style="font-size: 10px; opacity: 0.5;"></i></th>
                    <th>STATUS <i class="ri-arrow-up-down-line" style="font-size: 10px; opacity: 0.5;"></i></th>
                    <th>AKTIVITAS <i class="ri-arrow-up-down-line" style="font-size: 10px; opacity: 0.5;"></i></th>
                    <th style="border-top-right-radius: 12px; text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar" style="background-color: {{ ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'][ord($u->name[0]) % 7] }};">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div class="details">
                                <span class="name">{{ $u->name }}</span>
                                <span class="username">@<span></span>{{ $u->nrp_nip }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="pill-badges">
                            @foreach($u->roles as $role)
                                <span class="role-pill">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="status-pill active-status">
                                <i class="ri-checkbox-circle-line"></i> Aktif
                            </span>
                        @else
                            <span class="status-pill inactive-status">
                                <i class="ri-lock-line"></i> Tidak Aktif
                            </span>
                        @endif
                    </td>
                    <td class="last-active">
                        <div style="font-weight: 600; color: #111827;">{{ $u->updated_at->translatedFormat('d M Y') }}</div>
                        <div style="font-size: 11px; color: #9CA3AF;">Pukul {{ $u->updated_at->format('H:i') }}</div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon blue" onclick="openEditModal({{ json_encode($u->load(['roles', 'personnel'])) }})" title="Edit Data">
                                <i class="ri-edit-line"></i>
                            </button>
                            
                            {{-- Toggle Status Button --}}
                            <form method="POST" action="{{ route('admin.users.toggle-status', $u) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-icon {{ $u->is_active ? 'orange' : 'green' }}" 
                                    title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    {{ auth()->id() === $u->id ? 'disabled' : '' }}>
                                    <i class="{{ $u->is_active ? 'ri-lock-line' : 'ri-lock-unlock-line' }}"></i>
                                </button>
                            </form>

                            {{-- Delete Button --}}
                            <button type="button" class="btn-icon red" title="Hapus Permanen" 
                                onclick="confirmDelete({{ $u->id }}, '{{ $u->name }}')"
                                {{ auth()->id() === $u->id ? 'disabled' : '' }}>
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 48px; color: #9CA3AF;">Belum ada data pengguna yang sesuai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($users->total() > 0)
        <div class="table-footer">
            <div class="footer-left">
                Menampilkan {{ $users->firstItem() ?? 0 }} hingga {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} data
                <div class="per-page-selector">
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
                    <a href="{{ $users->url(1) }}" class="page-btn {{ $users->onFirstPage() ? 'disabled' : '' }}" title="Halaman Pertama">
                        <i class="ri-double-left-line"></i>
                    </a>
                    
                    <a href="{{ $users->previousPageUrl() }}" class="page-btn {{ $users->onFirstPage() ? 'disabled' : '' }}" title="Halaman Sebelumnya">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    
                    <span class="page-info">Halaman <strong>{{ $users->currentPage() }}</strong> dari <strong>{{ $users->lastPage() }}</strong></span>
                    
                    <a href="{{ $users->nextPageUrl() }}" class="page-btn {{ !$users->hasMorePages() ? 'disabled' : '' }}" title="Halaman Berikutnya">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    
                    <a href="{{ $users->url($users->lastPage()) }}" class="page-btn {{ !$users->hasMorePages() ? 'disabled' : '' }}" title="Halaman Terakhir">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@include('admin.users.modals')

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
    }
    .btn-maroon:hover { background: #991B1B !important; }

    /* ── Stats ──────────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
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
    }
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
    .stat-label { font-size: 13px; color: #6B7280; }
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
    
    .search-input {
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
    .search-input:focus-within { 
        border-color: #B91C1C; 
        box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.05); 
    }
    .search-input i.ri-search-line { 
        color: #64748B; 
        font-size: 20px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .search-input input {
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        font-size: 14px;
        color: #1E293B;
        padding: 0;
    }
    .search-input input::placeholder { color: #94A3B8; font-weight: 400; }

    .clear-search {
        background: none; border: none;
        color: #D1D5DB; cursor: pointer;
        padding: 4px;
        font-size: 18px; display: flex;
        align-items: center;
        margin-left: 8px;
        transition: color 0.2s;
    }
    .clear-search:hover { color: #9CA3AF; }

    .select-modern {
        padding: 12px 16px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        outline: none;
        cursor: pointer;
        min-width: 180px;
        transition: all 0.2s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239CA3AF' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
    }
    .select-modern:hover { border-color: #D1D5DB; }
    .select-modern:focus { border-color: #B91C1C; box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08); }

    /* ── Custom Select UI ────────────────────────────── */
    .custom-select-wrapper { position: relative; min-width: 180px; }
    .custom-select {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    .custom-select:hover { border-color: #D1D5DB; background: #fff; }
    .custom-select.active { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08); }
    
    .select-trigger {
        height: 46px;
        padding: 0 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }
    .select-trigger i { color: #9CA3AF; font-size: 18px; transition: transform 0.2s; }
    .custom-select.active .select-trigger i { transform: rotate(180deg); color: #B91C1C; }

    .custom-options {
        position: absolute;
        top: calc(100% + 12px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        box-shadow: 0 15px 30px -10px rgba(0,0,0,0.15), 0 10px 15px -5px rgba(0,0,0,0.05);
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        display: flex;
        flex-direction: column;
        max-height: 450px;
        overflow: hidden;
    }
    .custom-select.active .custom-options { 
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .select-search-container {
        padding: 12px;
        background: #fff;
        border-bottom: 1px solid #F3F4F6;
        position: sticky;
        top: 0;
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .select-search-container i { color: #94A3B8; font-size: 16px; }
    .select-search-input {
        flex: 1;
        padding: 9px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 13px;
        color: #1F2937;
        outline: none;
        transition: all 0.2s;
        background: #F8FAFC;
    }
    .select-search-input:focus { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.05); }

    .options-scroll {
        overflow-y: auto;
        flex: 1;
        padding: 4px;
    }
    .options-scroll::-webkit-scrollbar { width: 5px; }
    .options-scroll::-webkit-scrollbar-track { background: transparent; }
    .options-scroll::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
    .options-scroll::-webkit-scrollbar-thumb:hover { background: #D1D5DB; }
    
    .option {
        padding: 10px 12px;
        font-size: 14px;
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
    .option-empty {
        padding: 30px 20px;
        text-align: center;
        color: #9CA3AF;
        font-size: 13px;
    }

    /* ── Table ──────────────────────────────────────────── */
    .table-container {
        background: #fff;
        border: 1px solid #F3F4F6;
        border-radius: 12px;
        overflow: hidden;
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
    .username { font-size: 12px; color: #9CA3AF; }

    .role-pill {
        background: #EFF6FF;
        color: #3B82F6;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #DBEAFE;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
    }
    .active-status { background: #F0FDF4; color: #22C55E; border: 1px solid #DCFCE7; }
    .inactive-status { background: #FEF2F2; color: #EF4444; border: 1px solid #FEE2E2; }

    .last-active { font-size: 13px; color: #374151; line-height: 1.4; }
    .last-active small { color: #9CA3AF; }

    .action-buttons { display: flex; gap: 8px; }
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
    .btn-icon.orange { color: #F59E0B; }
    .btn-icon.orange:hover { background: #F59E0B; color: #fff; border-color: #F59E0B; }
    .btn-icon.green { color: #10B981; }
    .btn-icon.green:hover { background: #10B981; color: #fff; border-color: #10B981; }
    .btn-icon.red { color: #EF4444; }
    .btn-icon.red:hover { background: #EF4444; color: #fff; border-color: #EF4444; }
    .btn-icon[disabled] { opacity: 0.5; cursor: not-allowed; }

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

    /* ── Form Containers ────────────────────────────── */
    .inline-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: visible;
        animation: slideDown 0.3s ease-out;
    }
    .card-header-simple { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
    .card-body-simple { padding: 24px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* ── Unified Form Styles ────────────────────────── */
    .form-group-simple label, .form-group label { 
        display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; 
    }
    
    .form-input-simple, .form-input {
        width: 100%; 
        height: 46px;
        padding: 0 16px; 
        border: 1px solid #E5E7EB; 
        border-radius: 10px;
        font-size: 14px; 
        color: #1F2937; 
        outline: none; 
        transition: all 0.2s;
        background: #F9FAFB;
        appearance: none;
    }
    
    .form-input-simple:focus, .form-input:focus { 
        border-color: #B91C1C; background: #fff; 
        box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08); 
    }

    /* Custom Arrow for Selects */
    select.form-input-simple, select.form-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239CA3AF' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 40px;
    }

    .form-input-simple.is-invalid, .form-input.is-invalid {
        border-color: #EF4444; background: #FFFBFB;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08);
    }

    .error-msg {
        display: block; color: #EF4444; font-size: 11px; font-weight: 500;
        margin-top: 4px; animation: fadeInError 0.2s ease;
    }

    .password-wrapper-simple, .password-wrapper { position: relative; display: flex; align-items: center; }
    .password-toggle-simple, .password-toggle {
        position: absolute; right: 12px; background: none; border: none;
        color: #9CA3AF; cursor: pointer; font-size: 18px; display: flex;
    }

    .form-actions-simple { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    .btn-simple, .btn-modal { padding: 10px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; border: none; }
    .btn-outline-simple, .btn-modal-outline { background: #fff; border: 1px solid #E5E7EB; color: #374151; }
    .btn-outline-simple:hover, .btn-modal-outline:hover { background: #F9FAFB; border-color: #D1D5DB; }
    .btn-maroon-simple, .btn-modal-maroon { background: #B91C1C; border: 1px solid #B91C1C; color: #fff; }
    .btn-maroon-simple:hover, .btn-modal-maroon:hover { background: #991B1B; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInError {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1280px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 768px) {
        .page-header-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .page-header-actions {
            width: 100%;
            justify-content: flex-start;
        }
        .page-header-actions button {
            flex: 1;
            justify-content: center;
        }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .form-grid-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
        .filter-form { 
            flex-direction: column; 
            align-items: stretch;
        }
        .filter-form .search-input,
        .filter-form .custom-select-wrapper {
            width: 100%;
        }
        .table-footer {
            flex-direction: column;
            gap: 16px;
            align-items: center;
        }
        .footer-left {
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }
        .per-page-selector { margin-left: 0; }
        .table-container { overflow-x: auto; }
    }

    /* ── Toast Notifications ───────────────────────────── */
    .toast-container {
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .toast {
        min-width: 320px;
        max-width: 450px;
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        border: 1px solid #E5E7EB;
        animation: toast-slide-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        position: relative;
        overflow: hidden;
    }

    .toast::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 100%;
        background: currentColor;
        opacity: 0.3;
        animation: toast-progress 4s linear forwards;
    }

    .toast.success { border-left: 4px solid #10B981; color: #10B981; }
    .toast.warning { border-left: 4px solid #F59E0B; color: #F59E0B; }
    .toast.error { border-left: 4px solid #EF4444; color: #EF4444; }

    .toast-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .toast.success .toast-icon { background: #DCFCE7; color: #10B981; }
    .toast.warning .toast-icon { background: #FFF7ED; color: #F59E0B; }
    .toast.error .toast-icon { background: #FEF2F2; color: #EF4444; }

    .toast-content { flex: 1; }
    .toast-title {
        display: block;
        font-weight: 700;
        font-size: 14px;
        color: #111827;
        margin-bottom: 2px;
    }
    .toast-message {
        font-size: 13px;
        color: #6B7280;
    }

    .toast-close {
        color: #9CA3AF;
        cursor: pointer;
        font-size: 18px;
        transition: color 0.2s;
    }
    .toast-close:hover { color: #4B5563; }

    @keyframes toast-slide-in {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes toast-fade-out {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(120%); opacity: 0; }
    }

    @keyframes toast-progress {
        from { width: 100%; }
        to { width: 0%; }
    }
</style>
@endsection

@section('scripts')
<script>
    // Helper to update URL params
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    // Custom Dropdown Logic
    function toggleDropdown(el) {
        // Close other dropdowns
        document.querySelectorAll('.custom-select').forEach(s => {
            if (s !== el) s.classList.remove('active');
        });
        
        el.classList.toggle('active');
        
        // Focus search input if exists
        if (el.classList.contains('active')) {
            const searchInput = el.querySelector('.select-search-input');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        }
    }

    function setSelectValue(inputName, value, label, el) {
        const wrapper = el.closest('.custom-select');
        const input = document.getElementById(inputName + '_input') || wrapper.querySelector('input[type="hidden"]');
        const labelEl = document.getElementById(inputName + 'Label') || wrapper.querySelector('.select-trigger span');
        
        input.value = value;
        labelEl.innerText = label;
        
        // Update selection state
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Specific for edit modal syncing
        if (wrapper.id === 'editRoleSelect') {
            document.getElementById('edit_role').value = value;
        }
        if (wrapper.id === 'editSatkerSelect') {
            document.getElementById('edit_satker_id').value = value;
        }

        wrapper.classList.remove('active');
        event.stopPropagation();
    }

    function filterDropdownOptions(input) {
        const filter = input.value.toLowerCase();
        const optionsContainer = input.closest('.custom-options').querySelector('.options-scroll');
        const options = optionsContainer.querySelectorAll('.option');
        const emptyMsg = optionsContainer.querySelector('.option-empty');
        let hasVisible = false;

        options.forEach(opt => {
            if (opt.innerText.toLowerCase().includes(filter)) {
                opt.style.display = 'flex';
                hasVisible = true;
            } else {
                opt.style.display = 'none';
            }
        });

        if (emptyMsg) {
            emptyMsg.style.display = hasVisible ? 'none' : 'block';
        }
    }

    function selectOption(el, inputName, value, label) {
        const wrapper = el.closest('.custom-select');
        const input = document.getElementById('filter_' + inputName);
        const labelEl = wrapper.querySelector('.select-trigger span');
        
        input.value = value;
        labelEl.innerText = label;
        
        // Update selection state
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');

        wrapper.classList.remove('active');
        
        // Trigger generic filter form submission
        document.getElementById('filterForm').submit();
        
        event.stopPropagation();
    }

    // Close on outside click
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('active'));
        }
    });

    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    function toggleAddForm() {
        const section = document.getElementById('addUserSection');
        const btn = document.getElementById('addBtn');
        const form = document.getElementById('addUserForm');
        
        if (section.style.display === 'none') {
            // Reset form when opening
            if (form) {
                form.reset();
                // Clear custom dropdown labels
                const roleLabel = document.getElementById('roleLabel');
                const satkerLabel = document.getElementById('satkerLabel');
                if (roleLabel) roleLabel.innerText = '— Pilih Peran —';
                if (satkerLabel) satkerLabel.innerText = '— Pilih Satker —';
                
                // Clear hidden custom select values
                const roleInput = document.getElementById('role_input');
                const satkerInput = document.getElementById('satker_id_input');
                if (roleInput) roleInput.value = '';
                if (satkerInput) satkerInput.value = '';

                // Remove 'selected' class from custom options
                document.querySelectorAll('#addUserSection .option').forEach(opt => opt.classList.remove('selected'));
            }

            section.style.display = 'block';
            btn.innerHTML = '<i class="ri-close-line"></i> Batal';
            btn.classList.add('btn-maroon');
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            section.style.display = 'none';
            btn.innerHTML = '<i class="ri-user-add-line"></i> Tambah Pengguna Baru';
            btn.classList.remove('btn-maroon');
        }
    }

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-line');
            icon.classList.add('ri-eye-off-line');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-off-line');
            icon.classList.add('ri-eye-line');
        }
    }

    function openEditModal(user) {
        document.getElementById('edit_nrp_nip').value = user.nrp_nip;
        document.getElementById('edit_name').value = user.name;
        
        // If role is personil, take phone from personnel data
        const isPersonil = user.roles && user.roles.some(r => r.name === 'personil');
        let phoneNumber = user.phone || '';
        if (isPersonil && user.personnel && user.personnel.phone) {
            phoneNumber = user.personnel.phone;
        }
        document.getElementById('edit_phone').value = phoneNumber;

        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_is_active').checked = user.is_active;

        // Set Custom Select for Role
        const roleInput = document.getElementById('edit_role');
        const roleLabel = document.getElementById('edit_role_label');
        if (user.roles && user.roles.length > 0) {
            const roleName = user.roles[0].name;
            roleInput.value = roleName;
            roleLabel.innerText = roleName.charAt(0).toUpperCase() + roleName.slice(1);
            
            // Mark as selected in dropdown
            document.querySelectorAll('#editRoleSelect .option').forEach(opt => {
                opt.classList.toggle('selected', opt.getAttribute('data-value') === roleName);
            });
        }

        document.getElementById('editForm').action = '/admin/users/' + user.id;
        openModal('editModal');
    }

    function confirmDelete(userId, userName) {
        document.getElementById('delete_user_name').innerText = userName;
        document.getElementById('deleteForm').action = '/admin/users/' + userId;
        openModal('deleteModal');
    }

    // Auto-hide alerts
    var flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(function() {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity 0.3s';
            setTimeout(function() { flash.remove(); }, 300);
        }, 5000);
    }

    // Live Search Debounce
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500); // Wait 500ms after last keystroke
    }

    // Keep focus and cursor at end of input
    window.onload = function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput && "{{ request('search') }}") {
            const val = searchInput.value;
            searchInput.value = '';
            searchInput.focus();
            searchInput.value = val;
        }
    }
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill';
        const title = type === 'success' ? 'Berhasil!' : 'Terjadi Kesalahan';
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icon}"></i>
            </div>
            <div class="toast-content">
                <span class="toast-title">${title}</span>
                <span class="toast-message">${message}</span>
            </div>
            <i class="ri-close-line toast-close" onclick="this.parentElement.remove()"></i>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.style.animation = 'toast-fade-out 0.4s ease forwards';
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 400);
        }, 4000);
    }

    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }
</script>
@endsection
