@extends('layouts.app')

@section('title', 'Pratinjau Unggah Siswa')
@section('breadcrumb', 'Data Personel / Pratinjau Siswa')

@section('content')
@php
    $ranks = $ranks ?? collect();
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
@if(session('success'))
    <div class="student-alert success">
        <i class="ri-checkbox-circle-fill"></i>
        <span>{{ session('success') }}</span>
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
                                <button
                                    type="button"
                                    class="student-fix-button"
                                    data-fix-row="{{ base64_encode(json_encode($row)) }}"
                                    onclick="openStudentFixModal(this)"
                                >
                                    <i class="ri-edit-box-line"></i>
                                    Perbaiki di Web
                                </button>
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
        <div class="student-pagination">
            <div class="student-page-status">
                <span>Halaman</span>
                <strong>{{ $rows->currentPage() }}</strong>
                <span>dari {{ $rows->lastPage() }}</span>
            </div>
            <nav class="student-page-actions" aria-label="Navigasi halaman pratinjau siswa">
                @if($rows->onFirstPage())
                    <span class="student-page-button disabled" aria-disabled="true">
                        <i class="ri-arrow-left-s-line"></i>
                        Sebelumnya
                    </span>
                @else
                    <a class="student-page-button" href="{{ $rows->previousPageUrl() }}" rel="prev">
                        <i class="ri-arrow-left-s-line"></i>
                        Sebelumnya
                    </a>
                @endif

                @if($rows->hasMorePages())
                    <a class="student-page-button primary" href="{{ $rows->nextPageUrl() }}" rel="next">
                        Selanjutnya
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                @else
                    <span class="student-page-button disabled" aria-disabled="true">
                        Selanjutnya
                        <i class="ri-arrow-right-s-line"></i>
                    </span>
                @endif
            </nav>
        </div>
    @endif
</div>

