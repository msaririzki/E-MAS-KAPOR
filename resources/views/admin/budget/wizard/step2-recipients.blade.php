@extends('layouts.app')

@section('title', 'Pilih Penerima - ' . $budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Pilih Penerima</span>
@endsection

@section('content')
{{-- Wizard Steps Bar --}}
<div class="wizard-bar">
    <div class="wizard-step done"><span class="step-num"><i class="ri-check-line"></i></span> Pilih Barang</div>
    <div class="wizard-line done-line"></div>
    <div class="wizard-step active"><span class="step-num">2</span> Pilih Penerima</div>
    <div class="wizard-line"></div>
    <div class="wizard-step"><span class="step-num">3</span> Preview</div>
</div>

<div class="page-header" style="margin-top: 20px;">
    <div class="page-header-row">
        <div>
            <h1 style="font-size: 22px; font-weight: 700;">Tentukan Penerima per Barang</h1>
            <p style="color: #6B7280; font-size: 13px;">Pilih satker penerima untuk setiap item. Filter opsional — kosongkan untuk menghitung semua personil aktif.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.budget.wizard.step1', $budgetPackage) }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <a href="{{ route('admin.budget.wizard.step3', $budgetPackage) }}" class="btn btn-primary">
                Lihat Preview <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>

{{-- Items with Recipients --}}
@foreach($budgetPackage->items as $item)
<div class="recipient-card" id="item-card-{{ $item->id }}">
    <div class="recipient-card-header">
        <div class="recipient-item-info">
            <span class="badge badge-neutral" style="font-size: 10px;">{{ $item->kaporItem->category }}</span>
            <h3>{{ $item->kaporItem->item_name }}</h3>
            <span style="font-size: 13px; color: #6B7280;">
                {{ $item->formatted_price }} / {{ $item->kaporItem->unit ?? 'PCS' }}
            </span>
        </div>
        <div class="recipient-summary">
            <span class="recipient-count" id="count-{{ $item->id }}">{{ $item->recipients->sum('matched_count') }}</span>
            <span style="font-size: 11px; color: #6B7280;">personil</span>
        </div>
    </div>

    <div class="recipient-card-body">
        {{-- Satker selection --}}
        <div class="satker-select-wrap">
            <label class="section-label">PILIH SATKER PENERIMA</label>
            <div class="satker-checkboxes" id="satker-list-{{ $item->id }}">
                @foreach($allSatkers as $satker)
                <label class="satker-checkbox">
                    <input type="checkbox" name="satker_{{ $item->id }}[]"
                           value="{{ $satker->id }}"
                           {{ $item->recipients->pluck('satker_id')->contains($satker->id) ? 'checked' : '' }}>
                    <span class="satker-name">{{ $satker->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Filter Options (Dinamis) --}}
        <div class="filter-section" style="margin-top: 14px;">
            <label class="section-label">FILTER PERSONIL <span style="font-weight: 400; color: #9CA3AF;">(kosongkan = semua)</span></label>
            <div class="filter-grid">
                {{-- Tipe Personil --}}
                <div class="filter-group">
                    <label class="filter-label">Tipe Personil</label>
                    <div class="filter-pills">
                        <label class="pill-check">
                            <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="polri" data-item="{{ $item->id }}"
                                   onchange="toggleRankOptions({{ $item->id }})">
                            <span>Polri</span>
                        </label>
                        <label class="pill-check">
                            <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="pns" data-item="{{ $item->id }}"
                                   onchange="toggleRankOptions({{ $item->id }})">
                            <span>PNS</span>
                        </label>
                        <label class="pill-check">
                            <input type="checkbox" class="filter-input" data-filter="personnel_type" data-value="pppk" data-item="{{ $item->id }}"
                                   onchange="toggleRankOptions({{ $item->id }})">
                            <span>PPPK</span>
                        </label>
                    </div>
                </div>

                {{-- Gender --}}
                <div class="filter-group">
                    <label class="filter-label">Gender</label>
                    <div class="filter-pills">
                        <label class="pill-check">
                            <input type="checkbox" class="filter-input" data-filter="gender" data-value="L" data-item="{{ $item->id }}">
                            <span><i class="ri-men-line"></i> Pria</span>
                        </label>
                        <label class="pill-check">
                            <input type="checkbox" class="filter-input" data-filter="gender" data-value="P" data-item="{{ $item->id }}">
                            <span><i class="ri-women-line"></i> Wanita</span>
                        </label>
                    </div>
                </div>

                {{-- Kategori Pangkat (Dinamis berdasarkan tipe personil) --}}
                <div class="filter-group" id="rank-group-{{ $item->id }}">
                    <label class="filter-label">Kategori Pangkat</label>
                    <div class="filter-pills" id="rank-pills-{{ $item->id }}">
                        {{-- Pangkat Polri --}}
                        <label class="pill-check rank-pill polri-rank" data-type="polri">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PATI" data-item="{{ $item->id }}">
                            <span>PATI</span>
                        </label>
                        <label class="pill-check rank-pill polri-rank" data-type="polri">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PAMEN" data-item="{{ $item->id }}">
                            <span>PAMEN</span>
                        </label>
                        <label class="pill-check rank-pill polri-rank" data-type="polri">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PAMA" data-item="{{ $item->id }}">
                            <span>PAMA</span>
                        </label>
                        <label class="pill-check rank-pill polri-rank" data-type="polri">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="BINTARA" data-item="{{ $item->id }}">
                            <span>BINTARA</span>
                        </label>
                        <label class="pill-check rank-pill polri-rank" data-type="polri">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="TAMTAMA" data-item="{{ $item->id }}">
                            <span>TAMTAMA</span>
                        </label>
                        {{-- Pangkat PNS/PPPK --}}
                        <label class="pill-check rank-pill pns-rank" data-type="pns">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PNS" data-item="{{ $item->id }}">
                            <span>PNS</span>
                        </label>
                        <label class="pill-check rank-pill pns-rank" data-type="pppk">
                            <input type="checkbox" class="filter-input" data-filter="rank_categories" data-value="PPPK" data-item="{{ $item->id }}">
                            <span>PPPK</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Recipients --}}
        @if($item->recipients->count() > 0)
        <div class="current-recipients" style="margin-top: 14px;">
            <label class="section-label">SATKER TERPILIH SAAT INI</label>
            <div class="recipient-tags">
                @foreach($item->recipients as $recipient)
                <span class="recipient-tag">
                    {{ $recipient->satker->name }}
                    <span class="r-count">{{ $recipient->matched_count }} org</span>
                </span>
                @endforeach
            </div>
        </div>
        @endif

        <button class="btn btn-primary btn-sm save-btn" style="margin-top: 14px; width: 100%;"
                onclick="saveRecipients({{ $item->id }})" id="save-btn-{{ $item->id }}">
            <i class="ri-save-line"></i> Simpan Penerima
        </button>
    </div>
