@extends('layouts.app')

@section('title', 'Identifikasi Kebutuhan')
@section('breadcrumb', 'Identifikasi Kebutuhan')

@section('content')
<style>
    .km-overlay { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; animation: kmFadeIn .2s ease; }
    .km-overlay.active { display: flex; }
    .km-modal { background: #ffffff; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 24px 80px rgba(0,0,0,.4); overflow: hidden; animation: kmSlideUp .25s ease; }
    .km-modal-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 28px; }
    .km-modal-body { padding: 28px 24px 20px; text-align: center; }
    .km-modal-title { font-size: 17px; font-weight: 700; margin: 0 0 6px; color: #1e293b; }
    .km-modal-desc { font-size: 13px; color: #475569; margin: 0; line-height: 1.5; }
    .km-modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: center; background: #f8fafc; }
    .km-modal-footer .btn { min-width: 120px; justify-content: center; font-weight: 600; }
    @keyframes kmFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes kmSlideUp { from { opacity: 0; transform: translateY(20px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Identifikasi Kebutuhan</h1>
            <p>Pengajuan kebutuhan item kapor satker Anda.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin-satker.kebutuhan.create') }}" class="btn btn-primary btn-sm">
                <i class="ri-add-line"></i> Buat Pengajuan
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-row" style="grid-template-columns: repeat(5, 1fr);">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total</span>
            <div class="stat-icon-sm" style="background: var(--info-bg); color: var(--info);"><i class="ri-file-list-3-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Draft</span>
            <div class="stat-icon-sm" style="background: var(--slate-100); color: var(--slate-600);"><i class="ri-draft-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['draft'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Diajukan</span>
            <div class="stat-icon-sm" style="background: var(--warning-bg); color: var(--warning);"><i class="ri-time-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['diajukan'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Disetujui</span>
            <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);"><i class="ri-checkbox-circle-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['disetujui'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Ditolak</span>
            <div class="stat-icon-sm" style="background: var(--danger-bg); color: var(--danger);"><i class="ri-close-circle-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['ditolak'] }}</div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div style="background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-checkbox-circle-fill"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px;">
        <i class="ri-error-warning-fill"></i> {{ session('error') }}
    </div>
@endif

{{-- Filter --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="{{ route('admin-satker.kebutuhan.index') }}" style="display: flex; gap: 12px; align-items: center;">
            <select name="status" style="padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); min-width: 140px;">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="diajukan" {{ request('status') === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line"></i> Filter</button>
            @if(request('status'))
                <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-ghost btn-sm"><i class="ri-refresh-line"></i> Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-head"><h3>Daftar Pengajuan</h3></div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Judul</th>
                    <th style="text-align: center;">Jumlah Item</th>
                    <th>Tahun</th>
                    <th style="text-align: center;">Status</th>
                    <th>Tanggal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhans as $index => $k)
                <tr>
                    <td style="text-align: center;">{{ $kebutuhans->firstItem() + $index }}</td>
                    <td>
                        <div class="cell-name">{{ $k->title }}</div>
                        @if($k->notes)
                            <div class="cell-sub">{{ Str::limit($k->notes, 50) }}</div>
                        @endif
                    </td>
                    <td style="text-align: center;"><span class="badge badge-neutral">{{ $k->items->count() }} item</span></td>
                    <td>{{ $k->fiscal_year }}</td>
                    <td style="text-align: center;"><span class="badge {{ $k->status_badge }}">{{ $k->status_label }}</span></td>
                    <td>{{ $k->created_at->format('d/m/Y') }}</td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center;">
                            <a href="{{ route('admin-satker.kebutuhan.show', $k) }}" class="btn btn-outline btn-xs" title="Lihat Detail"><i class="ri-eye-line"></i></a>
                            @if($k->isDraft())
                                <a href="{{ route('admin-satker.kebutuhan.edit', $k) }}" class="btn btn-outline btn-xs" title="Edit"><i class="ri-edit-line"></i></a>
                                <button type="button" class="btn btn-outline btn-xs" style="color: var(--danger);" title="Hapus"
                                    onclick="openDeleteModal({{ $k->id }}, '{{ addslashes($k->title) }}')">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="ri-file-list-3-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Belum ada pengajuan. <a href="{{ route('admin-satker.kebutuhan.create') }}" style="color: var(--brand); text-decoration: none; font-weight: 600;">Buat pengajuan baru →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($kebutuhans->hasPages())
<div style="display: flex; justify-content: center; margin-top: 16px;">
    {{ $kebutuhans->links('pagination::simple-default') }}
</div>
@endif

{{-- DELETE MODAL --}}
<div class="km-overlay" id="deleteModal">
    <div class="km-modal">
        <div class="km-modal-body">
            <div class="km-modal-icon" style="background: #fee2e2; color: #dc2626;">
                <i class="ri-delete-bin-line"></i>
            </div>
            <h3 class="km-modal-title">Hapus Pengajuan?</h3>
            <p class="km-modal-desc" id="deleteDesc"></p>
        </div>
        <div class="km-modal-footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('deleteModal')">Batal</button>
            <form method="POST" id="deleteForm" style="margin: 0;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="background: #dc2626; color: #fff; border: none;">
                    <i class="ri-delete-bin-line"></i> Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openDeleteModal(id, title) {
    document.getElementById('deleteForm').action = `/admin-satker/kebutuhan/${id}`;
    document.getElementById('deleteDesc').innerHTML = `Anda akan menghapus pengajuan "<strong>${title}</strong>". Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

document.querySelectorAll('.km-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.querySelectorAll('.km-overlay.active').forEach(m => m.classList.remove('active'));
});
</script>
@endsection
