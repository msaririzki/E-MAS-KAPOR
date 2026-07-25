@extends('layouts.app')

@section('title', 'Periksa Pembaruan Data Personel')
@section('breadcrumb', 'Periksa Pembaruan Data')

@section('content')
@php
    $ranks = $ranks ?? collect();
    $stats = $preview['stats'];
    $errorCount = (int) $stats['error'];
    $hasBlockingErrors = $errorCount > 0;
    $statusMeta = [
        'update' => ['label' => 'Diperbarui', 'badge' => 'Berubah', 'icon' => 'ri-refresh-line', 'class' => 'update'],
        'new' => ['label' => 'Personel Baru', 'badge' => 'Baru', 'icon' => 'ri-user-add-line', 'class' => 'new'],
        'no_change' => ['label' => 'Tidak Berubah', 'badge' => 'Sesuai', 'icon' => 'ri-checkbox-circle-line', 'class' => 'same'],
        'transfer' => ['label' => 'Perlu Mutasi', 'badge' => 'Mutasi', 'icon' => 'ri-arrow-left-right-line', 'class' => 'transfer'],
        'duplicate_ignored' => ['label' => 'Duplikat Sama', 'badge' => 'Duplikat', 'icon' => 'ri-file-copy-2-line', 'class' => 'duplicate'],
        'error' => ['label' => 'Perlu Diperbaiki', 'badge' => 'Perbaiki', 'icon' => 'ri-error-warning-line', 'class' => 'error'],
    ];
@endphp

<div class="preview-header">
    <div class="preview-title">
        <a href="{{ route('admin.personnel.consolidation.index') }}" class="icon-back" title="Kembali">
            <i class="ri-arrow-left-line"></i>
        </a>
        <div>
            <span class="page-eyebrow">HASIL PEMERIKSAAN EXCEL</span>
            <h1>Periksa Pembaruan Data</h1>
            <p>{{ $preview['satker_name'] }} &middot; {{ $preview['source_file'] }}</p>
        </div>
    </div>
    <div class="preview-actions">
        <form action="{{ route('admin.personnel.consolidation.cancel') }}" method="POST">
            @csrf
            <button type="submit" class="preview-button secondary"><i class="ri-close-line"></i> Batalkan</button>
        </form>
        <button
            type="submit"
            form="consolidationConfirmForm"
            class="preview-button primary {{ $hasBlockingErrors ? 'blocked' : '' }}"
            id="saveConsolidationButton"
            @disabled($hasBlockingErrors)
            title="{{ $hasBlockingErrors ? 'Perbaiki semua baris bermasalah sebelum menyimpan' : 'Simpan pembaruan data personel' }}"
        >
            <i class="{{ $hasBlockingErrors ? 'ri-error-warning-line' : 'ri-shield-check-line' }}"></i>
            <span>{{ $hasBlockingErrors ? "Perbaiki {$errorCount} Baris Dulu" : 'Simpan Data Aman' }}</span>
        </button>
    </div>
</div>

@if(session('error'))
    <div class="preview-alert error"><i class="ri-error-warning-fill"></i><span>{{ session('error') }}</span></div>
@endif
@if(session('success'))
    <div class="preview-alert success"><i class="ri-checkbox-circle-fill"></i><span>{{ session('success') }}</span></div>
@endif

@if(($preview['warnings'] ?? []) !== [])
    <div class="preview-alert warning">
        <i class="ri-alert-fill"></i>
        <div>
            <strong>Format Excel diperbaiki otomatis.</strong>
            @foreach($preview['warnings'] as $warning)
                <span>{{ $warning }}</span>
            @endforeach
        </div>
    </div>
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

@if($hasBlockingErrors)
    <div class="preview-alert error">
        <i class="ri-error-warning-fill"></i>
        <div>
            <strong>Data belum dapat disimpan.</strong>
            <span>Ada {{ number_format($errorCount) }} baris berstatus Perbaiki. Gunakan tombol <strong>Perbaiki di Web</strong> pada baris tersebut, atau perbaiki Excel lalu unggah kembali. Belum ada data yang disimpan.</span>
            <a href="{{ route('admin.personnel.consolidation.preview', ['status' => 'error']) }}" class="error-filter-link">
                <i class="ri-filter-3-line"></i> Lihat baris yang harus diperbaiki
            </a>
        </div>
    </div>
