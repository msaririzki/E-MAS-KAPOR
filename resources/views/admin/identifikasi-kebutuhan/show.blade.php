@extends('layouts.app')

@section('title', 'Detail Kebutuhan')
@section('breadcrumb', 'Detail Kebutuhan')

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
            <h1>Detail Pengajuan Kebutuhan</h1>
            <p>{{ $kebutuhan->title }}</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin.identifikasi-kebutuhan.index') }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Kembali</a>
            @if($kebutuhan->isDiajukan() && auth()->user()->hasAnyRole(['admin', 'superadmin']))
                <button type="button" class="btn btn-sm" style="background: #059669; color: #fff; border: none;" onclick="document.getElementById('approveModal').classList.add('active')">
                    <i class="ri-checkbox-circle-line"></i> Setujui
                </button>
                <button type="button" class="btn btn-sm" style="background: #dc2626; color: #fff; border: none;" onclick="document.getElementById('rejectModal').classList.add('active')">
                    <i class="ri-close-circle-line"></i> Tolak
                </button>
            @endif
        </div>
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
@if($errors->any())
    <div style="background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
        <ul style="margin: 0; padding-left: 16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Status Banners --}}
@if($kebutuhan->isDiajukan() && auth()->user()->hasAnyRole(['admin', 'superadmin']))
<div style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border-left: 4px solid #f59e0b; border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.05);">
    <i class="ri-information-line" style="font-size: 24px; color: #d97706; margin-top: 2px;"></i>
    <div>
        <h3 style="margin: 0 0 4px; font-size: 15px; color: #92400e; font-weight: 700;">Pengajuan Ini Menunggu Review Anda</h3>
        <p style="margin: 0; font-size: 13px; color: #a16207; line-height: 1.5;">
            Pengajuan dikirim pada <strong>{{ $kebutuhan->submitted_at ? $kebutuhan->submitted_at->format('d M Y, H:i') : '-' }}</strong> oleh <strong>{{ $kebutuhan->user->name ?? '-' }}</strong>. Silakan periksa detail kebutuhan di bawah sebelum memberikan persetujuan dengan menggunakan tombol aksi di sudut kanan atas.
        </p>
    </div>
</div>
@elseif($kebutuhan->isDisetujui())
<div style="background: var(--success-bg); border: 1px solid var(--success-border); border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
    <i class="ri-checkbox-circle-fill" style="font-size: 24px; color: var(--success);"></i>
    <div>
        <strong style="color: var(--success); font-size: 14px;">Pengajuan Disetujui</strong>
        @if($kebutuhan->reviewed_at)
            <p style="margin: 0; font-size: 12px; color: var(--text-muted);">Disetujui pada {{ $kebutuhan->reviewed_at->format('d M Y, H:i') }} oleh {{ $kebutuhan->reviewer->name ?? '-' }}</p>
        @endif
        @if($kebutuhan->admin_notes)
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--success);">"{{ $kebutuhan->admin_notes }}"</p>
        @endif
    </div>
</div>
@elseif($kebutuhan->isDitolak())
<div style="background: var(--danger-bg); border: 1px solid var(--danger-border); border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
    <i class="ri-close-circle-fill" style="font-size: 24px; color: var(--danger);"></i>
    <div>
        <strong style="color: var(--danger); font-size: 14px;">Pengajuan Ditolak</strong>
        @if($kebutuhan->reviewed_at)
            <p style="margin: 0; font-size: 12px; color: var(--text-muted);">Ditolak pada {{ $kebutuhan->reviewed_at->format('d M Y, H:i') }} oleh {{ $kebutuhan->reviewer->name ?? '-' }}</p>
        @endif
        @if($kebutuhan->admin_notes)
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--danger);">Alasan: "{{ $kebutuhan->admin_notes }}"</p>
        @endif
    </div>
</div>
@elseif($kebutuhan->isDraft())
<div style="background: var(--slate-50); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
    <i class="ri-draft-line" style="font-size: 24px; color: var(--text-muted);"></i>
    <div>
        <strong style="color: var(--text-muted); font-size: 14px;">Draft</strong>
        <p style="margin: 0; font-size: 12px; color: var(--text-muted);">Pengajuan ini masih dalam status draft dan belum dikirim oleh Admin Satker.</p>
    </div>
</div>
@endif

