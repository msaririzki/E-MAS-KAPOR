@extends('layouts.app')

@section('title', 'Titipan SPPM - '.$budgetPackage->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}" class="hover:text-slate-900 transition-colors">Rencana Anggaran</a>
    <span class="text-slate-400 mx-2">/</span>
    <a href="{{ route('admin.budget.show-year', $budgetPackage->budgetYear) }}" class="hover:text-slate-900 transition-colors">{{ $budgetPackage->budgetYear->name }}</a>
    <span class="text-slate-400 mx-2">/</span>
    <a href="{{ route('admin.budget.show-package', $budgetPackage) }}" class="hover:text-slate-900 transition-colors">{{ $budgetPackage->name }}</a>
    <span class="text-slate-400 mx-2">/</span>
    <span class="text-slate-900 font-semibold">Titipan SPPM</span>
@endsection

@section('content')
<style>
    .sppm-page-shell {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .sppm-page-shell,
    .sppm-page-shell * {
        font-family: inherit;
        letter-spacing: 0;
    }

    .sppm-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        overflow: visible;
    }

    .sppm-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 16px 18px;
        border-bottom: 1px solid #eef2f7;
        background: #fafbfc;
        border-radius: 8px 8px 0 0;
    }

    .sppm-title-block {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .sppm-title-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        font-size: 18px;
        background: #eff6ff;
        color: #2563eb;
    }

    .sppm-title-icon.amber { background: #fffbeb; color: #d97706; }
    .sppm-title-icon.green { background: #ecfdf5; color: #059669; }
    .sppm-title-icon.indigo { background: #eef2ff; color: #4f46e5; }

    .sppm-card-title {
        margin: 0;
        color: #111827;
        font-size: 16px;
        line-height: 1.25;
        font-weight: 800;
    }

    .sppm-card-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
        font-weight: 500;
    }

    .sppm-filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 32px;
        padding: 0 10px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .sppm-filter-form {
        display: grid;
        grid-template-columns: minmax(240px, .9fr) minmax(280px, 1.1fr) auto;
        gap: 14px;
        align-items: end;
        padding: 18px;
    }

    .sppm-field-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0 0 8px;
        color: #64748b;
        font-size: 11px;
        line-height: 1;
        font-weight: 800;
        text-transform: uppercase;
    }

    .sppm-control,
    .sppm-select-button {
        width: 100%;
        height: 42px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        color: #374151;
        font-size: 14px;
        font-weight: 600;
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .sppm-control {
        padding: 0 38px 0 38px;
    }

    .sppm-select-button {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 0 12px;
        text-align: left;
        cursor: pointer;
    }

    .sppm-select-button:hover {
        border-color: #d1d5db;
    }

    [data-select-shell].is-open .sppm-select-button {
        border-color: #b91c1c;
        box-shadow: 0 0 0 4px #fef2f2;
    }

    [data-select-shell].is-open .dropdown-icon {
        transform: rotate(180deg);
        color: #b91c1c;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu:not(.hidden) {
        display: block;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu {
        border: 1px solid #f3f4f6;
        border-radius: 16px;
        box-shadow: 0 10px 40px -10px rgba(15, 23, 42, .18);
        padding: 8px;
        overflow: hidden;
        background: #fff;
        z-index: 80;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu.top-full {
        top: calc(100% + 8px) !important;
        bottom: auto !important;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu.bottom-full {
        top: auto !important;
        bottom: calc(100% + 8px) !important;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu > .p-2 {
        padding: 4px;
        margin: 0 0 6px;
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu input[type="text"] {
        height: 34px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        color: #1f2937;
        font-size: 13px;
        font-weight: 500;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu .relative > i.ri-search-line + input[type="text"] {
        padding-left: 34px !important;
        padding-right: 10px !important;
    }

    .sppm-page-shell [data-select-shell] .dropdown-menu input[type="text"]:focus {
        border-color: #b91c1c;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, .08);
    }

    .sppm-page-shell [data-select-shell] .sppm-options-scroll {
        max-height: 240px;
        overflow-y: auto;
        padding: 2px;
    }

    .sppm-page-shell [data-select-shell] .sppm-options-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .sppm-page-shell [data-select-shell] .sppm-options-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .sppm-page-shell [data-select-shell] .sppm-option {
        width: 100%;
        min-height: 40px;
        padding: 9px 11px;
        border: 0;
        border-radius: 8px;
        background: #fff;
        color: #4b5563;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 13px;
        line-height: 1.3;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, color .15s;
    }

    .sppm-page-shell [data-select-shell] .sppm-option:hover {
        background: #f9fafb;
        color: #111827;
    }

    .sppm-page-shell [data-select-shell] .sppm-option.bg-blue-50,
    .sppm-page-shell [data-select-shell] .sppm-option.text-blue-700 {
        background: #fef2f2;
        color: #b91c1c;
        font-weight: 700;
    }

    .sppm-control:focus,
    .sppm-select-button:focus {
        border-color: #b91c1c;
        box-shadow: 0 0 0 4px #fef2f2;
    }

    .sppm-search-wrap {
        position: relative;
    }

    .sppm-search-wrap > .ri-search-line {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 16px;
        pointer-events: none;
    }

    .sppm-clear-button {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 26px;
        height: 26px;
        border: 0;
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        display: inline-grid;
        place-items: center;
        cursor: pointer;
    }

    .sppm-clear-button:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    .sppm-clear-button.hidden {
        display: none;
    }

    .sppm-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .sppm-action-button {
        height: 42px;
        min-width: 128px;
        padding: 0 16px;
        border: 0;
        border-radius: 8px;
        background: #111827;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
    }

    .sppm-action-button.primary { background: #2563eb; }
    .sppm-action-button.light {
        min-width: 42px;
        width: 42px;
        padding: 0;
        background: #fff;
        color: #475569;
        border: 1px solid #d9e2ec;
    }

    .sppm-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 16px 18px;
    }

    .sppm-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 10px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .sppm-chip strong {
        color: #2563eb;
        font-size: 12px;
    }

    .sppm-table-wrap {
        overflow: visible;
    }

    .sppm-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sppm-table th {
        padding: 12px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
    }

    .sppm-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #eef2f7;
        color: #334155;
        font-size: 13px;
        line-height: 1.45;
        vertical-align: middle;
    }

    .sppm-table tbody tr:hover {
        background: #f8fafc;
    }

    .sppm-person-name {
        display: block;
        color: #111827;
        font-size: 13.5px;
        line-height: 1.35;
        font-weight: 800;
    }

    .sppm-person-meta {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 500;
    }

    .sppm-pill {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .sppm-item-preview {
        display: block;
        margin-top: 5px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.35;
        white-space: normal;
    }

    .sppm-form-footer {
        padding: 16px 18px;
        border-top: 1px solid #e5e7eb;
        background: #fafbfc;
        border-radius: 0 0 8px 8px;
    }

    .sppm-form-grid {
        display: grid;
        grid-template-columns: minmax(240px, 320px) minmax(260px, 1fr) auto;
        gap: 14px;
        align-items: end;
    }

    .sppm-empty {
        padding: 42px 18px;
        text-align: center;
        color: #64748b;
    }

    .sppm-empty-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        margin: 0 auto 12px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: 26px;
    }

    .sppm-inline-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sppm-inline-actions form:first-child {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sppm-small-button {
        height: 36px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid #d9e2ec;
        background: #fff;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
    }

    .sppm-small-button.danger {
        width: 36px;
        padding: 0;
        justify-content: center;
        color: #dc2626;
        border-color: #fecaca;
        background: #fff;
    }

    @media (max-width: 1180px) {
        .sppm-filter-form,
        .sppm-form-grid {
            grid-template-columns: 1fr;
        }

        .sppm-actions,
        .sppm-action-button {
            width: 100%;
        }

        .sppm-table-wrap {
            overflow-x: auto;
        }

        .sppm-table {
            min-width: 760px;
        }
    }

    @media (max-width: 760px) {
        .sppm-card-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Titipan SPPM</h1>
            <p class="page-subtitle">{{ $budgetPackage->name }} - atur satker tujuan SPPM tanpa mengubah satker asli personel</p>
        </div>
        <div class="page-header-actions">
            <a class="btn btn-outline" href="{{ route('admin.budget.show-package', $budgetPackage) }}">
                <i class="ri-arrow-left-line"></i> Kembali ke Paket
            </a>
        </div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Satker Asal</span>
            <div class="stat-icon-sm" style="background:#F1F5F9;color:#475569;">
                <i class="ri-building-4-line"></i>
            </div>
        </div>
        <div class="stat-value">{{ $sourceSatkers->count() }}</div>
        <div class="stat-footer">Sumber personel yang tersedia</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Personel Tampil</span>
            <div class="stat-icon-sm" style="background:#EFF6FF;color:#2563EB;">
                <i class="ri-group-line"></i>
            </div>
        </div>
        <div class="stat-value">{{ number_format($rows->count(), 0, ',', '.') }}</div>
        <div class="stat-footer">Mengikuti filter saat ini</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">SPPM Diatur</span>
            <div class="stat-icon-sm" style="background:#ECFDF5;color:#059669;">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>
        <div class="stat-value">{{ number_format($assignedRows->count(), 0, ',', '.') }}</div>
        <div class="stat-footer">Sudah punya satker SPPM</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Belum Diatur</span>
            <div class="stat-icon-sm" style="background:#FFFBEB;color:#D97706;">
                <i class="ri-error-warning-line"></i>
            </div>
        </div>
        <div class="stat-value">{{ number_format($unassignedRows->count(), 0, ',', '.') }}</div>
        <div class="stat-footer">Masih ikut satker asal</div>
    </div>
</div>

<div class="sppm-page-shell">

    <!-- Filter Section -->
    <section class="sppm-card" style="position:relative;z-index:30;">
        <div class="sppm-card-header">
            <div class="sppm-title-block">
                <div class="sppm-title-icon">
                    <i class="ri-filter-3-line"></i>
                </div>
                <div>
                    <h2 class="sppm-card-title">Filter Nominatif</h2>
                    <p class="sppm-card-subtitle">Pilih satker asal, lalu cari personel yang perlu diatur SPPM-nya.</p>
                </div>
            </div>
            <div class="sppm-filter-badge">
                <i class="ri-building-4-line" style="color:#2563eb;"></i>
                {{ $selectedSourceSatker?->name ?? 'Belum ada satker' }}
            </div>
        </div>

        <form method="GET" action="{{ route('admin.budget.sppm-assignments.index', $budgetPackage) }}" class="sppm-filter-form" id="sppmFilterForm">
            <div>
                <label class="sppm-field-label">
                    <i class="ri-building-line"></i> Satker Asal
                </label>
                <div class="relative" data-select-shell>
                    <input type="hidden" name="source_satker_id" id="source_satker_id" value="{{ $selectedSourceSatker?->id }}">
                    <button type="button" class="sppm-select-button" onclick="toggleSppmDropdown(this, event)">
                        <span id="source_satker_label" class="block truncate pr-4 {{ !$selectedSourceSatker ? 'text-slate-400' : '' }}">{{ $selectedSourceSatker?->name ?? 'Pilih satker asal' }}</span>
                        <i class="ri-arrow-down-s-line text-slate-400 group-hover:text-slate-600 transition-transform dropdown-icon"></i>
                    </button>
                    <div class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden z-50 hidden opacity-0 transition-opacity dropdown-menu">
                        <div class="p-2 border-b border-slate-100 bg-slate-50">
                            <div class="relative">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" placeholder="Cari satker asal..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onclick="event.stopPropagation()" onkeyup="filterSppmOptions(this)">
                            </div>
                        </div>
                        <div class="max-h-60 overflow-y-auto p-1.5 sppm-options-scroll">
                            @foreach($sourceSatkers as $satker)
                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between hover:bg-slate-50 transition-colors {{ $selectedSourceSatker?->id === $satker->id ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }} sppm-option" data-label="{{ $satker->name }}" onclick="selectSppmOption(this, 'source_satker_id', '{{ $satker->id }}', '{{ $satker->name }}', 'source_satker_label')">
                                    <div class="flex flex-col truncate pr-3">
                                        <span class="truncate">{{ $satker->name }}</span>
                                        <span class="text-[11px] font-semibold opacity-70 truncate">{{ $satker->code }}</span>
                                    </div>
                                    <i class="ri-check-line text-blue-600 text-lg {{ $selectedSourceSatker?->id === $satker->id ? 'opacity-100' : 'opacity-0' }} transition-opacity check-icon"></i>
                                </button>
                            @endforeach
                            <div class="px-4 py-6 text-center text-sm font-medium text-slate-400 sppm-option-empty hidden">Tidak ada satker yang cocok.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="sppm-field-label">
                    <i class="ri-search-line"></i> Cari Personel
                </label>
                <div class="sppm-search-wrap">
                    <i class="ri-search-line"></i>
                    <input id="sppmSearchInput" type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NRP, jabatan, bagian" autocomplete="off" class="sppm-control" data-has-active-search="{{ request()->filled('search') ? '1' : '0' }}" data-reset-url="{{ route('admin.budget.sppm-assignments.index', ['budgetPackage' => $budgetPackage, 'source_satker_id' => $selectedSourceSatker?->id]) }}">
                    <button type="button" id="sppmSearchClear" class="sppm-clear-button {{ request()->filled('search') ? '' : 'hidden' }}" title="Bersihkan pencarian">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>

            <div class="sppm-actions">
                <button type="submit" class="sppm-action-button">
                    <i class="ri-search-line"></i> Terapkan
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.budget.sppm-assignments.index', ['budgetPackage' => $budgetPackage, 'source_satker_id' => $selectedSourceSatker?->id]) }}" class="sppm-action-button light" title="Reset pencarian">
                        <i class="ri-refresh-line"></i>
                    </a>
                @endif
            </div>
        </form>
    </section>

    @if($summaryByTarget->isNotEmpty())
        <!-- Rekap Satker SPPM -->
        <section class="sppm-card">
            <div class="sppm-card-header">
                <div class="sppm-title-block">
                    <div class="sppm-title-icon indigo">
                        <i class="ri-building-2-line"></i>
                    </div>
                    <div>
                        <h2 class="sppm-card-title">Rekap Satker SPPM</h2>
                        <p class="sppm-card-subtitle">Ringkasan satker tujuan dari filter yang sedang dibuka.</p>
                    </div>
                </div>
            </div>
            <div class="sppm-chip-list">
                @foreach($summaryByTarget as $summary)
                    <div class="sppm-chip">
                        <i class="ri-map-pin-2-line" style="color:#4f46e5;"></i>
                        {{ $summary['satker']?->name ?? 'Satker tidak ditemukan' }}
                        <strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- SPPM Belum Diatur -->
    <section class="sppm-card" style="position:relative;z-index:20;">
        <div class="sppm-card-header">
            <div class="sppm-title-block">
                <div class="sppm-title-icon amber">
                    <i class="ri-user-add-line"></i>
                </div>
                <div>
                    <h2 class="sppm-card-title">SPPM Belum Diatur</h2>
                    <p class="sppm-card-subtitle"><strong style="color:#d97706;">{{ number_format($unassignedRows->count(), 0, ',', '.') }}</strong> personel masih mengikuti satker asal saat dokumen SPPM dibuat.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.budget.sppm-assignments.store', $budgetPackage) }}" id="bulkAssignmentForm">
            @csrf
            <input type="hidden" name="source_satker_id" value="{{ $selectedSourceSatker?->id }}">

            @if($unassignedRows->isEmpty())
                <div class="sppm-empty">
                    <div class="sppm-empty-icon">
                        @if(request()->filled('search'))
                            <i class="ri-search-eye-line"></i>
                        @else
                            <i class="ri-checkbox-circle-line"></i>
                        @endif
                    </div>
                    <p style="margin:0 auto;max-width:460px;font-size:13px;font-weight:700;line-height:1.5;color:#475569;">
                        @if(request()->filled('search') && $rows->isEmpty())
                            Tidak ada personel yang cocok dengan pencarian "{{ request('search') }}".
                        @elseif(request()->filled('search'))
                            Tidak ada personel belum diatur SPPM yang cocok dengan pencarian "{{ request('search') }}".
                        @else
                            Semua personel pada filter ini sudah memiliki pengaturan SPPM.
                        @endif
                    </p>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.budget.sppm-assignments.index', ['budgetPackage' => $budgetPackage, 'source_satker_id' => $selectedSourceSatker?->id]) }}" class="sppm-small-button" style="margin-top:14px;">
                            <i class="ri-refresh-line"></i> Tampilkan Semua
                        </a>
                    @endif
                </div>
            @else
                <div class="sppm-table-wrap">
                    <table class="sppm-table">
                        <thead>
                            <tr>
                                <th style="width:48px;text-align:center;">
                                    <input type="checkbox" id="checkAllUnassigned" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th style="width:30%;">Personel</th>
                                <th style="width:19%;">Jabatan</th>
                                <th style="width:24%;">Bag/Fungsi</th>
                                <th>Item Paket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedRows as $row)
                                <tr>
                                    <td style="text-align:center;">
                                        <input type="checkbox" name="personnel_ids[]" value="{{ $row['personnel_id'] }}" class="row-check w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td>
                                        <span class="sppm-person-name">{{ $row['full_name'] }}</span>
                                        <span class="sppm-person-meta">{{ $row['rank'] }} &bull; {{ $row['nrp'] }}</span>
                                    </td>
                                    <td>{{ $row['jabatan'] }}</td>
                                    <td>{{ $row['bagian'] }}</td>
                                    <td>
                                        <span class="sppm-pill">{{ $row['item_count'] }} item</span>
                                        <span class="sppm-item-preview" title="{{ $row['item_preview'] }}">{{ $row['item_preview'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sppm-form-footer">
                    <div class="sppm-form-grid">
                        <div>
                            <label class="sppm-field-label">Satker SPPM</label>
                            <div class="relative" data-select-shell>
                                <input type="hidden" name="sppm_satker_id" id="bulk_sppm_satker_id">
                                <button type="button" class="sppm-select-button" onclick="toggleSppmDropdown(this, event)">
                                    <span id="bulk_sppm_satker_label" class="block truncate pr-4 text-slate-400">Pilih satker titipan</span>
                                    <i class="ri-arrow-down-s-line text-slate-400 group-hover:text-slate-600 transition-transform dropdown-icon"></i>
                                </button>
                                <div class="absolute left-0 right-0 bottom-full mb-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden z-[60] hidden opacity-0 transition-opacity dropdown-menu">
                                    <div class="p-2 border-b border-slate-100 bg-slate-50">
                                        <div class="relative">
                                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <input type="text" placeholder="Cari satker tujuan..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onclick="event.stopPropagation()" onkeyup="filterSppmOptions(this)">
                                        </div>
                                    </div>
                                    <div class="max-h-60 overflow-y-auto p-1.5 sppm-options-scroll">
                                        @foreach($targetSatkers as $satker)
                                            @if($selectedSourceSatker?->id !== $satker->id)
                                                <button type="button" class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-between hover:bg-slate-50 transition-colors text-slate-700 sppm-option" data-label="{{ $satker->name }}" onclick="selectSppmOption(this, 'bulk_sppm_satker_id', '{{ $satker->id }}', '{{ $satker->name }}', 'bulk_sppm_satker_label')">
                                                    <div class="flex flex-col truncate pr-3">
                                                        <span class="truncate">{{ $satker->name }}</span>
                                                        <span class="text-[11px] font-semibold opacity-70 truncate">{{ $satker->code }}</span>
                                                    </div>
                                                    <i class="ri-check-line text-blue-600 text-lg opacity-0 transition-opacity check-icon"></i>
                                                </button>
                                            @endif
                                        @endforeach
                                        <div class="px-4 py-6 text-center text-sm font-medium text-slate-400 sppm-option-empty hidden">Tidak ada satker yang cocok.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="sppm-field-label">Catatan</label>
                            <input type="text" name="notes" placeholder="Opsional" class="sppm-control" style="padding-left:12px;padding-right:12px;">
                        </div>

                        <div>
                            <button type="submit" class="sppm-action-button primary" style="width:100%;">
                                <i class="ri-save-3-line"></i> Simpan Titipan
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </section>

    <!-- SPPM Sudah Diatur -->
    <section class="sppm-card" style="position:relative;z-index:10;margin-bottom:32px;">
        <div class="sppm-card-header">
            <div class="sppm-title-block">
                <div class="sppm-title-icon green">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <div>
                    <h2 class="sppm-card-title">SPPM Sudah Diatur</h2>
                    <p class="sppm-card-subtitle"><strong style="color:#059669;">{{ number_format($assignedRows->count(), 0, ',', '.') }}</strong> personel akan masuk ke satker SPPM yang dipilih saat dokumen dibuat.</p>
                </div>
            </div>
        </div>

        @if($assignedRows->isEmpty())
            <div class="sppm-empty">
                <div class="sppm-empty-icon">
                    <i class="ri-inbox-line"></i>
                </div>
                <p style="margin:0;color:#475569;font-size:13px;font-weight:700;">Belum ada personel yang sudah diatur SPPM pada filter ini.</p>
            </div>
        @else
            <div class="sppm-table-wrap">
                <table class="sppm-table">
                    <thead>
                        <tr>
                            <th style="width:27%;">Personel</th>
                            <th style="width:18%;">Jabatan</th>
                            <th style="width:18%;">Satker SPPM</th>
                            <th style="width:37%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedRows as $row)
                            @php($assignment = $row['assignment'])
                            <tr>
                                <td>
                                    <span class="sppm-person-name">{{ $row['full_name'] }}</span>
                                    <span class="sppm-person-meta">{{ $row['rank'] }} &bull; {{ $row['nrp'] }}</span>
                                </td>
                                <td>{{ $row['jabatan'] }}</td>
                                <td>
                                    <span class="sppm-chip" style="min-height:28px;padding:0 9px;background:#ecfdf5;border-color:#bbf7d0;color:#047857;">
                                        <i class="ri-building-4-line"></i>
                                        {{ $assignment->sppmSatker?->name ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="sppm-inline-actions">
                                        <form method="POST" action="{{ route('admin.budget.sppm-assignments.update', [$budgetPackage, $assignment]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="relative" style="width:170px;flex:0 0 170px;" data-select-shell>
                                                <input type="hidden" name="sppm_satker_id" id="edit_sppm_satker_id_{{ $assignment->id }}" value="{{ $assignment->sppm_satker_id }}">
                                                <button type="button" class="sppm-select-button" style="height:36px;font-size:12px;" onclick="toggleSppmDropdown(this, event)">
                                                    <span id="edit_sppm_satker_label_{{ $assignment->id }}" class="block truncate pr-2">{{ $assignment->sppmSatker?->name ?? 'Pilih satker' }}</span>
                                                    <i class="ri-arrow-down-s-line text-slate-400 group-hover:text-slate-600 transition-transform dropdown-icon"></i>
                                                </button>
                                                <div class="absolute right-0 top-full mt-1 w-64 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden z-40 hidden opacity-0 transition-opacity dropdown-menu">
                                                    <div class="p-2 border-b border-slate-100 bg-slate-50">
                                                        <div class="relative">
                                                            <i class="ri-search-line absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                                            <input type="text" placeholder="Cari satker..." class="w-full pl-7 pr-3 py-1.5 bg-white border border-slate-200 rounded text-xs focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onclick="event.stopPropagation()" onkeyup="filterSppmOptions(this)">
                                                        </div>
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto p-1.5 sppm-options-scroll">
                                                        @foreach($targetSatkers as $satker)
                                                            @if($assignment->original_satker_id !== $satker->id)
                                                                <button type="button" class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center justify-between hover:bg-slate-50 transition-colors {{ $assignment->sppm_satker_id === $satker->id ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }} sppm-option" data-label="{{ $satker->name }}" onclick="selectSppmOption(this, 'edit_sppm_satker_id_{{ $assignment->id }}', '{{ $satker->id }}', '{{ $satker->name }}', 'edit_sppm_satker_label_{{ $assignment->id }}')">
                                                                    <div class="flex flex-col truncate pr-2">
                                                                        <span class="truncate">{{ $satker->name }}</span>
                                                                        <span class="text-[10px] font-semibold opacity-70 truncate">{{ $satker->code }}</span>
                                                                    </div>
                                                                    <i class="ri-check-line text-blue-600 text-sm {{ $assignment->sppm_satker_id === $satker->id ? 'opacity-100' : 'opacity-0' }} transition-opacity check-icon"></i>
                                                                </button>
                                                            @endif
                                                        @endforeach
                                                        <div class="px-3 py-4 text-center text-xs font-medium text-slate-400 sppm-option-empty hidden">Tidak ada satker yang cocok.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="sppm-small-button">
                                                <i class="ri-refresh-line text-slate-400"></i> Ubah
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.budget.sppm-assignments.destroy', [$budgetPackage, $assignment]) }}" onsubmit="return confirm('Hapus titipan SPPM personel ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sppm-small-button danger" title="Hapus titipan">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
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
    document.addEventListener('DOMContentLoaded', () => {
        function closeSppmDropdowns(except = null) {
            document.querySelectorAll('[data-select-shell]').forEach((shell) => {
                const menu = shell.querySelector('.dropdown-menu');
                const icon = shell.querySelector('.dropdown-icon');
                if (menu && shell !== except && !menu.classList.contains('hidden')) {
                    shell.classList.remove('is-open');
                    menu.classList.add('opacity-0');
                    setTimeout(() => {
                        menu.classList.add('hidden');
                        menu.classList.remove('bottom-full', 'mb-2', 'top-full', 'mt-2', 'mt-1');
                    }, 150);
                    if (icon) icon.classList.remove('rotate-180');

                    const search = shell.querySelector('input[type="text"]');
                    if (search) search.value = '';
                    resetSppmOptions(shell);
                }
            });
        }

        function resetSppmOptions(shell) {
            const options = shell.querySelectorAll('.sppm-option');
            const emptyState = shell.querySelector('.sppm-option-empty');
            let visibleCount = 0;

            options.forEach((option) => {
                option.style.display = 'flex';
                visibleCount += 1;
            });

            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCount > 0);
            }
        }

        function positionSppmDropdown(shell) {
            const menu = shell.querySelector('.dropdown-menu');
            if (!menu) return;

            const rect = shell.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            const menuHeight = 250; // max expected height

            // Determine if dropdown should go up or down based on space, only if not forced (like edit rows)
            const isRowSelect = shell.closest('td');
            const isBulkSppmSelect = Boolean(shell.querySelector('#bulk_sppm_satker_id'));

            if (isBulkSppmSelect) {
                menu.classList.add('bottom-full', 'mb-2');
                menu.classList.remove('top-full', 'mt-1', 'mt-2');
            } else if (isRowSelect) {
                // For table rows, try to pop up if bottom row
                if (spaceBelow < menuHeight && spaceAbove > spaceBelow) {
                    menu.classList.add('bottom-full', 'mb-2');
                    menu.classList.remove('top-full', 'mt-1', 'mt-2');
                } else {
                    menu.classList.add('top-full', 'mt-1');
                    menu.classList.remove('bottom-full', 'mb-2', 'mt-2');
                }
            } else {
                 if (!shell.closest('#sppmFilterForm') && spaceBelow < menuHeight && spaceAbove > spaceBelow) {
                    menu.classList.add('bottom-full', 'mb-2');
                    menu.classList.remove('top-full', 'mt-2');
                 } else {
                    menu.classList.add('top-full', 'mt-2');
                    menu.classList.remove('bottom-full', 'mb-2');
                 }
            }
        }

        window.toggleSppmDropdown = function(btn, event) {
            event.stopPropagation();
            const shell = btn.closest('[data-select-shell]');
            const menu = shell.querySelector('.dropdown-menu');
            const icon = shell.querySelector('.dropdown-icon');
            const isHidden = menu.classList.contains('hidden');

            closeSppmDropdowns(shell);

            if (isHidden) {
                shell.classList.add('is-open');
                menu.classList.remove('hidden');
                positionSppmDropdown(shell);
                // force reflow
                void menu.offsetWidth;
                menu.classList.remove('opacity-0');
                if (icon) icon.classList.add('rotate-180');

                const search = menu.querySelector('input[type="text"]');
                if (search) setTimeout(() => search.focus(), 50);
            } else {
                shell.classList.remove('is-open');
                menu.classList.add('opacity-0');
                setTimeout(() => menu.classList.add('hidden'), 150);
                if (icon) icon.classList.remove('rotate-180');
            }
        };

        window.selectSppmOption = function(btn, inputId, value, label, labelId) {
            const input = document.getElementById(inputId);
            const triggerLabel = document.getElementById(labelId);

            input.value = value;
            triggerLabel.textContent = label;
            triggerLabel.classList.remove('text-slate-400');

            const shell = btn.closest('[data-select-shell]');
            shell.querySelectorAll('.sppm-option').forEach(opt => {
                opt.classList.remove('bg-blue-50', 'text-blue-700');
                opt.classList.add('text-slate-700');
                opt.querySelector('.check-icon').classList.remove('opacity-100');
                opt.querySelector('.check-icon').classList.add('opacity-0');
            });

            btn.classList.remove('text-slate-700');
            btn.classList.add('bg-blue-50', 'text-blue-700');
            btn.querySelector('.check-icon').classList.remove('opacity-0');
            btn.querySelector('.check-icon').classList.add('opacity-100');

            shell.classList.remove('is-open');
            closeSppmDropdowns();
        };

        window.filterSppmOptions = function(input) {
            const term = input.value.toLowerCase();
            const shell = input.closest('[data-select-shell]');
            let visibleCount = 0;

            shell.querySelectorAll('.sppm-option').forEach(opt => {
                const text = (opt.dataset.label || '').toLowerCase();
                const isMatch = text.includes(term);
                opt.style.display = isMatch ? 'flex' : 'none';
                if (isMatch) visibleCount++;
            });

            const emptyState = shell.querySelector('.sppm-option-empty');
            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCount === 0);
            }
        };

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-select-shell]')) {
                closeSppmDropdowns();
            }
        });

        // Window scroll/resize handling for dropdowns
        window.addEventListener('scroll', () => {
            document.querySelectorAll('[data-select-shell] .dropdown-menu:not(.hidden)').forEach(menu => {
                positionSppmDropdown(menu.closest('[data-select-shell]'));
            });
        }, true);
        window.addEventListener('resize', () => {
            document.querySelectorAll('[data-select-shell] .dropdown-menu:not(.hidden)').forEach(menu => {
                positionSppmDropdown(menu.closest('[data-select-shell]'));
            });
        });

        // Checkbox All
        const checkAll = document.getElementById('checkAllUnassigned');
        if (checkAll) {
            checkAll.addEventListener('change', () => {
                document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
            });
        }

        // Search clear and debounce
        const filterForm = document.getElementById('sppmFilterForm');
        const searchInput = document.getElementById('sppmSearchInput');
        const searchClear = document.getElementById('sppmSearchClear');
        let clearSearchTimer = null;

        function submitFilterWithoutEmptySearch() {
            if (!filterForm || !searchInput) return;
            if (!searchInput.value.trim()) {
                window.location.href = searchInput.dataset.resetUrl || filterForm.action;
                return;
            }
            filterForm.submit();
        }

        if (searchInput && searchClear) {
            searchInput.addEventListener('input', () => {
                const hasValue = searchInput.value.trim().length > 0;
                searchClear.classList.toggle('hidden', !hasValue);

                if (clearSearchTimer) clearTimeout(clearSearchTimer);
                if (!hasValue && searchInput.dataset.hasActiveSearch === '1') {
                    clearSearchTimer = setTimeout(submitFilterWithoutEmptySearch, 350);
                }
            });

            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchClear.classList.add('hidden');
                submitFilterWithoutEmptySearch();
            });
        }

        if (filterForm && searchInput) {
            filterForm.addEventListener('submit', (e) => {
                if (!searchInput.value.trim() && searchInput.dataset.hasActiveSearch === '1') {
                    e.preventDefault();
                    submitFilterWithoutEmptySearch();
                }
            });
        }

        const bulkForm = document.getElementById('bulkAssignmentForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => {
                const selected = document.querySelectorAll('.row-check:checked').length;
                const target = document.getElementById('bulk_sppm_satker_id').value;
                if (selected === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu personel.');
                    return;
                }
                if (!target) {
                    e.preventDefault();
                    alert('Pilih satker titipan terlebih dahulu.');
                }
            });
        }
    });
</script>
@endsection
