@extends('layouts.app')

@section('title', 'Paket - ' . $budgetYear->name)
@section('breadcrumb')
    <a href="{{ route('admin.budget.index') }}">Rencana Anggaran</a>
    <span class="sep">/</span>
    <span class="current">{{ $budgetYear->name }}</span>
@endsection

@section('content')
@php
    $canManageBudget = auth()->user()->hasAnyRole(['superadmin', 'admin']);
    $isHistoricalYear = (int) $budgetYear->year < (int) $activeFiscalYear;
    $isUpcomingYear = (int) $budgetYear->year > (int) $activeFiscalYear;
    $isCurrentFiscalYear = (int) $budgetYear->year === (int) $activeFiscalYear;
    $canManageThisYear = $canManageBudget && $isCurrentFiscalYear;
    $yearContextLabel = $budgetYear->is_active
        ? ($isCurrentFiscalYear ? 'Tahun Aktif Saat Ini' : 'Aktif di Modul Budget')
        : ($isHistoricalYear ? 'Riwayat / Arsip' : ($isUpcomingYear ? 'Belum Dipakai' : 'Nonaktif'));
    $yearContextClass = $budgetYear->is_active
        ? ($isCurrentFiscalYear ? 'active' : 'warning')
        : ($isHistoricalYear ? 'history' : 'neutral');
    $yearSubtitle = $isCurrentFiscalYear
        ? 'Kelola paket pengadaan kapor untuk tahun anggaran yang sedang berjalan.'
        : ($isHistoricalYear
            ? 'Lihat kembali paket pengadaan dan hasil anggaran pada tahun sebelumnya.'
            : 'Tahun ini sudah tersedia di modul budget, tetapi belum menjadi tahun sistem aktif.');
@endphp
<div class="page-header">
    <div class="page-header-row">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                <a href="{{ route('admin.budget.index') }}" class="btn btn-ghost btn-sm" style="padding: 4px 8px;">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <h1 class="page-title">{{ $budgetYear->name }}</h1>
            </div>
            <p class="page-subtitle">{{ $yearSubtitle }}</p>
        </div>
        @if($canManageThisYear)
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="openModal('addPackageModal')">
                <i class="ri-add-line"></i> Tambah Paket
            </button>
        </div>
        @endif
    </div>
</div>

<div class="year-context-card {{ $yearContextClass }}">
    <div class="year-context-meta">
        <span class="year-context-badge">{{ $yearContextLabel }}</span>
        <strong>T.A. {{ $budgetYear->year }}</strong>
        <small>
            {{ $budgetYear->packages->count() }} paket terdaftar.
            {{ $isHistoricalYear ? 'Gunakan halaman ini untuk melihat riwayat paket tahun tersebut.' : ($isUpcomingYear ? 'Paket bisa disiapkan lebih awal untuk tahun ini.' : 'Paket pada tahun ini dipakai untuk penyusunan anggaran aktif.') }}
        </small>
    </div>
    <div class="year-context-side">
        <span class="year-context-side-label">Tahun Sistem Aktif</span>
        <span class="year-context-side-value">TA {{ $activeFiscalYear }}</span>
    </div>
</div>