<div id="studentFixModal" class="student-fix-modal" aria-hidden="true">
    <div class="student-fix-backdrop" onclick="closeStudentFixModal()"></div>
    <section class="student-fix-dialog" role="dialog" aria-modal="true" aria-labelledby="studentFixTitle">
        <div class="student-fix-header">
            <div>
                <span class="student-eyebrow">PERBAIKAN LANGSUNG</span>
                <h2 id="studentFixTitle">Perbaiki Baris Siswa</h2>
                <p id="studentFixSubtitle">Lengkapi kolom yang ditandai, lalu simpan untuk memeriksa ulang seluruh file.</p>
            </div>
            <button type="button" class="student-fix-close" onclick="closeStudentFixModal()" aria-label="Tutup">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.personnel.student-import-fix-row') }}" id="studentFixForm">
            @csrf
            <input type="hidden" name="row_number" id="student_fix_row_number">
            <div class="student-fix-body">
                <div class="student-fix-notice">
                    <i class="ri-information-line"></i>
                    <span>Perbaikan ini hanya mengubah pratinjau. Data baru masuk ke sistem setelah tombol <strong>Simpan Data Siswa</strong> berhasil.</span>
                </div>
                <div class="student-fix-grid">
                    <label class="student-fix-field wide">
                        <span>Nama Lengkap <b>*</b></span>
                        <input type="text" name="full_name" id="student_fix_full_name" required>
                    </label>
                    <label class="student-fix-field">
                        <span>NRP / NIP <b>*</b></span>
                        <input type="text" name="nrp" id="student_fix_nrp" required>
                    </label>
                    <label class="student-fix-field">
                        <span>Jenis Kelamin <b>*</b></span>
                        <select name="gender" id="student_fix_gender" required>
                            <option value="L">Pria</option>
                            <option value="P">Wanita</option>
                        </select>
                    </label>
                    <label class="student-fix-field">
                        <span>Pangkat <b>*</b></span>
                        <select name="rank_id" id="student_fix_rank_id" required>
                            <option value="">Pilih pangkat</option>
                            @foreach($ranks as $rank)
                                <option value="{{ $rank->id }}">{{ $rank->name }}{{ $rank->category === 'PNS' ? ' (PNS)' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="student-fix-field">
                        <span>Golongan</span>
                        <input type="text" name="golongan" id="student_fix_golongan" placeholder="PNS: 1, 2, 3, atau 4">
                    </label>
                    <label class="student-fix-field wide">
                        <span>Jabatan <b>*</b></span>
                        <input type="text" name="jabatan" id="student_fix_jabatan" required>
                    </label>
                    <label class="student-fix-field">
                        <span>Bagian / Fungsi</span>
                        <input type="text" name="bagian" id="student_fix_bagian">
                    </label>
                    <label class="student-fix-field">
                        <span>Keterangan</span>
                        <input type="text" name="keterangan" id="student_fix_keterangan">
                    </label>
                </div>
                <div class="student-fix-section">
                    <div class="student-fix-section-title">
                        <span>Ukuran Kapor</span>
                        <small>Opsional, isi jika tersedia</small>
                    </div>
                    <div class="student-fix-size-grid">
                        @foreach($sizeLabels as $key => $label)
                            <label class="student-fix-field">
                                <span>{{ $label }}</span>
                                <input type="text" name="sizes[{{ $key }}]" id="student_fix_size_{{ $key }}">
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="student-fix-footer">
                <button type="button" class="student-btn secondary" onclick="closeStudentFixModal()">Batal</button>
                <button type="submit" class="student-btn primary" id="studentFixSubmitButton">
                    <i class="ri-check-line"></i>
                    Simpan Perbaikan
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@section('styles')
<style>
.student-preview-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:20px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:18px}.student-preview-title{display:flex;align-items:center;gap:14px;min-width:0}.student-preview-title h1{margin:2px 0 4px;font-size:24px;line-height:1.2;color:#111827;font-weight:800;letter-spacing:0}.student-preview-title p{margin:0;color:#64748B;font-size:13px;overflow-wrap:anywhere}.student-eyebrow{font-size:10px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.student-back{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #D1D5DB;border-radius:8px;color:#374151;background:#fff;text-decoration:none;flex:0 0 auto}.student-back:hover{border-color:#9CA3AF;background:#F9FAFB}.student-preview-actions{display:flex;gap:10px;align-items:center;flex:0 0 auto}.student-btn{height:40px;border-radius:8px;padding:0 15px;border:1px solid transparent;font-size:13px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer}.student-btn.secondary{background:#fff;border-color:#D1D5DB;color:#374151}.student-btn.primary{background:#B91C1C;color:#fff}.student-btn.primary:disabled{background:#E5E7EB;color:#9CA3AF;cursor:not-allowed}.student-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));border:1px solid #E5E7EB;border-radius:8px;background:#fff;margin-bottom:16px;overflow:hidden}.student-summary-item{min-height:78px;padding:14px 16px;display:flex;align-items:center;gap:12px;color:#334155;text-decoration:none;border-right:1px solid #E5E7EB;position:relative}.student-summary-item:last-child{border-right:0}.student-summary-item.active:after{content:\"\";position:absolute;left:0;right:0;bottom:0;height:3px;background:#334155}.student-summary-item.new.active:after{background:#059669}.student-summary-item.update.active:after{background:#2563EB}.student-summary-item.same.active:after{background:#64748B}.student-summary-item.error.active:after{background:#DC2626}.summary-icon{width:34px;height:34px;border-radius:8px;background:#F1F5F9;display:inline-flex;align-items:center;justify-content:center;font-size:18px}.student-summary-item.new .summary-icon{background:#ECFDF5;color:#059669}.student-summary-item.update .summary-icon{background:#EFF6FF;color:#2563EB}.student-summary-item.error .summary-icon{background:#FEF2F2;color:#DC2626}.student-summary-item span:last-child{display:flex;flex-direction:column}.student-summary-item small{font-size:11px;color:#64748B;font-weight:600}.student-summary-item strong{font-size:21px;line-height:1.15;color:#0F172A}.student-alert{border:1px solid;border-radius:8px;padding:13px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:11px;font-size:13px}.student-alert>i{font-size:20px;flex:0 0 auto}.student-alert div{display:flex;flex-direction:column;gap:2px}.student-alert.error,.student-alert.warning{background:#FFF7ED;border-color:#FED7AA;color:#9A3412}.student-alert.success{background:#F0FDF4;border-color:#BBF7D0;color:#166534}.student-table-shell{border:1px solid #E5E7EB;border-radius:8px;background:#fff;overflow:hidden}.student-table-heading{padding:14px 18px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;gap:16px}.student-table-heading h2{font-size:15px;margin:0;color:#111827;font-weight:800}.student-table-heading p{font-size:12px;margin:3px 0 0;color:#64748B}.student-clear-filter{font-size:12px;font-weight:700;color:#475569;text-decoration:none;display:inline-flex;align-items:center;gap:6px}.student-table-scroll{overflow:auto}.student-preview-table{width:100%;min-width:1420px;border-collapse:collapse;font-size:12px}.student-preview-table th{height:42px;padding:0 12px;background:#F8FAFC;border-bottom:1px solid #E5E7EB;color:#64748B;font-size:10px;text-align:left;font-weight:800}.student-preview-table td{padding:12px;border-bottom:1px solid #F1F5F9;vertical-align:top;color:#334155}.student-preview-table tbody tr:hover{background:#FAFAFA}.row-number{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#64748B}.student-status{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800;white-space:nowrap}.student-status.new{background:#ECFDF5;color:#047857}.student-status.update{background:#EFF6FF;color:#1D4ED8}.student-status.same{background:#F1F5F9;color:#475569}.student-status.error{background:#FEF2F2;color:#B91C1C}.person-cell,.stacked-cell{display:flex;flex-direction:column;gap:3px}.person-cell strong,.stacked-cell strong{color:#111827;font-size:12px}.person-cell code{font-size:11px;color:#475569;background:none;padding:0}.person-cell small,.stacked-cell span{font-size:10px;color:#64748B}.stacked-cell.compact{max-width:220px}.gender-badge{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800}.gender-badge.male{background:#EFF6FF;color:#1D4ED8}.gender-badge.female{background:#FDF2F8;color:#BE185D}.size-list{display:flex;flex-wrap:wrap;gap:4px;max-width:300px}.size-list span{font-size:9px;padding:3px 6px;border:1px solid #E2E8F0;border-radius:5px;background:#F8FAFC;white-space:nowrap}.empty-value{color:#94A3B8;font-size:11px}.error-list{display:flex;flex-direction:column;gap:4px;max-width:280px}.error-list span{display:flex;gap:5px;color:#B91C1C;font-size:10px;line-height:1.35}.valid-row{color:#047857;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:5px}.student-empty{padding:40px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px;color:#64748B}.student-empty i{font-size:28px}.student-pagination{padding:14px 18px;border-top:1px solid #E5E7EB}.student-btn.is-loading{pointer-events:none;opacity:.8}.student-btn.is-loading i{animation:student-spin .8s linear infinite}@keyframes student-spin{to{transform:rotate(360deg)}}@media(max-width:1000px){.student-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.student-summary-item{border-bottom:1px solid #E5E7EB}.student-preview-header{align-items:flex-start}.student-preview-actions{flex-direction:column;align-items:stretch}.student-preview-actions form,.student-btn{width:100%}}@media(max-width:640px){.student-preview-header{flex-direction:column}.student-preview-actions{width:100%;flex-direction:row}.student-summary{grid-template-columns:1fr}.student-summary-item{border-right:0}.student-preview-title h1{font-size:20px}}
.student-pagination{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#F8FAFC}
.student-page-status{display:flex;align-items:center;gap:6px;color:#64748B;font-size:12px}
.student-page-status strong{min-width:30px;height:30px;padding:0 8px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#0F172A;display:inline-flex;align-items:center;justify-content:center;font-size:13px}
.student-page-actions{display:flex;align-items:center;gap:8px}
.student-page-button{height:36px;padding:0 12px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#334155;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:700;transition:background-color .15s,border-color .15s,color .15s}
.student-page-button:hover{border-color:#94A3B8;background:#F1F5F9;color:#0F172A}
.student-page-button.primary{border-color:#B91C1C;background:#B91C1C;color:#fff}
.student-page-button.primary:hover{border-color:#991B1B;background:#991B1B;color:#fff}
.student-page-button.disabled{border-color:#E2E8F0;background:#F8FAFC;color:#94A3B8;cursor:not-allowed}
@media(max-width:640px){.student-pagination{align-items:stretch;flex-direction:column}.student-page-status{justify-content:center}.student-page-actions{display:grid;grid-template-columns:1fr 1fr}.student-page-button{width:100%;padding:0 8px}}
.student-fix-button{margin-top:8px;height:30px;padding:0 9px;border:1px solid #FCA5A5;border-radius:6px;background:#FFF7F7;color:#B91C1C;display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;cursor:pointer}.student-fix-button:hover{background:#FEF2F2;border-color:#EF4444}.student-fix-modal{display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;padding:20px}.student-fix-modal.open{display:flex}.student-fix-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.48)}.student-fix-dialog{position:relative;width:min(720px,100%);max-height:calc(100vh - 40px);overflow:auto;border:1px solid #E2E8F0;border-radius:12px;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.22)}.student-fix-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:22px 24px 18px;border-bottom:1px solid #E5E7EB}.student-fix-header h2{margin:3px 0 5px;color:#111827;font-size:21px}.student-fix-header p{margin:0;color:#64748B;font-size:13px;line-height:1.45}.student-fix-close{width:34px;height:34px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#475569;font-size:18px;cursor:pointer}.student-fix-body{padding:20px 24px}.student-fix-notice{display:flex;gap:8px;align-items:flex-start;padding:11px 12px;margin-bottom:18px;border:1px solid #BFDBFE;border-radius:8px;background:#EFF6FF;color:#1D4ED8;font-size:12px;line-height:1.45}.student-fix-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.student-fix-field{display:flex;flex-direction:column;gap:6px}.student-fix-field.wide{grid-column:span 2}.student-fix-field span{font-size:12px;color:#475569;font-weight:800}.student-fix-field b{color:#B91C1C}.student-fix-field input,.student-fix-field select{width:100%;height:39px;padding:0 10px;border:1px solid #CBD5E1;border-radius:7px;background:#fff;color:#1F2937;font-size:13px;outline:none}.student-fix-field input:focus,.student-fix-field select:focus{border-color:#B91C1C;box-shadow:0 0 0 3px rgba(185,28,28,.10)}.student-fix-section{margin-top:20px;padding-top:16px;border-top:1px solid #E5E7EB}.student-fix-section-title{display:flex;align-items:baseline;gap:8px;margin-bottom:12px}.student-fix-section-title span{font-size:13px;color:#1F2937;font-weight:800}.student-fix-section-title small{color:#64748B;font-size:11px}.student-fix-size-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.student-fix-footer{display:flex;justify-content:flex-end;gap:9px;padding:15px 24px;border-top:1px solid #E5E7EB;background:#F8FAFC}.student-fix-footer .student-btn{min-width:130px}.student-fix-submit-loading{pointer-events:none;opacity:.75}.student-fix-submit-loading i{animation:student-spin .8s linear infinite}@media(max-width:640px){.student-fix-modal{padding:10px}.student-fix-header,.student-fix-body,.student-fix-footer{padding-left:16px;padding-right:16px}.student-fix-grid,.student-fix-size-grid{grid-template-columns:1fr}.student-fix-field.wide{grid-column:span 1}.student-fix-footer{position:sticky;bottom:0}}
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

function openStudentFixModal(button) {
    const row = JSON.parse(atob(button.dataset.fixRow));
    const modal = document.getElementById('studentFixModal');
    document.getElementById('student_fix_row_number').value = row.row_number || '';
    document.getElementById('student_fix_full_name').value = row.full_name || '';
    document.getElementById('student_fix_nrp').value = row.nrp || '';
    document.getElementById('student_fix_gender').value = row.gender || 'L';
    document.getElementById('student_fix_rank_id').value = row.rank_id || '';
    document.getElementById('student_fix_golongan').value = row.golongan || '';
    document.getElementById('student_fix_jabatan').value = row.jabatan || '';
    document.getElementById('student_fix_bagian').value = row.bagian || '';
    document.getElementById('student_fix_keterangan').value = row.keterangan || '';
    const sizes = row.sizes || {};
    Object.keys(sizes).forEach((key) => {
        const input = document.getElementById('student_fix_size_' + key);
        if (input) input.value = sizes[key] || '';
    });
    document.querySelectorAll('[id^="student_fix_size_"]').forEach((input) => {
        if (!Object.prototype.hasOwnProperty.call(sizes, input.id.replace('student_fix_size_', ''))) input.value = '';
    });
    document.getElementById('studentFixSubtitle').textContent =
        'Baris ' + (row.row_number || '-') + ': lengkapi data yang salah, lalu simpan untuk memeriksa ulang seluruh file.';
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.getElementById('student_fix_full_name').focus();
}

function closeStudentFixModal() {
    const modal = document.getElementById('studentFixModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('studentFixForm')?.addEventListener('submit', function () {
    const button = document.getElementById('studentFixSubmitButton');
    button.classList.add('student-fix-submit-loading');
    button.disabled = true;
    button.querySelector('i').className = 'ri-loader-4-line';
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeStudentFixModal();
});
</script>
@endsection
