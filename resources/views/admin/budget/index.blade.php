@extends('layouts.app')

@section('title', 'Rencana Anggaran')
@section('breadcrumb', 'Rencana Anggaran')

@section('content')
@php
    $nextBudgetYear = max((int) date('Y'), (int) ($years->max('year') ?? date('Y'))) + 1;
    $canManageBudget = auth()->user()->hasAnyRole(['superadmin', 'admin']);
    $activeBudgetYearLabel = $activeBudgetYear?->name ?? 'Belum ada tahun anggaran budget yang ditandai aktif';
    $inactiveYearsCount = $years->where('year', '<', $activeFiscalYear)->count();
    $hasYearMismatch = $activeBudgetYear && (int) $activeBudgetYear->year !== (int) $activeFiscalYear;
@endphp
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Rencana Anggaran</h1>
            <p class="page-subtitle">Kelola tahun anggaran dan paket pengadaan kapor</p>
        </div>
        @if($canManageBudget)
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="openModal('addYearModal')">
                <i class="ri-add-line"></i> Tambah Tahun Anggaran
            </button>
        </div>
        @endif
    </div>
</div>

@if($canManageBudget)
<div class="budget-summary-compact {{ $hasYearMismatch ? 'is-warning' : '' }}">
    <div class="bsc-content">
        <div class="bsc-icon">
            <i class="ri-folder-chart-line"></i>
        </div>
        <div class="bsc-info">
            <div class="bsc-stats">
                <div class="bsc-stat">
                    <span class="bsc-label">Sistem Aktif:</span>
                    <strong class="bsc-val">TA {{ $activeFiscalYear }}</strong>
                </div>
                <div class="bsc-divider"></div>
                <div class="bsc-stat">
                    <span class="bsc-label">Budget Aktif:</span>
                    <strong class="bsc-val {{ $hasYearMismatch ? 'text-danger' : '' }}">
                        {{ $activeBudgetYear ? 'TA '.$activeBudgetYear->year : 'Belum ditetapkan' }}
                    </strong>
                </div>
                <div class="bsc-divider"></div>
                <div class="bsc-stat">
                    <span class="bsc-label">Riwayat:</span>
                    <strong class="bsc-val">{{ number_format($inactiveYearsCount) }} Tahun</strong>
                </div>
            </div>
            <div class="bsc-text">
                Kelola paket pengadaan kapor. Pengaturan tahun aktif diatur oleh Superadmin.
                @if($hasYearMismatch)
                    <span class="bsc-alert">
                        <i class="ri-error-warning-fill"></i> Sinkronisasi tertunda
                    </span>
                @endif
            </div>
        </div>
    </div>
    @if(auth()->user()->hasRole('superadmin'))
    <div class="bsc-actions">
        <a href="{{ route('superadmin.settings.index') }}" class="btn-modern-settings">
            <i class="ri-settings-3-line"></i> Buka Pengaturan
        </a>
    </div>
    @endif
</div>
@endif

{{-- Year Cards --}}
<div class="budget-years-intro">
    <div class="byi-heading-row">
        <h2>Daftar Tahun Anggaran</h2>
        <span class="byi-separator"></span>
        <p>Pilih salah satu tahun untuk melihat paket pengadaan dan hasil anggarannya. Tahun sebelumnya tetap bisa dibuka sebagai riwayat.</p>
    </div>
    <span class="budget-years-count">{{ number_format($years->count()) }} tahun</span>
