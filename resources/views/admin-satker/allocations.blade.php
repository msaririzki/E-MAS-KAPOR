@extends('layouts.app')

@section('title', 'Penerima Barang Satker')
@section('breadcrumb', 'Penerima Barang')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; letter-spacing: -.3px; color: #0F172A;">
                <i class="ri-gift-line" style="margin-right: 6px; color: var(--brand);"></i>
                Penerima Barang Satker
            </h1>
            <p style="font-size: 13px; color: #64748B; margin-top: 2px;">
                {{ $stats['satker_name'] }} — TA {{ $stats['fiscal_year'] }}
            </p>
        </div>
    </div>
</div>

<div class="compact-stats-bar" id="statsContainer" style="border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1); padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div class="compact-stat-item" title="Paket yang memuat satker ini">
            <div class="compact-stat-icon blue">
                <i class="ri-archive-stack-line"></i>
            </div>
            <div class="compact-stat-content">
                <span class="compact-stat-label">Paket Tersedia</span>
                <span class="compact-stat-value">{{ number_format($stats['package_count']) }}</span>
            </div>
        </div>
        
        <div class="compact-stat-divider" style="height: 32px;"></div>

        <div class="compact-stat-item" title="{{ $stats['selected_package_name'] }}">
            <div class="compact-stat-icon emerald">
                <i class="ri-user-received-line"></i>
            </div>
            <div class="compact-stat-content">
                <span class="compact-stat-label">Personel Penerima</span>
                <span class="compact-stat-value">{{ number_format($stats['personnel_count']) }}</span>
            </div>
        </div>

        <div class="compact-stat-divider" style="height: 32px;"></div>

        <div class="compact-stat-item" title="Akumulasi barang diterima">
            <div class="compact-stat-icon indigo">
                <i class="ri-shirt-line"></i>
            </div>
            <div class="compact-stat-content">
                <span class="compact-stat-label">Total Barang</span>
                <span class="compact-stat-value">{{ number_format($stats['item_count']) }}</span>
            </div>
        </div>
    </div>

    <div>
        <button type="button" onclick="exportPdf()" class="btn btn-outline" style="padding: 10px 18px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; border: 1px solid #FECACA; background: #FEF2F2; color: #DC2626; transition: all 0.2s; box-shadow: 0 1px 2px rgba(220, 38, 38, 0.05); font-size: 13.5px;">
            <i class="ri-file-pdf-2-line" style="font-size: 16px;"></i> Export PDF
        </button>
    </div>
</div>