@elseif($stats['transfer'] > 0)
    <div class="preview-alert warning">
        <i class="ri-information-fill"></i>
        <div>
            <strong>Pencocokan selesai dengan data mutasi.</strong>
            <span>{{ $stats['transfer'] }} data satker lain akan masuk antrean pemeriksaan superadmin setelah disimpan.</span>
        </div>
    </div>
@else
    <div class="preview-toast success" id="matchingCompleteAlert" role="status" aria-live="polite">
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
                <colgroup>
                    <col class="column-source">
                    <col class="column-status">
                    <col class="column-personnel">
                    <col class="column-rank">
                    <col class="column-position">
                    <col class="column-result">
                </colgroup>
                <thead>
                    <tr>
                        <th>SUMBER</th>
                        <th>STATUS</th>
                        <th>PERSONEL</th>
                        <th>PANGKAT / GOL.</th>
                        <th>JABATAN / BAGIAN</th>
                        <th>HASIL PEMERIKSAAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $meta = $statusMeta[$row['status']];
                            $personnelType = $row['personnel_type']
                                ?? data_get($row, 'data.personnel_type')
                                ?? (str_contains(strtolower($row['sheet']), 'pns') ? 'PNS' : 'Polri');
                        @endphp
                        <tr>
                            <td><span class="source-cell">{{ strtoupper($personnelType) }}<small>Baris {{ $row['row_number'] }}</small></span></td>
                            <td><span class="status-badge {{ $meta['class'] }}"><i class="{{ $meta['icon'] }}"></i>{{ $meta['badge'] }}</span></td>
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
                                <div class="result-cell">
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
                                    @if($row['errors'] !== [])
                                        <div class="message-list error">
                                            @foreach($row['errors'] as $message)<span><i class="ri-close-circle-fill"></i>{{ $message }}</span>@endforeach
                                        </div>
                                        <button
                                            type="button"
                                            class="fix-row-button"
                                            data-fix-row="{{ base64_encode(json_encode($row)) }}"
                                            onclick="openConsolidationFixModal(this)"
                                        >
                                            <i class="ri-edit-box-line"></i> Perbaiki di Web
                                        </button>
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="ri-file-search-line"></i><strong>Tidak ada data pada filter ini</strong></div></td></tr>
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
                        <h2>Personel yang Tidak Ada di File Excel</h2>
                        <p>Tidak perlu melakukan apa-apa jika mereka masih bertugas.</p>
                    </div>
                </div>
                <label class="select-all"><input type="checkbox" id="selectAllMissing"> Pilih semua yang sudah keluar</label>
            </div>
            <div class="missing-guide">
                <i class="ri-information-line"></i>
                <span><strong>Pilihan aman:</strong> semua personel di bawah ini tetap aktif. Centang hanya personel yang benar-benar sudah keluar dari satker.</span>
                <strong class="missing-selected-count" id="missingSelectedCount">Belum ada yang dipilih</strong>
            </div>
            <div class="missing-list">
                @foreach($preview['missing_rows'] as $personnel)
                    <label class="missing-person">
                        <input type="checkbox" name="deactivate_ids[]" value="{{ $personnel['personnel_id'] }}" class="missing-checkbox">
                        <span>
                            <strong>{{ $personnel['full_name'] }}</strong>
                            <small>{{ $personnel['rank_name'] ?: '-' }} &middot; {{ $personnel['nrp'] ?: 'NRP/NIP kosong' }} &middot; {{ $personnel['bagian'] ?: 'Bag/Fungsi kosong' }}</small>
                            <em>Centang jika sudah keluar</em>
                        </span>
                    </label>
                @endforeach
            </div>
            <label class="deactivation-confirm" id="deactivationConfirmRow" hidden>
                <input type="checkbox" name="confirm_deactivation" value="1" id="confirmDeactivation">
                <span id="deactivationConfirmText">Saya memahami bahwa personel yang dipilih akan dinonaktifkan dan tidak dapat login.</span>
            </label>
        </section>
    @endif
