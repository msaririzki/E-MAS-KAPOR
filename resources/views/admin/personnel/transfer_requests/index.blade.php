@extends('layouts.app')

@section('title', 'Pemeriksaan Mutasi Personel')
@section('breadcrumb', 'Pemeriksaan Mutasi')

@section('content')
<div class="transfer-header">
    <div class="transfer-title">
        <a href="{{ route('admin.personnel.index') }}" class="transfer-back" title="Kembali"><i class="ri-arrow-left-line"></i></a>
        <div><span>PEMERIKSAAN SUPERADMIN</span><h1>Mutasi Personel</h1><p>Periksa NRP/NIP yang diajukan oleh satker lain.</p></div>
    </div>
</div>

@if(session('success'))<div class="transfer-alert success"><i class="ri-checkbox-circle-fill"></i>{{ session('success') }}</div>@endif
@if(session('warning'))<div class="transfer-alert warning"><i class="ri-alert-fill"></i>{{ session('warning') }}</div>@endif

<div class="transfer-tabs">
    @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $key => $label)
        <a href="{{ route('admin.personnel.transfer-requests.index', ['status' => $key]) }}" class="{{ $status === $key ? 'active' : '' }}">
            {{ $label }} <strong>{{ number_format($counts[$key] ?? 0) }}</strong>
        </a>
    @endforeach
</div>

<form action="{{ route('admin.personnel.transfer-requests.review') }}" method="POST" id="transferReviewForm">
    @csrf
    @if($status === 'pending')
        <div class="transfer-toolbar">
            <label><input type="checkbox" id="selectAllTransfers"> Pilih semua halaman</label>
            <div>
                <input type="text" name="review_note" placeholder="Catatan pemeriksaan (opsional)">
                <button type="submit" name="action" value="reject" class="reject"><i class="ri-close-line"></i>Tolak</button>
                <button type="submit" name="action" value="approve" class="approve"><i class="ri-check-line"></i>Setujui</button>
            </div>
        </div>
    @endif

    <div class="transfer-table-shell">
        <div class="transfer-scroll">
            <table>
                <thead><tr>@if($status === 'pending')<th></th>@endif<th>PERSONEL</th><th>SATKER SAAT INI</th><th>SATKER TUJUAN</th><th>DATA DARI FILE</th><th>PENGAJUAN</th><th>STATUS</th></tr></thead>
                <tbody>
                    @forelse($requests as $transfer)
                        <tr>
                            @if($status === 'pending')<td><input type="checkbox" name="request_ids[]" value="{{ $transfer->id }}" class="transfer-checkbox"></td>@endif
                            <td><div class="transfer-person"><strong>{{ $transfer->personnel?->full_name }}</strong><code>{{ $transfer->personnel?->nrp }}</code><small>{{ $transfer->personnel?->rank?->name }}</small></div></td>
                            <td><span class="satker-label from">{{ $transfer->fromSatker?->name }}</span></td>
                            <td><span class="satker-label to">{{ $transfer->toSatker?->name }}</span></td>
                            <td><div class="payload-cell"><strong>{{ $transfer->payload['jabatan'] ?: '-' }}</strong><span>{{ $transfer->payload['bagian'] ?: 'Bag/Fungsi kosong' }}</span></div></td>
                            <td><div class="payload-cell"><strong>{{ $transfer->requester?->name }}</strong><span>{{ $transfer->created_at?->format('d M Y H:i') }}</span><small>{{ $transfer->source_file }} &middot; baris {{ $transfer->source_row }}</small></div></td>
                            <td><span class="transfer-status {{ $transfer->status }}">{{ match($transfer->status){'pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak',default=>$transfer->status} }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $status === 'pending' ? 7 : 6 }}"><div class="transfer-empty"><i class="ri-inbox-2-line"></i><strong>Tidak ada permintaan pada status ini</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())<div class="transfer-pagination">{{ $requests->links() }}</div>@endif
    </div>
</form>
@endsection

