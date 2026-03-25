@extends('layouts.app')

@section('title', 'Rencana Anggaran')
@section('breadcrumb', 'Rencana Anggaran')

@section('content')
@php
    $nextBudgetYear = max((int) date('Y'), (int) ($years->max('year') ?? date('Y'))) + 1;
@endphp
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Rencana Anggaran</h1>
            <p class="page-subtitle">Kelola tahun anggaran dan paket pengadaan kapor</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="openModal('addYearModal')">
                <i class="ri-add-line"></i> Tambah Tahun Anggaran
            </button>
        </div>
    </div>
</div>

{{-- Year Cards --}}
<div class="budget-years-grid">
    @forelse($years as $year)
    <div class="budget-year-card {{ $year->is_active ? '' : 'inactive' }}">
        <div class="year-card-header">
            <div class="year-badge">
                <i class="ri-calendar-line"></i>
                <span>T.A. {{ $year->year }}</span>
            </div>
            <div class="year-actions">
                <button class="btn-icon-sm" onclick="openEditYearModal({{ json_encode($year) }})" title="Edit">
                    <i class="ri-edit-line"></i>
                </button>
                <form method="POST" action="{{ route('admin.budget.destroy-year', $year) }}" style="display:inline;" onsubmit="return confirm('{{ $year->packages_count > 0 ? 'PERINGATAN: Tahun anggaran ' . $year->year . ' memiliki ' . $year->packages_count . ' paket. Semua paket beserta data terkait akan ikut TERHAPUS PERMANEN. Lanjutkan?' : 'Hapus tahun anggaran ' . $year->year . '?' }}')">
                    @csrf @method('DELETE')
                    <button class="btn-icon-sm red" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                </form>
            </div>
        </div>
        <div class="year-card-body">
            <h2 class="year-title">{{ $year->name }}</h2>
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
            @if(!$year->is_active)
                <span class="year-inactive-badge">Non-Aktif</span>
            @endif
        </div>
        <a href="{{ route('admin.budget.show-year', $year) }}" class="year-card-footer">
            <span>Lihat Paket</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="ri-calendar-schedule-line"></i>
        <h3>Belum ada Tahun Anggaran</h3>
        <p>Mulai dengan menambahkan tahun anggaran baru</p>
        <button class="btn btn-primary" onclick="openModal('addYearModal')">
            <i class="ri-add-line"></i> Tambah Tahun Anggaran
        </button>
    </div>
    @endforelse
</div>

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
    .budget-year-card.inactive { opacity: 0.6; }

    .year-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        margin-bottom: 16px;
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

    .year-inactive-badge {
        display: inline-block;
        margin-top: 12px;
        font-size: 11px;
        font-weight: 600;
        color: #991B1B;
        background: #FEE2E2;
        padding: 3px 10px;
        border-radius: 20px;
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

    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endsection
