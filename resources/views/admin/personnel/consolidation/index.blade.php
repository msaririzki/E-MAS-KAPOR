@extends('layouts.app')

@section('title', 'Konsolidasi Personel')
@section('breadcrumb', 'Konsolidasi Personel')

@section('content')
<div class="consolidation-header">
    <div class="consolidation-title">
        <a href="{{ route('admin.personnel.index') }}" class="icon-back" title="Kembali ke Data Personel">
            <i class="ri-arrow-left-line"></i>
        </a>
        <div>
            <span class="page-eyebrow">DATA PERSONEL SATKER</span>
            <h1>Konsolidasi Personel</h1>
            <p>Gabungkan hasil pemeriksaan unit bawahan dalam satu file Excel.</p>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="consolidation-alert error">
        <i class="ri-error-warning-fill"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if(session('info'))
    <div class="consolidation-alert info">
        <i class="ri-information-fill"></i>
        <span>{{ session('info') }}</span>
    </div>
@endif

<div class="workflow-strip">
    <div class="workflow-step active">
        <span>1</span>
        <div><strong>Unduh data</strong><small>File induk satker</small></div>
    </div>
    <i class="ri-arrow-right-line"></i>
    <div class="workflow-step">
        <span>2</span>
        <div><strong>Gabungkan hasil</strong><small>Urutkan sesuai Bag/Fungsi</small></div>
    </div>
    <i class="ri-arrow-right-line"></i>
    <div class="workflow-step">
        <span>3</span>
        <div><strong>Unggah dan periksa</strong><small>Simpan hanya data aman</small></div>
    </div>
</div>

<div class="consolidation-workspace">
    <section class="workspace-section">
        <div class="section-heading">
            <span class="section-icon download"><i class="ri-file-excel-2-line"></i></span>
            <div>
                <h2>File Induk Personel</h2>
                <p>Berisi data aktif, ukuran kapor, dan kode pencocokan.</p>
            </div>
        </div>

        <form action="{{ route('admin.personnel.consolidation.download') }}" method="GET" class="action-form">
            @if(auth()->user()->hasRole('admin_satker'))
                <input type="hidden" name="satker_id" value="{{ auth()->user()->satker_id }}">
                <div class="selected-satker">
                    <small>SATKER YANG DIKELOLA</small>
                    <strong>{{ $selectedSatker?->name }}</strong>
                </div>
            @else
                <div class="field-group">
                    <label for="download_satker_id">SATKER YANG DIKELOLA</label>
                    <div class="select-control">
                        <i class="ri-building-2-line"></i>
                        <select name="satker_id" id="download_satker_id" required>
                            <option value="">Pilih satker</option>
                            @foreach($satkers as $satker)
                                <option value="{{ $satker->id }}">{{ $satker->name }}</option>
                            @endforeach
                        </select>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </div>
            @endif

            <button type="submit" class="action-button secondary">
                <i class="ri-download-2-line"></i>
                Unduh File Induk
            </button>
        </form>
    </section>

    <div class="workspace-divider"></div>

    <section class="workspace-section">
        <div class="section-heading">
            <span class="section-icon upload"><i class="ri-upload-cloud-2-line"></i></span>
            <div>
                <h2>Unggah File Gabungan</h2>
                <p>File lama tetap dapat dibaca melalui pencocokan NRP/NIP.</p>
            </div>
        </div>

        <form action="{{ route('admin.personnel.consolidation.import') }}" method="POST" enctype="multipart/form-data" id="consolidationUploadForm">
            @csrf
            @if(auth()->user()->hasRole('admin_satker'))
                <input type="hidden" name="satker_id" value="{{ auth()->user()->satker_id }}">
            @else
                <div class="field-group">
                    <label for="upload_satker_id">SATKER TUJUAN</label>
                    <div class="select-control">
                        <i class="ri-building-2-line"></i>
                        <select name="satker_id" id="upload_satker_id" required>
                            <option value="">Pilih satker</option>
                            @foreach($satkers as $satker)
                                <option value="{{ $satker->id }}" @selected(old('satker_id') == $satker->id)>{{ $satker->name }}</option>
                            @endforeach
                        </select>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </div>
            @endif

            <label class="file-drop" for="consolidation_file" id="consolidationDrop">
                <input type="file" name="file" id="consolidation_file" accept=".xlsx,.xls" required>
                <span class="file-drop-icon"><i class="ri-file-excel-2-line"></i></span>
                <span class="file-drop-copy">
                    <strong id="consolidationFileName">Pilih file Excel gabungan</strong>
                    <small id="consolidationFileMeta">Format .xlsx atau .xls, maksimal 50 MB</small>
                </span>
                <span class="file-browse">Pilih File</span>
            </label>

            <div class="safety-note">
                <i class="ri-shield-check-line"></i>
                <span>Data belum berubah saat diunggah. Sistem selalu menampilkan pratinjau sebelum penyimpanan.</span>
            </div>

            <button type="submit" class="action-button primary" id="consolidationUploadButton">
                <i class="ri-search-eye-line"></i>
                <span>Periksa File</span>
            </button>
        </form>
    </section>
</div>
@endsection

