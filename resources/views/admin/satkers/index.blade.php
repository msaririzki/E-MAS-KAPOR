@extends('layouts.app')

@section('title', 'Data Satker')
@section('breadcrumb', 'Data Satker')

@section('content')
@php
    $routePrefix = auth()->user()->hasRole('superadmin') ? 'superadmin' : 'admin';
@endphp

{{-- Page Header --}}
<div class="page-header" style="margin-bottom: 24px;">
    <div class="page-header-row">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0;">Data Satuan Kerja</h1>
            </div>
            <p style="color: #6B7280; font-size: 14px;">Kelola data satuan kerja dan jumlah personil di lingkungan Polda NTB</p>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-maroon" onclick="openModal('addModal')">
                <i class="ri-add-line"></i> Tambah Satker
            </button>
        </div>
    </div>
</div>

{{-- Stats Grid --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-building-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Satker</span>
            <span class="stat-number">{{ $satkers->count() }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="ri-group-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Personil POLRI</span>
            <span class="stat-number">{{ number_format($satkers->sum('polri_count')) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="ri-user-star-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Personil PNS/P3K</span>
            <span class="stat-number">{{ number_format($satkers->sum('pns_count')) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i class="ri-team-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Kekuatan</span>
            <span class="stat-number">{{ number_format($satkers->sum('polri_count') + $satkers->sum('pns_count')) }}</span>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="table-container shadow-sm">
    <div class="card-header-simple" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 16px 20px; border-bottom: 1px solid #F3F4F6;">
        <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">
            <i class="ri-list-check-2" style="margin-right: 8px; color: #B91C1C;"></i> Daftar Satker
        </h3>
        <span class="role-pill">{{ $satkers->count() }} Satker Terdaftar</span>
    </div>
    <table class="user-table">
        <thead>
            <tr>
                <th style="width: 60px; text-align: center;">NO</th>
                <th>NAMA SATUAN KERJA</th>
                <th style="width: 150px; text-align: center;">POLRI</th>
                <th style="width: 150px; text-align: center;">PNS/P3K</th>
                <th style="width: 150px; text-align: center;">TOTAL</th>
                <th style="width: 120px; text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($satkers as $index => $satker)
            <tr>
                <td style="text-align: center; color: #9CA3AF; font-size: 13px; font-weight: 600;">{{ $index + 1 }}</td>
                <td>
                    <span style="font-weight: 600; color: #111827;">{{ $satker->name }}</span>
                </td>
                <td style="text-align: center;">
                    <form method="POST" action="{{ route($routePrefix . '.satkers.update-personnel', $satker) }}" class="inline-personnel-form" id="form-{{ $satker->id }}">
                        @csrf
                        @method('PATCH')
                        <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                            <input type="number" name="polri_count" value="{{ $satker->polri_count }}"
                                min="0" class="input-inline" style="width: 70px; text-align: center;"
                                oninput="showSaveBtn('{{ $satker->id }}')">
                        </div>
                </td>
                <td style="text-align: center;">
                        <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                            <input type="number" name="pns_count" value="{{ $satker->pns_count }}"
                                min="0" class="input-inline" style="width: 70px; text-align: center;"
                                oninput="showSaveBtn('{{ $satker->id }}')">
                            <button type="submit" class="btn-save-inline" id="save-{{ $satker->id }}" title="Simpan Perubahan" style="display: none;">
                                <i class="ri-checkbox-circle-fill"></i>
                            </button>
                        </div>
                    </form>
                </td>
                <td style="text-align: center;">
                    <span style="font-weight: 700; color: #4B5563; background: #F9FAFB; padding: 4px 12px; border-radius: 6px; font-size: 13px;">
                        {{ number_format($satker->polri_count + $satker->pns_count) }}
                    </span>
                </td>
                <td style="text-align: center;">
                    <div class="action-buttons" style="justify-content: center;">
                        <button class="btn-icon blue" title="Edit Satker" onclick="openEditModal({{ json_encode($satker) }})">
                            <i class="ri-edit-line"></i>
                        </button>
                        <form method="POST" action="{{ route($routePrefix . '.satkers.destroy', $satker) }}"
                            onsubmit="return confirm('Yakin ingin menghapus satker {{ $satker->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon red" title="Hapus Satker">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 48px; color: #9CA3AF;">
                    <i class="ri-building-2-line" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                    Belum ada data satuan kerja ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($satkers->isNotEmpty())
        <tfoot>
            <tr style="background: #FAFAFA; border-top: 2px solid #F3F4F6;">
                <td></td>
                <td style="font-weight: 700; color: #111827;">REKAPITULASI TOTAL</td>
                <td style="text-align: center; font-weight: 800; color: #B91C1C;">{{ number_format($satkers->sum('polri_count')) }}</td>
                <td style="text-align: center; font-weight: 800; color: #B91C1C;">{{ number_format($satkers->sum('pns_count')) }}</td>
                <td style="text-align: center; font-weight: 800; color: #111827; background: #F3F4F6;">{{ number_format($satkers->sum('polri_count') + $satkers->sum('pns_count')) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Modal Tambah --}}
<div class="modal-overlay" id="addModal">
    <div class="modal shadow-xl" style="max-width: 450px;">
        <div class="modal-header" style="border-bottom: 1px solid #F3F4F6; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">Tambah Satker Baru</h3>
            <button class="modal-close" onclick="closeModal('addModal')"><i class="ri-close-line"></i></button>
        </div>
        <form method="POST" action="{{ route($routePrefix . '.satkers.store') }}">
            @csrf
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group-simple" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Nama Satuan Kerja</label>
                    <input type="text" name="name" class="form-input-simple" placeholder="Masukkan nama satker..." required style="height: 46px;">
                </div>
                <div class="form-grid-2">
                    <div class="form-group-simple">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Jml POLRI</label>
                        <input type="number" name="polri_count" class="form-input-simple" value="0" min="0" style="height: 46px;">
                    </div>
                    <div class="form-group-simple">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Jml PNS/P3K</label>
                        <input type="number" name="pns_count" class="form-input-simple" value="0" min="0" style="height: 46px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-radius: 0 0 16px 16px;">
                <button type="button" class="btn-simple btn-outline-simple" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn-simple btn-maroon-simple" style="padding: 0 24px;">Simpan Satker</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal-overlay" id="editModal">
    <div class="modal shadow-xl" style="max-width: 450px;">
        <div class="modal-header" style="border-bottom: 1px solid #F3F4F6; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">Edit Data Satker</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><i class="ri-close-line"></i></button>
        </div>
        <form method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group-simple" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Nama Satuan Kerja</label>
                    <input type="text" name="name" id="edit_name" class="form-input-simple" required style="height: 46px;">
                </div>
                <div class="form-grid-2">
                    <div class="form-group-simple">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Jml POLRI</label>
                        <input type="number" name="polri_count" id="edit_polri_count" class="form-input-simple" min="0" style="height: 46px;">
                    </div>
                    <div class="form-group-simple">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Jml PNS/P3K</label>
                        <input type="number" name="pns_count" id="edit_pns_count" class="form-input-simple" min="0" style="height: 46px;">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: #F9FAFB; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 12px; border-radius: 0 0 16px 16px;">
                <button type="button" class="btn-simple btn-outline-simple" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn-simple btn-maroon-simple" style="padding: 0 24px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="toast-container"></div>
@endsection

@section('styles')
<style>
    /* Reuse global maroon styles */
    .btn-maroon {
        background: #B91C1C !important; border: none; color: #fff; 
        box-shadow: 0 4px 14px rgba(185, 28, 28, 0.2);
        border-radius: 8px; padding: 10px 20px; font-weight: 600;
    }
    .btn-maroon:hover { background: #991B1B !important; }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .icon-blue { background: #EBF5FF; color: #3B82F6; }
    .icon-green { background: #F0FDF4; color: #22C55E; }
    .icon-orange { background: #FFF7ED; color: #F97316; }
    .icon-purple { background: #F5F3FF; color: #8B5CF6; }
    .stat-label { font-size: 13px; color: #6B7280; display: block; }
    .stat-number { font-size: 20px; font-weight: 700; color: #111827; }

    /* Table Container */
    .table-container { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #F3F4F6; transition: box-shadow 0.3s; }
    .table-container:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #FAFAFA; padding: 12px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; border-bottom: 1px solid #F3F4F6; }
    .user-table td { padding: 14px 20px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }

    /* Inline Input */
    .input-inline {
        height: 32px; border: 1px solid #E2E8F0; border-radius: 8px; background: #F8FAFC;
        font-size: 13px; font-weight: 600; color: #1F2937; outline: none; transition: all 0.2s;
    }
    .input-inline:focus { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.05); }

    .btn-save-inline {
        background: none; border: none; color: #10B981; font-size: 24px; cursor: pointer;
        display: flex; align-items: center; transition: transform 0.2s;
    }
    .btn-save-inline:hover { transform: scale(1.1); color: #059669; }

    /* Pills & Actions */
    .role-pill { background: #FEF2F2; color: #B91C1C; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 600; border: 1px solid #FEE2E2; }
    .action-buttons { display: flex; gap: 8px; }
    .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #E5E7EB; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
    .btn-icon.blue { color: #3B82F6; }
    .btn-icon.blue:hover { background: #3B82F6; border-color: #3B82F6; color: #fff; }
    .btn-icon.red { color: #EF4444; }
    .btn-icon.red:hover { background: #EF4444; border-color: #EF4444; color: #fff; }

    /* Modal Styling */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; animation: fadeIn 0.2s ease-out; }
    .modal { background: #fff; border-radius: 16px; width: 90%; animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); overflow: hidden; }
    .modal-close { background: #F3F4F6; border: none; width: 32px; height: 32px; border-radius: 8px; color: #6B7280; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .modal-close:hover { background: #FEE2E2; color: #EF4444; }

    /* Form Styles */
    .form-input-simple { width: 100%; border: 1px solid #E5E7EB; border-radius: 10px; padding: 0 16px; font-size: 14px; color: #1F2937; outline: none; transition: all 0.2s; background: #F9FAFB; }
    .form-input-simple:focus { border-color: #B91C1C; background: #fff; box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.05); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .btn-simple { height: 46px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; padding: 0 20px; }
    .btn-maroon-simple { background: #B91C1C; color: #fff; }
    .btn-maroon-simple:hover { background: #991B1B; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2); }
    .btn-outline-simple { background: #fff; border: 1px solid #E5E7EB; color: #4B5563; }
    .btn-outline-simple:hover { background: #F9FAFB; border-color: #D1D5DB; }

    /* ── Toast Notifications ───────────────────────────── */
    .toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; }
    .toast { min-width: 320px; max-width: 450px; background: #fff; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #E5E7EB; animation: toast-slide-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; position: relative; overflow: hidden; }
    .toast::after { content: ''; position: absolute; bottom: 0; left: 0; height: 3px; width: 100%; background: currentColor; opacity: 0.3; animation: toast-progress 4s linear forwards; }
    .toast.success { border-left: 4px solid #10B981; color: #10B981; }
    .toast.error { border-left: 4px solid #EF4444; color: #EF4444; }
    .toast-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .toast.success .toast-icon { background: #DCFCE7; color: #10B981; }
    .toast.error .toast-icon { background: #FEF2F2; color: #EF4444; }
    .toast-content { flex: 1; }
    .toast-title { display: block; font-weight: 700; font-size: 14px; color: #111827; margin-bottom: 2px; }
    .toast-message { font-size: 13px; color: #6B7280; }
    .toast-close { color: #9CA3AF; cursor: pointer; font-size: 18px; transition: color 0.2s; }
    .toast-close:hover { color: #4B5563; }
    @keyframes toast-slide-in { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes toast-fade-out { from { transform: translateX(0); opacity: 1; } to { transform: translateX(120%); opacity: 0; } }
    @keyframes toast-progress { from { width: 100%; } to { width: 0%; } }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endsection

@section('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    const routePrefix = '{{ $routePrefix }}';

    function openEditModal(satker) {
        document.getElementById('edit_name').value = satker.name;
        document.getElementById('edit_polri_count').value = satker.polri_count || 0;
        document.getElementById('edit_pns_count').value = satker.pns_count || 0;
        document.getElementById('editForm').action = '/' + routePrefix + '/satkers/' + satker.id;
        openModal('editModal');
    }

    function showSaveBtn(id) {
        document.getElementById('save-' + id).style.display = 'flex';
    }

    // Toast logic from User Index
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill';
        const title = type === 'success' ? 'Berhasil!' : 'Terjadi Kesalahan';
        
        toast.innerHTML = `
            <div class="toast-icon"> <i class="${icon}"></i> </div>
            <div class="toast-content">
                <span class="toast-title">${title}</span>
                <span class="toast-message">${message}</span>
            </div>
            <i class="ri-close-line toast-close" onclick="this.parentElement.remove()"></i>
        `;
        
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'toast-fade-out 0.4s ease forwards';
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 400);
        }, 4000);
    }

    @if(session('success')) showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) showToast("{{ session('error') }}", 'error'); @endif
</script>
@endsection
