<div class="table-responsive">
    <table class="user-table">
        <thead>
            <tr>
                <th style="border-top-left-radius: 12px; width: 60px;">NO</th>
                <th>NAMA ITEM</th>
                <th>KATEGORI</th>
                <th>HARGA SATUAN</th>
                <th>SATUAN</th>
                <th>GROUP INVOICE</th>
                <th>STATUS</th>
                <th style="border-top-right-radius: 12px; text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
            <tr style="{{ !$item->is_active ? 'opacity: 0.5; background: #F9FAFB;' : '' }}">
                <td>{{ $items->firstItem() + $index }}</td>
                <td>
                    <div class="user-info">
                        <div class="details">
                            <span class="name">{{ $item->item_name }}</span>
                            @if($item->description)
                                <span class="nrp" style="font-size: 11px;">{{ $item->description }}</span>
                            @endif
                            @if($item->gender_specific)
                                <span style="font-size: 10px; color: {{ $item->gender_specific == 'L' ? '#3B82F6' : '#EC4899' }}; font-weight: 600;">
                                    <i class="ri-{{ $item->gender_specific == 'L' ? 'men' : 'women' }}-line"></i>
                                    {{ $item->gender_specific == 'L' ? 'Pria' : 'Wanita' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <span class="role-pill" style="
                        background: {{ $item->category == 'Tutup_Kepala' ? '#DBEAFE' : ($item->category == 'Tutup_Badan' ? '#F3E8FF' : ($item->category == 'Tutup_Kaki' ? '#FFEDD5' : '#F0FDF4')) }};
                        color: {{ $item->category == 'Tutup_Kepala' ? '#1E40AF' : ($item->category == 'Tutup_Badan' ? '#6B21A8' : ($item->category == 'Tutup_Kaki' ? '#9A3412' : '#166534')) }};
                    ">
                        {{ str_replace('_', ' ', $item->category) }}
                    </span>
                </td>
                <td>
                    @if($item->price)
                        <span style="font-weight: 600; color: #059669;">{{ $item->formatted_price }}</span>
                    @else
                        <span style="color: #D1D5DB; font-size: 12px;">Belum diatur</span>
                    @endif
                </td>
                <td>
                    <span style="font-size: 12px; font-weight: 500; color: #6B7280;">{{ $item->unit ?? 'PCS' }}</span>
                </td>
                <td>
                    @if($item->invoice_group)
                        <span class="role-pill" style="background: #FEF3C7; color: #92400E; font-size: 10px;">
                            {{ $item->invoice_group }}
                        </span>
                    @else
                        <span style="color: #D1D5DB; font-size: 12px;">-</span>
                    @endif
                </td>
                <td>
                    <span class="role-pill" style="
                        background: {{ $item->is_active ? '#DCFCE7' : '#FEE2E2' }};
                        color: {{ $item->is_active ? '#166534' : '#991B1B' }};
                    ">
                        {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon blue" onclick="openEditModal({{ json_encode($item) }})">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button class="btn-icon red" onclick="confirmDelete({{ $item->id }}, '{{ $item->item_name }}')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 48px; color: #9CA3AF;">
                    <i class="ri-inbox-line" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                    Belum ada data item kapor.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($items->total() > 0)
    <div class="table-footer">
        <div class="footer-left">
            <span class="text-sm text-gray-500">
                Menampilkan {{ $items->firstItem() }} hingga {{ $items->lastItem() }} dari {{ $items->total() }} data
            </span>
            
            <div class="per-page-selector">
                <select onchange="changePerPage(this.value)" 
                        style="border: 1px solid #E5E7EB; border-radius: 8px; padding: 4px 8px; font-size: 13px; color: #374151; outline: none; cursor: pointer; background-color: #fff; margin-left:12px;">
                    <option value="10" {{ $items->perPage() == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $items->perPage() == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $items->perPage() == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $items->perPage() == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="pagination-controls">
                <a href="{{ $items->url(1) }}" class="page-btn {{ $items->onFirstPage() ? 'disabled' : '' }} ajax-link" title="Halaman Pertama">
                    <i class="ri-skip-back-line"></i>
                </a>
                <a href="{{ $items->previousPageUrl() }}" class="page-btn {{ $items->onFirstPage() ? 'disabled' : '' }} ajax-link" title="Halaman Sebelumnya">
                    <i class="ri-arrow-left-s-line"></i>
                </a>
                
                <span class="page-info">Halaman <strong>{{ $items->currentPage() }}</strong> dari <strong>{{ $items->lastPage() }}</strong></span>
                
                <a href="{{ $items->nextPageUrl() }}" class="page-btn {{ !$items->hasMorePages() ? 'disabled' : '' }} ajax-link" title="Halaman Selanjutnya">
                    <i class="ri-arrow-right-s-line"></i>
                </a>
                <a href="{{ $items->url($items->lastPage()) }}" class="page-btn {{ !$items->hasMorePages() ? 'disabled' : '' }} ajax-link" title="Halaman Terakhir">
                    <i class="ri-skip-forward-line"></i>
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
