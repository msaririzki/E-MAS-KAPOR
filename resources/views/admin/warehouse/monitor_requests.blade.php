@extends('layouts.app')

@section('title', 'Pengajuan Edit Stok')

@section('content')
<div class="content-header">
    <div class="header-left">
        <h1 class="page-title">Monitor Pengajuan</h1>
        <p class="page-subtitle">Pantau status pengajuan Edit Stok dan Pengeluaran Anda</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success" style="padding:15px; margin-bottom:20px; background:#D1FAE5; color:#065F46; border-radius:8px;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="padding:15px; margin-bottom:20px; background:#FEE2E2; color:#B91C1C; border-radius:8px;">
                {{ session('error') }}
            </div>
        @endif

        <div class="custom-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #E5E7EB; padding-bottom: 10px;">
            <button class="tab-btn active" onclick="switchTab('edit_stok')" id="btn_edit_stok" style="padding: 10px 20px; background: transparent; border: none; font-weight: 600; cursor: pointer; color: #D97706; border-bottom: 2px solid #D97706;">Edit Stok</button>
            <button class="tab-btn" onclick="switchTab('pengeluaran')" id="btn_pengeluaran" style="padding: 10px 20px; background: transparent; border: none; font-weight: 600; cursor: pointer; color: #6B7280; border-bottom: 2px solid transparent;">Pengeluaran Barang</button>
        </div>

        <div id="tab_edit_stok" class="tab-content" style="display: block;">
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">TANGGAL PENGAJUAN</th>
                            <th style="width: 25%;">BARANG</th>
                            <th style="width: 15%;">UKURAN</th>
                            <th style="width: 20%;">STOK (LAMA &rarr; BARU)</th>
                            <th>ALASAN</th>
                            <th style="width: 10%;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $req->itemSize->item->name ?? '-' }}</td>
                                <td>{{ $req->itemSize->size_label ?? '-' }}</td>
                                <td>
                                    <span style="text-decoration: line-through; color: #EF4444;">{{ $req->old_stock }}</span>
                                    &rarr; 
                                    <span style="font-weight: bold; color: #10B981;">{{ $req->requested_stock }}</span>
                                </td>
                                <td>{{ $req->reason }}</td>
                                <td>
                                    @if($req->status === 'pending')
                                        <span class="badge" style="background:#FEF3C7; color:#D97706;">PROSES</span>
                                    @elseif($req->status === 'approved')
                                        <span class="badge badge-success">DISETUJUI</span>
                                    @else
                                        <span class="badge badge-danger">DITOLAK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #6B7280;">
                                    Belum ada pengajuan edit stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px;">
                {{ $requests->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>

        <div id="tab_pengeluaran" class="tab-content" style="display: none;">
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">TANGGAL PENGAJUAN</th>
                            <th style="width: 15%;">TUJUAN</th>
                            <th style="width: 15%;">NAMA BARANG</th>
                            <th style="width: 10%;">UKURAN</th>
                            <th style="width: 10%;">JUMLAH</th>
                            <th style="width: 10%;">STATUS</th>
                            <th style="width: 25%;">ALASAN PENOLAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dispenseRequests as $dReq)
                            <tr>
                                <td>{{ $dReq->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @foreach($dReq->parsed_items as $pi)
                                        <div style="white-space: nowrap; margin-bottom: 4px; font-size: 13px;">{{ $pi['tujuan'] }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($dReq->parsed_items as $pi)
                                        <div style="white-space: nowrap; margin-bottom: 4px; font-size: 13px;">{{ $pi['barang'] }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($dReq->parsed_items as $pi)
                                        <div style="white-space: nowrap; margin-bottom: 4px; font-size: 13px;">{{ $pi['ukuran'] }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($dReq->parsed_items as $pi)
                                        <div style="white-space: nowrap; margin-bottom: 4px; font-size: 13px; font-weight: bold;">{{ $pi['jumlah'] }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @if($dReq->status === 'pending')
                                        <span class="badge" style="background:#FEF3C7; color:#D97706;">PROSES</span>
                                    @elseif($dReq->status === 'approved')
                                        <span class="badge badge-success">DISETUJUI</span>
                                    @else
                                        <span class="badge badge-danger">DITOLAK</span>
                                    @endif
                                </td>
                                <td>{{ $dReq->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #6B7280;">
                                    Belum ada pengajuan pengeluaran barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px;">
                {{ $dispenseRequests->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.style.color = '#6B7280';
            btn.style.borderBottom = '2px solid transparent';
        });

        document.getElementById('tab_' + tab).style.display = 'block';
        const activeBtn = document.getElementById('btn_' + tab);
        activeBtn.style.color = '#D97706';
        activeBtn.style.borderBottom = '2px solid #D97706';
        
        // Update URL to remember tab
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.pushState({}, '', url);
    }
    
    // Switch to tab if in URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab && (tab === 'edit_stok' || tab === 'pengeluaran')) {
            switchTab(tab);
        }
    });

    function rejectDispense(id) {
        Swal.fire({
            title: 'Tolak Pengajuan Pengeluaran',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Masukkan alasan...',
            inputAttributes: {
                'aria-label': 'Masukkan alasan'
            },
            showCancelButton: true,
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Alasan penolakan wajib diisi!')
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reason_dispense_' + id).value = result.value;
                document.getElementById('form_reject_dispense_' + id).submit();
            }
        });
    }

    function confirmAction(event, actionText, type) {
        event.preventDefault();
        const form = event.target.closest('form');
        const isApprove = actionText === 'MENYETUJUI';
        
        if (type === 'edit' && !isApprove) {
            Swal.fire({
                title: 'Konfirmasi Penolakan',
                text: "Silakan konfirmasi jika Anda menolak.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: `Apakah Anda yakin ingin ${actionText} pengajuan ini?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Yakin',
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'modern-swal-popup',
                title: 'modern-swal-title',
                confirmButton: 'modern-swal-btn ' + (isApprove ? 'btn-primary' : 'btn-danger'),
                cancelButton: 'modern-swal-btn btn-secondary',
                actions: 'modern-swal-actions'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