@section('styles')
<style>
.transfer-header{padding:18px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:16px}.transfer-title{display:flex;align-items:center;gap:14px}.transfer-title span{font-size:10px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.transfer-title h1{font-size:24px;margin:2px 0 4px;color:#111827;font-weight:800;letter-spacing:0}.transfer-title p{font-size:13px;margin:0;color:#64748B}.transfer-back{width:38px;height:38px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;color:#374151;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.transfer-alert{border:1px solid;border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:center;gap:8px;font-size:12px}.transfer-alert.success{background:#F0FDF4;border-color:#BBF7D0;color:#166534}.transfer-alert.warning{background:#FFF7ED;border-color:#FED7AA;color:#9A3412}.transfer-tabs{display:flex;border-bottom:1px solid #E5E7EB;margin-bottom:14px}.transfer-tabs a{height:42px;padding:0 16px;color:#64748B;text-decoration:none;display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:700;border-bottom:2px solid transparent}.transfer-tabs a.active{color:#B91C1C;border-bottom-color:#B91C1C}.transfer-tabs strong{min-width:21px;height:21px;border-radius:999px;background:#F1F5F9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:9px}.transfer-toolbar{padding:11px 14px;border:1px solid #E5E7EB;border-radius:8px 8px 0 0;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between}.transfer-toolbar>label{font-size:11px;color:#475569;font-weight:700}.transfer-toolbar>div{display:flex;align-items:center;gap:7px}.transfer-toolbar input[type=text]{height:36px;width:270px;border:1px solid #CBD5E1;border-radius:7px;padding:0 10px;font-size:11px}.transfer-toolbar button{height:36px;border-radius:7px;border:1px solid;display:inline-flex;align-items:center;gap:5px;padding:0 11px;font-size:11px;font-weight:800;cursor:pointer}.transfer-toolbar button.reject{background:#fff;border-color:#FCA5A5;color:#B91C1C}.transfer-toolbar button.approve{background:#047857;border-color:#047857;color:#fff}.transfer-table-shell{border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden}.transfer-toolbar+.transfer-table-shell{border-top:0;border-radius:0 0 8px 8px}.transfer-scroll{overflow:auto}.transfer-table-shell table{width:100%;min-width:1100px;border-collapse:collapse}.transfer-table-shell th{height:40px;padding:0 11px;background:#F8FAFC;border-bottom:1px solid #E5E7EB;text-align:left;font-size:9px;color:#64748B}.transfer-table-shell td{padding:12px 11px;border-bottom:1px solid #F1F5F9;vertical-align:top;font-size:11px;color:#334155}.transfer-person,.payload-cell{display:flex;flex-direction:column;gap:3px}.transfer-person strong,.payload-cell strong{font-size:11px;color:#111827}.transfer-person code{font-size:10px;background:transparent;padding:0;color:#475569}.transfer-person small,.payload-cell span,.payload-cell small{font-size:9px;color:#64748B}.satker-label{display:inline-flex;padding:4px 7px;border-radius:5px;font-size:9px;font-weight:800}.satker-label.from{background:#F1F5F9;color:#475569}.satker-label.to{background:#ECFDF5;color:#047857}.transfer-status{display:inline-flex;padding:4px 7px;border-radius:999px;font-size:9px;font-weight:800}.transfer-status.pending{background:#FFF7ED;color:#C2410C}.transfer-status.approved{background:#ECFDF5;color:#047857}.transfer-status.rejected{background:#FEF2F2;color:#B91C1C}.transfer-empty{padding:40px;display:flex;flex-direction:column;align-items:center;gap:5px;color:#64748B}.transfer-empty i{font-size:27px}.transfer-pagination{padding:12px 14px;border-top:1px solid #E5E7EB}@media(max-width:760px){.transfer-toolbar{align-items:stretch;flex-direction:column;gap:10px}.transfer-toolbar>div{display:grid;grid-template-columns:1fr 1fr}.transfer-toolbar input[type=text]{width:100%;grid-column:1/-1}}
</style>
@endsection

@section('scripts')
<script>
document.getElementById('selectAllTransfers')?.addEventListener('change', function () {
    document.querySelectorAll('.transfer-checkbox').forEach((checkbox) => checkbox.checked = this.checked);
});
document.getElementById('transferReviewForm')?.addEventListener('submit', function (event) {
    if (document.querySelectorAll('.transfer-checkbox:checked').length === 0) {
        event.preventDefault();
    }
});
</script>
@endsection