<div class="card" style="border-radius: 12px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--slate-100); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <h3 style="font-size: 14px; font-weight: 700; color: #1E293B; display: flex; align-items: center; gap: 8px; margin: 0;">
            <i class="ri-list-check-2" style="color: var(--brand); font-size: 16px;"></i> Daftar Alokasi
        </h3>

        <form method="GET" action="{{ route('admin-satker.allocations') }}" class="allocation-filter" id="filterForm" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div class="search-container" style="position: relative; width: 300px;">
                <i class="ri-search-line" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 15px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NRP, jabatan..."
                    oninput="debounceSearch()"
                    style="width: 100%; padding: 9px 14px 9px 38px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 13px; font-family: inherit; background: #fff; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.03);"
                    onfocus="this.style.borderColor='var(--brand)'; this.style.boxShadow='0 0 0 3px rgba(37, 99, 235, 0.1)';"
                    onblur="this.style.borderColor='#CBD5E1'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.03)';">
            </div>
            
            <div class="custom-select-wrapper" style="width: 120px;" id="budgetYearSelectWrapper">
                <div class="custom-select" onclick="toggleDropdown(this)">
                    <div class="select-trigger" style="padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                        <span id="budget_year_label" style="font-weight: 500;">TA {{ $budgetYears->firstWhere('id', $selectedBudgetYearId)?->year ?? '-' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            @foreach($budgetYears as $by)
                                <div class="option {{ $selectedBudgetYearId == $by->id ? 'selected' : '' }}" 
                                     onclick="selectBudgetYearOption(event, this, '{{ $by->id }}', 'TA {{ $by->year }}')">
                                    TA {{ $by->year }}
                                    @if($by->is_active)
                                        <span style="font-size: 9px; background: var(--brand-bg); color: var(--brand); padding: 2px 4px; border-radius: 4px; margin-left: 4px; font-weight: 700;">Aktif</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="budget_year_id" id="budget_year_id" value="{{ $selectedBudgetYearId }}">
            </div>

            <div class="custom-select-wrapper" style="width: 280px;" id="packageSelectWrapper">
                <div class="custom-select" onclick="toggleDropdown(this)">
                    <div class="select-trigger" style="padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                        <span id="package_label" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;">
                            {{ $selectedPackage?->name ? ($selectedPackage->name . ' - TA ' . ($selectedPackage->budgetYear->year ?? '-')) : 'Belum ada paket penerima' }}
                        </span>
                        <i class="ri-arrow-down-s-line" style="flex-shrink: 0;"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            @forelse($packages as $package)
                                <div class="option {{ $selectedPackage?->id === $package->id ? 'selected' : '' }}" 
                                     onclick="selectPackageOption(event, this, '{{ $package->id }}', '{{ $package->name }} - TA {{ $package->budgetYear->year ?? '-' }}')">
                                    {{ $package->name }} - TA {{ $package->budgetYear->year ?? '-' }}
                                </div>
                            @empty
                                <div class="option" style="color: #94A3B8;">Belum ada paket penerima</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <input type="hidden" name="package_id" id="package_id" value="{{ request('package_id', $selectedPackage?->id) }}">
            </div>

            <button type="button" onclick="resetFilter()" class="btn btn-outline" style="padding: 9px 14px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 6px; border: 1px solid #CBD5E1; background: #fff; color: #475569; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                <i class="ri-refresh-line"></i> Reset
            </button>
        </form>
    </div>

    <div class="card-body flush" style="position: relative;">
        <!-- Loading Overlay -->
        <div id="tableLoader" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.6); z-index: 10; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
            <div style="width: 28px; height: 28px; border: 3px solid #E2E8F0; border-top-color: var(--brand); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        </div>

        <div class="table-wrap allocation-table-wrap" id="tableContainer">
            <table class="allocation-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th>Personel</th>
                        <th>Pangkat/Gol</th>
                        <th>Jabatan / Bagian</th>
                        <th style="text-align: center;">Jenis Kelamin</th>
                        <th>Barang & Kategori</th>
                        <th style="text-align: center;">Ukuran</th>
                        <th style="text-align: center; width: 70px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td style="color: #0F172A; font-size: 13px; text-align: center; vertical-align: top; padding-top: 18px; font-weight: 600;">{{ $index + 1 }}</td>
                            <td style="vertical-align: top; padding-top: 14px;">
                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <div style="width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; background: linear-gradient(135deg, var(--brand), #2563EB); box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                                        {{ strtoupper(substr($row['full_name'], 0, 1)) }}
                                    </div>
                                    <div style="padding-top: 2px;">
                                        <div style="font-weight: 700; color: #0F172A; font-size: 13px; line-height: 1.4;">{{ $row['full_name'] }}</div>
                                        <div style="font-size: 12px; color: #0F172A; margin-top: 2px; font-weight: 500;">{{ $row['nrp'] ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="vertical-align: top; padding-top: 16px;">
                                <div style="font-size: 13px; color: #0F172A; font-weight: 600;">{{ $row['rank'] ?? '—' }}</div>
                            </td>
                            <td style="vertical-align: top; padding-top: 16px;">
                                <div style="font-size: 13px; color: #0F172A; line-height: 1.4; font-weight: 500;">{{ $row['jabatan'] ?? '—' }}</div>
                                @if($row['bagian'] && trim((string)$row['bagian']) !== '-')
                                    <div style="font-size: 12px; color: #0F172A; margin-top: 4px; display: inline-block; background: #F8FAFC; padding: 2px 6px; border-radius: 4px; border: 1px solid #E2E8F0; font-weight: 500;">{{ $row['bagian'] }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; vertical-align: top; padding-top: 16px;">
                                @php
                                    $genderLower = strtolower($row['gender']);
                                @endphp
                                @if(in_array($genderLower, ['l', 'laki-laki', 'pria']))
                                    <span class="badge" style="background:#EFF6FF; color:#2563EB; font-size: 11px; padding: 4px 8px; font-weight: 700; border: 1px solid #DBEAFE;">Pria (L)</span>
                                @elseif(in_array($genderLower, ['p', 'perempuan', 'wanita']))
                                    <span class="badge" style="background:#FDF2F8; color:#DB2777; font-size: 11px; padding: 4px 8px; font-weight: 700; border: 1px solid #FCE7F3;">Wanita (P)</span>
                                @else
                                    <span class="badge" style="background:#F1F5F9; color:#0F172A; font-size: 11px; padding: 4px 8px; font-weight: 700; border: 1px solid #E2E8F0;">{{ $row['gender'] ?: '—' }}</span>
                                @endif
                            </td>
                            <td style="vertical-align: top; padding-top: 12px;">
                                <div class="stacked-list">
                                    @foreach($row['items'] as $itemIndex => $item)
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 600; color: #0F172A; font-size: 13px;">{{ $item }}</span>
                                            <span style="font-size: 12px; color: #0F172A; font-weight: 500;">{{ $row['categories'][$itemIndex] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td style="text-align: center; vertical-align: top; padding-top: 12px;">
                                <div class="stacked-list" style="border: none;">
                                    @foreach($row['sizes'] as $size)
                                        <div style="display: flex; justify-content: center; align-items: center;">
                                            <span class="size-badge" style="color: #0F172A; font-weight: 700; font-size: 12px;">{{ $size }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td style="text-align: center; vertical-align: top; padding-top: 16px;">
                                <span class="item-count">{{ $row['item_count'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 48px; color: #94A3B8;">
                                <i class="ri-inbox-line" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                                <div>Belum ada data penerima barang untuk filter ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle Dropdown
    function toggleDropdown(element) {
        const wrapper = element.closest('.custom-select-wrapper');
        const select = wrapper.querySelector('.custom-select');
        const wasActive = select.classList.contains('active');
        
        document.querySelectorAll('.custom-select').forEach(el => {
            el.classList.remove('active');
            const opts = el.querySelector('.custom-options');
            if (opts) opts.style.display = 'none';
        });
        
        if (!wasActive) {
            select.classList.add('active');
            const opts = select.querySelector('.custom-options');
            if (opts) opts.style.display = 'block';
        }
    }

    // Select Option
    function selectPackageOption(event, element, value, label) {
        event.stopPropagation();
        
        const wrapper = element.closest('.custom-select-wrapper');
        wrapper.querySelector('#package_id').value = value;
        wrapper.querySelector('#package_label').innerText = label;
        
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        const select = wrapper.querySelector('.custom-select');
        select.classList.remove('active');
        select.querySelector('.custom-options').style.display = 'none';
        
        applyFilter();
    }

    function selectBudgetYearOption(event, element, value, label) {
        event.stopPropagation();
        
        const wrapper = element.closest('.custom-select-wrapper');
        wrapper.querySelector('#budget_year_id').value = value;
        wrapper.querySelector('#budget_year_label').innerText = label;
        
        wrapper.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        const select = wrapper.querySelector('.custom-select');
        select.classList.remove('active');
        select.querySelector('.custom-options').style.display = 'none';
        
        // Reset package_id so backend automatically selects the first package of the new budget year
        document.getElementById('package_id').value = '';
        
        applyFilter();
    }

    // Close Dropdown outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select').forEach(select => {
                select.classList.remove('active');
                const opts = select.querySelector('.custom-options');
                if (opts) opts.style.display = 'none';
            });
        }
    });

    // Debounce search
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilter();
        }, 500);
    }

    // Apply Filter (AJAX)
    function applyFilter() {
        const form = document.getElementById('filterForm');
        const url = new URL(form.action);
        const searchParams = new URLSearchParams(new FormData(form));
        url.search = searchParams.toString();

        document.getElementById('tableLoader').style.display = 'flex';
        
        // Update URL state
        window.history.pushState({}, '', url);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newTable = doc.getElementById('tableContainer');
            if (newTable) document.getElementById('tableContainer').innerHTML = newTable.innerHTML;
            
            const newStats = doc.getElementById('statsContainer');
            if (newStats) document.getElementById('statsContainer').innerHTML = newStats.innerHTML;

            const newPackageSelect = doc.getElementById('packageSelectWrapper');
            if (newPackageSelect) document.getElementById('packageSelectWrapper').innerHTML = newPackageSelect.innerHTML;
            
            document.getElementById('tableLoader').style.display = 'none';
        })
        .catch(err => {
            console.error(err);
            document.getElementById('tableLoader').style.display = 'none';
        });
    }

    // Reset filter
    function resetFilter() {
        const form = document.getElementById('filterForm');
        form.querySelector('input[name="search"]').value = '';
        applyFilter();
    }

    function exportPdf() {
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(new FormData(form)).toString();
        window.open("{{ route('admin-satker.allocations.export-pdf') }}?" + params, "_blank");
    }
