@extends('layouts.app')

@section('title', 'Titipan SPPM - '.$budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}">{{ $budgetPackage->name }}</a>
    <span class="sep">/</span>
    <span class="current">Titipan SPPM</span>
@endsection

@section('content')
<style>
    .sppm-page { display: flex; flex-direction: column; gap: 18px; }
    .sppm-hero { background: linear-gradient(135deg, #0f172a 0%, #1f2937 45%, #991b1b 100%); border-radius: 8px; color: #fff; padding: 22px; border: 1px solid rgba(255,255,255,.18); box-shadow: 0 16px 40px rgba(15, 23, 42, .14); }
    .sppm-hero-top { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
    .sppm-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #fecaca; margin-bottom: 8px; }
    .sppm-title { font-size: 24px; line-height: 1.2; font-weight: 900; margin: 0; letter-spacing: 0; }
    .sppm-subtitle { margin: 8px 0 0; color: #e5e7eb; font-size: 13px; max-width: 760px; }
    .sppm-back { display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 12px; border-radius: 8px; color: #fff; border: 1px solid rgba(255,255,255,.28); background: rgba(255,255,255,.08); text-decoration: none; font-size: 12px; font-weight: 800; }
    .sppm-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
    .sppm-stat { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); border-radius: 8px; padding: 12px; }
    .sppm-stat span { display: block; font-size: 11px; color: #cbd5e1; font-weight: 800; text-transform: uppercase; }
    .sppm-stat strong { display: block; font-size: 22px; margin-top: 4px; color: #fff; }
    .sppm-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 24px rgba(15, 23, 42, .06); overflow: hidden; }
    .sppm-panel-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .sppm-panel-title { display: flex; align-items: center; gap: 12px; }
    .sppm-panel-icon { width: 38px; height: 38px; border-radius: 8px; display: grid; place-items: center; background: #fee2e2; color: #b91c1c; font-size: 18px; }
    .sppm-panel-title h2 { margin: 0; font-size: 16px; font-weight: 900; color: #0f172a; }
    .sppm-panel-title p { margin: 3px 0 0; font-size: 12px; color: #64748b; }
    .sppm-filter { display: grid; grid-template-columns: minmax(220px, 1.2fr) minmax(220px, 1fr) auto; gap: 12px; align-items: end; padding: 16px 18px; }
    .sppm-field label { display: block; font-size: 11px; font-weight: 900; color: #475569; text-transform: uppercase; margin-bottom: 7px; }
    .sppm-input, .sppm-select, .sppm-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a; font-size: 13px; padding: 10px 12px; outline: none; transition: border .15s, box-shadow .15s; }
    .sppm-input:focus, .sppm-select:focus, .sppm-textarea:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, .12); }
    .sppm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 8px; height: 38px; padding: 0 14px; font-size: 12px; font-weight: 900; cursor: pointer; text-decoration: none; white-space: nowrap; }
    .sppm-btn-primary { background: #dc2626; color: #fff; box-shadow: 0 8px 16px rgba(220,38,38,.18); }
    .sppm-btn-dark { background: #111827; color: #fff; }
    .sppm-btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
    .sppm-btn-danger { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
    .sppm-bulk { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto; gap: 12px; padding: 16px 18px; border-top: 1px solid #e2e8f0; background: #fff; }
    .sppm-table-wrap { overflow-x: auto; }
    .sppm-table { width: 100%; border-collapse: collapse; }
    .sppm-table th { background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 11px; text-align: left; padding: 11px 12px; text-transform: uppercase; white-space: nowrap; }
    .sppm-table td { border-bottom: 1px solid #edf2f7; color: #1f2937; font-size: 13px; padding: 12px; vertical-align: middle; }
    .sppm-person { display: flex; flex-direction: column; gap: 3px; min-width: 240px; }
    .sppm-person strong { font-size: 13.5px; color: #0f172a; }
    .sppm-person span { font-size: 12px; color: #64748b; }
    .sppm-pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 9px; font-size: 11px; font-weight: 900; white-space: nowrap; }
    .sppm-pill-neutral { background: #f1f5f9; color: #475569; }
    .sppm-pill-green { background: #dcfce7; color: #166534; }
    .sppm-pill-blue { background: #dbeafe; color: #1d4ed8; }
    .sppm-empty { padding: 36px 18px; text-align: center; color: #64748b; }
    .sppm-empty i { display: block; color: #94a3b8; font-size: 32px; margin-bottom: 8px; }
    .sppm-target-grid { display: flex; gap: 8px; flex-wrap: wrap; padding: 0 18px 16px; }
    .sppm-target-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; color: #334155; font-weight: 800; }
    .sppm-inline-form { display: flex; align-items: center; gap: 8px; min-width: 360px; }
    .sppm-inline-form .sppm-select { min-width: 210px; }
    .sppm-checkbox { width: 17px; height: 17px; accent-color: #dc2626; }
    @media (max-width: 900px) {
        .sppm-hero-top, .sppm-panel-header { flex-direction: column; align-items: stretch; }
        .sppm-stats, .sppm-filter, .sppm-bulk { grid-template-columns: 1fr; }
        .sppm-inline-form { min-width: 0; flex-wrap: wrap; }
    }
</style>

<div class="sppm-page">
    <section class="sppm-hero">
        <div class="sppm-hero-top">
            <div>
                <div class="sppm-eyebrow"><i class="ri-route-line"></i> Titipan SPPM Paket</div>
                <h1 class="sppm-title">{{ $budgetPackage->name }}</h1>
                <p class="sppm-subtitle">Satker asli personel tetap dipertahankan. Pengelompokan hanya berlaku saat dokumen SPPM paket dibuat.</p>
            </div>
            <a class="sppm-back" href="{{ route('admin.budget.show-package', $budgetPackage) }}">
                <i class="ri-arrow-left-line"></i> Kembali ke Paket
            </a>
        </div>
        <div class="sppm-stats">
            <div class="sppm-stat"><span>Satker Asal</span><strong>{{ $sourceSatkers->count() }}</strong></div>
            <div class="sppm-stat"><span>Nominatif Terlihat</span><strong>{{ number_format($rows->count(), 0, ',', '.') }}</strong></div>
            <div class="sppm-stat"><span>Sudah Dititipkan</span><strong>{{ number_format($assignedRows->count(), 0, ',', '.') }}</strong></div>
            <div class="sppm-stat"><span>Belum Dititipkan</span><strong>{{ number_format($unassignedRows->count(), 0, ',', '.') }}</strong></div>
        </div>
    </section>

    <section class="sppm-panel">
        <div class="sppm-panel-header">
            <div class="sppm-panel-title">
                <div class="sppm-panel-icon"><i class="ri-filter-3-line"></i></div>
                <div>
                    <h2>Filter Nominatif</h2>
                    <p>{{ $selectedSourceSatker?->name ?? 'Belum ada satker penerima pada paket ini' }}</p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.budget.sppm-assignments.index', $budgetPackage) }}" class="sppm-filter">
            <div class="sppm-field">
                <label>Satker asal</label>
                <select class="sppm-select" name="source_satker_id">
                    @foreach($sourceSatkers as $satker)
                        <option value="{{ $satker->id }}" @selected($selectedSourceSatker?->id === $satker->id)>{{ $satker->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sppm-field">
                <label>Cari personel</label>
                <input class="sppm-input" type="search" name="search" value="{{ request('search') }}" placeholder="Nama, NRP, jabatan, bagian">
            </div>
            <button class="sppm-btn sppm-btn-dark" type="submit"><i class="ri-search-line"></i> Terapkan</button>
        </form>
    </section>

    @if($summaryByTarget->isNotEmpty())
        <section class="sppm-panel">
            <div class="sppm-panel-header">
                <div class="sppm-panel-title">
                    <div class="sppm-panel-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="ri-building-2-line"></i></div>
                    <div>
                        <h2>Rekap Satker SPPM</h2>
                        <p>Ringkasan titipan dari satker asal yang sedang dibuka.</p>
                    </div>
                </div>
            </div>
            <div class="sppm-target-grid">
                @foreach($summaryByTarget as $summary)
                    <span class="sppm-target-chip">
                        <i class="ri-map-pin-2-line"></i>
                        {{ $summary['satker']?->name ?? 'Satker tidak ditemukan' }}
                        <strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong>
                    </span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="sppm-panel">
        <div class="sppm-panel-header">
            <div class="sppm-panel-title">
                <div class="sppm-panel-icon" style="background:#dcfce7;color:#166534;"><i class="ri-user-add-line"></i></div>
                <div>
                    <h2>Belum Dititipkan</h2>
                    <p>{{ number_format($unassignedRows->count(), 0, ',', '.') }} personel mengikuti satker asal saat SPPM dibuat.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.budget.sppm-assignments.store', $budgetPackage) }}" id="bulkAssignmentForm">
            @csrf
            <input type="hidden" name="source_satker_id" value="{{ $selectedSourceSatker?->id }}">

            @if($unassignedRows->isEmpty())
                <div class="sppm-empty">
                    <i class="ri-checkbox-circle-line"></i>
                    Semua personel pada filter ini sudah memiliki pengaturan SPPM.
                </div>
            @else
                <div class="sppm-table-wrap">
                    <table class="sppm-table">
                        <thead>
                            <tr>
                                <th style="width:42px;"><input class="sppm-checkbox" type="checkbox" id="checkAllUnassigned"></th>
                                <th>Personel</th>
                                <th>Jabatan</th>
                                <th>Bag/Fungsi</th>
                                <th>Item Paket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedRows as $row)
                                <tr>
                                    <td><input class="sppm-checkbox row-check" type="checkbox" name="personnel_ids[]" value="{{ $row['personnel_id'] }}"></td>
                                    <td>
                                        <div class="sppm-person">
                                            <strong>{{ $row['full_name'] }}</strong>
                                            <span>{{ $row['rank'] }} &bull; {{ $row['nrp'] }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $row['jabatan'] }}</td>
                                    <td>{{ $row['bagian'] }}</td>
                                    <td>
                                        <span class="sppm-pill sppm-pill-neutral">{{ $row['item_count'] }} item</span>
                                        <span style="display:block;margin-top:5px;color:#64748b;font-size:12px;">{{ $row['item_preview'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sppm-bulk">
                    <div class="sppm-field">
                        <label>Satker SPPM</label>
                        <select class="sppm-select" name="sppm_satker_id" required>
                            <option value="">Pilih satker titipan</option>
                            @foreach($targetSatkers as $satker)
                                @if($selectedSourceSatker?->id !== $satker->id)
                                    <option value="{{ $satker->id }}">{{ $satker->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="sppm-field">
                        <label>Catatan</label>
                        <input class="sppm-input" type="text" name="notes" placeholder="Opsional">
                    </div>
                    <button class="sppm-btn sppm-btn-primary" type="submit">
                        <i class="ri-save-3-line"></i> Simpan Titipan
                    </button>
                </div>
            @endif
        </form>
    </section>

    <section class="sppm-panel">
        <div class="sppm-panel-header">
            <div class="sppm-panel-title">
                <div class="sppm-panel-icon" style="background:#fef3c7;color:#b45309;"><i class="ri-exchange-box-line"></i></div>
                <div>
                    <h2>Sudah Dititipkan</h2>
                    <p>{{ number_format($assignedRows->count(), 0, ',', '.') }} personel akan masuk ke satker SPPM pengganti.</p>
                </div>
            </div>
        </div>

        @if($assignedRows->isEmpty())
            <div class="sppm-empty">
                <i class="ri-inbox-line"></i>
                Belum ada personel yang dititipkan pada filter ini.
            </div>
        @else
            <div class="sppm-table-wrap">
                <table class="sppm-table">
                    <thead>
                        <tr>
                            <th>Personel</th>
                            <th>Jabatan</th>
                            <th>Satker SPPM</th>
                            <th style="width:440px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedRows as $row)
                            @php($assignment = $row['assignment'])
                            <tr>
                                <td>
                                    <div class="sppm-person">
                                        <strong>{{ $row['full_name'] }}</strong>
                                        <span>{{ $row['rank'] }} &bull; {{ $row['nrp'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $row['jabatan'] }}</td>
                                <td>
                                    <span class="sppm-pill sppm-pill-green">
                                        <i class="ri-building-4-line"></i>
                                        {{ $assignment->sppmSatker?->name ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="sppm-inline-form">
                                        <form method="POST" action="{{ route('admin.budget.sppm-assignments.update', [$budgetPackage, $assignment]) }}" class="sppm-inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <select class="sppm-select" name="sppm_satker_id" required>
                                                @foreach($targetSatkers as $satker)
                                                    @if($assignment->original_satker_id !== $satker->id)
                                                        <option value="{{ $satker->id }}" @selected($assignment->sppm_satker_id === $satker->id)>{{ $satker->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button class="sppm-btn sppm-btn-outline" type="submit"><i class="ri-refresh-line"></i> Ubah</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.budget.sppm-assignments.destroy', [$budgetPackage, $assignment]) }}" onsubmit="return confirm('Hapus titipan SPPM personel ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="sppm-btn sppm-btn-danger" type="submit"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script>
    const checkAll = document.getElementById('checkAllUnassigned');
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach((checkbox) => {
                checkbox.checked = checkAll.checked;
            });
        });
    }

    const bulkForm = document.getElementById('bulkAssignmentForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', (event) => {
            const selected = document.querySelectorAll('.row-check:checked').length;
            if (selected === 0) {
                event.preventDefault();
                alert('Pilih minimal satu personel.');
            }
        });
    }
</script>
@endsection
