@extends('layouts.app')

@section('title', 'Pratinjau Konsolidasi Personel')
@section('breadcrumb', 'Pratinjau Konsolidasi')

@section('content')
@php
    $stats = $preview['stats'];
    $statusMeta = [
        'update' => ['label' => 'Diperbarui', 'icon' => 'ri-refresh-line', 'class' => 'update'],
        'new' => ['label' => 'Personel Baru', 'icon' => 'ri-user-add-line', 'class' => 'new'],
        'no_change' => ['label' => 'Tidak Berubah', 'icon' => 'ri-checkbox-circle-line', 'class' => 'same'],
        'transfer' => ['label' => 'Perlu Mutasi', 'icon' => 'ri-arrow-left-right-line', 'class' => 'transfer'],
        'duplicate_ignored' => ['label' => 'Duplikat Sama', 'icon' => 'ri-file-copy-2-line', 'class' => 'duplicate'],
        'error' => ['label' => 'Perlu Diperbaiki', 'icon' => 'ri-error-warning-line', 'class' => 'error'],
    ];
@endphp

<div class="preview-header">
    <div class="preview-title">
        <a href="{{ route('admin.personnel.consolidation.index') }}" class="icon-back" title="Kembali">
            <i class="ri-arrow-left-line"></i>
        </a>
        <div>
            <span class="page-eyebrow">HASIL PEMERIKSAAN EXCEL</span>
            <h1>Pratinjau Konsolidasi</h1>
            <p>{{ $preview['satker_name'] }} &middot; {{ $preview['source_file'] }}</p>
        </div>
    </div>
    <div class="preview-actions">
        <form action="{{ route('admin.personnel.consolidation.cancel') }}" method="POST">
            @csrf
            <button type="submit" class="preview-button secondary"><i class="ri-close-line"></i> Batalkan</button>
        </form>
        <button type="submit" form="consolidationConfirmForm" class="preview-button primary" id="saveConsolidationButton">
            <i class="ri-shield-check-line"></i>
            <span>Simpan Data Aman</span>
        </button>
    </div>
</div>

@if(session('error'))
    <div class="preview-alert error"><i class="ri-error-warning-fill"></i><span>{{ session('error') }}</span></div>
@endif

<div class="summary-grid">
    <a href="{{ route('admin.personnel.consolidation.preview') }}" class="summary-item total {{ $statusFilter === '' ? 'active' : '' }}">
        <small>Baris Dibaca</small><strong>{{ number_format($stats['total']) }}</strong>
    </a>
    @foreach(['update', 'new', 'transfer', 'error'] as $summaryStatus)
        <a href="{{ route('admin.personnel.consolidation.preview', ['status' => $summaryStatus]) }}" class="summary-item {{ $statusMeta[$summaryStatus]['class'] }} {{ $statusFilter === $summaryStatus ? 'active' : '' }}">
            <small>{{ $statusMeta[$summaryStatus]['label'] }}</small><strong>{{ number_format($stats[$summaryStatus]) }}</strong>
        </a>
    @endforeach
    <div class="summary-item missing">
        <small>Tidak Ada di File</small><strong>{{ number_format($stats['missing']) }}</strong>
    </div>
</div>

@if($stats['error'] > 0 || $stats['transfer'] > 0)
    <div class="preview-alert warning">
        <i class="ri-information-fill"></i>
        <div>
            <strong>Baris aman tetap dapat disimpan.</strong>
            <span>{{ $stats['error'] }} baris bermasalah akan dilewati. {{ $stats['transfer'] }} data satker lain akan masuk antrean pemeriksaan superadmin.</span>
        </div>
    </div>
@else
    <div class="preview-alert success">
        <i class="ri-checkbox-circle-fill"></i>
        <div><strong>Pencocokan selesai.</strong><span>Tinjau perubahan dan personel yang tidak ditemukan sebelum menyimpan.</span></div>
    </div>
@endif

