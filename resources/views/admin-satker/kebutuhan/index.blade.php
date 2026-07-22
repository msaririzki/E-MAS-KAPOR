@extends('layouts.app')

@section('title', 'Identifikasi Kebutuhan')
@section('breadcrumb', 'Identifikasi Kebutuhan')

@section('content')

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Identifikasi Kebutuhan</h1>
            <p>Pengajuan kebutuhan item kapor satker Anda.</p>
        </div>
        <div class="page-header-actions">
            @if($hasSubmissionThisYear)
                <span class="btn btn-ghost btn-sm" style="cursor: default; opacity: 0.6;" title="Sudah ada pengajuan untuk TA {{ $nextFiscalYear }}">
                    <i class="ri-lock-line"></i> Pengajuan TA {{ $nextFiscalYear }} Sudah Dibuat
                </span>
            @else
                <a href="{{ route('admin-satker.kebutuhan.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri-add-line"></i> Buat Pengajuan
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-row" style="grid-template-columns: repeat(2, 1fr);">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Pengajuan</span>
            <div class="stat-icon-sm" style="background: var(--info-bg); color: var(--info);"><i class="ri-file-list-3-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Diajukan</span>
            <div class="stat-icon-sm" style="background: var(--success-bg); color: var(--success);"><i class="ri-checkbox-circle-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['diajukan'] + ($stats['disetujui'] ?? 0) }}</div>
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

{{-- Table --}}
<div class="card">
    <div class="card-head"><h3>Daftar Pengajuan</h3></div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th style="text-align: center;">Jumlah Item</th>
                    <th>Tahun</th>
                    <th>Tanggal Pengajuan</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhans as $index => $k)
                <tr class="kebutuhan-row" data-url="{{ route('admin-satker.kebutuhan.show', $k) }}"
                    onclick="if (!event.target.closest('.kebutuhan-actions')) window.location.href = this.dataset.url;"
                    tabindex="0" role="link" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.url; }">
                    <td style="text-align: center;">{{ $kebutuhans->firstItem() + $index }}</td>
                    <td style="text-align: center;"><span class="badge badge-neutral">{{ $k->items->count() }} item</span></td>
                    <td>{{ $k->fiscal_year }}</td>
                    <td style="font-size: 12px;">{{ $k->submitted_at ? $k->submitted_at->format('d/m/Y H:i') : $k->created_at->format('d/m/Y H:i') }}</td>
                    <td style="text-align: center;">
                        <div class="kebutuhan-actions" style="display: flex; gap: 4px; justify-content: center;">
                            <a href="{{ route('admin-satker.kebutuhan.show', $k) }}" class="btn btn-outline btn-xs" title="Lihat Detail" onclick="event.stopPropagation();"><i class="ri-eye-line"></i></a>
                            <a href="{{ route('admin-satker.kebutuhan.print', $k) }}" target="_blank" class="btn btn-outline btn-xs" title="Cetak PDF" onclick="event.stopPropagation();"><i class="ri-printer-line"></i></a>
                            <button type="button" class="btn btn-outline btn-xs" style="color: var(--danger);" title="Hapus"
                                onclick="event.stopPropagation(); openDeleteModal({{ $k->id }}, '{{ addslashes($k->title) }}')">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="ri-file-list-3-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        @if($hasSubmissionThisYear)
                            Tidak ada pengajuan yang ditampilkan.
                        @else
                            Belum ada pengajuan. <a href="{{ route('admin-satker.kebutuhan.create') }}" style="color: var(--brand); text-decoration: none; font-weight: 600;">Buat pengajuan baru →</a>
                        @endif
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

@section('styles')
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
<script>
    document.querySelectorAll('.kebutuhan-row').forEach((row) => {
        row.style.cursor = 'pointer';
        row.addEventListener('mouseenter', () => row.style.background = 'rgba(59, 130, 246, 0.04)');
        row.addEventListener('mouseleave', () => row.style.background = '');
    });
</script>
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
