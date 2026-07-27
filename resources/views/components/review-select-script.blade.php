<script>
    (() => {
        const initializeReviewSelects = (root = document) => {
            if (!window.jQuery || !window.jQuery.fn?.select2) {
                return;
            }

            root.querySelectorAll('select.review-modern-select').forEach((select) => {
                const $select = window.jQuery(select);

                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                const searchable = select.dataset.search === 'true';

                $select.select2({
                    width: '100%',
                    minimumResultsForSearch: searchable ? 0 : Infinity,
                    dropdownCssClass: 'review-modern-select-dropdown',
                    language: {
                        noResults: () => 'Pilihan tidak ditemukan',
                        searching: () => 'Mencari...',
                    },
                });

                $select.on('select2:open.reviewSelect', () => {
                    const search = document.querySelector('.select2-container--open .select2-search__field');
                    if (search && searchable) {
                        search.placeholder = select.dataset.searchPlaceholder || 'Cari pilihan...';
                        window.setTimeout(() => search.focus(), 0);
                    }
                });
            });
        };

        initializeReviewSelects();
    })();
</script>