</div>
<div class="budget-years-grid">
    @forelse($years as $year)
    @php
        $isHistoricalYear = (int) $year->year < (int) $activeFiscalYear;
        $isCurrentFiscalYear = (int) $year->year === (int) $activeFiscalYear;
        $isUpcomingYear = (int) $year->year > (int) $activeFiscalYear;
        $isInvalidActiveHistory = $isHistoricalYear && $year->is_active;
        $yearStatusLabel = $isHistoricalYear
            ? 'Riwayat / Arsip'
            : ($isCurrentFiscalYear
                ? ($year->is_active ? 'Aktif Sekarang' : 'Tahun Sistem Aktif')
                : ($year->is_active ? 'Aktif di Modul Budget' : 'Belum Dipakai'));
        $yearStatusClass = $isHistoricalYear ? 'history' : (($year->is_active || $isCurrentFiscalYear) ? 'active' : 'upcoming');
        $yearHelperText = $isHistoricalYear
            ? ($isInvalidActiveHistory
                ? 'Tahun ini sudah lewat, jadi tetap diperlakukan sebagai arsip walaupun pernah ditandai aktif di modul budget.'
                : 'Data tahun ini disimpan sebagai riwayat dan hanya bisa dibuka kembali untuk melihat hasilnya.')
            : ($isCurrentFiscalYear
                ? 'Tahun ini sedang dipakai untuk penyusunan paket budget.'
                : ($year->is_active
                    ? 'Budget year ini aktif untuk modul budget, tetapi masih berbeda dengan tahun sistem aktif.'
                    : 'Tahun ini sudah dibuat, tetapi belum menjadi budget year aktif.'));
        $yearFooterLabel = $isHistoricalYear ? 'Lihat Riwayat Paket' : 'Buka Paket Tahun Ini';
    @endphp
    <div class="budget-year-card {{ ($isHistoricalYear || ! $year->is_active) ? 'inactive' : '' }}">
        <div class="year-card-header">
            <div class="year-badge">
                <i class="ri-calendar-line"></i>
                <span>T.A. {{ $year->year }}</span>
            </div>
            <span class="year-status-badge {{ $yearStatusClass }}">{{ $yearStatusLabel }}</span>
            @if($canManageBudget && ! $isHistoricalYear)
            <div class="year-actions">
                <button class="btn-icon-sm" onclick="openEditYearModal({{ json_encode($year) }})" title="Edit">
                    <i class="ri-edit-line"></i>
                </button>
                <form method="POST" action="{{ route('admin.budget.destroy-year', $year) }}" style="display:inline;" onsubmit="return confirm('{{ $year->packages_count > 0 ? 'PERINGATAN: Tahun anggaran ' . $year->year . ' memiliki ' . $year->packages_count . ' paket. Semua paket beserta data terkait akan ikut TERHAPUS PERMANEN. Lanjutkan?' : 'Hapus tahun anggaran ' . $year->year . '?' }}')">
                    @csrf @method('DELETE')
                    <button class="btn-icon-sm red" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                </form>
            </div>
            @endif
        </div>
        <div class="year-card-body">
            <h2 class="year-title">{{ $year->name }}</h2>
            <p class="year-helper-text">{{ $yearHelperText }}</p>
            <div class="year-stats">
                <div class="year-stat">
                    <span class="year-stat-value">{{ $year->packages_count }}</span>
                    <span class="year-stat-label">Paket</span>
                </div>
                <div class="year-stat">
                    <span class="year-stat-value">{{ $year->total_budget }}</span>
                    <span class="year-stat-label">Total Anggaran</span>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.budget.show-year', $year) }}" class="year-card-footer">
            <span>{{ $yearFooterLabel }}</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="ri-calendar-schedule-line"></i>
        <h3>Belum ada Tahun Anggaran</h3>
        <p>{{ $canManageBudget ? 'Mulai dengan menambahkan tahun anggaran baru' : 'Belum ada tahun anggaran yang tersedia untuk ditampilkan.' }}</p>
        @if($canManageBudget)
        <button class="btn btn-primary" onclick="openModal('addYearModal')">
            <i class="ri-add-line"></i> Tambah Tahun Anggaran
        </button>
        @endif
    </div>
    @endforelse
</div>

@if($canManageBudget)
{{-- Add Year Modal --}}
<div id="addYearModal" class="modal">
    <div class="modal-content" style="max-width: 420px;">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Tahun Anggaran</h2>
            <button class="modal-close" onclick="closeModal('addYearModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.budget.store-year') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>TAHUN</label>
                    <input type="number" name="year" required class="form-input @error('year') is-invalid @enderror" value="{{ old('year', $nextBudgetYear) }}" min="2020" max="2050">
                    @error('year')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group">
                    <label>NAMA (OPSIONAL)</label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" placeholder="Tahun Anggaran 2028" value="{{ old('name') }}">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <p style="font-size: 11px; color: #6B7280; margin-top: 4px;">Otomatis jika dikosongkan</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addYearModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Year Modal --}}
<div id="editYearModal" class="modal">
    <div class="modal-content" style="max-width: 420px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Tahun Anggaran</h2>
            <button class="modal-close" onclick="closeModal('editYearModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editYearForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>TAHUN</label>
                    <input type="number" name="year" id="edit_year" required class="form-input" min="2020" max="2050">
                </div>
                <div class="form-group">
                    <label>NAMA</label>
                    <input type="text" name="name" id="edit_year_name" class="form-input">
                </div>
                <div class="form-group">
                    <label>STATUS</label>
                    <select name="is_active" id="edit_year_active" class="form-input" style="appearance: auto;">
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editYearModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    function openEditYearModal(year) {
        document.getElementById('edit_year').value = year.year;
        document.getElementById('edit_year_name').value = year.name;
        document.getElementById('edit_year_active').value = year.is_active ? '1' : '0';
        document.getElementById('editYearForm').action = "/admin/budget/years/" + year.id;
        openModal('editYearModal');
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.classList.remove('open');
    }

    @if($errors->has('year') || $errors->has('name'))
        openModal('addYearModal');
    @endif
