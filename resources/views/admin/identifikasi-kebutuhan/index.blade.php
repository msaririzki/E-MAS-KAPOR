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
    .km-modal-desc { font-size: 13px; color: #475569; margin: 0 0 20px; line-height: 1.5; }
    .km-modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: center; background: #f8fafc; }
    .km-modal-footer .btn { min-width: 120px; justify-content: center; font-weight: 600; }
    .km-modal textarea { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; font-family: inherit; background: #ffffff; color: #1e293b; resize: vertical; min-height: 60px; transition: border-color .15s; }
    .km-modal textarea:focus { outline: none; border-color: #c62828; box-shadow: 0 0 0 3px rgba(198,40,40,.08); }
    @keyframes kmFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes kmSlideUp { from { opacity: 0; transform: translateY(20px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Identifikasi Kebutuhan</h1>
            <p>Kelola pengajuan kebutuhan kapor dari seluruh satker.</p>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Pengajuan</span>
            <div class="stat-icon-sm" style="background: var(--info-bg); color: var(--info);"><i class="ri-file-list-3-line"></i></div>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card" style="{{ $stats['diajukan'] > 0 ? 'border-left: 3px solid var(--warning);' : '' }}">
        <div class="stat-top">
            <span class="stat-label">Menunggu Review</span>
            <div class="stat-icon-sm" style="background: var(--warning-bg); color: var(--warning);"><i class="ri-time-line"></i></div>
        </div>
        <div class="stat-value" style="{{ $stats['diajukan'] > 0 ? 'color: var(--warning);' : '' }}">{{ $stats['diajukan'] }}</div>
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

{{-- Filters --}}
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="{{ route('admin.identifikasi-kebutuhan.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul / satker..." class="search-input" style="width: 100%;">
            </div>
            <select name="satker_id" style="padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); min-width: 160px;">
                <option value="">Semua Satker</option>
                @foreach($satkers as $satker)
                    <option value="{{ $satker->id }}" {{ request('satker_id') == $satker->id ? 'selected' : '' }}>{{ $satker->name }}</option>
                @endforeach
            </select>
            <select name="status" style="padding: 7px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 13px; font-family: inherit; background: var(--input-bg); color: var(--text-main); min-width: 130px;">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="diajukan" {{ request('status') === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri-search-line"></i> Filter</button>
            @if(request()->hasAny(['search', 'satker_id', 'status']))
                <a href="{{ route('admin.identifikasi-kebutuhan.index') }}" class="btn btn-ghost btn-sm"><i class="ri-refresh-line"></i> Reset</a>
            @endif
        </form>
    </div>
</div>

{{-- Pending Review Banner --}}
@if($stats['diajukan'] > 0 && !request('status'))
<div style="background: linear-gradient(135deg, #fff7ed, #fef3c7); border: 1px solid #f59e0b; padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; align-items: center; gap: 12px; font-size: 13px;">
    <i class="ri-notification-3-line" style="font-size: 20px; color: #f59e0b;"></i>
    <div>
        <strong style="color: #92400e;">{{ $stats['diajukan'] }} pengajuan menunggu review Anda.</strong>
        <a href="{{ route('admin.identifikasi-kebutuhan.index', ['status' => 'diajukan']) }}" style="color: #b45309; margin-left: 8px;">Lihat semua →</a>
    </div>
</div>
@endif

{{-- Table --}}
<div class="card">
    <div class="card-head">
        <h3>Daftar Pengajuan Kebutuhan</h3>
    </div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Judul Pengajuan</th>
                    <th>Satker</th>
                    <th>Pengaju</th>
                    <th style="text-align: center;">Item</th>
                    <th style="text-align: center;">Status</th>
                    <th>Tanggal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhans as $index => $k)
                <tr style="{{ $k->isDiajukan() ? 'background: #fffbeb;' : '' }}">
                    <td style="text-align: center;">{{ $kebutuhans->firstItem() + $index }}</td>
                    <td>
                        <a href="{{ route('admin.identifikasi-kebutuhan.show', $k) }}" style="text-decoration: none; color: inherit;">
                            <div class="cell-name" style="color: var(--brand);">{{ $k->title }}</div>
                        </a>
                        @if($k->notes)
                            <div class="cell-sub">{{ Str::limit($k->notes, 50) }}</div>
                        @endif
                    </td>
                    <td><span style="font-size: 12px;">{{ $k->satker->name ?? '-' }}</span></td>
                    <td><span style="font-size: 12px;">{{ $k->user->name ?? '-' }}</span></td>
                    <td style="text-align: center;">
                        <span class="badge badge-neutral">{{ $k->items->count() }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $k->status_badge }}">{{ $k->status_label }}</span>
                    </td>
                    <td style="font-size: 12px;">{{ $k->created_at->format('d/m/Y') }}</td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                            <a href="{{ route('admin.identifikasi-kebutuhan.show', $k) }}" class="btn btn-outline btn-xs" title="Lihat Detail">
                                <i class="ri-eye-line"></i>
                            </a>
                            @if($k->isDiajukan() && auth()->user()->hasAnyRole(['admin', 'superadmin']))
                                <button type="button" class="btn btn-xs" style="background: var(--success); color: #fff; border: none;" title="Setujui"
                                    onclick="openApproveModal({{ $k->id }}, '{{ addslashes($k->title) }}')">
                                    <i class="ri-check-line"></i>
                                </button>
                                <button type="button" class="btn btn-xs" style="background: var(--danger); color: #fff; border: none;" title="Tolak"
                                    onclick="openRejectModal({{ $k->id }}, '{{ addslashes($k->title) }}')">
                                    <i class="ri-close-line"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <i class="ri-file-list-3-line" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                        Belum ada pengajuan kebutuhan.
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

{{-- APPROVE MODAL --}}
<div class="km-overlay" id="approveModal">
    <div class="km-modal">
        <form method="POST" id="approveForm">
            @csrf
            <div class="km-modal-body">
                <div class="km-modal-icon" style="background: #d1fae5; color: #059669;">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <h3 class="km-modal-title">Setujui Pengajuan?</h3>
                <p class="km-modal-desc" id="approveDesc"></p>
                <textarea name="admin_notes" placeholder="Catatan persetujuan (opsional)..."></textarea>
            </div>
            <div class="km-modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('approveModal')">Batal</button>
                <button type="submit" class="btn btn-sm" style="background: #059669; color: #fff; border: none;">
                    <i class="ri-checkbox-circle-line"></i> Ya, Setujui
                </button>
            </div>
        </form>
    </div>
</div>

{{-- REJECT MODAL --}}
<div class="km-overlay" id="rejectModal">
    <div class="km-modal">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="km-modal-body">
                <div class="km-modal-icon" style="background: #fee2e2; color: #dc2626;">
                    <i class="ri-close-circle-line"></i>
                </div>
                <h3 class="km-modal-title">Tolak Pengajuan?</h3>
                <p class="km-modal-desc" id="rejectDesc"></p>
                <textarea name="admin_notes" placeholder="Alasan penolakan (wajib diisi)..." required></textarea>
            </div>
            <div class="km-modal-footer">
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('rejectModal')">Batal</button>
                <button type="submit" class="btn btn-sm" style="background: #dc2626; color: #fff; border: none;">
                    <i class="ri-close-circle-line"></i> Ya, Tolak
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openApproveModal(id, title) {
    document.getElementById('approveForm').action = `/admin/identifikasi-kebutuhan/${id}/approve`;
    document.getElementById('approveDesc').textContent = `Anda akan menyetujui pengajuan "${title}". Tindakan ini tidak dapat dibatalkan.`;
    document.getElementById('approveModal').classList.add('active');
}

function openRejectModal(id, title) {
    document.getElementById('rejectForm').action = `/admin/identifikasi-kebutuhan/${id}/reject`;
    document.getElementById('rejectDesc').textContent = `Anda akan menolak pengajuan "${title}". Harap berikan alasan penolakan.`;
    document.getElementById('rejectModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close on backdrop click
document.querySelectorAll('.km-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.km-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>
@endsection