</script>
@endsection

@section('styles')
<style>
    .compact-stats-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 16px 24px;
        gap: 20px;
        margin-bottom: 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    }

    .compact-stat-item {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .compact-stat-divider {
        width: 1px;
        height: 36px;
        background: #e2e8f0;
    }

    .compact-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        font-size: 22px;
        transition: transform 0.2s ease;
    }

    .compact-stat-item:hover .compact-stat-icon {
        transform: scale(1.05);
    }

    .compact-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
    .compact-stat-icon.indigo { background: #eef2ff; color: #6366f1; }
    .compact-stat-icon.emerald { background: #ecfdf5; color: #10b981; }

    .compact-stat-content {
        display: flex;
        flex-direction: column;
    }

    .compact-stat-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 2px;
    }

    .compact-stat-value {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    .allocation-filter {
        display: flex; 
        gap: 12px; 
        align-items: center;
        flex-wrap: wrap;
        flex: 1;
        justify-content: flex-end;
    }

    /* Custom Select Styles */
    .custom-select-wrapper {
        position: relative;
        user-select: none;
    }
    .custom-select {
        position: relative;
        cursor: pointer;
    }
    .select-trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid var(--slate-200);
        border-radius: 8px;
        font-size: 13px;
        color: #1E293B;
        transition: all 0.2s;
    }
    .select-trigger i {
        color: #94A3B8;
        transition: transform 0.2s;
    }
    .custom-select.active .select-trigger {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .custom-select.active .select-trigger i {
        transform: rotate(180deg);
    }
    .custom-options {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid var(--slate-200);
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        z-index: 50;
        display: none;
        overflow: hidden;
    }
    .options-scroll {
        max-height: 250px;
        overflow-y: auto;
    }
    .option {
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        transition: background 0.2s;
    }
    .option:hover {
        background: #F8FAFC;
    }
    .option.selected {
        background: #EFF6FF;
        color: #2563EB;
        font-weight: 600;
    }

    .allocation-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .allocation-table {
        min-width: 1000px;
        width: 100%;
        border-collapse: collapse;
    }

    .allocation-table th {
        white-space: nowrap;
        background: #F8FAFC;
        padding: 12px 16px;
        color: #0F172A;
        font-weight: 700;
        font-size: 12px;
        border-bottom: 1px solid #E2E8F0;
        text-align: left;
    }

    .allocation-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #F1F5F9;
        font-size: 13px;
    }

    .allocation-table tbody tr {
        transition: background-color 0.15s ease;
    }

    .allocation-table tbody tr:hover {
        background-color: #F8FAFC;
    }

    .stacked-list > div {
        padding: 4px 0;
        min-height: 28px;
        border-bottom: 1px dashed #E2E8F0;
    }

    .stacked-list > div:last-child {
        border-bottom: 0;
    }

    .size-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #F1F5F9;
        color: #475569;
        border-radius: 4px;
        font-weight: 600;
        font-size: 11px;
    }

    .item-count {
        display: inline-flex;
        min-width: 32px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--brand-bg);
        color: var(--brand);
        font-weight: 800;
        font-size: 12px;
    }

    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 900px) {
        .compact-stats-bar {
            padding: 16px;
            gap: 16px;
            border-radius: 16px;
        }
        .compact-stat-divider { display: none; }
        .compact-stat-item { flex-basis: calc(50% - 8px); }

        .allocation-filter {
            width: 100%;
            justify-content: flex-start;
        }
        .allocation-filter .search-container,
        .allocation-filter .custom-select-wrapper,
        .allocation-filter .btn {
            width: 100% !important;
            flex: 1 1 100%;
        }
    }
</style>
@endsection