{{-- Info Card --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-head"><h3>Informasi Pengajuan</h3></div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Satker</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->satker->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Pengaju</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->user->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Tahun Anggaran</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->fiscal_year }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Status</div>
                <span class="badge {{ $kebutuhan->status_badge }}">{{ $kebutuhan->status_label }}</span>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Dibuat</div>
                <div style="font-size: 13px;">{{ $kebutuhan->created_at->format('d M Y, H:i') }}</div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px;">Total Item</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $kebutuhan->items->count() }} item ({{ $kebutuhan->items->sum('quantity') }} unit)</div>
            </div>
        </div>
        @if($kebutuhan->notes)
            <div style="margin-top: 16px; padding: 12px 16px; background: var(--slate-50); border-radius: var(--radius-sm); font-size: 13px; border-left: 3px solid var(--info);">
                <strong style="color: var(--text-main);">Catatan Pengaju:</strong>
                <p style="margin: 4px 0 0; color: var(--text-muted);">{{ $kebutuhan->notes }}</p>
            </div>
        @endif
    </div>
</div>

{{-- Items Table --}}
<div class="card">
    <div class="card-head">
        <h3>Daftar Item Kebutuhan ({{ $kebutuhan->items->count() }} item)</h3>
    </div>
    <div class="card-body flush table-wrap">
        <table>
            <thead>
                <tr>
                    <th width="50" style="text-align: center;">No</th>
                    <th>Nama Item</th>
                    <th>Kategori</th>
                    <th style="text-align: center;">Jumlah</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kebutuhan->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        <div class="cell-name">{{ $item->kaporItem->item_name ?? '-' }}</div>
                        @if($item->kaporItem && $item->kaporItem->description)
                            <div class="cell-sub">{{ Str::limit($item->kaporItem->description, 40) }}</div>
                        @endif
                    </td>
                    <td><span class="badge badge-neutral">{{ str_replace('_', ' ', $item->kaporItem->category ?? '-') }}</span></td>
                    <td style="text-align: center; font-weight: 700; font-size: 14px;">{{ $item->quantity }}</td>
                    <td style="color: var(--text-muted); font-size: 12px;">{{ $item->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
            @if($kebutuhan->items->count() > 0)
            <tfoot>
                <tr style="background: var(--slate-50); font-weight: 600;">
                    <td colspan="3" style="text-align: right; padding: 10px 12px; font-size: 13px;">Total Jumlah:</td>
                    <td style="text-align: center; font-size: 14px; color: var(--brand);">{{ $kebutuhan->items->sum('quantity') }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- APPROVE MODAL --}}
@if($kebutuhan->isDiajukan() && auth()->user()->hasAnyRole(['admin', 'superadmin']))
<div class="km-overlay" id="approveModal">
    <div class="km-modal">
        <form method="POST" action="{{ route('admin.identifikasi-kebutuhan.approve', $kebutuhan) }}" id="approveForm">
            @csrf
            <div class="km-modal-body">
                <div class="km-modal-icon" style="background: #d1fae5; color: #059669;">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <h3 class="km-modal-title">Setujui Pengajuan?</h3>
                <p class="km-modal-desc">Anda akan menyetujui pengajuan "<strong>{{ $kebutuhan->title }}</strong>" dari {{ $kebutuhan->satker->name ?? '-' }}. Tindakan ini tidak dapat dibatalkan.</p>
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

<div class="km-overlay" id="rejectModal">
    <div class="km-modal">
        <form method="POST" action="{{ route('admin.identifikasi-kebutuhan.reject', $kebutuhan) }}" id="rejectForm">
            @csrf
            <div class="km-modal-body">
                <div class="km-modal-icon" style="background: #fee2e2; color: #dc2626;">
                    <i class="ri-close-circle-line"></i>
                </div>
                <h3 class="km-modal-title">Tolak Pengajuan?</h3>
                <p class="km-modal-desc">Anda akan menolak pengajuan "<strong>{{ $kebutuhan->title }}</strong>" dari {{ $kebutuhan->satker->name ?? '-' }}. Harap berikan alasan penolakan.</p>
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
@endif
@endsection

@section('scripts')
<script>
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.km-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.querySelectorAll('.km-overlay.active').forEach(m => m.classList.remove('active'));
});
</script>
@endsection
