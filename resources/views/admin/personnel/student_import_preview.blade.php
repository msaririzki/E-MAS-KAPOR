@extends('layouts.app')

@section('title', 'Pratinjau Unggah Siswa')
@section('breadcrumb', 'Data Personel / Pratinjau Siswa')

@section('content')
@php
    $statusMeta = [
        'create' => ['label' => 'Data Baru', 'icon' => 'ri-user-add-line', 'class' => 'new'],
        'update' => ['label' => 'Diperbarui', 'icon' => 'ri-refresh-line', 'class' => 'update'],
        'no_change' => ['label' => 'Tidak Berubah', 'icon' => 'ri-checkbox-circle-line', 'class' => 'same'],
        'error' => ['label' => 'Bermasalah', 'icon' => 'ri-error-warning-line', 'class' => 'error'],
    ];
    $sizeLabels = [
        'topi' => 'Topi',
        'kemeja' => 'Kemeja',
        'celana' => 'Celana/Rok',
        'olahraga' => 'Olahraga',
        'sepatu_dinas' => 'Sepatu Dinas',
        'sepatu_olahraga' => 'Sepatu Olahraga',
        'jaket' => 'Jaket',
        'sabuk' => 'Sabuk',
        'jilbab' => 'Jilbab',
    ];
@endphp

@if(session('error'))
    <div class="student-alert error">
        <i class="ri-error-warning-fill"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="student-preview-header">
    <div class="student-preview-title">
        <a href="{{ route('admin.personnel.index') }}" class="student-back" title="Kembali ke Data Personel">
            <i class="ri-arrow-left-line"></i>
        </a>
        <div>
            <span class="student-eyebrow">PEMERIKSAAN DATA EXCEL</span>
            <h1>Pratinjau Unggah Siswa</h1>
            <p>{{ $payload['satker_name'] }} &middot; {{ $payload['source_file'] }}</p>
        </div>
    </div>
    <div class="student-preview-actions">
        <form action="{{ route('admin.personnel.student-import-cancel') }}" method="POST">
            @csrf
            <button type="submit" class="student-btn secondary">
                <i class="ri-close-line"></i>
                Batalkan
            </button>
        </form>
        <form action="{{ route('admin.personnel.student-import-confirm') }}" method="POST" id="studentImportConfirmForm">
            @csrf
            <button type="submit" class="student-btn primary" id="studentImportConfirmButton" @disabled($stats['error'] > 0)>
                <i class="{{ $stats['error'] > 0 ? 'ri-lock-line' : 'ri-check-double-line' }}"></i>
                <span>{{ $stats['error'] > 0 ? 'Perbaiki Excel' : 'Simpan Data Siswa' }}</span>
            </button>
        </form>
    </div>
</div>

<div class="student-summary">
    <a href="{{ route('admin.personnel.student-import-preview') }}" class="student-summary-item total {{ $statusFilter === '' ? 'active' : '' }}">
        <span class="summary-icon"><i class="ri-file-list-3-line"></i></span>
        <span><small>Total Baris</small><strong>{{ number_format($stats['total']) }}</strong></span>
    </a>
    <a href="{{ route('admin.personnel.student-import-preview', ['status' => 'create']) }}" class="student-summary-item new {{ $statusFilter === 'create' ? 'active' : '' }}">
        <span class="summary-icon"><i class="ri-user-add-line"></i></span>
        <span><small>Data Baru</small><strong>{{ number_format($stats['create']) }}</strong></span>
    </a>
    <a href="{{ route('admin.personnel.student-import-preview', ['status' => 'update']) }}" class="student-summary-item update {{ $statusFilter === 'update' ? 'active' : '' }}">
        <span class="summary-icon"><i class="ri-refresh-line"></i></span>
        <span><small>Diperbarui</small><strong>{{ number_format($stats['update']) }}</strong></span>
    </a>
    <a href="{{ route('admin.personnel.student-import-preview', ['status' => 'no_change']) }}" class="student-summary-item same {{ $statusFilter === 'no_change' ? 'active' : '' }}">
        <span class="summary-icon"><i class="ri-checkbox-circle-line"></i></span>
        <span><small>Tidak Berubah</small><strong>{{ number_format($stats['no_change']) }}</strong></span>
    </a>
    <a href="{{ route('admin.personnel.student-import-preview', ['status' => 'error']) }}" class="student-summary-item error {{ $statusFilter === 'error' ? 'active' : '' }}">
        <span class="summary-icon"><i class="ri-error-warning-line"></i></span>
        <span><small>Bermasalah</small><strong>{{ number_format($stats['error']) }}</strong></span>
    </a>
</div>

@if($stats['error'] > 0)
    <div class="student-alert warning">
        <i class="ri-file-warning-line"></i>
        <div>
            <strong>{{ number_format($stats['error']) }} baris belum dapat disimpan.</strong>
            <span>Perbaiki baris tersebut pada file Excel, batalkan pratinjau ini, lalu unggah kembali file yang sudah diperbaiki.</span>
        </div>
    </div>