</script>
@endif
@endsection

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }

    .budget-years-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 20px;
    }
    .budget-years-intro {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }
    .byi-heading-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .budget-years-intro h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }
    .byi-separator {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #CBD5E1;
        flex-shrink: 0;
    }
    .budget-years-intro p {
        margin: 0;
        font-size: 13px;
        color: #6B7280;
    }
    .budget-years-count {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .budget-summary-compact {
        background: #ffffff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border-left: 4px solid #1E3A8A;
    }
    .budget-summary-compact.is-warning {
        background: #FEF2F2;
        border-color: #FECACA;
        border-left-color: #DC2626;
    }
    .bsc-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .bsc-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        background: #EFF6FF;
        color: #1E3A8A;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .budget-summary-compact.is-warning .bsc-icon {
        background: #FEE2E2;
        color: #DC2626;
    }
    .bsc-info {
        display: flex; flex-direction: column; gap: 6px;
    }
    .bsc-stats {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .bsc-stat {
        display: flex; align-items: center; gap: 6px;
    }
    .bsc-label {
        font-size: 12px; color: #64748B;
        text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;
    }
    .bsc-val {
        font-size: 13px; font-weight: 700; color: #0F172A;
        background: #F1F5F9; padding: 2px 8px; border-radius: 6px;
    }
    .bsc-val.text-danger {
        background: #FECACA; color: #991B1B;
    }
    .bsc-divider {
        width: 4px; height: 4px; border-radius: 50%; background: #CBD5E1;
    }
    .bsc-text {
        font-size: 13px; color: #475569;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .bsc-alert {
        color: #B91C1C; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; background: #FEE2E2; padding: 2px 6px; border-radius: 4px;
    }
    .btn-modern-settings {
        display: inline-flex; align-items: center; gap: 6px;
        background: #1e3a5f; color: #ffffff;
        padding: 8px 16px; border-radius: 8px;
        font-size: 13px; font-weight: 600; text-decoration: none;
        transition: all 0.2s ease; border: none; cursor: pointer;
        box-shadow: 0 2px 4px rgba(30, 58, 95, 0.2); white-space: nowrap;
    }
    .btn-modern-settings:hover {
        background: #152b47; box-shadow: 0 4px 6px rgba(30, 58, 95, 0.3);
        transform: translateY(-1px); color: #ffffff;
    }

    .budget-year-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
    }
    .budget-year-card:hover {
        box-shadow: 0 8px 25px -5px rgba(0,0,0,0.08);
        border-color: #D1D5DB;
    }
    .budget-year-card.inactive { opacity: 0.92; }

    .year-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 16px 20px 0;
    }
    .year-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #B91C1C;
        background: #FEF2F2;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .year-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .year-status-badge.active {
        background: #DCFCE7;
        color: #166534;
    }
    .year-status-badge.history {
        background: #EFF6FF;
        color: #1D4ED8;
    }
    .year-status-badge.upcoming {
        background: #F3F4F6;
        color: #4B5563;
    }
    .year-actions { display: flex; gap: 4px; }

    .btn-icon-sm {
        width: 28px; height: 28px;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #E5E7EB;
        cursor: pointer; background: #fff;
        color: #6B7280; font-size: 14px;
        transition: all 0.15s;
    }
    .btn-icon-sm:hover { background: #F3F4F6; color: #374151; }
    .btn-icon-sm.red { color: #EF4444; }
    .btn-icon-sm.red:hover { background: #FEF2F2; }

    .year-card-body {
        padding: 16px 20px 20px;
        flex: 1;
    }
    .year-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }
    .year-helper-text {
        margin: 0 0 16px;
        font-size: 13px;
        line-height: 1.6;
        color: #6B7280;
    }
    .year-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .year-stat {
        background: #F9FAFB;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
    }
    .year-stat-value {
        display: block;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
    .year-stat-label {
        display: block;
        font-size: 11px;
        color: #6B7280;
        margin-top: 2px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .year-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        border-top: 1px solid #F3F4F6;
        font-size: 13px;
        font-weight: 600;
        color: #B91C1C;
        text-decoration: none;
        transition: all 0.15s;
        background: #FEFCE8;
    }
    .year-card-footer:hover {
        background: #FEF9C3;
        color: #991B1B;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #9CA3AF;
    }
    .empty-state i {
        font-size: 56px;
        display: block;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    .empty-state h3 {
        font-size: 18px;
        color: #6B7280;
        margin-bottom: 8px;
    }
    .empty-state p {
        font-size: 14px;
        margin-bottom: 20px;
    }

    /* Modals */
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 50; backdrop-filter: blur(4px); }
    .modal.open { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: #fff; border-radius: 16px; width: 90%; position: relative; animation: zoomIn 0.2s; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
    .modal-title { font-size: 16px; font-weight: 700; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; }
    .modal-close { background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6B7280; }
    .modal-close:hover { background: #E5E7EB; color: #111827; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit; }
    .form-input:focus { border-color: #B91C1C; box-shadow: 0 0 0 3px #FEF2F2; }
    .form-input.is-invalid { border-color: #DC2626; box-shadow: 0 0 0 3px #FEF2F2; }
    .form-error { font-size: 12px; color: #B91C1C; margin-top: 6px; }

    @media (max-width: 768px) {
        .page-header-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .budget-years-intro {
            flex-direction: column;
            align-items: flex-start;
        }
        .byi-heading-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .byi-separator {
            display: none;
        }
        .budget-summary-compact {
            flex-direction: column;
            align-items: flex-start;
        }
        .bsc-actions, .bsc-actions .btn-modern-settings {
            width: 100%;
            justify-content: center;
        }
        .bsc-divider {
            display: none;
        }
        .bsc-stats {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
    }

    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endsection
