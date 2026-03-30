@extends('layouts.app')

@section('title', 'Laporan Pengeluaran Gudang')
@section('breadcrumb', 'Data Gudang / Laporan Pengeluaran')

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Laporan Pengeluaran</h1>
            <p class="page-subtitle">Riwayat barang keluar dari gudang</p>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px;">
            <a href="{{ route('admin.warehouse-items.reports.export-pdf', request()->all()) }}" class="btn" style="background:#DC2626; color:white; border:none; text-decoration:none;">
                <i class="ri-file-pdf-line"></i> Cetak PDF
            </a>
            <a href="{{ route('admin.warehouse-items.index') }}" class="btn btn-outline">
                <i class="ri-arrow-left-line"></i> Kembali ke Stok
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(1, 1fr) !important; max-width: 400px;">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="ri-upload-cloud-2-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">TOTAL BARANG KELUAR</span>
            <span class="stat-number">{{ number_format($totalItemsOut, 0, ',', '.') }}</span>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('admin.warehouse-items.reports') }}" class="filter-form" style="display:flex; gap:16px;">
        <div class="search-input-wrapper">
            <i class="ri-search-line search-icon"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama barang..." class="search-field" autocomplete="off">
        </div>
        <div class="custom-select-wrapper filter-satker" style="width: 240px;">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span id="filter_satker_label">{{ request('satker_id') ? $satkers->firstWhere('id', request('satker_id'))->name ?? '-- Semua Satker --' : '-- Semua Satker --' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options">
                    <div class="select-search-container">
                        <input type="text" class="select-search-input" placeholder="Cari Satker..." onclick="event.stopPropagation()" onkeyup="filterCustomOptions(this)">
                    </div>
                    <div class="options-scroll">
                        <div class="option {{ !request('satker_id') ? 'selected' : '' }}" data-label="-- Semua Satker --" onclick="selectCustomOptionFilter('satker_id', '', '-- Semua Satker --')">-- Semua Satker --</div>
                        @foreach($satkers as $satker)
                            <div class="option {{ request('satker_id') == $satker->id ? 'selected' : '' }}" data-label="{{ $satker->name }}" onclick="selectCustomOptionFilter('satker_id', '{{ $satker->id }}', '{{ $satker->name }}')">{{ $satker->name }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <input type="hidden" name="satker_id" id="satker_id" value="{{ request('satker_id') }}">
        </div>
        
        <div class="custom-select-wrapper filter-sppm" style="width: 180px;">
            <div class="custom-select" onclick="toggleDropdown(this)">
                <div class="select-trigger">
                    <span id="filter_sppm_label">{{ request('sppm_status') ? request('sppm_status') : 'Semua Status SPPM' }}</span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                <div class="custom-options">
                    <div class="options-scroll">
                        <div class="option {{ !request('sppm_status') ? 'selected' : '' }}" onclick="selectCustomOptionFilter('sppm_status', '', 'Semua Status SPPM')">Semua Status SPPM</div>
                        <div class="option {{ request('sppm_status') == 'Sudah Ada' ? 'selected' : '' }}" onclick="selectCustomOptionFilter('sppm_status', 'Sudah Ada', 'Sudah Ada')">Sudah Ada</div>
                        <div class="option {{ request('sppm_status') == 'Belum Ada' ? 'selected' : '' }}" onclick="selectCustomOptionFilter('sppm_status', 'Belum Ada', 'Belum Ada')">Belum Ada</div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="sppm_status" id="sppm_status" value="{{ request('sppm_status') }}">
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:600; color:#6B7280;">DARI</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-input" style="width:140px; padding:6px 12px; height:36px;">
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:600; color:#6B7280;">Hingga</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-input" style="width:140px; padding:6px 12px; height:36px;">
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <label style="font-size:12px; font-weight:600; color:#6B7280;">Tampilkan</label>
            <select name="per_page" class="form-input" style="width:70px; padding:4px 8px; height:36px;" onchange="this.form.submit()">
                <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:36px; padding:0 16px;">Filter</button>
        @if(request('search') || request('start_date') || request('end_date') || request('per_page'))
            <a href="{{ route('admin.warehouse-items.reports') }}" class="btn btn-outline" style="height:36px; padding:0 16px;">Reset</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="table-container">
    <table class="user-table">
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">NO</th>
                <th>SATKER</th>
                <th>TGL KELUAR</th>
                <th>PENERIMA</th>
                <th style="width: 100px; text-align: center;">JUMLAH BARANG</th>
                <th style="width: 80px; text-align: center;">TOTAL QTY</th>
                <th style="width: 80px; text-align: center;">DETAIL</th>
                <th style="width: 110px; text-align: center;">STATUS SPPM</th>
                <th style="width: 100px; text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($outflows as $index => $group)
                <tr>
                    <td>{{ $outflows->firstItem() + $index }}</td>
                    <td>
                        @if($group->satker)
                            <span style="font-size: 13px; color: #374151; font-weight: 600;">{{ $group->satker->name }}</span>
                        @else
                            <span style="font-size: 13px; color: #6B7280;">-</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($group->outflow_date)->format('d/m/Y') }}</td>
                    <td>{{ $group->recipient_name ?: '-' }}</td>
                    <td style="text-align: center;">
                        <span style="background: #EFF6FF; color: #3B82F6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            {{ $group->item_count }} Item
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: #D97706;">{{ number_format($group->total_quantity, 0, ',', '.') }}</strong>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn btn-outline btn-detail-items" 
                            data-items="{{ $group->items_json }}"
                            style="padding: 6px 12px; font-size: 13px; height: auto; border-color: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="ri-list-check-3"></i> Detail
                        </button>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $groupStatus = $group->group_status;
                            $isSppmAda = ($groupStatus === 'Sudah Ada' || $groupStatus === 'Ada');
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <select onchange="updateSppm('{{ $group->group_ids }}', this.value)" class="form-input" style="width: 105px; padding: 2px 4px; font-size: 12px; font-weight: 700; border-radius: 6px; {{ $isSppmAda ? 'color: #059669; border-color: #059669; background: #ECFDF5;' : 'color: #DC2626; border-color: #DC2626; background: #FEF2F2;' }}" {{ auth()->user()->hasRole('admin_gudang') ? 'disabled' : '' }}>
                                <option value="Belum Ada" {{ ! $isSppmAda ? 'selected' : '' }}>Belum</option>
                                <option value="Sudah Ada" {{ $isSppmAda ? 'selected' : '' }}>Sudah</option>
                            </select>
                        </div>
                    </td>
                    <td style="display:flex; gap:6px; align-items:center; justify-content: center;">
                        @if(! $isSppmAda && auth()->user()->hasRole('superadmin'))
                        <button type="button" class="btn btn-outline" onclick="openSppmModal('{{ $group->group_ids }}')"
                            style="border-color: #3B82F6; color: #3B82F6; padding: 6px; font-size: 16px; height: auto;"
                            title="Buat SPPM (Word)">
                            <i class="ri-file-add-line"></i>
                        </button>
                        @endif
                        <form action="{{ route('admin.warehouse-items.reports.cancel', $group->group_ids) }}" method="POST" class="cancel-form" id="cancel-form-{{ str_replace(',', '-', $group->group_ids) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline btn-cancel" id="cancel-btn-{{ str_replace(',', '-', $group->group_ids) }}"
                                data-ids="{{ $group->group_ids }}"
                                style="border-color: #DC2626; color: #DC2626; padding: 6px; font-size: 16px; height: auto; {{ $isSppmAda ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' }}"
                                {{ $isSppmAda ? 'disabled title="Tidak dapat dibatalkan karena SPPM sudah ada"' : '' }}>
                                <i class="ri-arrow-go-back-line"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.warehouse-items.reports.destroy', $group->group_ids) }}" method="POST" class="delete-form" id="delete-form-{{ str_replace(',', '-', $group->group_ids) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline btn-delete" 
                                data-ids="{{ $group->group_ids }}"
                                style="border-color: #9CA3AF; color: #4B5563; padding: 4px; font-size: 14px; height: auto;" title="Hapus Riwayat Permanen">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #9CA3AF; padding: 32px;">Belum ada riwayat pengeluaran barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($outflows->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6;">
            {{ $outflows->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<!-- SweetAlert2 Plugin -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cancelButtons = document.querySelectorAll('.btn-cancel');
        cancelButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form');
                Swal.fire({
                    title: 'Batalkan Pengeluaran?',
                    text: 'Stok akan dikembalikan ke gudang. Tindakan ini tidak dapat diurungkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC2626',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '<i class="ri-check-line" style="margin-right:4px;"></i> Ya, Batalkan!',
                    cancelButtonText: 'Kembali',
                    reverseButtons: true,
                    customClass: {
                        popup: 'modern-swal-popup',
                        title: 'modern-swal-title',
                        confirmButton: 'modern-swal-btn btn-danger',
                        cancelButton: 'modern-swal-btn btn-secondary',
                        actions: 'modern-swal-actions'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('form');
                Swal.fire({
                    title: 'Hapus Riwayat?',
                    text: 'Riwayat pengeluaran ini akan dihapus secara permanen. Stok TIDAK akan dikembalikan.',
                    icon: 'error',
                    input: 'textarea',
                    inputLabel: 'Alasan Penghapusan',
                    inputPlaceholder: 'Masukkan alasan penghapusan di sini...',
                    inputAttributes: {
                        'aria-label': 'Masukkan alasan penghapusan di sini'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#DC2626',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '<i class="ri-delete-bin-line" style="margin-right:4px;"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Alasan penghapusan harus diisi');
                        }
                        return reason;
                    },
                    customClass: {
                        popup: 'modern-swal-popup',
                        title: 'modern-swal-title',
                        confirmButton: 'modern-swal-btn btn-danger',
                        cancelButton: 'modern-swal-btn btn-secondary',
                        actions: 'modern-swal-actions'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reasonInput = document.createElement('input');
                        reasonInput.type = 'hidden';
                        reasonInput.name = 'deletion_reason';
                        reasonInput.value = result.value;
                        form.appendChild(reasonInput);
                        form.submit();
                    }
                });
            });
        });

        const detailButtons = document.querySelectorAll('.btn-detail-items');
        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const items = JSON.parse(this.dataset.items);
                let html = '<div style="text-align: left; font-size: 14px; max-height: 400px; overflow-y: auto; padding: 8px;">';
                items.forEach(item => {
                    html += `<div style="padding: 12px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:10px; height:10px; border-radius:50%; background:#3B82F6;"></div>
                                    <div style="display:flex; flex-direction:column;">
                                        <span style="font-weight: 700; color: #111827; font-size: 15px;">${item.name}</span>
                                        <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                                            <span style="background: #EFF6FF; color:#3B82F6; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700;">Ukuran: ${item.size}</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex; flex-direction:column; align-items:flex-end;">
                                    <span style="color: #059669; font-weight: 800; font-size: 16px;">${item.qty}</span>
                                    <span style="color: #6B7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">${item.unit}</span>
                                </div>
                            </div>`;
                });
                html += '</div>';

                Swal.fire({
                    title: 'Detail Barang dan Ukuran',
                    html: html,
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#3B82F6',
                    width: '500px',
                    customClass: {
                        popup: 'modern-swal-popup',
                        title: 'modern-swal-title',
                        confirmButton: 'modern-swal-btn btn-secondary',
                    }
                });
            });
        });
    });

    // Custom Select Filtering UI
    function toggleDropdown(el) {
        const options = el.querySelector('.custom-options');
        const isOpen = options.style.display === 'block';

        document.querySelectorAll('.custom-options').forEach(opt => opt.style.display = 'none');
        document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));

        if (!isOpen) {
            options.style.display = 'block';
            el.classList.add('active');
        } 
        event.stopPropagation();
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select')) {
            document.querySelectorAll('.custom-options').forEach(opt => {
                opt.style.display = 'none';
            });
            document.querySelectorAll('.custom-select').forEach(sel => sel.classList.remove('active'));
        }
    });

    function selectCustomOptionFilter(inputId, value, label) {
        document.getElementById(inputId).value = value;
        // submit the form directly to filter
        document.querySelector('.filter-form').submit();
    }

    function filterCustomOptions(input) {
        const filter = input.value.toLowerCase();
        const optionsContainer = input.closest('.custom-options');
        const options = optionsContainer.querySelectorAll('.option:not(.select-search-container)');
        
        options.forEach(opt => {
            const text = (opt.dataset.label || '').toLowerCase();
            if (text.includes(filter)) {
                opt.style.display = 'flex';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function openSppmModal(groupIds) {
        Swal.fire({
            title: 'Buat SPPM Baru',
            html: `
                <div style="text-align: left; margin-bottom: 12px;">
                    <label style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block;">Nomor Surat <span style="color:#EF4444">*</span></label>
                    <input type="text" id="sppm_letter_number" class="swal2-input" style="width: 100%; margin: 0; box-sizing: border-box; height: 42px; font-size: 14px; border-radius: 8px;" placeholder="Contoh: B/123/III/2026">
                </div>
                <div style="text-align: left;">
                    <label style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block;">Tanggal Surat <span style="color:#EF4444">*</span></label>
                    <input type="date" id="sppm_letter_date" class="swal2-input" style="width: 100%; margin: 0; box-sizing: border-box; height: 42px; font-size: 14px; border-radius: 8px;" value="{{ date('Y-m-d') }}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="ri-save-line" style="margin-right:4px;"></i> Simpan SPPM',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'modern-swal-popup',
                title: 'modern-swal-title',
                confirmButton: 'modern-swal-btn',
                cancelButton: 'modern-swal-btn btn-secondary',
                actions: 'modern-swal-actions'
            },
            preConfirm: () => {
                const number = document.getElementById('sppm_letter_number').value;
                const date = document.getElementById('sppm_letter_date').value;
                if (!number || !date) {
                    Swal.showValidationMessage('Nomor surat dan tanggal surat harus diisi');
                    return false;
                }
                return { number, date };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit hidden form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.warehouse-items.save-sppm-grouped") }}';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="group_ids" value="${groupIds}">
                    <input type="hidden" name="letter_number" value="${result.value.number}">
                    <input type="hidden" name="letter_date" value="${result.value.date}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function updateSppm(ids, value) {
        const safeId = ids.replace(/,/g, '-');
        const selectElement = document.querySelector(`select[onchange="updateSppm('${ids}', this.value)"]`);

        if (value === 'Sudah Ada') {
            Swal.fire({
                title: 'Ubah Status SPPM?',
                text: 'Apakah Anda yakin ingin mengubah status SPPM menjadi "Sudah Ada" untuk seluruh item dlm transaksi ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '<i class="ri-check-line" style="margin-right:4px;"></i> Ya, Yakin!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'modern-swal-popup',
                    title: 'modern-swal-title',
                    confirmButton: 'modern-swal-btn',
                    cancelButton: 'modern-swal-btn btn-secondary',
                    actions: 'modern-swal-actions'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeSppmUpdate(ids, value, selectElement);
                } else {
                    // Revert to previous value
                    selectElement.value = 'Belum Ada';
                }
            });
        } else {
            executeSppmUpdate(ids, value, selectElement);
        }
    }

    function executeSppmUpdate(ids, value, selectElement) {
        const safeId = ids.replace(/,/g, '-');
        fetch(`/admin/warehouse-items/reports/${ids}/sppm`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ reference_note: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Status SPPM diperbarui',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    customClass: { popup: 'colored-toast swal2-icon-success' }
                });
                // Update select color and action availability
                const cancelBtn = document.getElementById(`cancel-btn-${safeId}`);

                if (value === 'Sudah Ada') {
                    selectElement.style.color = '#059669';
                    selectElement.style.borderColor = '#059669';
                    selectElement.style.backgroundColor = '#ECFDF5';
                    if(cancelBtn) {
                        cancelBtn.disabled = true;
                        cancelBtn.style.opacity = '0.5';
                        cancelBtn.style.cursor = 'not-allowed';
                        cancelBtn.style.pointerEvents = 'none';
                        cancelBtn.title = 'Tidak dapat dibatalkan karena SPPM sudah ada';
                    }
                } else {
                    selectElement.style.color = '#DC2626';
                    selectElement.style.borderColor = '#DC2626';
                    selectElement.style.backgroundColor = '#FEF2F2';
                    if(cancelBtn) {
                        cancelBtn.disabled = false;
                        cancelBtn.style.opacity = '1';
                        cancelBtn.style.cursor = 'pointer';
                        cancelBtn.style.pointerEvents = 'auto';
                        cancelBtn.removeAttribute('title');
                    }
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memperbarui',
                    text: data.message || 'Terjadi kesalahan sistem.'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal memperbarui',
                text: 'Koneksi bermasalah atau pengaturan server salah.'
            });
        });
    }
</script>
@endsection

@section('styles')
<style>
    .page-title { font-size: 24px; font-weight: 700; color: #111827; }
    .page-subtitle { color: #6B7280; font-size: 14px; margin-top: 4px; }
    .page-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
    .page-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; }
    
    .stats-grid { display: grid; gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #F3F4F6; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; }
    .stat-number { font-size: 24px; font-weight: 800; color: #111827; }
    
    .icon-blue { background: #EFF6FF; color: #3B82F6; }

    .table-container { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 2px rgba(0,0,0,0.02);}
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #F9FAFB; padding: 12px 24px; text-align: left; font-size: 12px; font-weight: 600; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
    .user-table td { padding: 16px 24px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; color: #374151; font-size: 14px; }
    
    .form-input { padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; outline: none; transition: border-color .15s;}
    .form-input:focus { border-color: #3B82F6; ring: 2px solid #3B82F6; }

    /* Filter Bar */
    .filter-bar { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .search-input-wrapper { flex: 1; position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 14px; color: #9CA3AF; font-size: 18px; pointer-events: none; }
    .search-field { width: 100%; height: 36px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 16px 0 38px; font-size: 14px; color: #374151; outline: none; background: #fff; }
    .search-field::placeholder { color: #9CA3AF; }

    /* Modern Select Styling */
    .custom-select-wrapper { position: relative; width: 100%; }
    
    .custom-select {
        background: #fff; border: 1px solid #D1D5DB; border-radius: 8px; cursor: pointer;
        position: relative; transition: all 0.2s ease; height: 36px; display: flex; align-items: center;
    }
    .custom-select:hover { border-color: #9CA3AF; }
    .custom-select.active { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }

    .select-trigger {
        width: 100%; padding: 0 16px; display: flex; justify-content: space-between; align-items: center;
        font-weight: 500; color: #374151; font-size: 13px;
    }
    .select-trigger i { color: #6B7280; font-size: 18px; transition: transform 0.2s ease; }
    .custom-select.active .select-trigger { color: #111827; }
    .custom-select.active .select-trigger i { transform: rotate(180deg); color: #3B82F6; }

    /* Dropdown UI */
    .custom-options {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1px solid #F3F4F6; border-radius: 12px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); z-index: 2000;
        display: none; flex-direction: column; padding: 6px;
    }
    .options-scroll { max-height: 240px; overflow-y: auto; padding-right: 2px; }
    .options-scroll::-webkit-scrollbar { width: 4px; }
    .options-scroll::-webkit-scrollbar-track { background: transparent; }
    .options-scroll::-webkit-scrollbar-thumb { background-color: #E5E7EB; border-radius: 10px; }
    .options-scroll::-webkit-scrollbar-thumb:hover { background-color: #D1D5DB; }

    /* Option Item UI */
    .option {
        padding: 8px 12px; cursor: pointer; transition: all 0.15s; font-size: 13px;
        color: #4B5563; border-radius: 8px; margin-bottom: 2px; font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }
    .option:last-child { margin-bottom: 0; }
    .option:hover { background-color: #F9FAFB; color: #111827; }
    .option.selected { background-color: #F3F4F6; color: #111827; font-weight: 600;}

    /* Select Search UI */
    .select-search-container {
        padding: 4px; position: sticky; top: 0; background: #fff; z-index: 10;
        border-bottom: 1px solid #F3F4F6; margin-bottom: 4px;
    }
    .select-search-input {
        width: 100%; height: 32px; padding: 0 12px; border: 1px solid #E5E7EB;
        border-radius: 6px; font-size: 12px; outline: none; background: #F9FAFB; transition: all 0.2s;
    }
    .select-search-input:focus { border-color: #3B82F6; background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

    /* Modern SweetAlert Custom Styles */
    .modern-swal-popup {
        border-radius: 16px !important;
        padding: 24px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    }
    .modern-swal-title {
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #111827 !important;
    }
    div:where(.swal2-container) div:where(.swal2-html-container) {
        color: #4B5563 !important;
        font-size: 15px !important;
        margin-top: 12px !important;
    }
    .modern-swal-actions {
        margin-top: 24px !important;
        gap: 12px;
    }
    .modern-swal-btn {
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 10px 24px !important;
        font-size: 14px !important;
        letter-spacing: 0.3px;
        transition: all 0.2s;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .modern-swal-btn.btn-danger {
        background-color: #DC2626 !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2) !important;
    }
    .modern-swal-btn.btn-danger:hover { background-color: #B91C1C !important; transform: translateY(-1px); }
    .modern-swal-btn.btn-secondary {
        background-color: #F3F4F6 !important;
        color: #374151 !important;
        border: 1px solid #E5E7EB !important;
    }
    .modern-swal-btn.btn-secondary:hover { background-color: #E5E7EB !important; }

    /* Toast customizations */
    .colored-toast.swal2-icon-success { background-color: #ECFDF5 !important; color: #059669 !important; border: 1px solid #A7F3D0 !important; }
    .colored-toast .swal2-title { color: inherit !important; font-size: 14px !important;}
</style>
@endsection