@else
    <div class="student-alert success">
        <i class="ri-shield-check-line"></i>
        <div>
            <strong>Data siap disimpan.</strong>
            <span>Siswa akan tampil sebagai personel aktif tanpa dibuatkan akun login.</span>
        </div>
    </div>
@endif

<div class="student-table-shell">
    <div class="student-table-heading">
        <div>
            <h2>{{ $statusFilter !== '' ? $statusMeta[$statusFilter]['label'] : 'Seluruh Data' }}</h2>
            <p>Menampilkan {{ number_format($rows->firstItem() ?? 0) }}-{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} baris.</p>
        </div>
        @if($statusFilter !== '')
            <a href="{{ route('admin.personnel.student-import-preview') }}" class="student-clear-filter">
                <i class="ri-filter-off-line"></i>
                Hapus Filter
            </a>
        @endif
    </div>

    <div class="student-table-scroll">
        <table class="student-preview-table">
            <thead>
                <tr>
                    <th>BARIS</th>
                    <th>STATUS</th>
                    <th>PERSONEL</th>
                    <th>PANGKAT / GOL.</th>
                    <th>JABATAN / BAGIAN</th>
                    <th>JK</th>
                    <th>UKURAN TERISI</th>
                    <th>KETERANGAN</th>
                    <th>PEMERIKSAAN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php($meta = $statusMeta[$row['status']])
                    <tr>
                        <td><span class="row-number">{{ $row['row_number'] }}</span></td>
                        <td>
                            <span class="student-status {{ $meta['class'] }}">
                                <i class="{{ $meta['icon'] }}"></i>
                                {{ $meta['label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="person-cell">
                                <strong>{{ $row['full_name'] ?: '-' }}</strong>
                                <code>{{ $row['nrp'] ?: 'NRP/NIP kosong' }}</code>
                                @if($row['status'] === 'update' && filled($row['existing_name']) && $row['existing_name'] !== $row['full_name'])
                                    <small>Sebelumnya: {{ $row['existing_name'] }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="stacked-cell">
                                <strong>{{ $row['rank_name'] ?: '-' }}</strong>
                                <span>{{ $row['personnel_type'] }}{{ filled($row['golongan']) ? ' - Gol. '.$row['golongan'] : '' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="stacked-cell">
                                <strong>{{ $row['jabatan'] ?: '-' }}</strong>
                                <span>{{ $row['bagian'] ?: '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="gender-badge {{ $row['gender'] === 'P' ? 'female' : 'male' }}">{{ $row['gender_label'] }}</span>
                        </td>
                        <td>
                            @if($row['sizes'] !== [])
                                <div class="size-list">
                                    @foreach($row['sizes'] as $key => $value)
                                        <span title="{{ $sizeLabels[$key] ?? $key }}">{{ $sizeLabels[$key] ?? $key }}: <strong>{{ $value }}</strong></span>
                                    @endforeach
                                </div>
                            @else
                                <span class="empty-value">Belum diisi</span>
                            @endif
                        </td>
                        <td>
                            <div class="stacked-cell compact">
                                <strong>{{ $row['keterangan'] ?: '-' }}</strong>
                            </div>
                        </td>
                        <td>
                            @if($row['errors'] !== [])
                                <div class="error-list">
                                    @foreach($row['errors'] as $error)
                                        <span><i class="ri-close-circle-fill"></i>{{ $error }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="valid-row"><i class="ri-checkbox-circle-fill"></i> Valid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="student-empty">
                                <i class="ri-file-search-line"></i>
                                <strong>Tidak ada data pada filter ini</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div class="student-pagination">{{ $rows->links() }}</div>
    @endif
</div>
@endsection

@section('styles')
<style>
.student-preview-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:20px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:18px}.student-preview-title{display:flex;align-items:center;gap:14px;min-width:0}.student-preview-title h1{margin:2px 0 4px;font-size:24px;line-height:1.2;color:#111827;font-weight:800;letter-spacing:0}.student-preview-title p{margin:0;color:#64748B;font-size:13px;overflow-wrap:anywhere}.student-eyebrow{font-size:10px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.student-back{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #D1D5DB;border-radius:8px;color:#374151;background:#fff;text-decoration:none;flex:0 0 auto}.student-back:hover{border-color:#9CA3AF;background:#F9FAFB}.student-preview-actions{display:flex;gap:10px;align-items:center;flex:0 0 auto}.student-btn{height:40px;border-radius:8px;padding:0 15px;border:1px solid transparent;font-size:13px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer}.student-btn.secondary{background:#fff;border-color:#D1D5DB;color:#374151}.student-btn.primary{background:#B91C1C;color:#fff}.student-btn.primary:disabled{background:#E5E7EB;color:#9CA3AF;cursor:not-allowed}.student-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));border:1px solid #E5E7EB;border-radius:8px;background:#fff;margin-bottom:16px;overflow:hidden}.student-summary-item{min-height:78px;padding:14px 16px;display:flex;align-items:center;gap:12px;color:#334155;text-decoration:none;border-right:1px solid #E5E7EB;position:relative}.student-summary-item:last-child{border-right:0}.student-summary-item.active:after{content:\"\";position:absolute;left:0;right:0;bottom:0;height:3px;background:#334155}.student-summary-item.new.active:after{background:#059669}.student-summary-item.update.active:after{background:#2563EB}.student-summary-item.same.active:after{background:#64748B}.student-summary-item.error.active:after{background:#DC2626}.summary-icon{width:34px;height:34px;border-radius:8px;background:#F1F5F9;display:inline-flex;align-items:center;justify-content:center;font-size:18px}.student-summary-item.new .summary-icon{background:#ECFDF5;color:#059669}.student-summary-item.update .summary-icon{background:#EFF6FF;color:#2563EB}.student-summary-item.error .summary-icon{background:#FEF2F2;color:#DC2626}.student-summary-item span:last-child{display:flex;flex-direction:column}.student-summary-item small{font-size:11px;color:#64748B;font-weight:600}.student-summary-item strong{font-size:21px;line-height:1.15;color:#0F172A}.student-alert{border:1px solid;border-radius:8px;padding:13px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:11px;font-size:13px}.student-alert>i{font-size:20px;flex:0 0 auto}.student-alert div{display:flex;flex-direction:column;gap:2px}.student-alert.error,.student-alert.warning{background:#FFF7ED;border-color:#FED7AA;color:#9A3412}.student-alert.success{background:#F0FDF4;border-color:#BBF7D0;color:#166534}.student-table-shell{border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden}.student-table-heading{padding:14px 18px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;gap:16px}.student-table-heading h2{font-size:15px;margin:0;color:#111827;font-weight:800}.student-table-heading p{font-size:12px;margin:3px 0 0;color:#64748B}.student-clear-filter{font-size:12px;font-weight:700;color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:6px}.student-table-scroll{overflow:auto}.student-preview-table{width:100%;min-width:1420px;border-collapse:collapse;font-size:12px}.student-preview-table th{height:42px;padding:0 12px;background:#F8FAFC;border-bottom:1px solid #E5E7EB;color:#64748B;font-size:10px;text-align:left;font-weight:800}.student-preview-table td{padding:12px;border-bottom:1px solid #F1F5F9;vertical-align:top;color:#334155}.student-preview-table tbody tr:hover{background:#FAFAFA}.row-number{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#64748B}.student-status{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800;white-space:nowrap}.student-status.new{background:#ECFDF5;color:#047857}.student-status.update{background:#EFF6FF;color:#1D4ED8}.student-status.same{background:#F1F5F9;color:#475569}.student-status.error{background:#FEF2F2;color:#B91C1C}.person-cell,.stacked-cell{display:flex;flex-direction:column;gap:3px}.person-cell strong,.stacked-cell strong{color:#111827;font-size:12px}.person-cell code{font-size:11px;color:#475569;background:none;padding:0}.person-cell small,.stacked-cell span{font-size:10px;color:#64748B}.stacked-cell.compact{max-width:220px}.gender-badge{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800}.gender-badge.male{background:#EFF6FF;color:#1D4ED8}.gender-badge.female{background:#FDF2F8;color:#BE185D}.size-list{display:flex;flex-wrap:wrap;gap:4px;max-width:300px}.size-list span{font-size:9px;padding:3px 6px;border:1px solid #E2E8F0;border-radius:5px;background:#F8FAFC;white-space:nowrap}.empty-value{color:#94A3B8;font-size:11px}.error-list{display:flex;flex-direction:column;gap:4px;max-width:280px}.error-list span{display:flex;gap:5px;color:#B91C1C;font-size:10px;line-height:1.35}.valid-row{color:#047857;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px}.student-empty{padding:40px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px;color:#64748B}.student-empty i{font-size:28px}.student-pagination{padding:14px 18px;border-top:1px solid #E5E7EB}.student-btn.is-loading{pointer-events:none;opacity:.8}.student-btn.is-loading i{animation:student-spin .8s linear infinite}@keyframes student-spin{to{transform:rotate(360deg)}}@media(max-width:1000px){.student-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.student-summary-item{border-bottom:1px solid #E5E7EB}.student-preview-header{align-items:flex-start}.student-preview-actions{flex-direction:column;align-items:stretch}.student-preview-actions form,.student-btn{width:100%}}@media(max-width:640px){.student-preview-header{flex-direction:column}.student-preview-actions{width:100%;flex-direction:row}.student-summary{grid-template-columns:1fr}.student-summary-item{border-right:0}.student-preview-title h1{font-size:20px}}
</style>
@endsection

@section('scripts')
<script>
document.getElementById('studentImportConfirmForm')?.addEventListener('submit', function () {
    const button = document.getElementById('studentImportConfirmButton');
    if (!button || button.disabled) return;
    button.classList.add('is-loading');
    button.disabled = true;
    button.querySelector('i').className = 'ri-loader-4-line';
    button.querySelector('span').textContent = 'Menyimpan data...';
});
</script>
@endsection
