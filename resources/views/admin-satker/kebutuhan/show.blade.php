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
    .km-modal-desc { font-size: 13px; color: #475569; margin: 0 0 4px; line-height: 1.5; }
    .km-modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: center; background: #f8fafc; }
    .km-modal-footer .btn { min-width: 120px; justify-content: center; font-weight: 600; }
    @keyframes kmFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes kmSlideUp { from { opacity: 0; transform: translateY(20px) scale(.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Detail Pengajuan</h1>
            <p>{{ $kebutuhan->title }}</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('admin-satker.kebutuhan.index') }}" class="btn btn-outline btn-sm"><i class="ri-arrow-left-line"></i> Kembali</a>
            @if($kebutuhan->isDraft())
                <a href="{{ route('admin-satker.kebutuhan.edit', $kebutuhan) }}" class="btn btn-outline btn-sm"><i class="ri-edit-line"></i> Edit</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('submitModal').classList.add('active')">
                    <i class="ri-send-plane-line"></i> Kirim Pengajuan
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

{{-- Timeline / Status --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-head"><h3>Status Pengajuan</h3></div>
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 0; justify-content: center; flex-wrap: wrap;">
            {{-- Step 1: Draft --}}
            <div style="text-align: center; flex: 0 0 auto;">
                <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;
                    background: var(--success-bg); color: var(--success); border: 2px solid var(--success);">
                    <i class="ri-check-line" style="font-size: 18px;"></i>
                </div>
                <div style="font-size: 11px; font-weight: 600;">Draft</div>
                <div style="font-size: 10px; color: var(--text-muted);">{{ $kebutuhan->created_at->format('d/m/Y') }}</div>
            </div>

            <div style="flex: 1; max-width: 80px; height: 2px; background: {{ $kebutuhan->submitted_at ? 'var(--success)' : 'var(--slate-200)' }};"></div>

            {{-- Step 2: Diajukan --}}
            <div style="text-align: center; flex: 0 0 auto;">
                <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;
                    @if($kebutuhan->submitted_at) background: var(--success-bg); color: var(--success); border: 2px solid var(--success);
                    @else background: var(--slate-50); color: var(--slate-400); border: 2px solid var(--slate-200); @endif">
                    <i class="{{ $kebutuhan->submitted_at ? 'ri-check-line' : 'ri-time-line' }}" style="font-size: 18px;"></i>
                </div>
                <div style="font-size: 11px; font-weight: 600;">Diajukan</div>
                <div style="font-size: 10px; color: var(--text-muted);">{{ $kebutuhan->submitted_at ? $kebutuhan->submitted_at->format('d/m/Y') : '-' }}</div>
            </div>

            <div style="flex: 1; max-width: 80px; height: 2px; background: {{ $kebutuhan->reviewed_at ? ($kebutuhan->isDisetujui() ? 'var(--success)' : 'var(--danger)') : 'var(--slate-200)' }};"></div>

            {{-- Step 3: Reviewed --}}
            <div style="text-align: center; flex: 0 0 auto;">
                <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;
                    @if($kebutuhan->isDisetujui()) background: var(--success-bg); color: var(--success); border: 2px solid var(--success);
                    @elseif($kebutuhan->isDitolak()) background: var(--danger-bg); color: var(--danger); border: 2px solid var(--danger);
                    @else background: var(--slate-50); color: var(--slate-400); border: 2px solid var(--slate-200); @endif">
                    <i class="{{ $kebutuhan->isDisetujui() ? 'ri-check-double-line' : ($kebutuhan->isDitolak() ? 'ri-close-line' : 'ri-more-line') }}" style="font-size: 18px;"></i>
                </div>
                <div style="font-size: 11px; font-weight: 600;">
                    {{ $kebutuhan->isDisetujui() ? 'Disetujui' : ($kebutuhan->isDitolak() ? 'Ditolak' : 'Menunggu Review') }}
                </div>
                <div style="font-size: 10px; color: var(--text-muted);">{{ $kebutuhan->reviewed_at ? $kebutuhan->reviewed_at->format('d/m/Y') : '-' }}</div>
            </div>
        </div>
</div>

{{-- Draft Action Banner --}}
@if($kebutuhan->isDraft())
<div style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #3b82f6; border-radius: var(--radius-sm); padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1); display: flex; align-items: flex-start; gap: 16px;">
    <div style="width: 48px; height: 48px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
        <i class="ri-information-line"></i>
    </div>
    <div style="flex: 1;">
        <h3 style="margin: 0 0 6px; font-size: 17px; color: #1e3a8a; font-weight: 700;">Pengajuan Belum Dikirim!</h3>
        <p style="margin: 0 0 16px; font-size: 14px; color: #1e40af; line-height: 1.5;">
            Pengajuan ini masih berstatus <strong>Draft</strong> dan belum masuk ke Admin Polda. Periksa kembali detail item di bawah, lalu klik tombol <strong>Kirim Pengajuan Sekarang</strong> jika data sudah benar.
        </p>
        <button type="button" class="btn btn-sm" style="background: #2563eb; color: #fff; border: none; font-weight: 600; padding: 10px 20px; font-size: 14px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);" onclick="document.getElementById('submitModal').classList.add('active')">
            <i class="ri-send-plane-line" style="margin-right: 6px;"></i> Kirim Pengajuan Sekarang
        </button>
    </div>
</div>
@endif

<div class="grid-2">
    {{-- Info --}}
    <div class="card">
        <div class="card-head"><h3>Informasi</h3></div>
        <div class="card-body">
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; color: var(--text-muted); width: 130px; font-size: 13px;">Judul</td>
                    <td style="padding: 8px 0; font-weight: 600; font-size: 13px;">{{ $kebutuhan->title }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--text-muted); font-size: 13px;">Tahun Anggaran</td>
                    <td style="padding: 8px 0; font-size: 13px;">{{ $kebutuhan->fiscal_year }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--text-muted); font-size: 13px;">Status</td>
                    <td style="padding: 8px 0;"><span class="badge {{ $kebutuhan->status_badge }}">{{ $kebutuhan->status_label }}</span></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--text-muted); font-size: 13px;">Total Item</td>
                    <td style="padding: 8px 0; font-size: 13px;">{{ $kebutuhan->items->count() }} item</td>
                </tr>
            </table>
            @if($kebutuhan->notes)
                <div style="margin-top: 12px; padding: 12px; background: var(--info-bg); border: 1px solid var(--info-border); border-radius: var(--radius-sm); font-size: 13px;">
                    <strong style="color: var(--info);">Catatan:</strong> {{ $kebutuhan->notes }}
                </div>
            @endif
        </div>
    </div>

    {{-- Admin Notes --}}
    <div class="card">
        <div class="card-head"><h3>Respon Admin</h3></div>
        <div class="card-body">
            @if($kebutuhan->admin_notes)
                <div style="padding: 12px; background: {{ $kebutuhan->isDisetujui() ? 'var(--success-bg)' : 'var(--danger-bg)' }}; border: 1px solid {{ $kebutuhan->isDisetujui() ? 'var(--success-border)' : 'var(--danger-border)' }}; border-radius: var(--radius-sm); font-size: 13px;">
                    <strong style="color: {{ $kebutuhan->isDisetujui() ? 'var(--success)' : 'var(--danger)' }};">
                        {{ $kebutuhan->isDisetujui() ? 'Catatan Persetujuan:' : 'Alasan Penolakan:' }}
                    </strong><br>
                    {{ $kebutuhan->admin_notes }}
                </div>
                @if($kebutuhan->reviewer)
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                        Direview oleh <strong>{{ $kebutuhan->reviewer->name }}</strong> pada {{ $kebutuhan->reviewed_at->format('d M Y, H:i') }}
                    </p>
                @endif
            @else
                <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                    <i class="ri-chat-3-line" style="font-size: 28px; display: block; margin-bottom: 6px; opacity: 0.5;"></i>
                    <p style="font-size: 13px;">Belum ada respon dari admin.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Items --}}
<div class="card">
    <div class="card-head"><h3>Item Kebutuhan ({{ $kebutuhan->items->count() }})</h3></div>
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
                @foreach($kebutuhan->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><div class="cell-name">{{ $item->kaporItem->item_name ?? '-' }}</div></td>
                    <td><span class="badge badge-neutral">{{ $item->kaporItem->category ?? '-' }}</span></td>
                    <td style="text-align: center; font-weight: 600;">{{ $item->quantity }}</td>
                    <td style="color: var(--text-muted); font-size: 12px;">{{ $item->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- SUBMIT MODAL --}}
@if($kebutuhan->isDraft())
<div class="km-overlay" id="submitModal">
    <div class="km-modal">
        <div class="km-modal-body">
            <div class="km-modal-icon" style="background: #dbeafe; color: #2563eb;">
                <i class="ri-send-plane-line"></i>
            </div>
            <h3 class="km-modal-title">Kirim Pengajuan?</h3>
            <p class="km-modal-desc">Anda akan mengirim pengajuan "<strong>{{ $kebutuhan->title }}</strong>" untuk direview oleh Admin.</p>
            <p style="font-size: 12px; color: #dc2626; margin: 8px 0 0;">
                <i class="ri-error-warning-line"></i> Setelah dikirim, pengajuan tidak dapat diedit lagi.
            </p>
        </div>
        <div class="km-modal-footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('submitModal')">Batal</button>
            <form method="POST" action="{{ route('admin-satker.kebutuhan.submit', $kebutuhan) }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-sm" style="background: #2563eb; color: #fff; border: none;">
                    <i class="ri-send-plane-line"></i> Ya, Kirim
                </button>
            </form>
        </div>
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
    overlay.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.querySelectorAll('.km-overlay.active').forEach(m => m.classList.remove('active'));
});
</script>
@endsection