{{-- Package Cards --}}
<div class="packages-grid">
    @forelse($budgetYear->packages as $package)
    <div class="package-card">
        <div class="package-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="package-icon">
                    <i class="ri-box-3-line"></i>
                </div>
                <div>
                    <h3 class="package-name">{{ $package->name }}</h3>
                    <span class="package-badge" style="background: {{ $package->status_color['bg'] }}; color: {{ $package->status_color['text'] }};">
                        {{ $package->status_label }}
                    </span>
                </div>
            </div>
            @if($canManageThisYear)
            <div class="package-actions">
                <button class="btn-icon-sm" onclick="openEditPackageModal({{ json_encode($package) }})" title="Edit">
                    <i class="ri-edit-line"></i>
                </button>
                <form method="POST" action="{{ route('admin.budget.destroy-package', $package) }}" style="display:inline;" onsubmit="return confirm('Hapus paket {{ $package->name }}?')">
                    @csrf @method('DELETE')
                    <button class="btn-icon-sm red" title="Hapus"><i class="ri-delete-bin-line"></i></button>
                </form>
            </div>
            @endif
        </div>

        @if($package->description)
        <div class="package-description">
            {{ $package->description }}
        </div>
        @endif

        <div class="package-stats">
            <div class="pkg-stat">
                <i class="ri-shirt-line"></i>
                <span>{{ $package->items()->count() }} Barang</span>
            </div>
            <div class="pkg-stat">
                <i class="ri-money-dollar-circle-line"></i>
                <span>{{ $package->formatted_budget }}</span>
            </div>
        </div>

        <a href="{{ route('admin.budget.show-package', $package) }}" class="package-card-footer">
            <span>{{ $canManageThisYear ? 'Kelola Item Paket' : 'Lihat Detail Paket' }}</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="ri-box-3-line"></i>
        <h3>Belum ada Paket</h3>
        <p>{{ $canManageThisYear ? 'Tambahkan paket pengadaan untuk tahun anggaran ini' : 'Belum ada paket pengadaan pada tahun anggaran ini.' }}</p>
        @if($canManageThisYear)
        <button class="btn btn-primary" onclick="openModal('addPackageModal')">
            <i class="ri-add-line"></i> Tambah Paket
        </button>
        @endif
    </div>
    @endforelse
</div>

@if($canManageThisYear)
{{-- Add Package Modal --}}
<div id="addPackageModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Paket Baru</h2>
            <button class="modal-close" onclick="closeModal('addPackageModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.budget.store-package', $budgetYear) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>NAMA PAKET</label>
                    <input type="text" name="name" required class="form-input" placeholder="Contoh: Paket I">
                </div>
                <div class="form-group">
                    <label>DESKRIPSI (OPSIONAL)</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Contoh: PAKAIAN DINAS, TOPI LAPANGAN PATI, TOPI LAPANGAN PNS, JILBAB DAN PAKAIAN OLAHRAGA"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addPackageModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Package Modal --}}
<div id="editPackageModal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h2 class="modal-title">Edit Paket</h2>
            <button class="modal-close" onclick="closeModal('editPackageModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editPackageForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>NAMA PAKET</label>
                    <input type="text" name="name" id="edit_pkg_name" required class="form-input">
                </div>
                <div class="form-group">
                    <label>DESKRIPSI</label>
                    <textarea name="description" id="edit_pkg_desc" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>STATUS</label>
                    <select name="status" id="edit_pkg_status" class="form-input" style="appearance: auto;">
                        <option value="draft">Draft</option>
                        <option value="finalized">Final</option>
                        <option value="archived">Arsip</option>
                    </select>
                    <div style="margin-top: 8px; font-size: 12px; color: #6B7280; line-height: 1.5;">
                        Saat paket disimpan sebagai <strong>Final</strong>, sistem akan membentuk atau memperbarui snapshot item review untuk personil yang termasuk dalam nominatif paket tersebut.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editPackageModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    function openEditPackageModal(pkg) {
        document.getElementById('edit_pkg_name').value = pkg.name;
        document.getElementById('edit_pkg_desc').value = pkg.description || '';
        document.getElementById('edit_pkg_status').value = pkg.status;
        document.getElementById('editPackageForm').action = "/admin/budget/packages/" + pkg.id;
        openModal('editPackageModal');
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) e.target.classList.remove('open');
    }
