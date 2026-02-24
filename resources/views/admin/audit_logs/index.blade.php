@extends('layouts.app')

@section('title', 'Log Audit')
@section('breadcrumb', 'Log Audit')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
    <div class="page-header-row">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                <div style="width: 40px; height: 40px; background: #FEF2F2; color: #B91C1C; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ri-shield-user-line"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0;">Log Audit Aktivitas</h1>
            </div>
            <p style="color: #6B7280; font-size: 14px; margin-left: 52px;">Pantau seluruh jejak aktivitas admin dan pengguna dalam sistem</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-maroon" onclick="window.location.reload()">
                <i class="ri-refresh-line"></i> Refresh Data
            </button>
        </div>
    </div>
</div>

{{-- Stats Grid --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-history-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Kegiatan</span>
            <span class="stat-number">{{ number_format($stats['total']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-calendar-check-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Kegiatan Hari Ini</span>
            <span class="stat-number">{{ number_format($stats['today']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-red">
            <i class="ri-error-warning-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Aksi Gagal</span>
            <span class="stat-number">{{ number_format($stats['failed']) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="ri-key-2-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Autentikasi</span>
            <span class="stat-number">{{ number_format($stats['auth']) }}</span>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filter-form" id="filterForm">
        <div class="search-input" style="flex: 2;">
            <i class="ri-search-line"></i>
            <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari target, pengguna, atau detail..." autocomplete="off" oninput="debounceSearch()">
            @if(request('search'))
                <button type="button" class="clear-search" onclick="document.getElementById('searchInput').value=''; document.getElementById('filterForm').submit();">
                    <i class="ri-close-circle-fill"></i>
                </button>
            @endif
        </div>

        <div style="display: flex; gap: 12px; flex: 3;">
            <div class="date-input-wrapper" style="flex: 1;">
                <label style="font-size: 11px; font-weight: 600; color: #6B7280; margin-bottom: 4px; display: block;">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-input-simple" onchange="this.form.submit()" style="height: 46px;">
            </div>
            <div class="date-input-wrapper" style="flex: 1;">
                <label style="font-size: 11px; font-weight: 600; color: #6B7280; margin-bottom: 4px; display: block;">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-input-simple" onchange="this.form.submit()" style="height: 46px;">
            </div>
        </div>
        
        <div class="custom-select-wrapper" style="min-width: 140px;">
            <label style="font-size: 11px; font-weight: 600; color: #6B7280; margin-bottom: 4px; display: block;">Aksi</label>
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>{{ request('action') ?: 'Semua Aksi' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options">
                    <div class="options-scroll">
                        <div class="option {{ !request('action') ? 'selected' : '' }}" onclick="selectOption(this, 'action', '', 'Semua Aksi')">Semua Aksi</div>
                        @foreach($actions as $action)
                            <div class="option {{ request('action') == $action ? 'selected' : '' }}" onclick="selectOption(this, 'action', '{{ $action }}', '{{ $action }}')">{{ $action }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="action" value="{{ request('action') }}">
        </div>

        <div class="custom-select-wrapper" style="min-width: 140px;">
            <label style="font-size: 11px; font-weight: 600; color: #6B7280; margin-bottom: 4px; display: block;">Peran</label>
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>{{ request('role') ? ucfirst(request('role')) : 'Semua Peran' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options">
                    <div class="options-scroll">
                        <div class="option {{ !request('role') ? 'selected' : '' }}" onclick="selectOption(this, 'role', '', 'Semua Peran')">Semua Peran</div>
                        @foreach($roles as $role)
                            <div class="option {{ request('role') == $role->name ? 'selected' : '' }}" onclick="selectOption(this, 'role', '{{ $role->name }}', '{{ ucfirst($role->name) }}')">{{ ucfirst($role->name) }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="role" value="{{ request('role') }}">
        </div>

        <div style="display: flex; align-items: flex-end;">
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-simple btn-outline-simple" style="height: 46px; display: flex; align-items: center; justify-content: center; padding: 0 16px;">Reset</a>
        </div>
    </form>
</div>

{{-- Audit Log Table --}}
<div class="table-container shadow-sm">
    <table class="user-table">
        <thead>
            <tr>
                <th>WAKTU <i class="ri-arrow-down-s-fill"></i></th>
                <th>PENGGUNA</th>
                <th>PERAN</th>
                <th>TINDAKAN</th>
                <th>KATEGORI</th>
                <th>TARGET</th>
                <th>STATUS</th>
                <th>IP ADDRESS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>
                    <div style="white-space: nowrap;">
                        <div style="font-size: 13px; font-weight: 600; color: #1F2937;">{{ $log->created_at->translatedFormat('d M Y') }}</div>
                        <div style="font-size: 11px; color: #6B7280; margin-top: 2px;">{{ $log->created_at->format('H:i') }} WITA</div>
                    </div>
                </td>
                <td>
                    @if($log->user)
                        <div class="user-info">
                            <div class="avatar" style="background: {{ '#' . substr(md5($log->user->name), 0, 6) }}">
                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                            </div>
                            <div class="details">
                                <span class="name">{{ $log->user->name }}</span>
                                <span class="username">{{ $log->user->username }}</span>
                            </div>
                        </div>
                    @else
                        <span style="color: #9CA3AF;">System / Unknown</span>
                    @endif
                </td>
                <td>
                    @if($log->user)
                        @foreach($log->user->roles as $role)
                            <span class="role-pill">{{ $role->name }}</span>
                        @endforeach
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span class="action-pill {{ strtolower($log->action) }}">
                        {{ strtoupper($log->action) }}
                    </span>
                </td>
                <td>
                    <span style="font-size: 13px; color: #4B5563;">{{ $log->category ?: 'General' }}</span>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 500; color: #111827;">
                            {{ $log->details ?: ($log->auditable_type ? class_basename($log->auditable_type) . ' #' . $log->auditable_id : '—') }}
                        </span>
                        @if($log->auditable_id)
                            <i class="ri-file-copy-line" style="color: #3B82F6; cursor: pointer; font-size: 14px;" title="Copy ID"></i>
                        @endif
                    </div>
                </td>
                <td>
                    <span class="status-pill {{ $log->status == 'success' ? 'active-status' : 'inactive-status' }}">
                        <i class="{{ $log->status == 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line' }}"></i>
                        {{ $log->status == 'success' ? 'Berhasil' : 'Gagal' }}
                    </span>
                </td>
                <td>
                    <span style="font-family: monospace; font-size: 12px; color: #6B7280;">{{ $log->ip_address }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 48px; color: #9CA3AF;">
                    <i class="ri-inbox-line" style="font-size: 48px; display: block; margin-bottom: 12px;"></i>
                    Belum ada data log audit ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="table-footer">
        <div class="footer-left">
            Menampilkan {{ $logs->firstItem() ?? 0 }} hingga {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
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
                <a href="{{ $logs->url(1) }}" class="page-btn {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                    <i class="ri-double-left-line"></i>
                </a>
                <a href="{{ $logs->previousPageUrl() }}" class="page-btn {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                    <i class="ri-arrow-left-s-line"></i>
                </a>
                <span class="page-info">Halaman <strong>{{ $logs->currentPage() }}</strong> dari <strong>{{ $logs->lastPage() }}</strong></span>
                <a href="{{ $logs->nextPageUrl() }}" class="page-btn {{ !$logs->hasMorePages() ? 'disabled' : '' }}">
                    <i class="ri-arrow-right-s-line"></i>
                </a>
                <a href="{{ $logs->url($logs->lastPage()) }}" class="page-btn {{ !$logs->hasMorePages() ? 'disabled' : '' }}">
                    <i class="ri-double-right-line"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    /* Reuse logic from User Index */
    .btn-maroon { background: #B91C1C !important; border: none; color: #fff; border-radius: 8px; padding: 10px 20px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-maroon:hover { background: #991B1B !important; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .icon-blue { background: #EBF5FF; color: #3B82F6; }
    .icon-green { background: #F0FDF4; color: #22C55E; }
    .icon-red { background: #FEF2F2; color: #EF4444; }
    .icon-orange { background: #FFF7ED; color: #F97316; }
    .stat-label { font-size: 13px; color: #6B7280; display: block; }
    .stat-number { font-size: 20px; font-weight: 700; color: #111827; }

    .filter-bar { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; margin-bottom: 24px; }
    .filter-form { display: flex; gap: 16px; align-items: flex-end; }
    
    .search-input { position: relative; background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; height: 46px; display: flex; align-items: center; padding: 0 16px; }
    .search-input i.ri-search-line { color: #64748B; font-size: 20px; margin-right: 12px; }
    .search-input input { border: none; outline: none; width: 100%; font-size: 14px; color: #1E293B; background: transparent; }
    .clear-search { background: none; border: none; color: #D1D5DB; cursor: pointer; font-size: 18px; margin-left: 8px; }

    .custom-select-wrapper { position: relative; }
    .custom-select { background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; height: 46px; cursor: pointer; position: relative; transition: all 0.2s; }
    .select-trigger { height: 100%; padding: 0 16px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #374151; font-weight: 500; }
    .custom-options { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 100; display: none; margin-top: 4px; }
    .options-scroll { max-height: 240px; overflow-y: auto; padding: 4px; }
    .option { padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #4B5563; }
    .option:hover { background: #F3F4F6; }
    .option.selected { background: #FEF2F2; color: #B91C1C; font-weight: 600; }

    .table-container { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #F3F4F6; }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #FAFAFA; padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; border-bottom: 1px solid #F3F4F6; }
    .user-table td { padding: 14px 20px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }

    .user-info { display: flex; align-items: center; gap: 10px; }
    .avatar { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 700; }
    .name { font-weight: 600; color: #111827; font-size: 13px; display: block; }
    .username { color: #6B7280; font-size: 11px; }

    .role-pill { background: #F3F4F6; color: #4B5563; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    
    .action-pill { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; display: inline-block; }
    .action-pill.login { background: #E0F2FE; color: #0369A1; }
    .action-pill.create { background: #DCFCE7; color: #15803D; }
    .action-pill.update { background: #FEF3C7; color: #92400E; }
    .action-pill.delete { background: #FEE2E2; color: #B91C1C; }

    .status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; }
    .active-status { background: #F0FDF4; color: #22C55E; border: 1px solid #DCFCE7; }
    .inactive-status { background: #FEF2F2; color: #EF4444; border: 1px solid #FEE2E2; }

    .table-footer { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; background: #fff; border-top: 1px solid #F3F4F6; }
    .footer-left { display: flex; align-items: center; gap: 12px; font-size: 13px; color: #6B7280; }
    .pagination-controls { display: flex; gap: 4px; align-items: center; }
    .page-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; border-radius: 6px; color: #4B5563; text-decoration: none; }
    .page-btn.disabled { opacity: 0.5; pointer-events: none; }
    .page-info { margin: 0 8px; font-size: 13px; }

    .form-input-simple { width: 100%; border: 1px solid #E2E8F0; border-radius: 10px; padding: 0 12px; font-size: 14px; outline: none; }

</style>
@endsection

@section('scripts')
<script>
    function toggleDropdown(el) {
        const options = el.querySelector('.custom-options');
        const allOptions = document.querySelectorAll('.custom-options');
        allOptions.forEach(opt => {
            if (opt !== options) opt.style.display = 'none';
        });
        options.style.display = options.style.display === 'block' ? 'none' : 'block';
        event.stopPropagation();
    }

    function selectOption(el, inputName, value, label) {
        const wrapper = el.closest('.custom-select-wrapper');
        const trigger = wrapper.querySelector('.select-trigger span');
        const input = wrapper.querySelector('input[type="hidden"]');
        
        trigger.innerText = label;
        input.value = value;
        
        // Mark as selected
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
        
        // Hide dropdown
        wrapper.querySelector('.custom-options').style.display = 'none';
        
        // Submit form
        document.getElementById('filterForm').submit();
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
    });

    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
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