</div>
@endforeach

<script>
    // Mapping PackageItem IDs
    const packageItemMap = @json($budgetPackage->items->pluck('id', 'id'));

    // Dinamis: tampilkan/sembunyikan rank options berdasarkan tipe personil
    function toggleRankOptions(itemId) {
        const polriChecked = document.querySelector(`input[data-item="${itemId}"][data-value="polri"]`)?.checked;
        const pnsChecked = document.querySelector(`input[data-item="${itemId}"][data-value="pns"]`)?.checked;
        const pppkChecked = document.querySelector(`input[data-item="${itemId}"][data-value="pppk"]`)?.checked;

        const rankGroup = document.getElementById('rank-pills-' + itemId);
        if (!rankGroup) return;

        const polriRanks = rankGroup.querySelectorAll('.polri-rank');
        const pnsRanks = rankGroup.querySelectorAll('.pns-rank[data-type="pns"]');
        const pppkRanks = rankGroup.querySelectorAll('.pns-rank[data-type="pppk"]');

        const anySelected = polriChecked || pnsChecked || pppkChecked;

        // Jika tidak ada tipe yg dipilih, tampilkan semua rank
        if (!anySelected) {
            rankGroup.querySelectorAll('.rank-pill').forEach(p => { p.style.display = ''; });
            return;
        }

        // Sembunyikan/tampilkan berdasarkan pilihan
        polriRanks.forEach(p => { p.style.display = polriChecked ? '' : 'none'; });
        pnsRanks.forEach(p => { p.style.display = pnsChecked ? '' : 'none'; });
        pppkRanks.forEach(p => { p.style.display = pppkChecked ? '' : 'none'; });

        // Uncheck yang disembunyikan
        rankGroup.querySelectorAll('.rank-pill').forEach(pill => {
            if (pill.style.display === 'none') {
                pill.querySelector('input').checked = false;
            }
        });
    }

    async function saveRecipients(packageItemId) {
        const btn = document.getElementById('save-btn-' + packageItemId);
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-2-line"></i> Menyimpan...';

        // Kumpulkan satker terpilih
        const satkerList = document.getElementById('satker-list-' + packageItemId);
        const satkerIds = [];
        satkerList.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            satkerIds.push(cb.value);
        });

        if (satkerIds.length === 0) {
            alert('Pilih minimal 1 satker!');
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line"></i> Simpan Penerima';
            return;
        }

        // Kumpulkan filter
        const filters = {};
        document.querySelectorAll(`.filter-input[data-item="${packageItemId}"]:checked`).forEach(cb => {
            const key = cb.dataset.filter;
            if (!filters[key]) filters[key] = [];
            filters[key].push(cb.dataset.value);
        });

        try {
            const resp = await fetch(`/admin/budget/package-items/${packageItemId}/save-recipients`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    satker_ids: satkerIds,
                    filters: Object.keys(filters).length > 0 ? filters : null,
                })
            });

            const data = await resp.json();

            if (data.success) {
                document.getElementById('count-' + packageItemId).textContent = data.total_recipients;
                btn.innerHTML = '<i class="ri-check-line"></i> Tersimpan! (' + data.total_recipients + ' personil)';
                btn.style.background = '#10B981';

                setTimeout(() => {
                    btn.innerHTML = '<i class="ri-save-line"></i> Simpan Penerima';
                    btn.style.background = '';
                    btn.disabled = false;
                    location.reload();
                }, 1500);
            } else {
                throw new Error('Save failed');
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi error saat menyimpan. Cek console.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-save-line"></i> Simpan Penerima';
        }
    }

    // Pre-populate filters from database
    const savedFilters = @json(
        $budgetPackage->items->mapWithKeys(function($item) {
            // Ambil filter dari recipient pertama (semua recipients punya filter sama per item)
            $firstRecipient = $item->recipients->first();
            return [$item->id => $firstRecipient ? ($firstRecipient->recipient_filters ?? []) : []];
        })
    );

    // Init: pre-check filter pills and toggle rank options on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Pre-check filter pills berdasarkan data tersimpan
        Object.keys(savedFilters).forEach(itemId => {
            const filters = savedFilters[itemId];
            if (!filters || Object.keys(filters).length === 0) return;

            // Pre-check personnel_type
            if (filters.personnel_type && Array.isArray(filters.personnel_type)) {
                filters.personnel_type.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="personnel_type"][data-value="${val.toLowerCase()}"]`);
                    if (cb) cb.checked = true;
                });
            }

            // Pre-check gender
            if (filters.gender && Array.isArray(filters.gender)) {
                filters.gender.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="gender"][data-value="${val}"]`);
                    if (cb) cb.checked = true;
                });
            }

            // Pre-check rank_categories
            if (filters.rank_categories && Array.isArray(filters.rank_categories)) {
                filters.rank_categories.forEach(val => {
                    const cb = document.querySelector(`input.filter-input[data-item="${itemId}"][data-filter="rank_categories"][data-value="${val}"]`);
                    if (cb) cb.checked = true;
                });
            }
        });

        // Toggle rank visibility berdasarkan tipe personel yang tercheck
        @foreach($budgetPackage->items as $item)
            toggleRankOptions({{ $item->id }});
        @endforeach
    });
