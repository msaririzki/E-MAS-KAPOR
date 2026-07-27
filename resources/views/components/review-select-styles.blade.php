<style>
    .review-select-shell {
        position: relative;
        min-width: 180px;
        font-family: 'Outfit', sans-serif;
    }

    .review-select-shell.has-leading-icon > i {
        position: absolute;
        left: 13px;
        top: 50%;
        z-index: 3;
        transform: translateY(-50%);
        color: #b91c1c;
        font-size: 17px;
        pointer-events: none;
    }

    .review-select-shell .review-modern-select {
        width: 100%;
        height: 44px;
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        padding: 0 38px 0 13px;
        background: #fff;
        color: #1e293b;
        font-size: 13px;
        font-weight: 700;
    }

    .review-select-shell.has-leading-icon .review-modern-select {
        padding-left: 40px;
    }

    .review-select-shell .select2-container {
        width: 100% !important;
        display: block;
    }

    .review-select-shell .select2-container--default .select2-selection--single {
        height: 44px;
        display: flex;
        align-items: center;
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .035);
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .review-select-shell .select2-container--default:hover .select2-selection--single {
        border-color: #cbd5e1;
        background: #fdfefe;
    }

    .review-select-shell .select2-container--default.select2-container--focus .select2-selection--single,
    .review-select-shell .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #b91c1c;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, .09);
    }

    .review-select-shell .select2-container--default .select2-selection--single .select2-selection__rendered {
        width: 100%;
        padding: 0 40px 0 13px;
        color: #1e293b;
        font-size: 13px;
        font-weight: 700;
        line-height: 42px;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .review-select-shell.has-leading-icon .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-left: 40px;
    }

    .review-select-shell .select2-container--default .select2-selection--single .select2-selection__arrow {
        width: 34px;
        height: 42px;
        right: 4px;
    }

    .review-select-shell .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #64748b transparent transparent transparent;
        border-width: 5px 4px 0 4px;
        margin-left: -4px;
        margin-top: -2px;
    }

    .review-select-shell .select2-container--default.select2-container--open .select2-selection__arrow b {
        border-color: transparent transparent #b91c1c transparent;
        border-width: 0 4px 5px 4px;
    }

    .review-modern-select-dropdown {
        margin-top: 6px;
        padding: 5px;
        overflow: hidden;
        border: 1px solid #dbe2ea !important;
        border-radius: 8px !important;
        background: #fff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, .14);
        font-family: 'Outfit', sans-serif;
        z-index: 3000;
    }

    .review-modern-select-dropdown.select2-dropdown--above {
        margin-top: -6px;
        margin-bottom: 6px;
    }

    .review-modern-select-dropdown > .select2-results > .select2-results__options {
        max-height: 260px !important;
    }

    .review-modern-select-dropdown .select2-results__option--selectable {
        min-height: 38px;
        display: flex;
        align-items: center;
        margin: 1px 0;
        padding: 9px 11px;
        border-radius: 6px;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        white-space: normal;
    }

    .review-modern-select-dropdown .select2-results__option[role='group'] {
        display: block;
        margin: 0;
        padding: 0;
    }

    .review-modern-select-dropdown .select2-results__group {
        display: block;
        padding: 9px 10px 5px;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .review-modern-select-dropdown .select2-results__options--nested {
        max-height: none !important;
        overflow: visible;
        padding: 0;
    }

    .review-modern-select-dropdown .select2-results__option--highlighted.select2-results__option--selectable {
        background: #f8fafc;
        color: #0f172a;
    }

    .review-modern-select-dropdown .select2-results__option--selected {
        position: relative;
        padding-right: 34px;
        background: #fef2f2;
        color: #b91c1c;
        font-weight: 800;
    }

    .review-modern-select-dropdown .select2-results__option--selected::after {
        content: '\EB7B';
        position: absolute;
        right: 11px;
        font-family: 'remixicon';
        font-size: 16px;
    }

    .review-modern-select-dropdown .select2-search--dropdown {
        padding: 5px 5px 8px;
    }

    .review-modern-select-dropdown .select2-search__field {
        height: 38px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 7px;
        padding: 0 11px !important;
        color: #1e293b;
        font-size: 13px;
        outline: none;
    }

    .review-modern-select-dropdown .select2-search__field:focus {
        border-color: #b91c1c !important;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, .08);
    }

    .review-filter-field {
        display: grid;
        gap: 5px;
        min-width: 0;
    }

    .review-filter-field > span {
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .review-year-form {
        min-width: 190px;
        margin: 0;
    }

    @media (max-width: 760px) {
        .review-year-form,
        .review-year-form .review-select-shell {
            width: 100%;
        }

        .review-select-shell {
            min-width: 0;
        }
    }
</style>
