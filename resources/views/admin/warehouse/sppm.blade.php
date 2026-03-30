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

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.warehouse-items.sppm') }}" class="filter-form" style="display:flex; gap:16px;">
        <div class="search-input-wrapper">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari penerima atau nomor surat..." class="search-field" autocomplete="off">
        </div>
        <div class="custom-select-wrapper filter-satker" style="width: 240px;">
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
        
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:600; color:#6B7280;">DARI</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-input" style="width:140px; padding:6px 12px; height:36px;">
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:600; color:#6B7280;">Hingga</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-input" style="width:140px; padding:6px 12px; height:36px;">
        </div>
        <button type="submit" class="btn btn-primary" style="height:36px; padding:0 16px;">Filter</button>
        @if(request('search') || request('start_date') || request('end_date') || request('satker_id'))
            <a href="{{ route('admin.warehouse-items.sppm') }}" class="btn btn-outline" style="height:36px; padding:0 16px;">Reset</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="table-container">
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
    
    @if($groupedOutflows->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6;">
            {{ $groupedOutflows->appends(request()->query())->links() }}
        </div>
    @endif
</div>
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

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    
    .table-container { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #F9FAFB; padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
    .user-table td { padding: 16px 24px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; color: #374151; font-size: 14px; }
    
    .form-input { padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; transition: border-color .15s;}
    
    /* Filter Bar */
    .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .search-input-wrapper { flex: 1; position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
    .search-field { width: 100%; height: 36px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 16px 0 38px; font-size: 14px; color: #374151; outline: none; background: #fff; }

    /* Custom Select */
    .custom-select-wrapper { position: relative; }
    .custom-select { background: #fff; border: 1px solid #D1D5DB; border-radius: 8px; cursor: pointer; position: relative; height: 36px; display: flex; align-items: center; }
    .select-trigger { width: 100%; padding: 0 16px; display: flex; justify-content: space-between; align-items: center; font-weight: 500; color: #374151; font-size: 13px; }
    .custom-options { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); z-index: 2000; display: none; flex-direction: column; padding: 6px; }
    .options-scroll { max-height: 240px; overflow-y: auto; }
    .option { padding: 8px 12px; cursor: pointer; transition: all 0.15s; font-size: 13px; color: #4B5563; border-radius: 8px; }
    .option:hover { background-color: #F9FAFB; color: #111827; }
    .select-search-container { padding: 4px; position: sticky; top: 0; background: #fff; z-index: 10; border-bottom: 1px solid #F3F4F6; }
    .select-search-input { width: 100%; height: 32px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: 6px; font-size: 12px; outline: none; }
</style>
@endsection