</script>
@endsection

@section('styles')
<style>
    .wizard-bar {
        display: flex; align-items: center;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 12px;
        padding: 16px 24px;
    }
    .wizard-step {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600; color: #9CA3AF; white-space: nowrap;
    }
    .wizard-step.active { color: #B91C1C; }
    .wizard-step.done { color: #10B981; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; background: #F3F4F6; color: #9CA3AF;
    }
    .wizard-step.active .step-num { background: #B91C1C; color: #fff; }
    .wizard-step.done .step-num { background: #10B981; color: #fff; }
    .wizard-line { flex: 1; height: 2px; background: #E5E7EB; margin: 0 12px; }
    .wizard-line.done-line { background: #10B981; }

    .page-header-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }

    .section-label {
        font-size: 12px; font-weight: 700; color: #374151;
        text-transform: uppercase; display: block; margin-bottom: 8px;
    }

    .recipient-card {
        background: #fff; border: 1px solid #E5E7EB; border-radius: 14px;
        margin-bottom: 16px; overflow: hidden;
    }
    .recipient-card-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; background: #F9FAFB; border-bottom: 1px solid #F3F4F6;
    }
    .recipient-item-info h3 { font-size: 15px; font-weight: 700; color: #111827; margin: 4px 0 2px; }
    .recipient-summary { text-align: center; }
    .recipient-count { font-size: 24px; font-weight: 800; color: #B91C1C; display: block; }

    .recipient-card-body { padding: 20px; }

    /* Satker checkboxes (scrollable grid) */
    .satker-checkboxes {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 6px; max-height: 180px; overflow-y: auto;
        padding: 10px; border: 1px solid #E5E7EB; border-radius: 10px;
        background: #FAFAFA;
    }
    .satker-checkbox {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 500; color: #374151; cursor: pointer;
        padding: 4px 6px; border-radius: 6px; transition: background 0.15s;
    }
    .satker-checkbox:hover { background: #F3F4F6; }
    .satker-checkbox input[type="checkbox"] {
        accent-color: #B91C1C; width: 16px; height: 16px; flex-shrink: 0;
    }
    .satker-name { line-height: 1.3; }

    /* Filters */
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .filter-label { font-size: 11px; font-weight: 600; color: #6B7280; margin-bottom: 6px; display: block; }
    .filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }

    .pill-check { cursor: pointer; }
    .pill-check input { display: none; }
    .pill-check span {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 5px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 600; border: 1.5px solid #E5E7EB;
        background: #fff; color: #6B7280; transition: all 0.15s;
        user-select: none;
    }
    .pill-check input:checked + span {
        background: #B91C1C; color: #fff; border-color: #B91C1C;
    }
    .pill-check span:hover { border-color: #D1D5DB; background: #F9FAFB; }
    .pill-check input:checked + span:hover { background: #991B1B; }

    .recipient-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .recipient-tag {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 600; padding: 5px 12px;
        background: #EFF6FF; color: #1D4ED8; border-radius: 20px;
    }
    .r-count {
        background: #DBEAFE; padding: 1px 6px; border-radius: 10px;
        font-size: 10px; font-weight: 700;
    }

    .save-btn { transition: all 0.2s; }

    @media (max-width: 768px) {
        .satker-checkboxes { grid-template-columns: 1fr; }
        .filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection
