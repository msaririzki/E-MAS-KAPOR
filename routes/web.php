<?php

use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonilPortalController;
use App\Http\Controllers\SatkerController;
use App\Http\Controllers\Superadmin\StatisticsController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/* |-------------------------------------------------------------------------- | SI-KAPOR Polda NTB — Web Routes |-------------------------------------------------------------------------- */

// ── Public / Auth Routes ──────────────────────────────────────────────
Route::get('/', function () {
    return response()
        ->view('public.index')
        ->header('Cache-Control', 'public, max-age=300, s-maxage=3600');
})->withoutMiddleware([
    AddQueuedCookiesToResponse::class,
    ValidateCsrfToken::class,
    StartSession::class,
    ShareErrorsFromSession::class,
])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated Routes (Semua Role) ──────────────────────────

Route::middleware(['auth', 'read.only', 'satker.scope'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    Route::post('/profile/theme', [\App\Http\Controllers\Admin\ProfileController::class, 'updateTheme'])->name('profile.updateTheme');
});

// ── Personil Routes ──────────────────────────────────────────────────

Route::middleware(['auth', 'role:personil'])->prefix('personil')->name('personil.')->group(function () {
    Route::get('/kapor', fn () => redirect()->route('dashboard'))->name('kapor.index');
    Route::post('/kapor', [PersonilPortalController::class, 'storeKapor'])->middleware('system.lock')->name('kapor.store');
    Route::get('/testimoni', [PersonilPortalController::class, 'showTestimoni'])->name('testimoni.index');
    Route::post('/testimoni', [PersonilPortalController::class, 'storeTestimoni'])->middleware('review.period')->name('testimoni.store');
    Route::get('/kapor/riwayat', [PersonilPortalController::class, 'showHistory'])->name('kapor.history');
});

// ── Admin Satker Routes ──────────────────────────────────────────────

Route::middleware(['auth', 'satker.write.lock', 'role:admin_satker', \App\Http\Middleware\SatkerScope::class, 'system.lock'])->prefix('admin-satker')->name('admin-satker.')->group(function () {
    Route::get('/monitor', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'monitor'])->name('monitor');
    Route::get('/reports', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'reports'])->name('reports');
    Route::get('/allocations', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'allocations'])->name('allocations');
    Route::get('/allocations/export-pdf', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'allocationsExportPdf'])->name('allocations.export-pdf');
    Route::get('/settings', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'settings'])->name('settings');
    Route::put('/settings/signatory', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'updateSignatorySettings'])->name('settings.signatory.update');

    // Identifikasi Kebutuhan (Admin Satker) dibuka sebagai fase terpisah dari input data personel.
    Route::withoutMiddleware('system.lock')->group(function () {
        Route::get('/kebutuhan', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'index'])->name('kebutuhan.index');
        Route::get('/kebutuhan/create', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'create'])->name('kebutuhan.create');
        Route::post('/kebutuhan', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'store'])->name('kebutuhan.store');
        Route::get('/kebutuhan/{kebutuhan}', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'show'])->name('kebutuhan.show');
        Route::get('/kebutuhan/{kebutuhan}/edit', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'edit'])->name('kebutuhan.edit');
        Route::put('/kebutuhan/{kebutuhan}', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'update'])->name('kebutuhan.update');
        Route::delete('/kebutuhan/{kebutuhan}', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'destroy'])->name('kebutuhan.destroy');
        Route::post('/kebutuhan/{kebutuhan}/submit', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'submit'])->name('kebutuhan.submit');
        Route::get('/kebutuhan/{kebutuhan}/print', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'printPdf'])->name('kebutuhan.print');
        Route::get('/kebutuhan/{kebutuhan}/export-excel', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'exportExcel'])->name('kebutuhan.export-excel');
        Route::get('/kebutuhan/{kebutuhan}/export-pdf', [\App\Http\Controllers\AdminSatker\KebutuhanController::class, 'exportPdf'])->name('kebutuhan.export-pdf');
    });
});

// ── Admin Central Routes ──────────────────────────────────────────────