</form>

<div id="consolidationFixModal" class="consolidation-fix-modal" aria-hidden="true">
    <div class="consolidation-fix-backdrop" onclick="closeConsolidationFixModal()"></div>
    <section class="consolidation-fix-dialog" role="dialog" aria-modal="true" aria-labelledby="consolidationFixTitle">
        <div class="consolidation-fix-header">
            <div>
                <span class="page-eyebrow">PERBAIKAN LANGSUNG</span>
                <h2 id="consolidationFixTitle">Perbaiki Data Personel</h2>
                <p id="consolidationFixSubtitle">Lengkapi data yang kurang, lalu sistem akan memeriksa ulang seluruh file.</p>
            </div>
            <button type="button" class="consolidation-fix-close" onclick="closeConsolidationFixModal()" aria-label="Tutup">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form action="{{ route('admin.personnel.consolidation.fix-row') }}" method="POST" id="consolidationFixForm">
            @csrf
            <input type="hidden" name="sheet" id="consolidation_fix_sheet">
            <input type="hidden" name="row_number" id="consolidation_fix_row_number">
            <div class="consolidation-fix-body">
                <div class="consolidation-fix-note">
                    <i class="ri-information-line"></i>
                    <span>Perubahan ini masih berada di pratinjau. Data baru masuk setelah semua baris valid dan Anda menekan <strong>Simpan Data Aman</strong>.</span>
                </div>
                <div class="consolidation-fix-grid">
                    <label class="consolidation-fix-field wide">
                        <span>Nama Lengkap <b>*</b></span>
                        <input type="text" name="full_name" id="consolidation_fix_full_name" required>
                    </label>
                    <label class="consolidation-fix-field">
                        <span>NRP / NIP <b>*</b></span>
                        <input type="text" name="nrp" id="consolidation_fix_nrp" required>
                    </label>
                    <label class="consolidation-fix-field">
                        <span>Jenis Kelamin <b>*</b></span>
                        <select name="gender" id="consolidation_fix_gender" required>
                            <option value="L">Pria</option>
                            <option value="P">Wanita</option>
                        </select>
                    </label>
                    <label class="consolidation-fix-field">
                        <span>Pangkat <b>*</b></span>
                        <select name="rank_id" id="consolidation_fix_rank_id" required>
                            <option value="">Pilih pangkat</option>
                            @foreach($ranks as $rank)
                                <option value="{{ $rank->id }}">{{ $rank->name }}{{ $rank->category === 'PNS' ? ' (PNS)' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="consolidation-fix-field">
                        <span>Golongan</span>
                        <input type="text" name="golongan" id="consolidation_fix_golongan" placeholder="Contoh: BINTARA atau 3">
                    </label>
                    <label class="consolidation-fix-field wide">
                        <span>Jabatan</span>
                        <input type="text" name="jabatan" id="consolidation_fix_jabatan">
                    </label>
                    <label class="consolidation-fix-field">
                        <span>Bagian / Fungsi</span>
                        <input type="text" name="bagian" id="consolidation_fix_bagian">
                    </label>
                    <label class="consolidation-fix-field">
                        <span>Agama</span>
                        <input type="text" name="religion" id="consolidation_fix_religion">
                    </label>
                    <label class="consolidation-fix-field wide">
                        <span>Keterangan</span>
                        <input type="text" name="keterangan" id="consolidation_fix_keterangan">
                    </label>
                </div>
            </div>
            <div class="consolidation-fix-footer">
                <button type="button" class="preview-button secondary" onclick="closeConsolidationFixModal()">Batal</button>
                <button type="submit" class="preview-button primary" id="consolidationFixSubmitButton">
                    <i class="ri-check-line"></i> Simpan Perbaikan
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@section('styles')
<style>
.preview-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:18px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:16px}.preview-title{display:flex;align-items:center;gap:14px;min-width:0}.preview-title h1{font-size:28px;line-height:1.2;margin:2px 0 5px;color:#111827;font-weight:800;letter-spacing:0}.preview-title p{font-size:15px;margin:0;color:#64748B;overflow-wrap:anywhere}.page-eyebrow{font-size:12px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.icon-back{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #D1D5DB;border-radius:8px;background:#fff;color:#374151;text-decoration:none;flex:0 0 auto;font-size:18px}.preview-actions{display:flex;align-items:center;gap:9px;flex:0 0 auto}.preview-button{height:46px;padding:0 17px;border-radius:8px;border:1px solid;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:14px;font-weight:800;cursor:pointer}.preview-button.secondary{background:#fff;border-color:#CBD5E1;color:#334155}.preview-button.primary{background:#B91C1C;border-color:#B91C1C;color:#fff}.preview-button:disabled{cursor:not-allowed}.preview-button.blocked{background:#FEE2E2;border-color:#FCA5A5;color:#991B1B;opacity:1}.preview-button.is-loading{cursor:wait}.summary-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden;margin-bottom:16px}.summary-item{min-height:86px;padding:15px 17px;border-right:1px solid #E5E7EB;display:flex;flex-direction:column;justify-content:center;text-decoration:none;position:relative}.summary-item:last-child{border-right:0}.summary-item small{font-size:13px;line-height:1.3;color:#64748B;font-weight:700}.summary-item strong{font-size:28px;line-height:1.15;color:#0F172A}.summary-item.active:after{content:"";position:absolute;left:0;right:0;bottom:0;height:4px;background:#334155}.summary-item.update.active:after{background:#2563EB}.summary-item.new.active:after{background:#059669}.summary-item.transfer.active:after{background:#7C3AED}.summary-item.error.active:after{background:#DC2626}.summary-item.missing{background:#FFF7ED}.preview-alert{display:flex;align-items:flex-start;gap:11px;border:1px solid;border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:14px;line-height:1.5}.preview-alert>i{font-size:21px}.preview-alert div{display:flex;flex-direction:column;gap:4px}.preview-alert.warning{background:#FFF7ED;border-color:#FED7AA;color:#9A3412}.preview-alert.success{background:#F0FDF4;border-color:#BBF7D0;color:#166534}.preview-alert.error{background:#FEF2F2;border-color:#FECACA;color:#991B1B}.error-filter-link{display:inline-flex;align-items:center;gap:6px;width:max-content;margin-top:3px;color:#991B1B;font-weight:800;text-decoration:underline;text-underline-offset:3px}.preview-table-shell,.missing-section{border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden}.table-heading{min-height:76px;padding:13px 18px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between}.table-heading h2{font-size:18px;margin:0;color:#111827;font-weight:800}.table-heading p{font-size:14px;margin:4px 0 0;color:#64748B}.clear-filter{font-size:14px;color:#475569;font-weight:700;text-decoration:none;display:inline-flex;gap:6px}.table-scroll{overflow:auto}.preview-table{width:100%;min-width:1120px;table-layout:fixed;border-collapse:collapse;font-size:14px}.preview-table .column-source{width:82px}.preview-table .column-status{width:105px}.preview-table .column-personnel{width:24%}.preview-table .column-rank{width:130px}.preview-table .column-position{width:27%}.preview-table .column-result{width:24%}.preview-table th{height:48px;padding:0 14px;background:#F8FAFC;border-bottom:1px solid #E5E7EB;color:#64748B;text-align:left;font-size:12px;font-weight:800}.preview-table td{padding:14px;border-bottom:1px solid #E5E7EB;vertical-align:top;color:#334155;line-height:1.45}.preview-table tbody tr:hover{background:#FAFAFA}.source-cell,.person-cell,.stacked-cell{display:flex;flex-direction:column;gap:4px}.source-cell{font-size:13px;font-weight:700;color:#475569;white-space:nowrap}.source-cell small,.person-cell small,.stacked-cell span{font-size:12px;color:#64748B}.person-cell strong,.stacked-cell strong{font-size:14px;color:#111827;overflow-wrap:anywhere}.person-cell code{font-size:13px;color:#475569;background:transparent;padding:0}.stacked-cell.wide{max-width:none}.status-badge{display:inline-flex;align-items:center;gap:5px;border-radius:6px;padding:6px 8px;font-size:12px;font-weight:800;white-space:nowrap}.status-badge.update{background:#EFF6FF;color:#1D4ED8}.status-badge.new{background:#ECFDF5;color:#047857}.status-badge.same{background:#F1F5F9;color:#475569}.status-badge.transfer{background:#F5F3FF;color:#6D28D9}.status-badge.duplicate{background:#F8FAFC;color:#64748B}.status-badge.error{background:#FEF2F2;color:#B91C1C}.result-cell{display:flex;flex-direction:column;align-items:flex-start;gap:7px}.match-cell span{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#475569;white-space:nowrap}.match-code{color:#047857!important}.match-nrp{color:#1D4ED8!important}.message-list{display:flex;flex-direction:column;gap:5px;max-width:100%}.message-list span{display:flex;gap:6px;font-size:13px;line-height:1.45}.message-list.error span{color:#B91C1C}.message-list.warning span{color:#92400E}.valid-row{display:inline-flex;align-items:center;gap:5px;color:#047857;font-size:13px;font-weight:700}.diff-details{max-width:100%}.diff-details summary{cursor:pointer;color:#1D4ED8;font-size:13px;font-weight:800}.diff-details div{display:flex;flex-direction:column;gap:6px;margin-top:8px}.diff-details span{font-size:12px;color:#475569;line-height:1.45}.empty-state{padding:42px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:7px;color:#64748B;font-size:14px}.empty-state i{font-size:28px}.preview-pagination{min-height:64px;padding:11px 18px;border-top:1px solid #E5E7EB;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between;font-size:13px;color:#64748B}.preview-pagination>div{display:flex;gap:7px}.page-button{height:38px;padding:0 13px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#334155;display:inline-flex;align-items:center;gap:5px;text-decoration:none;font-size:13px;font-weight:700}.page-button.primary{background:#B91C1C;border-color:#B91C1C;color:#fff}.page-button.disabled{background:#F8FAFC;color:#94A3B8}.missing-section{margin-top:16px}.missing-heading{padding:16px 18px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between}.missing-heading>div{display:flex;align-items:center;gap:11px}.missing-heading h2{font-size:17px;margin:0;color:#111827;font-weight:800}.missing-heading p{font-size:14px;margin:3px 0 0;color:#64748B}.missing-icon{width:38px;height:38px;border-radius:8px;background:#FFF7ED;color:#C2410C;display:inline-flex;align-items:center;justify-content:center;font-size:19px}.select-all{font-size:14px;color:#475569;font-weight:700;display:flex;gap:6px}.missing-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:350px;overflow:auto}.missing-person{min-height:70px;padding:13px 17px;border-bottom:1px solid #F1F5F9;display:flex;align-items:flex-start;gap:10px;cursor:pointer}.missing-person:nth-child(odd){border-right:1px solid #F1F5F9}.missing-person:hover{background:#FAFAFA}.missing-person span{display:flex;flex-direction:column;gap:4px}.missing-person strong{font-size:14px;color:#1F2937}.missing-person small{font-size:12px;color:#64748B}.missing-person input,.select-all input,.deactivation-confirm input{accent-color:#B91C1C}.deactivation-confirm{padding:15px 17px;background:#FFF7ED;border-top:1px solid #FED7AA;display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#9A3412;font-weight:700}.is-loading i{animation:preview-spin .8s linear infinite}@keyframes preview-spin{to{transform:rotate(360deg)}}@media(max-width:1200px){.summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.summary-item{border-bottom:1px solid #E5E7EB}.preview-header{align-items:flex-start}.missing-list{grid-template-columns:1fr}.missing-person:nth-child(odd){border-right:0}}@media(max-width:640px){.preview-header{flex-direction:column}.preview-actions{width:100%}.preview-actions form,.preview-button{flex:1}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.preview-title h1{font-size:24px}.preview-title p{font-size:13px}.summary-item small{font-size:12px}.summary-item strong{font-size:25px}.preview-pagination{align-items:stretch;flex-direction:column;gap:10px}.preview-pagination>div{display:grid;grid-template-columns:1fr 1fr}.page-button{justify-content:center}}
.preview-toast{position:fixed;top:82px;right:22px;z-index:950;width:min(420px,calc(100vw - 32px));display:flex;align-items:flex-start;gap:11px;padding:14px 16px;border:1px solid;border-radius:8px;background:#fff;box-shadow:0 14px 35px rgba(15,23,42,.16);font-size:14px;line-height:1.5;animation:preview-toast-in .25s ease-out;transition:opacity .25s ease,transform .25s ease}.preview-toast.success{background:#F0FDF4;border-color:#86EFAC;color:#166534}.preview-toast>i{font-size:21px;flex:0 0 auto}.preview-toast div{display:flex;flex-direction:column;gap:2px}.preview-toast.is-hiding{opacity:0;transform:translateY(-8px)}@keyframes preview-toast-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}.missing-guide{display:flex;align-items:center;gap:9px;padding:12px 17px;border-bottom:1px solid #E5E7EB;background:#F8FAFC;color:#475569;font-size:13px;line-height:1.45}.missing-guide>i{color:#2563EB;font-size:18px;flex:0 0 auto}.missing-guide>span{flex:1}.missing-selected-count{color:#1D4ED8;white-space:nowrap}.deactivation-confirm[hidden]{display:none}.fix-row-button{margin-top:8px;height:30px;padding:0 9px;border:1px solid #FCA5A5;border-radius:6px;background:#FFF7F7;color:#B91C1C;display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;cursor:pointer}.fix-row-button:hover{background:#FEF2F2;border-color:#EF4444}.consolidation-fix-modal{display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;padding:20px}.consolidation-fix-modal.open{display:flex}.consolidation-fix-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48)}.consolidation-fix-dialog{position:relative;width:min(700px,100%);max-height:calc(100vh - 40px);overflow:auto;border:1px solid #E2E8F0;border-radius:12px;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.22)}.consolidation-fix-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:22px 24px 18px;border-bottom:1px solid #E5E7EB}.consolidation-fix-header h2{margin:3px 0 5px;color:#111827;font-size:21px}.consolidation-fix-header p{margin:0;color:#64748B;font-size:13px;line-height:1.45}.consolidation-fix-close{width:34px;height:34px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#475569;font-size:18px;cursor:pointer}.consolidation-fix-body{padding:20px 24px}.consolidation-fix-note{display:flex;gap:8px;align-items:flex-start;padding:11px 12px;margin-bottom:18px;border:1px solid #BFDBFE;border-radius:8px;background:#EFF6FF;color:#1D4ED8;font-size:12px;line-height:1.45}.consolidation-fix-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.consolidation-fix-field{display:flex;flex-direction:column;gap:6px}.consolidation-fix-field.wide{grid-column:span 2}.consolidation-fix-field span{font-size:12px;color:#475569;font-weight:800}.consolidation-fix-field b{color:#B91C1C}.consolidation-fix-field input,.consolidation-fix-field select{width:100%;height:39px;padding:0 10px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#1F2937;font-size:13px;outline:none}.consolidation-fix-field input:focus,.consolidation-fix-field select:focus{border-color:#B91C1C;box-shadow:0 0 0 3px rgba(185,28,28,.10)}.consolidation-fix-footer{display:flex;justify-content:flex-end;gap:9px;padding:15px 24px;border-top:1px solid #E5E7EB;background:#F8FAFC}.consolidation-fix-footer .preview-button{min-width:130px}@media(max-width:640px){.preview-toast{top:70px;right:16px}.missing-guide{align-items:flex-start;flex-wrap:wrap}.missing-selected-count{width:100%;padding-left:27px}.consolidation-fix-modal{padding:10px}.consolidation-fix-header,.consolidation-fix-body,.consolidation-fix-footer{padding-left:16px;padding-right:16px}.consolidation-fix-grid{grid-template-columns:1fr}.consolidation-fix-field.wide{grid-column:span 1}.consolidation-fix-footer{position:sticky;bottom:0}}
</style>
@endsection

@section('scripts')
<script>
const selectAllMissing = document.getElementById('selectAllMissing');
const missingCheckboxes = [...document.querySelectorAll('.missing-checkbox')];
const missingSelectedCount = document.getElementById('missingSelectedCount');
const deactivationConfirmRow = document.getElementById('deactivationConfirmRow');
const deactivationConfirmText = document.getElementById('deactivationConfirmText');
const updateMissingSelection = () => {
    const selected = missingCheckboxes.filter((checkbox) => checkbox.checked).length;
    if (missingSelectedCount) {
        missingSelectedCount.textContent = selected > 0
            ? `${selected} personel dipilih untuk dinonaktifkan`
            : 'Belum ada yang dipilih';
    }
    if (deactivationConfirmRow) deactivationConfirmRow.hidden = selected === 0;
    if (deactivationConfirmText && selected > 0) {
        deactivationConfirmText.textContent =
            `Ya, saya memahami bahwa ${selected} personel yang dipilih akan dinonaktifkan dan tidak dapat login.`;
    }
    if (selectAllMissing) {
        selectAllMissing.checked = selected > 0 && selected === missingCheckboxes.length;
        selectAllMissing.indeterminate = selected > 0 && selected < missingCheckboxes.length;
    }
};
selectAllMissing?.addEventListener('change', function () {
    missingCheckboxes.forEach((checkbox) => checkbox.checked = this.checked);
    updateMissingSelection();
});
missingCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateMissingSelection));
updateMissingSelection();
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

function openConsolidationFixModal(button) {
    const row = JSON.parse(atob(button.dataset.fixRow));
    const data = row.data || {};
    const modal = document.getElementById('consolidationFixModal');
    document.getElementById('consolidation_fix_sheet').value = row.sheet || '';
    document.getElementById('consolidation_fix_row_number').value = row.row_number || '';
    document.getElementById('consolidation_fix_full_name').value = row.full_name || data.full_name || '';
    document.getElementById('consolidation_fix_nrp').value = row.nrp || data.nrp || '';
    document.getElementById('consolidation_fix_gender').value = data.gender || 'L';
    document.getElementById('consolidation_fix_rank_id').value = data.rank_id || '';
    document.getElementById('consolidation_fix_golongan').value = row.golongan || data.golongan || '';
    document.getElementById('consolidation_fix_jabatan').value = row.jabatan || data.jabatan || '';
    document.getElementById('consolidation_fix_bagian').value = row.bagian || data.bagian || '';
    document.getElementById('consolidation_fix_religion').value = data.religion || '';
    document.getElementById('consolidation_fix_keterangan').value = data.keterangan || '';
    document.getElementById('consolidationFixSubtitle').textContent =
        'Sheet ' + (row.sheet || '-') + ', baris ' + (row.row_number || '-') + '. Lengkapi data yang kurang lalu simpan.';
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.getElementById('consolidation_fix_full_name').focus();
}

function closeConsolidationFixModal() {
    const modal = document.getElementById('consolidationFixModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('consolidationFixForm')?.addEventListener('submit', function () {
    const button = document.getElementById('consolidationFixSubmitButton');
    button.disabled = true;
    button.classList.add('is-loading');
    button.querySelector('i').className = 'ri-loader-4-line';
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeConsolidationFixModal();
});

const matchingCompleteAlert = document.getElementById('matchingCompleteAlert');
if (matchingCompleteAlert) {
    setTimeout(function () {
        matchingCompleteAlert.classList.add('is-hiding');
        setTimeout(() => matchingCompleteAlert.remove(), 250);
    }, 4000);
}
</script>
@endsection
