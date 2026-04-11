@extends('layouts.app')

@section('title', 'Monitoring Review Item')
@section('breadcrumb', 'Monitoring Review Item')

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-header-row">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: #111827;">Monitoring Review Item Kapor</h1>
                <p style="color: #6B7280; font-size: 14px; margin-top: 4px;">Pantau review item, laporan belum menerima, dan
                    catatan lapangan dari personil.</p>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="{{ route('superadmin.testimonials.index') }}" class="filter-form" id="filterForm">
            <div class="search-input-wrapper" style="flex: 2;">
                <i class="ri-search-line search-icon"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, item, atau catatan..." class="search-field" autocomplete="off">
            </div>

            <div class="filter-divider"></div>

            <div class="custom-select-wrapper" style="flex: 1.2;">
                <div class="custom-select" onclick="toggleDropdown(this)" style="border: none; background: transparent; height: 44px;">
                    <div class="select-trigger" style="padding-left: 10px;">
                        <span id="filter_category_label">{{ request('category') ? (['kepala' => 'Tutup Kepala', 'badan' => 'Tutup Badan', 'kaki' => 'Tutup Kaki', 'lainnya' => 'Item Lainnya / Atribut'][request('category')] ?? 'Semua Kategori') : 'Semua Kategori' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            <div class="option {{ !request('category') ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'category', '', 'Semua Kategori')">Semua Kategori</div>
                            <div class="option {{ request('category') == 'kepala' ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'category', 'kepala', 'Tutup Kepala')">Tutup Kepala</div>
                            <div class="option {{ request('category') == 'badan' ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'category', 'badan', 'Tutup Badan')">Tutup Badan</div>
                            <div class="option {{ request('category') == 'kaki' ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'category', 'kaki', 'Tutup Kaki')">Tutup Kaki</div>
                            <div class="option {{ request('category') == 'lainnya' ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'category', 'lainnya', 'Item Lainnya / Atribut')">Item Lainnya / Atribut</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="category" value="{{ request('category') }}">
            </div>

            <div class="filter-divider"></div>

            <div class="custom-select-wrapper" style="flex: 1;">
                <div class="custom-select" onclick="toggleDropdown(this)" style="border: none; background: transparent; height: 44px;">
                    <div class="select-trigger" style="padding-left: 10px;">
                        <span id="filter_status_label">{{ request('response_status') ? (\App\Models\ItemReview::RESPONSE_STATUSES[request('response_status')] ?? 'Semua Status') : 'Semua Status' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            <div class="option {{ !request('response_status') ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'response_status', '', 'Semua Status')">Semua Status</div>
                            @foreach(\App\Models\ItemReview::RESPONSE_STATUSES as $status => $label)
                                <div class="option {{ request('response_status') == $status ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'response_status', '{{ $status }}', '{{ $label }}')">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="response_status" value="{{ request('response_status') }}">
            </div>

            <div class="filter-divider"></div>

            <div class="custom-select-wrapper" style="flex: 1;">
                <div class="custom-select" onclick="toggleDropdown(this)" style="border: none; background: transparent; height: 44px;">
                    <div class="select-trigger" style="padding-left: 10px;">
                        <span id="filter_rating_label">{{ request('rating') ? request('rating') . ' Bintang' : 'Semua Rating' }}</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <div class="custom-options">
                        <div class="options-scroll">
                            <div class="option {{ !request('rating') ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'rating', '', 'Semua Rating')">Semua Rating</div>
                            @for($i = 5; $i >= 1; $i--)
                                <div class="option {{ (string) request('rating') === (string) $i ? 'selected' : '' }}" onclick="selectOptionSearch(this, 'rating', '{{ $i }}', '{{ $i }} Bintang')">{{ $i }} Bintang</div>
                            @endfor
                        </div>
                    </div>
                </div>
                <input type="hidden" name="rating" value="{{ request('rating') }}">
            </div>

            @if(request('search') || request('category') || request('response_status') || request('rating'))
                <div style="padding: 0 10px;">
                    <a href="{{ route('superadmin.testimonials.index') }}" class="btn btn-ghost" style="color: #6B7280;"><i class="ri-close-line"></i> Reset</a>
                </div>
            @endif

            <button type="submit" style="display: none;"></button>
        </form>
    </div>

    <div class="table-container">
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th style="width: 22%;">PERSONIL</th>
                        <th style="width: 20%;">ITEM</th>
                        <th style="width: 16%;">STATUS</th>
                        <th style="width: 26%;">CATATAN</th>
                        <th style="width: 16%;">TANGGAL</th>
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
                                    <div class="avatar" style="background-color: {{ $bgColor }};">{{ $nameInitial }}</div>
                                    <div class="details">
                                        <span
                                            class="name">{{ $item->user->name ?? $item->allocation?->full_name_snapshot ?? 'Personil' }}</span>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="username" id="nrp_{{ $item->id }}">{{ $item->user->nrp_nip ?? $item->allocation?->nrp_snapshot ?? '-' }}</span>
                                            @if($item->user->nrp_nip ?? $item->allocation?->nrp_snapshot)
                                                <button type="button" onclick="copyNrp('{{ $item->user->nrp_nip ?? $item->allocation?->nrp_snapshot }}', this)" 
                                                        style="background: none; border: none; padding: 2px; cursor: pointer; color: #9CA3AF; display: flex; align-items: center;" 
                                                        title="Salin NRP">
                                                    <i class="ri-file-copy-line" style="font-size: 14px;"></i>
                                                </button>
                                            @endif
                                        </div>
                                        <span class="username"
                                            style="color: #6366F1; font-weight: 600;">{{ $item->allocation?->satker_name_snapshot ?? $item->user->satker->name ?? 'Tanpa Satker' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:grid; gap:4px;">
                                    <strong style="font-size: 14px; color: #111827;">{{ $item->item_name_snapshot }}</strong>
                                    <span class="role-pill"
                                        style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe; width: fit-content;">{{ $item->category_label }}</span>
                                    <span class="username">{{ $item->package_name_snapshot ?? 'Snapshot paket' }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="display:grid; gap:8px;">
                                    <span class="role-pill"
                                        style="background: {{ $item->response_status === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? '#fffbeb' : '#ecfdf5' }}; color: {{ $item->response_status === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? '#b45309' : '#047857' }}; border-color: {{ $item->response_status === \App\Models\ItemReview::STATUS_NOT_RECEIVED ? '#fde68a' : '#a7f3d0' }}; width: fit-content;">
                                        {{ $item->response_label }}
                                    </span>
                                    @if($item->response_status === \App\Models\ItemReview::STATUS_REVIEWED)
                                        <div style="color: #F59E0B; font-size: 14px;">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="{{ ($item->rating ?? 0) >= $i ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                                            @endfor
                                        </div>
                                    @else
                                        <span class="username">Perlu cek distribusi</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div
                                    style="font-size: 13px; color: #374151; line-height: 1.6; {{ !filled($item->comment) ? 'color: #9CA3AF; font-style: italic;' : '' }}">
                                    {{ $item->display_message }}
                                </div>
                            </td>
                            <td class="last-active">
                                <div style="font-weight: 600; color: #111827;">
                                    {{ optional($item->submitted_at ?? $item->updated_at)->translatedFormat('d M Y') }}</div>
                                <div style="font-size: 11px; color: #9CA3AF;">Pukul
                                    {{ optional($item->submitted_at ?? $item->updated_at)->format('H:i') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 48px; color: #9CA3AF;">Belum ada hasil yang
                                ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($testimonials->total() > 0)
                <div class="table-footer">
                    <div class="footer-left">
                        Menampilkan {{ $testimonials->firstItem() ?? 0 }} hingga {{ $testimonials->lastItem() ?? 0 }} dari
                        {{ $testimonials->total() }} data
                    </div>

                    <div class="footer-right">
                        <div class="pagination-controls">
                            <a href="{{ $testimonials->url(1) }}"
                                class="page-btn {{ $testimonials->onFirstPage() ? 'disabled' : '' }}"><i
                                    class="ri-double-left-line"></i></a>
                            <a href="{{ $testimonials->previousPageUrl() }}"
                                class="page-btn {{ $testimonials->onFirstPage() ? 'disabled' : '' }}"><i
                                    class="ri-arrow-left-s-line"></i></a>
                            <span class="page-info">Halaman <strong>{{ $testimonials->currentPage() }}</strong> dari
                                <strong>{{ $testimonials->lastPage() }}</strong></span>
                            <a href="{{ $testimonials->nextPageUrl() }}"
                                class="page-btn {{ !$testimonials->hasMorePages() ? 'disabled' : '' }}"><i
                                    class="ri-arrow-right-s-line"></i></a>
                            <a href="{{ $testimonials->url($testimonials->lastPage()) }}"
                                class="page-btn {{ !$testimonials->hasMorePages() ? 'disabled' : '' }}"><i
                                    class="ri-double-right-line"></i></a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Close on click outside
        window.onclick = function(event) {
            if (!event.target.closest('.custom-select')) {
                document.querySelectorAll('.custom-options').forEach(opt => {
                    opt.style.display = 'none';
                });
                document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));
            }
        }

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

        function selectOptionSearch(el, inputName, value, label) {
            const wrapper = el.closest('.custom-select-wrapper');
            const trigger = wrapper.querySelector('.select-trigger span');
            const input = wrapper.querySelector('input[type="hidden"]');

            trigger.innerText = label;
            input.value = value;

            document.getElementById('filterForm').submit();
        }

        function copyNrp(nrp, btn) {
            navigator.clipboard.writeText(nrp).then(() => {
                const icon = btn.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'ri-check-line';
                icon.style.color = '#10B981';
                
                setTimeout(() => {
                    icon.className = originalClass;
                    icon.style.color = '';
                }, 2000);
            });
        }
    </script>
@endsection

@section('styles')
    <style>
        .page-header { margin-bottom: 24px; }
        .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 4px 6px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .filter-form { display: flex; align-items: center; width: 100%; }
        .search-input-wrapper { display: flex; align-items: center; position: relative; }
        .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
        .search-field { width: 100%; height: 44px; border: none; padding: 0 16px 0 44px; font-size: 14px; outline: none; background: transparent; color: #1f2937; }
        .search-field::placeholder { color: #9ca3af; }
        .filter-divider { width: 1px; height: 24px; background: #E5E7EB; margin: 0 8px; }

        .table-container { background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table th { background: #FAFAFA; padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; border-bottom: 1px solid #F3F4F6; }
        .user-table td { padding: 16px 20px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }

        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 14px; }
        .details { display: flex; flex-direction: column; gap: 2px; }
        .name { font-weight: 600; color: #111827; font-size: 14px; }
        .username { font-size: 12px; color: #9CA3AF; }
        .role-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700; border: 1px solid transparent; }

        .table-footer { padding: 16px 20px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .footer-left { color: #6B7280; font-size: 13px; }
        .pagination-controls { display: flex; align-items: center; gap: 8px; background: #F9FAFB; padding: 6px; border-radius: 10px; border: 1px solid #F3F4F6; }
        .page-btn { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #fff; border: 1px solid #E5E7EB; color: #374151; text-decoration: none; transition: all 0.2s; font-size: 14px; }
        .page-btn:hover:not(.disabled) { background: #3B82F6; color: #fff; border-color: #3B82F6; }
        .page-btn.disabled { opacity: 0.5; pointer-events: none; background: #F3F4F6; }
        .page-info { font-size: 13px; color: #4B5563; padding: 0 12px; }

        /* ── Custom Select UI ───────────────────── */
        .custom-select-wrapper { position: relative; width: 100%; }
        .custom-select { background: #fff; border: 1px solid transparent; border-radius: 8px; cursor: pointer; position: relative; transition: all 0.2s ease; height: 44px; display: flex; align-items: center; }
        .custom-select:hover { background: #f9fafb; }
        .custom-select.active { background: #fff; }
        .select-trigger { width: 100%; padding: 0 12px; display: flex; justify-content: space-between; align-items: center; font-weight: 500; color: #374151; font-size: 14px; }
        .select-trigger i { color: #9CA3AF; font-size: 18px; transition: transform 0.2s ease; }
        .custom-select.active .select-trigger { color: #111827; }
        .custom-select.active .select-trigger i { transform: rotate(180deg); color: #B91C1C; }
        .custom-options { position: absolute; top: calc(100% + 8px); left: 0; right: 0; background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); z-index: 2000; display: none; padding: 6px; }
        .options-scroll { max-height: 240px; overflow-y: auto; }
        .option { padding: 10px 12px; cursor: pointer; transition: all 0.1s; font-size: 14px; color: #4B5563; border-radius: 8px; margin-bottom: 2px; font-weight: 500; display: flex; align-items: center; justify-content: space-between; }
        .option:hover { background-color: #F9FAFB; color: #111827; }
        .option.selected { background-color: #FEF2F2; color: #B91C1C; font-weight: 600; }
        .btn-ghost { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .btn-ghost:hover { background: #F3F4F6; color: #111827; }
    </style>
@endsection