@section('styles')
<style>
.consolidation-header{display:flex;align-items:center;justify-content:space-between;padding:18px 0 22px;border-bottom:1px solid #E5E7EB;margin-bottom:18px}.consolidation-title{display:flex;align-items:center;gap:14px}.consolidation-title h1{font-size:24px;line-height:1.2;margin:2px 0 4px;color:#111827;font-weight:800;letter-spacing:0}.consolidation-title p{font-size:13px;margin:0;color:#64748B}.page-eyebrow{font-size:10px;font-weight:800;color:#B91C1C;letter-spacing:.08em}.icon-back{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #D1D5DB;border-radius:8px;background:#fff;color:#374151;text-decoration:none}.icon-back:hover{background:#F8FAFC;border-color:#9CA3AF}.consolidation-alert{display:flex;align-items:center;gap:10px;padding:13px 15px;border:1px solid;border-radius:8px;margin-bottom:16px;font-size:13px}.consolidation-alert i{font-size:19px}.consolidation-alert.error{background:#FEF2F2;border-color:#FECACA;color:#991B1B}.consolidation-alert.info{background:#EFF6FF;border-color:#BFDBFE;color:#1E40AF}.workflow-strip{display:flex;align-items:center;justify-content:center;gap:20px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;padding:14px 20px;margin-bottom:16px}.workflow-strip>i{color:#CBD5E1}.workflow-step{display:flex;align-items:center;gap:9px;min-width:0}.workflow-step>span{width:28px;height:28px;border-radius:50%;background:#F1F5F9;color:#64748B;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800}.workflow-step.active>span{background:#B91C1C;color:#fff}.workflow-step div{display:flex;flex-direction:column}.workflow-step strong{font-size:12px;color:#1F2937}.workflow-step small{font-size:10px;color:#64748B}.consolidation-workspace{display:grid;grid-template-columns:minmax(0,1fr) 1px minmax(0,1fr);gap:28px;border:1px solid #E5E7EB;border-radius:8px;background:#fff;padding:24px}.workspace-divider{background:#E5E7EB}.workspace-section{min-width:0}.section-heading{display:flex;align-items:flex-start;gap:12px;margin-bottom:20px}.section-heading h2{font-size:16px;margin:1px 0 3px;color:#111827;font-weight:800}.section-heading p{font-size:12px;margin:0;color:#64748B}.section-icon{width:38px;height:38px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:19px;flex:0 0 auto}.section-icon.download{background:#ECFDF5;color:#047857}.section-icon.upload{background:#FEF2F2;color:#B91C1C}.action-form{display:flex;flex-direction:column;gap:16px}.selected-satker{border:1px solid #E2E8F0;background:#F8FAFC;border-radius:8px;padding:13px 14px;display:flex;flex-direction:column;gap:3px}.selected-satker small,.field-group label{font-size:10px;color:#64748B;font-weight:800}.selected-satker strong{font-size:14px;color:#0F172A}.field-group{display:flex;flex-direction:column;gap:7px}.select-control{height:44px;display:grid;grid-template-columns:20px 1fr 18px;align-items:center;gap:8px;border:1px solid #CBD5E1;border-radius:8px;padding:0 12px;background:#fff;color:#64748B}.select-control:focus-within{border-color:#B91C1C;box-shadow:0 0 0 3px rgba(185,28,28,.08)}.select-control select{width:100%;height:100%;border:0;outline:0;background:transparent;appearance:none;color:#1F2937;font-size:13px;font-weight:600}.file-drop{min-height:88px;border:1.5px dashed #CBD5E1;border-radius:8px;padding:15px;display:flex;align-items:center;gap:12px;background:#F8FAFC;cursor:pointer}.file-drop:hover{border-color:#94A3B8;background:#F1F5F9}.file-drop input{display:none}.file-drop-icon{width:42px;height:42px;border-radius:8px;background:#DCFCE7;color:#15803D;display:inline-flex;align-items:center;justify-content:center;font-size:22px;flex:0 0 auto}.file-drop-copy{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}.file-drop-copy strong{font-size:12px;color:#1F2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.file-drop-copy small{font-size:10px;color:#64748B}.file-browse{font-size:11px;font-weight:800;color:#B91C1C;white-space:nowrap}.safety-note{display:flex;align-items:flex-start;gap:8px;color:#475569;font-size:11px;line-height:1.45;margin:13px 0}.safety-note i{color:#059669;font-size:16px}.action-button{height:42px;border-radius:8px;border:1px solid;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}.action-button.secondary{background:#fff;border-color:#CBD5E1;color:#334155}.action-button.primary{width:100%;background:#B91C1C;border-color:#B91C1C;color:#fff}.action-button:disabled{opacity:.7;cursor:wait}.action-button.loading i{animation:consolidation-spin .8s linear infinite}@keyframes consolidation-spin{to{transform:rotate(360deg)}}@media(max-width:900px){.consolidation-workspace{grid-template-columns:1fr}.workspace-divider{height:1px}.workflow-strip{justify-content:flex-start;overflow-x:auto}.workflow-step{min-width:150px}}@media(max-width:600px){.consolidation-title h1{font-size:20px}.consolidation-workspace{padding:18px}.file-browse{display:none}.workflow-strip{align-items:stretch;flex-direction:column;gap:8px;overflow:visible}.workflow-strip>i{align-self:center;transform:rotate(90deg)}.workflow-step{min-width:0}}
</style>
@endsection

@section('scripts')
<script>
const consolidationFile = document.getElementById('consolidation_file');
consolidationFile?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    document.getElementById('consolidationFileName').textContent = file.name;
    document.getElementById('consolidationFileMeta').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
});
document.getElementById('consolidationUploadForm')?.addEventListener('submit', function () {
    const button = document.getElementById('consolidationUploadButton');
    button.disabled = true;
    button.classList.add('loading');
    button.querySelector('i').className = 'ri-loader-4-line';
    button.querySelector('span').textContent = 'Membaca dan mencocokkan data...';
});
</script>
@endsection