Route::middleware(['auth', 'satker.write.lock', 'read.only', 'role:admin|superadmin|kabak_bekum|admin_gudang|kepala_gudang|admin_satker', 'satker.scope'])->prefix('admin')->name('admin.')->group(function () {

    // User Management
    Route::get('/users/template', [\App\Http\Controllers\UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/users/import', [\App\Http\Controllers\UserController::class, 'import'])->name('users.import');
    Route::post('/users/bulk-admin-satker', [\App\Http\Controllers\UserController::class, 'bulkCreateAdminSatker'])->name('users.bulk-admin-satker');
    Route::delete('/users/bulk-admin-satker', [\App\Http\Controllers\UserController::class, 'bulkDeleteAdminSatker'])->name('users.bulk-delete-admin-satker');
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserStatusController::class, 'toggle'])->name('users.toggle-status');

    // Personnel Management
    Route::get('/personnel/template', [\App\Http\Controllers\Admin\PersonnelController::class, 'downloadTemplate'])->name('personnel.template');
    Route::post('/personnel/import', [\App\Http\Controllers\Admin\PersonnelController::class, 'import'])->name('personnel.import');
    Route::get('/personnel/import-preview', [\App\Http\Controllers\Admin\PersonnelController::class, 'importPreview'])->name('personnel.import-preview');
    Route::post('/personnel/import-confirm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importConfirm'])->name('personnel.import-confirm');
    Route::post('/personnel/import-cancel', [\App\Http\Controllers\Admin\PersonnelController::class, 'importCancel'])->name('personnel.import-cancel');
    Route::delete('/personnel/bulk-delete-all', [\App\Http\Controllers\Admin\PersonnelController::class, 'bulkDeleteAll'])->name('personnel.bulk-delete-all');
    Route::delete('/personnel/bulk-delete', [\App\Http\Controllers\Admin\PersonnelController::class, 'bulkDeleteBySatker'])->name('personnel.bulk-delete');

    // NRP Issues
    Route::get('/personnel/nrp-issues', [\App\Http\Controllers\Admin\PersonnelController::class, 'nrpIssues'])->name('personnel.nrp-issues');
    Route::post('/personnel/{personnel}/resolve-nrp', [\App\Http\Controllers\Admin\PersonnelController::class, 'resolveNrpIssue'])->name('personnel.resolve-nrp');

    Route::get('/personnel/export-rekap', [\App\Http\Controllers\Admin\PersonnelController::class, 'exportRekap'])->name('personnel.export-rekap');
    Route::get('/personnel/export-personnel', [\App\Http\Controllers\Admin\PersonnelController::class, 'exportPersonnel'])->name('personnel.export-personnel');
    Route::get('/personnel/export-keterangan', [\App\Http\Controllers\Admin\PersonnelController::class, 'exportKeterangan'])->name('personnel.export-keterangan');
    Route::post('/personnel/import-update', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdate'])->name('personnel.import-update');
    Route::get('/personnel/import-update-preview', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdatePreview'])->name('personnel.import-update-preview');
    Route::post('/personnel/import-update-confirm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdateConfirm'])->name('personnel.import-update-confirm');
    Route::post('/personnel/import-update-cancel', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdateCancel'])->name('personnel.import-update-cancel');
    Route::get('/personnel/consolidation', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'index'])->name('personnel.consolidation.index');
    Route::get('/personnel/consolidation/download', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'download'])->name('personnel.consolidation.download');
    Route::post('/personnel/consolidation/import', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'import'])->name('personnel.consolidation.import');
    Route::get('/personnel/consolidation/preview', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'preview'])->name('personnel.consolidation.preview');
    Route::post('/personnel/consolidation/fix-row', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'fixRow'])->name('personnel.consolidation.fix-row');
    Route::post('/personnel/consolidation/confirm', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'confirm'])->name('personnel.consolidation.confirm');
    Route::post('/personnel/consolidation/cancel', [\App\Http\Controllers\Admin\PersonnelConsolidationController::class, 'cancel'])->name('personnel.consolidation.cancel');
    Route::post('/personnel/request-transfer', [\App\Http\Controllers\Admin\PersonnelController::class, 'requestTransfer'])->name('personnel.request-transfer');
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/personnel/transfer-requests', [\App\Http\Controllers\Admin\PersonnelTransferRequestController::class, 'index'])->name('personnel.transfer-requests.index');
        Route::post('/personnel/transfer-requests/review', [\App\Http\Controllers\Admin\PersonnelTransferRequestController::class, 'review'])->name('personnel.transfer-requests.review');
    });
    Route::post('/personnel/import-keterangan', [\App\Http\Controllers\Admin\PersonnelController::class, 'importKeterangan'])->name('personnel.import-keterangan');
    Route::get('/personnel/import-keterangan-preview', [\App\Http\Controllers\Admin\PersonnelController::class, 'importKeteranganPreview'])->name('personnel.import-keterangan-preview');
    Route::post('/personnel/import-keterangan-confirm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importKeteranganConfirm'])->name('personnel.import-keterangan-confirm');
    Route::post('/personnel/import-keterangan-cancel', [\App\Http\Controllers\Admin\PersonnelController::class, 'importKeteranganCancel'])->name('personnel.import-keterangan-cancel');

    // Import Data SDM (Super Admin Only)
    Route::post('/personnel/import-sdm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importSdm'])->name('personnel.import-sdm');
    Route::get('/personnel/import-sdm-preview', [\App\Http\Controllers\Admin\PersonnelController::class, 'importSdmPreview'])->name('personnel.import-sdm-preview');
    Route::get('/personnel/import-sdm-runs/{sdmImportRun}/status', [\App\Http\Controllers\Admin\PersonnelController::class, 'getSdmImportRunStatus'])->name('personnel.import-sdm-runs.status');
    Route::post('/personnel/import-sdm-confirm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importSdmConfirm'])->name('personnel.import-sdm-confirm');
    Route::post('/personnel/import-sdm-cancel', [\App\Http\Controllers\Admin\PersonnelController::class, 'importSdmCancel'])->name('personnel.import-sdm-cancel');
    Route::get('/personnel/import-sdm-runs/{sdmImportRun}/error-report', [\App\Http\Controllers\Admin\PersonnelController::class, 'downloadSdmImportErrorReport'])->name('personnel.import-sdm-runs.error-report');

    // Unggah Siswa Lengkap (Superadmin Only)
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/personnel/student-template', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'downloadTemplate'])->name('personnel.student-template');
        Route::post('/personnel/student-import', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'import'])->name('personnel.student-import');
        Route::get('/personnel/student-import-preview', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'preview'])->name('personnel.student-import-preview');
        Route::post('/personnel/student-import-fix-row', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'fixRow'])->name('personnel.student-import-fix-row');
        Route::post('/personnel/student-import-confirm', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'confirm'])->name('personnel.student-import-confirm');
        Route::post('/personnel/student-import-cancel', [\App\Http\Controllers\Admin\StudentPersonnelImportController::class, 'cancel'])->name('personnel.student-import-cancel');
    });

    // Personnel Print & Actions
    Route::get('/personnel/print-satker', [\App\Http\Controllers\Admin\PersonnelController::class, 'printSatker'])->name('personnel.print-satker');
    Route::get('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class, 'index'])->name('personnel.index');
    Route::post('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class, 'store'])->name('personnel.store');
    Route::put('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class, 'update'])->name('personnel.update');
    Route::post('/personnel/{personnel}/approve-verification', [\App\Http\Controllers\Admin\PersonnelController::class, 'approveVerification'])->name('personnel.approve-verification');
    Route::post('/personnel/{personnel}/reject-verification', [\App\Http\Controllers\Admin\PersonnelController::class, 'rejectVerification'])->name('personnel.reject-verification');
    Route::post('/personnel/{personnel}/measurements', [\App\Http\Controllers\Admin\PersonnelController::class, 'storeMeasurements'])->name('personnel.measurements.store');
    Route::delete('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class, 'destroy'])->name('personnel.destroy');

    // Kapor Item & Sizes
    Route::resource('kapor-items', \App\Http\Controllers\Admin\KaporItemController::class)->except(['create', 'edit', 'show']);
    Route::get('/kapor-items/{kaporItem}/sizes', [\App\Http\Controllers\Admin\KaporItemController::class, 'getSizes'])->name('kapor-items.sizes.index');
    Route::post('/kapor-items/{kaporItem}/sizes', [\App\Http\Controllers\Admin\KaporItemController::class, 'addSize'])->name('kapor-items.sizes.store');
    Route::delete('/kapor-items/{kaporItem}/sizes/{size}', [\App\Http\Controllers\Admin\KaporItemController::class, 'deleteSize'])->name('kapor-items.sizes.destroy');

    // Identifikasi Kebutuhan (Admin View)
    Route::get('/identifikasi-kebutuhan', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'index'])->name('identifikasi-kebutuhan.index');
    Route::get('/identifikasi-kebutuhan/export-pdf', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'exportPdf'])->name('identifikasi-kebutuhan.export-pdf');
    Route::get('/identifikasi-kebutuhan/export-detail-pdf', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'exportDetailPdf'])->name('identifikasi-kebutuhan.export-detail-pdf');
    Route::get('/identifikasi-kebutuhan/export-word', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'exportWord'])->name('identifikasi-kebutuhan.export-word');
    Route::get('/identifikasi-kebutuhan/export-detail-word', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'exportDetailWord'])->name('identifikasi-kebutuhan.export-detail-word');
    Route::get('/identifikasi-kebutuhan/{kebutuhan}', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'show'])->name('identifikasi-kebutuhan.show');
    Route::post('/identifikasi-kebutuhan/{kebutuhan}/reject', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'reject'])->name('identifikasi-kebutuhan.reject');
    Route::delete('/identifikasi-kebutuhan/{kebutuhan}', [\App\Http\Controllers\Admin\IdentifikasiKebutuhanController::class, 'destroy'])->name('identifikasi-kebutuhan.destroy');

    Route::middleware('role:superadmin|kabak_bekum|admin_gudang|kepala_gudang')->group(function () {
        // Warehouse Data Gudang (Unified)
        Route::post('/warehouse-items/import', [WarehouseController::class, 'import'])->name('warehouse-items.import');
        Route::get('/warehouse-items/template', [WarehouseController::class, 'downloadTemplate'])->name('warehouse-items.download-template');
        Route::get('/warehouse-items/export-excel', [WarehouseController::class, 'exportExcel'])->name('warehouse-items.export-excel');
        Route::get('/warehouse-items/export-pdf', [\App\Http\Controllers\Admin\WarehouseController::class, 'exportPdf'])->name('warehouse-items.export-pdf');
        Route::get('/warehouse-items/reports/export-pdf', [\App\Http\Controllers\Admin\WarehouseController::class, 'exportReportsPdf'])->name('warehouse-items.reports.export-pdf');

        // Pengeluaran Barang (Halaman Terpisah)
        Route::middleware('role:superadmin|admin_gudang|kepala_gudang')->group(function () {
            Route::get('/warehouse-items/dispense', [\App\Http\Controllers\Admin\WarehouseController::class, 'dispenseForm'])->name('warehouse-items.dispense-form');
            Route::post('/warehouse-items/dispense', [\App\Http\Controllers\Admin\WarehouseController::class, 'dispense'])->name('warehouse-items.dispense');
            Route::get('/warehouse-items/api/item-sizes/{id}', [\App\Http\Controllers\Admin\WarehouseController::class, 'getItemSizes'])->name('warehouse-items.api.item-sizes');
            
            Route::get('/warehouse-items/monitor-requests', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'monitor'])->name('warehouse-items.monitor-requests');
        });

        // Permohonan Pengeluaran Barang (Super Admin only)
        Route::middleware('role:superadmin|kepala_gudang')->group(function () {
            Route::post('/warehouse-items/dispense-requests/{id}/approve', [\App\Http\Controllers\Admin\DispenseRequestController::class, 'approve'])->name('warehouse-items.dispense-requests.approve');
            Route::post('/warehouse-items/dispense-requests/{id}/reject', [\App\Http\Controllers\Admin\DispenseRequestController::class, 'reject'])->name('warehouse-items.dispense-requests.reject');
            Route::delete('/warehouse-items/dispense-requests/{id}', [\App\Http\Controllers\Admin\DispenseRequestController::class, 'destroy'])->name('warehouse-items.dispense-requests.destroy');
        });

        // Penanda Tangan
        Route::get('/warehouse-items/signatories', [\App\Http\Controllers\Admin\WarehouseController::class, 'signatories'])->name('warehouse-items.signatories');
        Route::post('/warehouse-items/signatories', [\App\Http\Controllers\Admin\WarehouseController::class, 'storeSignatory'])->name('warehouse-items.signatories.store');
        Route::put('/warehouse-items/signatories/{signatory}', [\App\Http\Controllers\Admin\WarehouseController::class, 'updateSignatory'])->name('warehouse-items.signatories.update');
        Route::delete('/warehouse-items/signatories/{signatory}', [\App\Http\Controllers\Admin\WarehouseController::class, 'deleteSignatory'])->name('warehouse-items.signatories.destroy');
        Route::post('/warehouse-items/signatories/{signatory}/toggle', [\App\Http\Controllers\Admin\WarehouseController::class, 'toggleSignatoryActive'])->name('warehouse-items.signatories.toggle');

        Route::get('/warehouse-items/reports/{outflow}/download-sppm', [\App\Http\Controllers\Admin\WarehouseController::class, 'downloadSppm'])->name('warehouse-items.reports.download-sppm');
        Route::get('/warehouse-items/reports', [\App\Http\Controllers\Admin\WarehouseController::class, 'reports'])->name('warehouse-items.reports');
        Route::get('/warehouse-items/sppm', [\App\Http\Controllers\Admin\WarehouseController::class, 'sppm'])->name('warehouse-items.sppm');
        Route::post('/warehouse-items/download-sppm-grouped', [\App\Http\Controllers\Admin\WarehouseController::class, 'downloadSppmGrouped'])->name('warehouse-items.download-sppm-grouped');
        Route::post('/warehouse-items/save-sppm-grouped', [\App\Http\Controllers\Admin\WarehouseController::class, 'saveSppmGrouped'])->name('warehouse-items.save-sppm-grouped');
        Route::get('/warehouse-items/deletion-history', [\App\Http\Controllers\Admin\WarehouseController::class, 'deletionHistory'])->name('warehouse-items.deletion-history');
        Route::delete('/warehouse-items/deletion-history/item/{id}', [\App\Http\Controllers\Admin\WarehouseController::class, 'forceDeleteItem'])->name('warehouse-items.force-delete')->middleware('role:superadmin|kepala_gudang');
        Route::delete('/warehouse-items/deletion-history/outflow/{id}', [\App\Http\Controllers\Admin\WarehouseController::class, 'forceDeleteOutflow'])->name('warehouse-items.reports.force-delete')->middleware('role:superadmin|kepala_gudang');
        Route::delete('/warehouse-items/reports/{outflow}', [\App\Http\Controllers\Admin\WarehouseController::class, 'destroyOutflow'])->name('warehouse-items.reports.destroy');
        Route::delete('/warehouse-items/reports/{outflow}/cancel', [\App\Http\Controllers\Admin\WarehouseController::class, 'cancelOutflow'])->name('warehouse-items.reports.cancel');
        Route::patch('/warehouse-items/reports/{outflow}/sppm', [\App\Http\Controllers\Admin\WarehouseController::class, 'updateSppm'])->name('warehouse-items.reports.update-sppm');
        Route::resource('warehouse-items', \App\Http\Controllers\Admin\WarehouseController::class)->except(['create', 'edit', 'show']);
        Route::get('/warehouse-items/{warehouse_item}/sizes', [\App\Http\Controllers\Admin\WarehouseController::class, 'getSizes'])->name('warehouse-items.sizes.index');
        Route::post('/warehouse-items/{warehouse_item}/sizes', [\App\Http\Controllers\Admin\WarehouseController::class, 'addSize'])->name('warehouse-items.sizes.store');
        Route::put('/warehouse-items/{warehouse_item}/sizes/{size}', [\App\Http\Controllers\Admin\WarehouseController::class, 'updateSize'])->name('warehouse-items.sizes.update');
        Route::delete('/warehouse-items/{warehouse_item}/sizes/{size}', [\App\Http\Controllers\Admin\WarehouseController::class, 'deleteSize'])->name('warehouse-items.sizes.destroy');
        
        // Permohonan Edit Stok
        Route::post('/warehouse-items/sizes/{size}/request-edit', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'store'])->name('warehouse-items.sizes.request-edit');
        Route::get('/warehouse-items/edit-requests', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'index'])->name('warehouse-items.edit-requests.index')->middleware('role:superadmin|kepala_gudang');
        Route::post('/warehouse-items/edit-requests/{id}/approve', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'approve'])->name('warehouse-items.edit-requests.approve')->middleware('role:superadmin|kepala_gudang');
        Route::post('/warehouse-items/edit-requests/{id}/reject', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'reject'])->name('warehouse-items.edit-requests.reject')->middleware('role:superadmin|kepala_gudang');
        Route::delete('/warehouse-items/edit-requests/{id}', [\App\Http\Controllers\Admin\SizeEditRequestController::class, 'destroy'])->name('warehouse-items.edit-requests.destroy')->middleware('role:superadmin|kepala_gudang');
    });

    // Laporan & Audit
    Route::get('/laporan', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports');
    Route::get('/laporan/export', [\App\Http\Controllers\Admin\ReportsController::class, 'export'])->name('reports.export');
    Route::get('/laporan/arsip-tahunan', [\App\Http\Controllers\Admin\ReportsController::class, 'annualArchives'])->name('reports.annual-archives');
    Route::get('/laporan/arsip-tahunan/{annualArchive}', [\App\Http\Controllers\Admin\ReportsController::class, 'downloadAnnualArchive'])->name('reports.annual-archives.download');
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class, 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class, 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class, 'destroy'])->name('satkers.destroy');

    // ── Budget / Rencana Anggaran ──────────────────────────────
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BudgetController::class, 'index'])->name('index');
        Route::post('/years', [\App\Http\Controllers\Admin\BudgetController::class, 'storeYear'])->name('store-year');
        Route::put('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'updateYear'])->name('update-year');
        Route::delete('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'destroyYear'])->name('destroy-year');
        Route::get('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'showYear'])->name('show-year');
        Route::post('/years/{budgetYear}/packages', [\App\Http\Controllers\Admin\BudgetController::class, 'storePackage'])->name('store-package');
        Route::put('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'updatePackage'])->name('update-package');
        Route::delete('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'destroyPackage'])->name('destroy-package');
        Route::get('/packages/{budgetPackage}/sppm-assignments', [\App\Http\Controllers\Admin\BudgetPackageSppmAssignmentController::class, 'index'])->name('sppm-assignments.index');
        Route::post('/packages/{budgetPackage}/sppm-assignments', [\App\Http\Controllers\Admin\BudgetPackageSppmAssignmentController::class, 'store'])->name('sppm-assignments.store');
        Route::patch('/packages/{budgetPackage}/sppm-assignments/{assignment}', [\App\Http\Controllers\Admin\BudgetPackageSppmAssignmentController::class, 'update'])->name('sppm-assignments.update');
        Route::delete('/packages/{budgetPackage}/sppm-assignments/{assignment}', [\App\Http\Controllers\Admin\BudgetPackageSppmAssignmentController::class, 'destroy'])->name('sppm-assignments.destroy');
        Route::get('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'showPackage'])->name('show-package');
        Route::post('/packages/{budgetPackage}/recalculate', [\App\Http\Controllers\Admin\BudgetController::class, 'recalculatePackage'])->name('recalculate-package');
        Route::get('/packages/{budgetPackage}/select-items', [\App\Http\Controllers\Admin\PackageItemController::class, 'selectItems'])->name('wizard.step1');
        Route::post('/packages/{budgetPackage}/toggle-item', [\App\Http\Controllers\Admin\PackageItemController::class, 'toggleItem'])->name('wizard.toggle-item');
        Route::post('/packages/{budgetPackage}/reorder-items', [\App\Http\Controllers\Admin\PackageItemController::class, 'reorderItems'])->name('wizard.reorder-items');
        Route::get('/packages/{budgetPackage}/select-recipients', [\App\Http\Controllers\Admin\PackageItemController::class, 'selectRecipients'])->name('wizard.step2');
        Route::post('/package-items/{packageItem}/save-recipients', [\App\Http\Controllers\Admin\PackageItemController::class, 'saveRecipients'])->name('wizard.save-recipients');
        Route::get('/packages/{budgetPackage}/preview', [\App\Http\Controllers\Admin\PackageItemController::class, 'preview'])->name('wizard.step3');
        Route::delete('/package-items/{packageItem}', [\App\Http\Controllers\Admin\PackageItemController::class, 'removeItem'])->name('wizard.remove-item');
        Route::get('/satker-keterangan/{satker}', [\App\Http\Controllers\Admin\PackageItemController::class, 'getSatkerKeterangan'])->name('wizard.satker-keterangan');
        Route::get('/packages/{budgetPackage}/recap', [\App\Http\Controllers\Admin\BudgetExportController::class, 'previewRecap'])->name('recap');
        Route::get('/packages/{budgetPackage}/invoice', [\App\Http\Controllers\Admin\BudgetExportController::class, 'previewInvoice'])->name('invoice');
        Route::get('/packages/{budgetPackage}/export-csv', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportRecapExcel'])->name('export-csv');
        Route::get('/packages/{budgetPackage}/export-pdf', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportRecapPdf'])->name('export-pdf');
        Route::get('/packages/{budgetPackage}/export-detail', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportDetailExcel'])->name('export-detail');
        Route::get('/packages/{budgetPackage}/export-detail-satker', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportDetailBySatkerExcel'])->name('export-detail-satker');
        Route::get('/years/{budgetYear}/export-detail-satker', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportYearDetailBySatkerExcel'])->name('export-year-detail-satker');
        Route::post('/packages/{budgetPackage}/export-sppm', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportSppmWord'])->name('export-sppm');
        Route::post('/invoice-settings', [\App\Http\Controllers\Admin\BudgetExportController::class, 'updateSettings'])->name('update-settings');
    });
});

// ── Superadmin Routes ────────────────────────────────────────────────

Route::middleware(['auth', 'read.only', 'role:superadmin|kabak_bekum'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class, 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class, 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class, 'destroy'])->name('satkers.destroy');

    Route::post('/bagian-options', [\App\Http\Controllers\Admin\BagianOptionController::class, 'store'])->name('bagian-options.store');
    Route::put('/bagian-options/{bagianOption}', [\App\Http\Controllers\Admin\BagianOptionController::class, 'update'])->name('bagian-options.update');
    Route::delete('/bagian-options/{bagianOption}', [\App\Http\Controllers\Admin\BagianOptionController::class, 'destroy'])->name('bagian-options.destroy');
    Route::post('/sdm-satker-aliases', [\App\Http\Controllers\Admin\SdmSatkerAliasController::class, 'store'])->name('sdm-satker-aliases.store');
    Route::put('/sdm-satker-aliases/{sdmSatkerAlias}', [\App\Http\Controllers\Admin\SdmSatkerAliasController::class, 'update'])->name('sdm-satker-aliases.update');
    Route::delete('/sdm-satker-aliases/{sdmSatkerAlias}', [\App\Http\Controllers\Admin\SdmSatkerAliasController::class, 'destroy'])->name('sdm-satker-aliases.destroy');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/signatory', [\App\Http\Controllers\SettingsController::class, 'updateSignatory'])->name('settings.signatory.update');
    Route::post('/settings/open-identification-cycle', [\App\Http\Controllers\SettingsController::class, 'openIdentificationCycle'])->name('settings.open-identification-cycle');
    Route::post('/settings/next-year', [\App\Http\Controllers\SettingsController::class, 'nextYear'])->name('settings.next-year');

    Route::get('/statistik', [StatisticsController::class, 'index'])->name('statistics');
    Route::get('/statistik/satker/export-pdf', [StatisticsController::class, 'exportSatkerSummaryPdf'])->name('statistics.satkers.export-pdf');
    Route::get('/statistik/satker/{satker}/export-pdf', [StatisticsController::class, 'exportSatkerDetailPdf'])->name('statistics.satkers.detail.export-pdf');
    Route::get('/statistik/satker/{satker}', [StatisticsController::class, 'showSatker'])->name('statistics.satkers.show');
    Route::get('/testimonials/export-pdf', [\App\Http\Controllers\Superadmin\TestimonialController::class, 'exportPdf'])->name('testimonials.export-pdf');
    Route::get('/testimonials/export-word', [\App\Http\Controllers\Superadmin\TestimonialController::class, 'exportWord'])->name('testimonials.export-word');
    Route::get('/testimonials', [\App\Http\Controllers\Superadmin\TestimonialController::class, 'index'])->name('testimonials.index');

    Route::resource('identifikasi-items', \App\Http\Controllers\Superadmin\IdentifikasiItemController::class)
        ->parameters(['identifikasi-items' => 'identifikasi_item'])
        ->except(['show']);
    Route::patch('/identifikasi-items/{identifikasi_item}/toggle', [\App\Http\Controllers\Superadmin\IdentifikasiItemController::class, 'toggleActive'])->name('identifikasi-items.toggle');

    Route::get('/kapor-items', function () {
        return view('superadmin.kapor-items.index');
    })->name('kapor-items.index');
});