</script>
@endif
@endsection

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 2px; margin-left: 40px; }
    .page-header { margin-bottom: 24px; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    .year-context-card {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        padding: 18px 20px;
        margin-bottom: 20px;
        border-radius: 16px;
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
    }
    .year-context-card.active {
        background: linear-gradient(135deg, #ECFDF5 0%, #F0FDF4 100%);
        border-color: #BBF7D0;
    }
    .year-context-card.history {
        background: linear-gradient(135deg, #EFF6FF 0%, #F8FAFC 100%);
        border-color: #BFDBFE;
    }
    .year-context-card.warning {
        background: linear-gradient(135deg, #FFF7ED 0%, #FFFBEB 100%);
        border-color: #FCD34D;
    }
    .year-context-card.neutral {
        background: linear-gradient(135deg, #F8FAFC 0%, #FFFFFF 100%);
        border-color: #E2E8F0;
    }
    .year-context-meta {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .year-context-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.06);
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }
    .year-context-meta strong {
        font-size: 24px;
        line-height: 1.2;
        color: #0F172A;
    }
    .year-context-meta small {
        font-size: 13px;
        line-height: 1.6;
        color: #64748B;
    }
    .year-context-side {
        min-width: 140px;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(148, 163, 184, 0.25);
        text-align: center;
    }
    .year-context-side-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #64748B;
        margin-bottom: 4px;
    }
    .year-context-side-value {
        display: block;
        font-size: 22px;
        font-weight: 800;
        color: #0F172A;
    }

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 20px;
    }

    .package-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
    }
    .package-card:hover { box-shadow: 0 8px 25px -5px rgba(0,0,0,0.08); border-color: #D1D5DB; }

    .package-card-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        padding: 20px 20px 0;
    }
    .package-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: #EFF6FF; color: #3B82F6;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .package-name { font-size: 16px; font-weight: 700; color: #111827; }
    .package-badge {
        display: inline-block; font-size: 10px; font-weight: 700;
        padding: 2px 8px; border-radius: 20px; margin-top: 2px;
    }
    .package-actions { display: flex; gap: 4px; }
    .btn-icon-sm {
        width: 28px; height: 28px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #E5E7EB; cursor: pointer; background: #fff;
        color: #6B7280; font-size: 14px; transition: all 0.15s;
    }
    .btn-icon-sm:hover { background: #F3F4F6; }
    .btn-icon-sm.red { color: #EF4444; }

    .package-description {
        padding: 12px 20px 0;
        font-size: 13px; color: #6B7280; line-height: 1.5;
    }

    .package-stats {
        display: flex; gap: 20px; padding: 16px 20px;
    }
    .pkg-stat {
        display: flex; align-items: center; gap: 6px;
        font-size: 13px; color: #6B7280; font-weight: 500;
    }
    .pkg-stat i { color: #9CA3AF; font-size: 15px; }

    .package-card-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 20px; border-top: 1px solid #F3F4F6;
        font-size: 13px; font-weight: 600; color: #B91C1C;
        text-decoration: none; transition: all 0.15s;
        background: #FEFCE8; margin-top: auto;
    }
    .package-card-footer:hover { background: #FEF9C3; }

    .empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }
    .empty-state i { font-size: 56px; display: block; margin-bottom: 16px; opacity: 0.3; }
    .empty-state h3 { font-size: 18px; color: #6B7280; margin-bottom: 8px; }
    .empty-state p { font-size: 14px; margin-bottom: 20px; }

    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 50; backdrop-filter: blur(4px); }
    .modal.open { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: #fff; border-radius: 16px; width: 90%; position: relative; animation: zoomIn 0.2s; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
    .modal-title { font-size: 16px; font-weight: 700; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; }
    .modal-close { background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #6B7280; }
    .modal-close:hover { background: #E5E7EB; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit; }
    .form-input:focus { border-color: #B91C1C; box-shadow: 0 0 0 3px #FEF2F2; }
    textarea.form-input { resize: vertical; }

    @media (max-width: 768px) {
        .page-header-row { flex-direction: column; align-items: flex-start; gap: 12px; }
        .page-subtitle { margin-left: 0; }
        .year-context-card {
            flex-direction: column;
            align-items: stretch;
        }
        .year-context-side {
            min-width: 0;
        }
    }

    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endsection
