@if($paginator->hasPages())
    <div class="pagination-controls">
        <a href="{{ $paginator->url(1) }}"
            class="page-btn {{ $paginator->onFirstPage() ? 'disabled' : '' }} ajax-link"
            title="Halaman Pertama"
            aria-label="Halaman Pertama">
            <i class="ri-skip-back-line"></i>
        </a>
        <a href="{{ $paginator->previousPageUrl() }}"
            class="page-btn {{ $paginator->onFirstPage() ? 'disabled' : '' }} ajax-link"
            title="Halaman Sebelumnya"
            aria-label="Halaman Sebelumnya">
            <i class="ri-arrow-left-s-line"></i>
        </a>

        <span class="page-info">Halaman <strong>{{ $paginator->currentPage() }}</strong> dari <strong>{{ $paginator->lastPage() }}</strong></span>

        <a href="{{ $paginator->nextPageUrl() }}"
            class="page-btn {{ ! $paginator->hasMorePages() ? 'disabled' : '' }} ajax-link"
            title="Halaman Selanjutnya"
            aria-label="Halaman Selanjutnya">
            <i class="ri-arrow-right-s-line"></i>
        </a>
        <a href="{{ $paginator->url($paginator->lastPage()) }}"
            class="page-btn {{ ! $paginator->hasMorePages() ? 'disabled' : '' }} ajax-link"
            title="Halaman Terakhir"
            aria-label="Halaman Terakhir">
            <i class="ri-skip-forward-line"></i>
        </a>
    </div>
@endif
