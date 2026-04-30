@extends('layouts.app')

@section('title', 'Penanda Tangan SPPM')
@section('breadcrumb', 'Penanda Tangan')

@section('content')



<div class="sig-page">
    <div class="sig-header">
        <div class="sig-header-left">
            <h1>Penanda Tangan SPPM</h1>
            <p>Kelola data penanda tangan untuk dokumen SPPM gudang</p>
        </div>
        <button class="btn-add-sig" onclick="openSigModal('addModal')">
            <i class="ri-add-line"></i> Tambah Penanda Tangan
        </button>
    </div>

    <div class="sig-info">
        <i class="ri-information-fill"></i>
        <p><strong>Penanda tangan aktif</strong> akan digunakan secara otomatis saat men-generate dokumen SPPM. Hanya bisa ada 1 penanda tangan aktif.</p>
    </div>

    @if($signatories->count())
        <div class="sig-grid">
            @foreach($signatories as $sig)
                <div class="sig-card {{ $sig->is_active ? 'active' : '' }}">
                    <div class="sig-card-top">
                        <div class="sig-avatar">{{ strtoupper(substr($sig->name, 0, 1)) }}</div>
                        <span class="sig-badge {{ $sig->is_active ? 'active' : 'inactive' }}">
                            <i class="{{ $sig->is_active ? 'ri-checkbox-circle-fill' : 'ri-close-circle-line' }}"></i>
                            {{ $sig->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <h3 class="sig-name">{{ $sig->name }}</h3>
                    <p class="sig-jabatan">{{ $sig->jabatan }}</p>
                    <div class="sig-meta" style="flex-wrap:wrap;">
                        @if($sig->satuan_kerja)
                            <span class="sig-meta-item"><i class="ri-building-line"></i> {{ $sig->satuan_kerja }}</span>
                        @endif
                        @if($sig->pangkat)
                            <span class="sig-meta-item"><i class="ri-shield-star-line"></i> {{ $sig->pangkat }}</span>
                        @endif
                        @if($sig->nrp)
                            <span class="sig-meta-item"><i class="ri-hashtag"></i> {{ $sig->nrp }}</span>
                        @endif
                        @if($sig->atribut && $sig->wakil)
                            <span class="sig-meta-item"><i class="ri-user-follow-line"></i> {{ $sig->atribut }} {{ $sig->wakil }}</span>
                        @endif
                    </div>
                    <div class="sig-actions">
                        @if(!$sig->is_active)
                            <form action="{{ route('admin.warehouse-items.signatories.toggle', $sig) }}" method="POST" style="flex:1; display:flex;">
                                @csrf
                                <button type="submit" class="sig-btn green" style="flex:1;"><i class="ri-check-line"></i> Aktifkan</button>
                            </form>
                        @endif
                        <button onclick='openEditModal(@json($sig))' class="sig-btn blue" style="flex:1;"><i class="ri-edit-line"></i> Edit</button>
                        <button onclick="confirmDelete({{ $sig->id }}, '{{ $sig->name }}')" class="sig-btn red" style="flex:1;"><i class="ri-delete-bin-line"></i> Hapus</button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sig-empty">
            <i class="ri-user-unfollow-line"></i>
            <p>Belum ada data penanda tangan.<br>Klik tombol <strong>Tambah Penanda Tangan</strong> untuk memulai.</p>
        </div>
    @endif
</div>

{{-- Add Modal --}}
<div class="sig-modal-overlay" id="addModal">
    <div class="sig-modal">
        <div class="sig-modal-header">
            <h3>Tambah Penanda Tangan</h3>
            <button class="sig-modal-close" onclick="closeSigModal('addModal')"><i class="ri-close-line"></i></button>
        </div>
        <form action="{{ route('admin.warehouse-items.signatories.store') }}" method="POST">
            @csrf
            <div class="sig-modal-body">
                <div class="form-group">
                    <label>KEPALA BIRO</label>
                    <input type="text" name="satuan_kerja" class="f-input" placeholder="Contoh: BIRO LOGISTIK POLDA NTB" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="f-input" placeholder="Contoh: PRATIWI NOFIANI, S.I.K.,M.M." required>
                </div>
                <div class="form-group">
                    <label>KABAG</label>
                    <input type="text" name="jabatan" class="f-input" placeholder="Contoh: KABAG BEKUM" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Pangkat</label>
                        <input type="text" name="pangkat" class="f-input" placeholder="Contoh: KOMISARIS POLISI">
                    </div>
                    <div class="form-group">
                        <label>NRP</label>
                        <input type="text" name="nrp" class="f-input" placeholder="Contoh: 85031977">
                    </div>
                </div>
                <div class="form-group">
                    <label>Atribut</label>
                    <input type="text" name="atribut" class="f-input" placeholder="Contoh: u.b">
                </div>
                <div class="form-group">
                    <label>MEWAKILI</label>
                    <input type="text" name="wakil" class="f-input" placeholder="Contoh: KAUR SUBBAG KAPSINTOR">
                </div>
            </div>
            <div class="sig-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSigModal('addModal')">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div class="sig-modal-overlay" id="editModal">
    <div class="sig-modal">
        <div class="sig-modal-header">
            <h3>Edit Penanda Tangan</h3>
            <button class="sig-modal-close" onclick="closeSigModal('editModal')"><i class="ri-close-line"></i></button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="sig-modal-body">
                <div class="form-group">
                    <label>KEPALA BIRO</label>
                    <input type="text" name="satuan_kerja" id="e_satuan_kerja" class="f-input" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" id="e_name" class="f-input" required>
                </div>
                <div class="form-group">
                    <label>KABAG</label>
                    <input type="text" name="jabatan" id="e_jabatan" class="f-input" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Pangkat</label>
                        <input type="text" name="pangkat" id="e_pangkat" class="f-input">
                    </div>
                    <div class="form-group">
                        <label>NRP</label>
                        <input type="text" name="nrp" id="e_nrp" class="f-input">
                    </div>
                </div>
                <div class="form-group">
                    <label>Atribut</label>
                    <input type="text" name="atribut" id="e_atribut" class="f-input">
                </div>
                <div class="form-group">
                    <label>MEWAKILI</label>
                    <input type="text" name="wakil" id="e_wakil" class="f-input">
                </div>
            </div>
            <div class="sig-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSigModal('editModal')">Batal</button>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
@endsection

@section('styles')
<style>
    .sig-page { max-width: 900px; margin: 0 auto; }
    .sig-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
    }
    .sig-header-left h1 { font-size: 22px; font-weight: 700; color: var(--text-main, #111827); margin: 0; }
    .sig-header-left p { font-size: 13px; color: var(--text-muted, #6B7280); margin: 4px 0 0; }
    .btn-add-sig {
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%); color: white;
        padding: 10px 22px; border: none; border-radius: 10px; font-size: 13px;
        font-weight: 700; cursor: pointer; display: inline-flex; align-items: center;
        gap: 6px; transition: all 0.2s; font-family: inherit;
    }
    .btn-add-sig:hover { box-shadow: 0 4px 12px rgba(217,119,6,0.3); transform: translateY(-1px); }

    .sig-info {
        background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px;
        padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 10px;
    }
    .sig-info i { color: #D97706; font-size: 18px; margin-top: 1px; flex-shrink: 0; }
    .sig-info p { font-size: 13px; color: #92400E; margin: 0; }

    .sig-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 16px; }
    .sig-card {
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #E5E7EB);
        border-radius: 14px; padding: 20px 24px; position: relative; transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .sig-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .sig-card.active { border-color: #34D399; background: linear-gradient(135deg, #ECFDF5 0%, #F0FDF4 100%); }
    .sig-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
    .sig-avatar {
        width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center;
        justify-content: center; font-size: 20px; font-weight: 800;
    }
    .sig-card.active .sig-avatar { background: #D1FAE5; color: #059669; }
    .sig-card:not(.active) .sig-avatar { background: #F3F4F6; color: #6B7280; }
    .sig-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px;
        border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .sig-badge.active { background: #D1FAE5; color: #059669; }
    .sig-badge.inactive { background: #F3F4F6; color: #9CA3AF; }
    .sig-name { font-size: 16px; font-weight: 700; color: var(--text-main, #111827); margin: 0 0 4px; }
    .sig-jabatan { font-size: 13px; color: var(--text-muted, #6B7280); margin: 0 0 10px; }
    .sig-meta { display: flex; gap: 16px; margin-bottom: 16px; }
    .sig-meta-item {
        display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-muted, #6B7280);
    }
    .sig-meta-item i { font-size: 14px; }
    .sig-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid var(--border-color, #F3F4F6); }
    .sig-btn {
        flex: 1; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color, #E5E7EB);
        background: var(--bg-card, #fff); font-size: 12px; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 4px;
        transition: all 0.15s; font-family: inherit;
    }
    .sig-btn.green { color: #059669; border-color: #A7F3D0; }
    .sig-btn.green:hover { background: #ECFDF5; }
    .sig-btn.blue { color: #3B82F6; border-color: #BFDBFE; }
    .sig-btn.blue:hover { background: #EFF6FF; }
    .sig-btn.red { color: #DC2626; border-color: #FECACA; }
    .sig-btn.red:hover { background: #FEF2F2; }

    .sig-empty {
        text-align: center; padding: 60px 20px; color: #9CA3AF;
        background: var(--bg-card, #fff); border: 2px dashed var(--border-color, #E5E7EB);
        border-radius: 16px;
    }
    .sig-empty i { font-size: 48px; display: block; margin-bottom: 12px; color: #D1D5DB; }
    .sig-empty p { font-size: 14px; margin: 0; }

    /* Modal */
    .sig-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
        z-index: 9999; justify-content: center; align-items: center;
        backdrop-filter: blur(2px);
    }
    .sig-modal-overlay.open { display: flex; }
    .sig-modal {
        background: var(--bg-card, #fff); border-radius: 16px; width: 100%; max-width: 520px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden; animation: slideUp 0.2s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .sig-modal-header {
        padding: 20px 24px; border-bottom: 1px solid var(--border-color, #F3F4F6);
        display: flex; align-items: center; justify-content: space-between;
    }
    .sig-modal-header h3 { font-size: 17px; font-weight: 700; color: var(--text-main, #111827); margin: 0; }
    .sig-modal-close {
        width: 32px; height: 32px; border: none; background: #F3F4F6; border-radius: 8px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: #6B7280; transition: all 0.15s;
    }
    .sig-modal-close:hover { background: #E5E7EB; color: #111827; }
    .sig-modal-body { padding: 24px; }
    .sig-modal-body .form-group { margin-bottom: 18px; }
    .sig-modal-body .form-group:last-child { margin-bottom: 0; }
    .sig-modal-body label {
        display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.5px; color: var(--text-muted, #6B7280); margin-bottom: 6px;
    }
    .sig-modal-body .f-input {
        width: 100%; padding: 10px 14px; border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 8px; font-size: 14px; font-family: inherit;
        background: var(--bg-card, #fff); color: var(--text-main, #1F2937);
        transition: border-color 0.2s, box-shadow 0.2s; outline: none; box-sizing: border-box;
    }
    .sig-modal-body .f-input:focus { border-color: #D97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.1); }
    .sig-modal-body .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .sig-modal-footer {
        padding: 16px 24px; border-top: 1px solid var(--border-color, #F3F4F6);
        display: flex; justify-content: flex-end; gap: 10px;
    }
    .sig-modal-footer .btn-cancel {
        padding: 10px 20px; border: 1px solid var(--border-color, #D1D5DB); border-radius: 8px;
        background: var(--bg-card, #fff); color: var(--text-muted, #6B7280); font-size: 13px;
        font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.15s;
    }
    .sig-modal-footer .btn-cancel:hover { background: #F3F4F6; }
    .sig-modal-footer .btn-save {
        padding: 10px 24px; border: none; border-radius: 8px;
        background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%); color: white;
        font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.15s;
    }
    .sig-modal-footer .btn-save:hover { box-shadow: 0 4px 12px rgba(217,119,6,0.3); }
</style>
@endsection

@section('scripts')
<script>
    function openSigModal(id) { document.getElementById(id).classList.add('open'); }
    function closeSigModal(id) { document.getElementById(id).classList.remove('open'); }

    // Close on overlay click
    document.querySelectorAll('.sig-modal-overlay').forEach(el => {
        el.addEventListener('click', e => { if (e.target === el) closeSigModal(el.id); });
    });

    function openEditModal(sig) {
        document.getElementById('e_satuan_kerja').value = sig.satuan_kerja || '';
        document.getElementById('e_name').value = sig.name;
        document.getElementById('e_jabatan').value = sig.jabatan;
        document.getElementById('e_pangkat').value = sig.pangkat || '';
        document.getElementById('e_nrp').value = sig.nrp || '';
        document.getElementById('e_atribut').value = sig.atribut || '';
        document.getElementById('e_wakil').value = sig.wakil || '';
        document.getElementById('editForm').action = "{{ url('admin/warehouse-items/signatories') }}/" + sig.id;
        openSigModal('editModal');
    }

    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Hapus Penanda Tangan?',
            html: `Data <strong>${name}</strong> akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then(r => {
            if (r.isConfirmed) {
                const f = document.getElementById('deleteForm');
                f.action = "{{ url('admin/warehouse-items/signatories') }}/" + id;
                f.submit();
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
