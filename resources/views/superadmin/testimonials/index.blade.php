@extends('layouts.app')

@section('title', 'Data Testimoni')
@section('breadcrumb', 'Data Testimoni')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 24px; font-weight: 700; color: #111827;">Data Testimoni</h1>
            <p style="color: #6B7280; font-size: 14px; margin-top: 4px;">Daftar masukan dan testimoni dari seluruh personil</p>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('superadmin.testimonials.index') }}" class="filter-form" id="filterForm">
        <div class="search-input">
            <i class="ri-search-line"></i>
            <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Cari pesan, nama, nrp/nip, satker..." autocomplete="off">
            @if(request('search'))
                <button type="button" class="clear-search" onclick="document.getElementById('searchInput').value=''; document.getElementById('filterForm').submit();">
                    <i class="ri-close-circle-fill"></i>
                </button>
            @endif
        </div>

        <div class="custom-select-wrapper">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>
                        @if(request('category'))
                            @php
                                $catName = \App\Models\Testimonial::CATEGORIES[request('category')] ?? request('category');
                            @endphp
                            {{ $catName }}
                        @else
                            Semua Kategori
                        @endif
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options" style="background: #fff !important;">
                    <div class="options-scroll">
                        <div class="option {{ !request('category') ? 'selected' : '' }}" onclick="selectOption(this, 'category', '', 'Semua Kategori')">Semua Kategori</div>
                        @foreach(\App\Models\Testimonial::CATEGORIES as $key => $label)
                            <div class="option {{ request('category') == $key ? 'selected' : '' }}" 
                                 onclick="selectOption(this, 'category', '{{ $key }}', '{{ $label }}')">
                                {{ $label }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="category" id="filter_category" value="{{ request('category') }}">
        </div>

        <div class="custom-select-wrapper">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span>
                        @if(request('rating'))
                            {{ request('rating') }} Bintang
                        @else
                            Semua Rating
                        @endif
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options" style="background: #fff !important;">
                    <div class="options-scroll">
                        <div class="option {{ !request('rating') ? 'selected' : '' }}" onclick="selectOption(this, 'rating', '', 'Semua Rating')">Semua Rating</div>
                        @for($i = 5; $i >= 1; $i--)
                            <div class="option {{ request('rating') == $i ? 'selected' : '' }}" 
                                 onclick="selectOption(this, 'rating', '{{ $i }}', '{{ $i }} Bintang')">
                                {{ $i }} Bintang
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            <input type="hidden" name="rating" id="filter_rating" value="{{ request('rating') }}">
        </div>
        
        <button type="submit" class="btn btn-outline" style="border-radius: 10px; padding: 0 16px; height: 46px; font-weight: 600; border-color: #E2E8F0; color: #475569; background: #fff;">
            Terapkan
        </button>
    </form>
</div>

{{-- Data Table --}}
<div class="table-container">
    <div class="table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th style="border-top-left-radius: 12px; width: 25%;">PERSONIL & SATKER</th>
                    <th style="width: 15%;">KATEGORI & RATING</th>
                    <th style="width: 45%;">PESAN / MASUKAN</th>
                    <th style="border-top-right-radius: 12px; width: 15%;">TANGGAL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($testimonials as $item)
                <tr>
                    <td>
                        <div class="user-info">
                            @php
                                $colors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'];
                                $nameInitial = strtoupper(substr($item->user->name ?? 'P', 0, 1));
                                $bgColor = $colors[ord($nameInitial) % count($colors)];
                            @endphp
                            <div class="avatar" style="background-color: {{ $bgColor }};">
                                {{ $nameInitial }}
                            </div>
                            <div class="details">
                                <span class="name">{{ $item->user->name ?? 'Personil' }}</span>
                                <span class="username" style="margin-top: 2px;">{{ $item->user->nrp_nip ?? '-' }}</span>
                                <span class="username" style="color: #6366F1; margin-top: 2px; font-weight: 500;">
                                    {{ $item->user->satker->name ?? 'Tanpa Satker' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            @if($item->category && isset(\App\Models\Testimonial::CATEGORIES[$item->category]))
                                @php
                                    $catIcons = [
                                        'tutup_kepala' => ['icon' => 'ri-shield-user-line', 'bg' => '#eff6ff', 'color' => '#2563eb'],
                                        'tutup_badan'  => ['icon' => 'ri-t-shirt-2-line', 'bg' => '#f0fdf4', 'color' => '#059669'],
                                        'tutup_kaki'   => ['icon' => 'ri-footprint-line', 'bg' => '#fff7ed', 'color' => '#d97706'],
                                    ];
                                    $catMeta = $catIcons[$item->category] ?? ['icon' => 'ri-price-tag-3-line', 'bg' => '#f3f4f6', 'color' => '#4b5563'];
                                @endphp
                                <span class="role-pill" style="background: {{ $catMeta['bg'] }}; color: {{ $catMeta['color'] }}; border-color: {{ $catMeta['color'] }}30;">
                                    <i class="{{ $catMeta['icon'] }}" style="margin-right: 4px;"></i> {{ \App\Models\Testimonial::CATEGORIES[$item->category] }}
                                </span>
                            @else
                                <span class="role-pill" style="background: #f3f4f6; color: #4b5563; border-color: #d1d5db;">
                                    Umum
                                </span>
                            @endif
                        </div>
                        <div style="margin-top: 8px; color: #F59E0B; font-size: 14px;">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="{{ ($item->rating ?? 5) >= $i ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                            @endfor
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 13px; color: #374151; line-height: 1.5; {{ !filled($item->message) ? 'color: #9CA3AF; font-style: italic;' : '' }}">
                            @if(filled($item->message))
                                "{{ $item->message }}"
                            @else
                                Tidak ada pesan
                            @endif
                        </div>
                    </td>
                    <td class="last-active">
                        <div style="font-weight: 600; color: #111827;">{{ $item->created_at->translatedFormat('d M Y') }}</div>
                        <div style="font-size: 11px; color: #9CA3AF;">Pukul {{ $item->created_at->format('H:i') }}</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding: 48px; color: #9CA3AF;">Belum ada hasil yang ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($testimonials->total() > 0)
        <div class="table-footer">
            <div class="footer-left">
                Menampilkan {{ $testimonials->firstItem() ?? 0 }} hingga {{ $testimonials->lastItem() ?? 0 }} dari {{ $testimonials->total() }} data
            </div>
            
            <div class="footer-right">
                <div class="pagination-controls">
                    <a href="{{ $testimonials->url(1) }}" class="page-btn {{ $testimonials->onFirstPage() ? 'disabled' : '' }}" title="Halaman Pertama">
                        <i class="ri-double-left-line"></i>
                    </a>
                    
                    <a href="{{ $testimonials->previousPageUrl() }}" class="page-btn {{ $testimonials->onFirstPage() ? 'disabled' : '' }}" title="Halaman Sebelumnya">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                    
                    <span class="page-info">Halaman <strong>{{ $testimonials->currentPage() }}</strong> dari <strong>{{ $testimonials->lastPage() }}</strong></span>
                    
                    <a href="{{ $testimonials->nextPageUrl() }}" class="page-btn {{ !$testimonials->hasMorePages() ? 'disabled' : '' }}" title="Halaman Berikutnya">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    
                    <a href="{{ $testimonials->url($testimonials->lastPage()) }}" class="page-btn {{ !$testimonials->hasMorePages() ? 'disabled' : '' }}" title="Halaman Terakhir">
                        <i class="ri-double-right-line"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('styles')
<style>
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

    /* Custom Select */
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
        opacity: 1; visibility: visible; transform: translateY(0);
    }
    
    .options-scroll {
        overflow-y: auto; flex: 1; padding: 4px;
    }
    
    .option {
        padding: 10px 12px;
        font-size: 14px;
        color: #4B5563;
        cursor: pointer;
        transition: all 0.1s;
        border-radius: 8px;
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 2px;
    }
    .option:last-child { margin-bottom: 0; }
    .option:hover { background: #F3F4F6; color: #111827; }
    .option.selected { background: #FEF2F2; color: #B91C1C; font-weight: 600; }

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
    .user-table td { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    
    .user-info { display: flex; align-items: center; gap: 12px; }
    .avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 14px;
    }
    .details { display: flex; flex-direction: column; }
    .name { font-weight: 600; color: #111827; }
    .username { font-size: 12px; color: #9CA3AF; }

    .role-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .last-active { font-size: 13px; color: #374151; line-height: 1.4; }

    /* ── Table Footer ────────────────────── */
    .table-footer {
        padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center;
        border-top: 1px solid #F3F4F6; background: #fff;
    }
    .footer-left { display: flex; align-items: center; gap: 12px; color: #6B7280; font-size: 13px; }
    .pagination-controls { display: flex; align-items: center; gap: 4px; }
    .page-btn {
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #E5E7EB; background: #fff; border-radius: 6px;
        color: #374151; text-decoration: none; transition: all 0.2s;
    }
    .page-btn:hover:not(.disabled) { background: #F9FAFB; border-color: #D1D5DB; }
    .page-btn.disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }
    .page-info { font-size: 13px; color: #4B5563; margin: 0 12px; }
    .page-info strong { color: #111827; }
</style>
@endsection

@section('scripts')
<script>
    function toggleDropdown(element) {
        document.querySelectorAll('.custom-select.active').forEach(el => {
            if(el !== element) el.classList.remove('active');
        });
        element.classList.toggle('active');
    }

    function selectOption(element, fieldName, value, label) {
        const wrapper = element.closest('.custom-select-wrapper');
        const trigger = wrapper.querySelector('.select-trigger span');
        const hiddenInput = document.getElementById('filter_' + fieldName);
        
        wrapper.querySelectorAll('.option').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        trigger.innerHTML = label;
        hiddenInput.value = value;
        wrapper.querySelector('.custom-select').classList.remove('active');
        
        document.getElementById('filterForm').submit();
    }

    document.addEventListener('click', (e) => {
        if(!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-select.active').forEach(el => el.classList.remove('active'));
        }
    });
</script>
@endsection