<form action="{{ route('admin.personnel.consolidation.confirm') }}" method="POST" id="consolidationConfirmForm">
    @csrf

    <section class="preview-table-shell">
        <div class="table-heading">
            <div>
                <h2>{{ $statusFilter !== '' ? $statusMeta[$statusFilter]['label'] : 'Seluruh Data Dalam File' }}</h2>
                <p>{{ number_format($rows->firstItem() ?? 0) }}-{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} baris</p>
            </div>
            @if($statusFilter !== '')
                <a href="{{ route('admin.personnel.consolidation.preview') }}" class="clear-filter"><i class="ri-filter-off-line"></i> Hapus Filter</a>
            @endif
        </div>

        <div class="table-scroll">
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>SUMBER</th>
                        <th>STATUS</th>
                        <th>PERSONEL</th>
                        <th>PANGKAT / GOL.</th>
                        <th>JABATAN / BAGIAN</th>
                        <th>PENCOCOKAN</th>
                        <th>HASIL PEMERIKSAAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php($meta = $statusMeta[$row['status']])
                        <tr>
                            <td><span class="source-cell">{{ $row['sheet'] }}<small>Baris {{ $row['row_number'] }}</small></span></td>
                            <td><span class="status-badge {{ $meta['class'] }}"><i class="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span></td>
                            <td>
                                <div class="person-cell">
                                    <strong>{{ $row['full_name'] ?: '-' }}</strong>
                                    <code>{{ $row['nrp'] ?: 'NRP/NIP kosong' }}</code>
                                    <small>{{ $row['gender_label'] }}</small>
                                </div>
                            </td>
                            <td><div class="stacked-cell"><strong>{{ $row['rank_name'] ?: '-' }}</strong><span>{{ $row['golongan'] ?: '-' }}</span></div></td>
                            <td><div class="stacked-cell wide"><strong>{{ $row['jabatan'] ?: '-' }}</strong><span>{{ $row['bagian'] ?: 'Bag/Fungsi kosong' }}</span></div></td>
                            <td>
                                <div class="match-cell">
                                    @if($row['system_code_present'])
                                        <span class="match-code"><i class="ri-key-2-line"></i>Kode data</span>
                                    @elseif($row['match_method'] === 'nrp')
                                        <span class="match-nrp"><i class="ri-fingerprint-line"></i>NRP/NIP</span>
                                    @elseif($row['match_method'] === 'inactive_account')
                                        <span class="match-account"><i class="ri-user-follow-line"></i>Akun lama</span>
                                    @else
                                        <span class="match-new"><i class="ri-add-circle-line"></i>Data baru</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($row['errors'] !== [])
                                    <div class="message-list error">
                                        @foreach($row['errors'] as $message)<span><i class="ri-close-circle-fill"></i>{{ $message }}</span>@endforeach
                                    </div>
                                @elseif($row['warnings'] !== [])
                                    <div class="message-list warning">
                                        @foreach($row['warnings'] as $message)<span><i class="ri-alert-fill"></i>{{ $message }}</span>@endforeach
                                    </div>
                                @elseif($row['diff'] !== [])
                                    <details class="diff-details">
                                        <summary>{{ count($row['diff']) }} perubahan</summary>
                                        <div>
                                            @foreach($row['diff'] as $label => $change)
                                                <span><b>{{ $label }}</b>: {{ $change['from'] }} <i class="ri-arrow-right-line"></i> {{ $change['to'] }}</span>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="valid-row"><i class="ri-checkbox-circle-fill"></i>Valid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="ri-file-search-line"></i><strong>Tidak ada data pada filter ini</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="preview-pagination">
                <span>Halaman <strong>{{ $rows->currentPage() }}</strong> dari {{ $rows->lastPage() }}</span>
                <div>
                    @if($rows->onFirstPage())
                        <span class="page-button disabled"><i class="ri-arrow-left-s-line"></i>Sebelumnya</span>
                    @else
                        <a class="page-button" href="{{ $rows->previousPageUrl() }}"><i class="ri-arrow-left-s-line"></i>Sebelumnya</a>
                    @endif
                    @if($rows->hasMorePages())
                        <a class="page-button primary" href="{{ $rows->nextPageUrl() }}">Selanjutnya<i class="ri-arrow-right-s-line"></i></a>
                    @else
                        <span class="page-button disabled">Selanjutnya<i class="ri-arrow-right-s-line"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    @if($preview['missing_rows'] !== [])
        <section class="missing-section">
            <div class="missing-heading">
                <div>
                    <span class="missing-icon"><i class="ri-user-unfollow-line"></i></span>
                    <div>
                        <h2>Tidak Ditemukan Dalam File</h2>
                        <p>Data berikut tetap aktif kecuali dipilih untuk dinonaktifkan.</p>
                    </div>
                </div>
                <label class="select-all"><input type="checkbox" id="selectAllMissing"> Pilih semua</label>
            </div>
            <div class="missing-list">
                @foreach($preview['missing_rows'] as $personnel)
                    <label class="missing-person">
                        <input type="checkbox" name="deactivate_ids[]" value="{{ $personnel['personnel_id'] }}" class="missing-checkbox">
                        <span>
                            <strong>{{ $personnel['full_name'] }}</strong>
                            <small>{{ $personnel['rank_name'] ?: '-' }} &middot; {{ $personnel['nrp'] ?: 'NRP/NIP kosong' }} &middot; {{ $personnel['bagian'] ?: 'Bag/Fungsi kosong' }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <label class="deactivation-confirm">
                <input type="checkbox" name="confirm_deactivation" value="1" id="confirmDeactivation">
                <span>Saya memahami bahwa personel yang dipilih akan dinonaktifkan dan tidak dapat login.</span>
            </label>
        </section>
    @endif
</form>
@endsection

@section('styles')
<style>
.preview-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:18px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:16px}.preview-title{display:flex;align-items:center;gap:14px;min-width:0}.preview-title h1{font-size:24px;line-height:1.2;margin:2px 0 4px;color:#111827;font-weight:800;letter-spacing:0}.preview-title p{font-size:13px;margin:0;color:#64748B;overflow-wrap:anywhere}.page-eyebrow{font-size:10px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.icon-back{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #D1D5DB;border-radius:8px;background:#fff;color:#374151;text-decoration:none;flex:0 0 auto}.preview-actions{display:flex;align-items:center;gap:9px;flex:0 0 auto}.preview-button{height:40px;padding:0 14px;border-radius:8px;border:1px solid;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:12px;font-weight:800;cursor:pointer}.preview-button.secondary{background:#fff;border-color:#CBD5E1;color:#334155}.preview-button.primary{background:#B91C1C;border-color:#B91C1C;color:#fff}.preview-button:disabled{opacity:.75;cursor:wait}.summary-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden;margin-bottom:14px}.summary-item{min-height:70px;padding:12px 14px;border-right:1px solid #E5E7EB;display:flex;flex-direction:column;justify-content:center;text-decoration:none;position:relative}.summary-item:last-child{border-right:0}.summary-item small{font-size:10px;color:#64748B;font-weight:700}.summary-item strong{font-size:21px;color:#0F172A}.summary-item.active:after{content:"";position:absolute;left:0;right:0;bottom:0;height:3px;background:#334155}.summary-item.update.active:after{background:#2563EB}.summary-item.new.active:after{background:#059669}.summary-item.transfer.active:after{background:#7C3AED}.summary-item.error.active:after{background:#DC2626}.summary-item.missing{background:#FFF7ED}.preview-alert{display:flex;align-items:flex-start;gap:10px;border:1px solid;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:12px}.preview-alert>i{font-size:19px}.preview-alert div{display:flex;flex-direction:column;gap:2px}.preview-alert.warning{background:#FFF7ED;border-color:#FED7AA;color:#9A3412}.preview-alert.success{background:#F0FDF4;border-color:#BBF7D0;color:#166534}.preview-alert.error{background:#FEF2F2;border-color:#FECACA;color:#991B1B}.preview-table-shell,.missing-section{border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden}.table-heading{height:64px;padding:0 17px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between}.table-heading h2{font-size:14px;margin:0;color:#111827;font-weight:800}.table-heading p{font-size:11px;margin:3px 0 0;color:#64748B}.clear-filter{font-size:11px;color:#475569;font-weight:700;text-decoration:none;display:inline-flex;gap:6px}.table-scroll{overflow:auto}.preview-table{width:100%;min-width:1280px;border-collapse:collapse;font-size:11px}.preview-table th{height:40px;padding:0 11px;background:#F8FAFC;border-bottom:1px solid #E5E7EB;color:#64748B;text-align:left;font-size:9px;font-weight:800}.preview-table td{padding:11px;border-bottom:1px solid #F1F5F9;vertical-align:top;color:#334155}.preview-table tbody tr:hover{background:#FAFAFA}.source-cell,.person-cell,.stacked-cell{display:flex;flex-direction:column;gap:3px}.source-cell{font-size:10px;color:#475569;white-space:nowrap}.source-cell small,.person-cell small,.stacked-cell span{font-size:9px;color:#64748B}.person-cell strong,.stacked-cell strong{font-size:11px;color:#111827}.person-cell code{font-size:10px;color:#475569;background:transparent;padding:0}.stacked-cell.wide{max-width:240px}.status-badge{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:4px 7px;font-size:9px;font-weight:800;white-space:nowrap}.status-badge.update{background:#EFF6FF;color:#1D4ED8}.status-badge.new{background:#ECFDF5;color:#047857}.status-badge.same{background:#F1F5F9;color:#475569}.status-badge.transfer{background:#F5F3FF;color:#6D28D9}.status-badge.duplicate{background:#F8FAFC;color:#64748B}.status-badge.error{background:#FEF2F2;color:#B91C1C}.match-cell span{display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:700;color:#475569;white-space:nowrap}.match-code{color:#047857!important}.match-nrp{color:#1D4ED8!important}.message-list{display:flex;flex-direction:column;gap:4px;max-width:300px}.message-list span{display:flex;gap:5px;font-size:9px;line-height:1.4}.message-list.error span{color:#B91C1C}.message-list.warning span{color:#92400E}.valid-row{display:inline-flex;align-items:center;gap:5px;color:#047857;font-size:10px;font-weight:700}.diff-details{max-width:310px}.diff-details summary{cursor:pointer;color:#1D4ED8;font-size:10px;font-weight:700}.diff-details div{display:flex;flex-direction:column;gap:5px;margin-top:7px}.diff-details span{font-size:9px;color:#475569;line-height:1.4}.empty-state{padding:38px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:5px;color:#64748B}.empty-state i{font-size:26px}.preview-pagination{min-height:58px;padding:10px 17px;border-top:1px solid #E5E7EB;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#64748B}.preview-pagination>div{display:flex;gap:7px}.page-button{height:34px;padding:0 11px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#334155;display:inline-flex;align-items:center;gap:5px;text-decoration:none;font-weight:700}.page-button.primary{background:#B91C1C;border-color:#B91C1C;color:#fff}.page-button.disabled{background:#F8FAFC;color:#94A3B8}.missing-section{margin-top:16px}.missing-heading{padding:15px 17px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between}.missing-heading>div{display:flex;align-items:center;gap:11px}.missing-heading h2{font-size:14px;margin:0;color:#111827;font-weight:800}.missing-heading p{font-size:11px;margin:3px 0 0;color:#64748B}.missing-icon{width:34px;height:34px;border-radius:8px;background:#FFF7ED;color:#C2410C;display:inline-flex;align-items:center;justify-content:center;font-size:17px}.select-all{font-size:11px;color:#475569;font-weight:700;display:flex;gap:6px}.missing-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:350px;overflow:auto}.missing-person{min-height:62px;padding:11px 16px;border-bottom:1px solid #F1F5F9;display:flex;align-items:flex-start;gap:9px;cursor:pointer}.missing-person:nth-child(odd){border-right:1px solid #F1F5F9}.missing-person:hover{background:#FAFAFA}.missing-person span{display:flex;flex-direction:column;gap:3px}.missing-person strong{font-size:11px;color:#1F2937}.missing-person small{font-size:9px;color:#64748B}.missing-person input,.select-all input,.deactivation-confirm input{accent-color:#B91C1C}.deactivation-confirm{padding:13px 16px;background:#FFF7ED;border-top:1px solid #FED7AA;display:flex;align-items:flex-start;gap:8px;font-size:10px;color:#9A3412;font-weight:700}.is-loading i{animation:preview-spin .8s linear infinite}@keyframes preview-spin{to{transform:rotate(360deg)}}@media(max-width:1000px){.summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.summary-item{border-bottom:1px solid #E5E7EB}.preview-header{align-items:flex-start}.missing-list{grid-template-columns:1fr}.missing-person:nth-child(odd){border-right:0}}@media(max-width:640px){.preview-header{flex-direction:column}.preview-actions{width:100%}.preview-actions form,.preview-button{flex:1}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.preview-title h1{font-size:20px}.preview-pagination{align-items:stretch;flex-direction:column;gap:10px}.preview-pagination>div{display:grid;grid-template-columns:1fr 1fr}.page-button{justify-content:center}}
</style>
@endsection

@section('scripts')
<script>
const selectAllMissing = document.getElementById('selectAllMissing');
selectAllMissing?.addEventListener('change', function () {
    document.querySelectorAll('.missing-checkbox').forEach((checkbox) => checkbox.checked = this.checked);
});
document.getElementById('consolidationConfirmForm')?.addEventListener('submit', function (event) {
    const selected = document.querySelectorAll('.missing-checkbox:checked').length;
    const confirmation = document.getElementById('confirmDeactivation');
    if (selected > 0 && !confirmation?.checked) {
        event.preventDefault();
        confirmation?.scrollIntoView({behavior: 'smooth', block: 'center'});
        return;
    }
    const button = document.getElementById('saveConsolidationButton');
    button.disabled = true;
    button.classList.add('is-loading');
    button.querySelector('i').className = 'ri-loader-4-line';
    button.querySelector('span').textContent = 'Menyimpan data...';
});
</script>
@endsection
