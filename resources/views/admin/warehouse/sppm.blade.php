@extends('layouts.app')

@section('title', 'SPPM Gudang')
@section('breadcrumb', 'Data Gudang / SPPM')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Surat Perintah Pengeluaran Materiil (SPPM)</h1>
            <p class="page-subtitle">Download SPPM yang telah digabungkan per pengeluaran</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.warehouse-items.reports') }}" class="btn btn-outline">
                <i class="ri-file-list-3-line"></i> Lihat Laporan Detail
            </a>
        </div>
    </div>
</div>

{{-- Filter & Table --}}
<div class="card">
    <div class="card-body">
        <div class="filter-bar-modern" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <form method="GET" action="{{ route('admin.warehouse-items.sppm') }}" class="filter-form" style="display:flex; flex-wrap: wrap; gap:12px; align-items: flex-end;">
                <div class="filter-group" style="flex: 1; min-width: 200px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Cari SPPM</label>
                    <div class="search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari penerima atau nomor surat..." class="search-input" autocomplete="off" style="padding-left: 36px;">
                    </div>
                </div>

                <div class="filter-group" style="width: 220px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Satuan Kerja</label>
                    <div class="custom-select-wrapper">
                        <div class="custom-select" onclick="toggleDropdown(this)">
                            <div class="select-trigger">
                                <span id="filter_satker_label">{{ request('satker_id') ? $satkers->firstWhere('id', request('satker_id'))->name ?? '-- Semua Satker --' : '-- Semua Satker --' }}</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                            <div class="custom-options">
                                <div class="select-search-container">
                                    <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterCustomOptions(this)">
                                </div>
                                <div class="options-scroll">
                                    <div class="option {{ !request('satker_id') ? 'selected' : '' }}" data-label="-- Semua Satker --" onclick="selectCustomOptionFilter('satker_id', '', '-- Semua Satker --')">-- Semua Satker --</div>
                                    @foreach($satkers as $satker)
                                        <div class="option {{ request('satker_id') == $satker->id ? 'selected' : '' }}" data-label="{{ $satker->name }}" onclick="selectCustomOptionFilter('satker_id', '{{ $satker->id }}', '{{ $satker->name }}')">{{ $satker->name }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="satker_id" id="satker_id" value="{{ request('satker_id') }}">
                    </div>
                </div>

                <div class="filter-group" style="width: 140px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="search-input" style="padding: 7px 12px; height: 36px;">
                </div>

                <div class="filter-group" style="width: 140px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase;">Hingga</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="search-input" style="padding: 7px 12px; height: 36px;">
                </div>

                <div class="filter-actions" style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary" style="height:36px; padding:0 20px;">
                        <i class="ri-filter-3-line"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'satker_id', 'start_date', 'end_date']))
                        <a href="{{ route('admin.warehouse-items.sppm') }}" class="btn btn-outline" style="height:36px; padding:0 16px;" title="Reset Filter">
                            <i class="ri-refresh-line"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table class="user-table">
        <thead>
            <tr>
                <th>NO</th>
                <th>SATKER</th>
                <th>TGL KELUAR</th>
                <th>PENERIMA</th>
                <th>NO. SURAT</th>
                <th>TGL SURAT</th>
                <th style="text-align: center;">JUMLAH BARANG</th>
                <th style="text-align: center;">TOTAL QTY</th>
                <th style="text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @if ($groupedOutflows->count() > 0)
                @foreach ($groupedOutflows as $index => $group)
                    <tr>
                        <td>{{ $groupedOutflows->firstItem() + $index }}</td>
                        <td>
                            @if($group->satker)
                                <span style="font-size: 13px; color: #374151; font-weight: 600;">{{ $group->satker->name }}</span>
                            @else
                                <span style="font-size: 13px; color: #6B7280;">-</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($group->outflow_date)->format('d/m/Y') }}</td>
                        <td>{{ $group->recipient_name ?: '-' }}</td>
                        <td style="font-size: 12px;">{{ $group->letter_number ?: '-' }}</td>
                        <td style="font-size: 12px;">{{ $group->letter_date ? \Carbon\Carbon::parse($group->letter_date)->format('d/m/Y') : '-' }}</td>
                        <td style="text-align: center;">
                            <span style="background: #EFF6FF; color: #3B82F6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                {{ $group->item_count }} Item
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <strong style="color: #D97706;">{{ number_format($group->total_quantity, 0, ',', '.') }}</strong>
                        </td>
                        <td style="text-align: center;">
                            <form action="{{ route('admin.warehouse-items.download-sppm-grouped') }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="group_ids" value="{{ $group->group_ids }}">
                                <input type="hidden" name="letter_number" value="{{ $group->letter_number }}">
                                <input type="hidden" name="letter_date" value="{{ $group->letter_date ? \Carbon\Carbon::parse($group->letter_date)->format('Y-m-d') : date('Y-m-d') }}">
                                
                                <button type="submit" class="btn" 
                                    style="background: #059669; color: white; padding: 8px 14px; font-size: 13px; height: 36px; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                                    <i class="ri-file-word-line" style="font-size: 16px;"></i> Download SPPM
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="9" style="text-align: center; color: #9CA3AF; padding: 48px;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                            <i class="ri-inbox-line" style="font-size: 48px; color: #E5E7EB;"></i>
                            <span>Belum ada data pengeluaran untuk dibuatkan SPPM.</span>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    
        </div>

        @if($groupedOutflows->hasPages())
            <div class="pagination-footer">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $groupedOutflows->firstItem() }}</strong> sampai <strong>{{ $groupedOutflows->lastItem() }}</strong> dari <strong>{{ $groupedOutflows->total() }}</strong> data
                </div>
                <div class="pagination-links">
                    {{ $groupedOutflows->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Style Overrides and Refinements */
    .user-table thead th {
        background: var(--slate-50);
        padding: 14px 20px;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: var(--slate-500);
        font-weight: 700;
        border-bottom: 2px solid var(--slate-100);
    }
    .user-table tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid var(--slate-100);
    }
    
    /* Pagination Styling */
    .pagination-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .pagination-info {
        font-size: 13px;
        color: var(--slate-500);
    }
    .pagination-links .pagination {
        display: flex;
        list-style: none;
        gap: 4px;
        margin: 0;
        padding: 0;
    }
    .pagination-links .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #fff;
        color: var(--slate-600);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .pagination-links .page-item.active .page-link {
        background: var(--brand);
        border-color: var(--brand);
        color: #fff;
    }
    .pagination-links .page-item.disabled .page-link {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .pagination-links .page-item:not(.active):not(.disabled) .page-link:hover {
        background: var(--slate-50);
        border-color: var(--slate-300);
        color: var(--brand);
    }

    /* Custom Select & Dropdown UI */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select {
        background: #fff; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer;
        position: relative; transition: all 0.2s ease; height: 36px; display: flex; align-items: center;
    }
    .custom-select:hover { border-color: var(--slate-400); }
    .custom-select.active { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.08); }

    .select-trigger {
        width: 100%; padding: 0 12px; display: flex; justify-content: space-between; align-items: center;
        font-weight: 500; color: var(--text-main); font-size: 13px;
    }
    .select-trigger i { color: var(--slate-400); font-size: 18px; transition: transform 0.2s ease; }
    .custom-select.active .select-trigger i { transform: rotate(180deg); color: var(--brand); }

    .custom-options {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1px solid var(--border-color); border-radius: 12px;
        box-shadow: var(--shadow-lg); z-index: 2000;
        display: none; flex-direction: column; padding: 6px;
    }
    .options-scroll { max-height: 240px; overflow-y: auto; padding-right: 2px; }
    .options-scroll::-webkit-scrollbar { width: 4px; }
    .options-scroll::-webkit-scrollbar-thumb { background-color: var(--slate-200); border-radius: 10px; }

    .option {
        padding: 8px 12px; cursor: pointer; transition: all 0.15s; font-size: 13px;
        color: var(--text-main); border-radius: 8px; margin-bottom: 2px; font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }
    .option:hover { background-color: var(--slate-50); color: var(--brand); }
    .option.selected { background-color: var(--brand-bg); color: var(--brand); font-weight: 600;}

    .select-search-container {
        padding: 4px; position: sticky; top: 0; background: #fff; z-index: 10;
        border-bottom: 1px solid var(--slate-100); margin-bottom: 4px;
    }
    .select-search-input {
        width: 100%; height: 32px; padding: 0 10px; border: 1px solid var(--border-color);
        border-radius: 6px; font-size: 12px; outline: none; background: var(--slate-50); transition: all 0.2s;
    }
    .select-search-input:focus { border-color: var(--brand); background: #fff; }

    @media (max-width: 768px) {
        .custom-select-wrapper { width: 100% !important; }
        .filter-actions { width: 100%; }
        .filter-actions .btn { flex: 1; }
    }
</style>
@endsection

@section('scripts')
<script>
    // Custom Select Filtering UI
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

    function selectCustomOptionFilter(inputId, value, label) {
        document.getElementById(inputId).value = value;
        document.querySelector('.filter-form').submit();
    }

    function filterCustomOptions(input) {
        const filter = input.value.toLowerCase();
        const optionsContainer = input.closest('.custom-options');
        const options = optionsContainer.querySelectorAll('.option:not(.select-search-container)');
        
        options.forEach(opt => {
            const text = (opt.dataset.label || '').toLowerCase();
            if (text.includes(filter)) {
                opt.style.display = 'flex';
            } else {
                opt.style.display = 'none';
            }
        });
    }
</script>
@endsection
