<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SatkerController;
use Illuminate\Support\Facades\Route;

/* |-------------------------------------------------------------------------- | SI-KAPOR Polda NTB â€” Web Routes |-------------------------------------------------------------------------- */

// â”€â”€ Public / Auth Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// â”€â”€ Authenticated Routes (Semua Role) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::middleware(['auth', 'satker.scope'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    Route::post('/profile/theme', [\App\Http\Controllers\Admin\ProfileController::class, 'updateTheme'])->name('profile.updateTheme');
});

// â”€â”€ Personil Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::middleware(['auth', 'role:personil', 'system.lock'])->prefix('personil')->name('personil.')->group(function () {
    Route::get('/kapor', function () {
        $personnel = auth()->user()->personnel;
        $kaporSizes = $personnel ? ($personnel->kapor_sizes ?? []) : [];

        return view('personil.kapor.index', compact('kaporSizes'));
    })->name('kapor.index');

    Route::post('/kapor', function (\Illuminate\Http\Request $request) {
        $personnel = auth()->user()->personnel;
        if ($personnel) {
            $rules = [
                'kemeja' => 'required|string',
                'celana' => 'required|string',
                'olahraga' => 'required|string',
                'jaket' => 'required|string',
                'topi' => 'required|string',
                'sabuk' => 'required|string',
                'sepatu_dinas' => 'required|string',
                'sepatu_olahraga' => 'required|string',
            ];
            if ($personnel->gender === 'P') {
                $rules['jilbab'] = 'required|string';
            }
            $validated = $request->validate($rules);

            // Menggabungkan dengan json format sebelumnya
            $currentSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
            $newSizes = array_merge($currentSizes, $validated);

            $personnel->kapor_sizes = $newSizes;
            $personnel->save();
        }

        return redirect()->route('dashboard')->with('success', 'Data ukuran Anda berhasil disimpan dan disinkronkan ke sistem.');
    })->name('kapor.store');

    Route::post('/testimoni', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        \App\Models\Testimonial::create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'rating' => $validated['rating'] ?? 5,
        ]);

        return back()->with('success_testimoni', 'Terima kasih atas tanggapan Anda! Testimoni berhasil dikirim.');
    })->name('testimoni.store');

    Route::get('/kapor/riwayat', function () {
        $user = auth()->user();
        $personnel = $user->personnel;
        $kaporSizes = $personnel ? ($personnel->kapor_sizes ?? []) : [];
        $hasSubmitted = ! empty($kaporSizes) && is_array($kaporSizes) && count(array_filter($kaporSizes)) > 0;

        return view('personil.kapor.history', compact('kaporSizes', 'hasSubmitted', 'personnel'));
    })->name('kapor.history');
});

// â”€â”€ Admin Satker Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::middleware(['auth', 'role:admin_satker', \App\Http\Middleware\SatkerScope::class])->prefix('admin-satker')->name('admin-satker.')->group(function () {
    Route::get('/monitor', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'monitor'])->name('monitor');
    Route::get('/reports', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'reports'])->name('reports');
    Route::get('/settings', [\App\Http\Controllers\AdminSatker\AdminSatkerController::class, 'settings'])->name('settings');
});

// â”€â”€ Admin Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::middleware(['auth', 'role:admin|superadmin|admin_satker', 'satker.scope'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::get('/users/template', [\App\Http\Controllers\UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('/users/import', [\App\Http\Controllers\UserController::class, 'import'])->name('users.import');
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
    Route::post('/personnel/import-update', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdate'])->name('personnel.import-update');
    Route::get('/personnel/import-update-preview', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdatePreview'])->name('personnel.import-update-preview');
    Route::post('/personnel/import-update-confirm', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdateConfirm'])->name('personnel.import-update-confirm');
    Route::post('/personnel/import-update-cancel', [\App\Http\Controllers\Admin\PersonnelController::class, 'importUpdateCancel'])->name('personnel.import-update-cancel');
    Route::resource('kapor-items', \App\Http\Controllers\Admin\KaporItemController::class)->except(['create', 'edit', 'show']);
    Route::get('/kapor-items/{kaporItem}/sizes', [\App\Http\Controllers\Admin\KaporItemController::class, 'getSizes'])->name('kapor-items.sizes.index');
    Route::post('/kapor-items/{kaporItem}/sizes', [\App\Http\Controllers\Admin\KaporItemController::class, 'addSize'])->name('kapor-items.sizes.store');
    Route::delete('/kapor-items/{kaporItem}/sizes/{size}', [\App\Http\Controllers\Admin\KaporItemController::class, 'deleteSize'])->name('kapor-items.sizes.destroy');

    Route::get('/personnel/print-satker', [\App\Http\Controllers\Admin\PersonnelController::class, 'printSatker'])->name('personnel.print-satker');
    Route::get('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class, 'index'])->name('personnel.index');
    Route::post('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class, 'store'])->name('personnel.store');
    Route::put('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class, 'update'])->name('personnel.update');
    Route::post('/personnel/{personnel}/measurements', [\App\Http\Controllers\Admin\PersonnelController::class, 'storeMeasurements'])->name('personnel.measurements.store');
    Route::delete('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class, 'destroy'])->name('personnel.destroy');

    // Satker CRUD
    Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class, 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class, 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class, 'destroy'])->name('satkers.destroy');

    Route::get('/laporan', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports');
    Route::get('/laporan/export', [\App\Http\Controllers\Admin\ReportsController::class, 'export'])->name('reports.export');

    // Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');

    // ── Budget / Rencana Anggaran ──────────────────────────────
    Route::prefix('budget')->name('budget.')->group(function () {
        // Tahun Anggaran
        Route::get('/', [\App\Http\Controllers\Admin\BudgetController::class, 'index'])->name('index');
        Route::post('/years', [\App\Http\Controllers\Admin\BudgetController::class, 'storeYear'])->name('store-year');
        Route::put('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'updateYear'])->name('update-year');
        Route::delete('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'destroyYear'])->name('destroy-year');

        // Paket dalam Tahun
        Route::get('/years/{budgetYear}', [\App\Http\Controllers\Admin\BudgetController::class, 'showYear'])->name('show-year');
        Route::post('/years/{budgetYear}/packages', [\App\Http\Controllers\Admin\BudgetController::class, 'storePackage'])->name('store-package');
        Route::put('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'updatePackage'])->name('update-package');
        Route::delete('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'destroyPackage'])->name('destroy-package');

        // Detail Paket (Wizard)
        Route::get('/packages/{budgetPackage}', [\App\Http\Controllers\Admin\BudgetController::class, 'showPackage'])->name('show-package');
        Route::post('/packages/{budgetPackage}/recalculate', [\App\Http\Controllers\Admin\BudgetController::class, 'recalculatePackage'])->name('recalculate-package');

        // Wizard Step 1: Pilih Barang
        Route::get('/packages/{budgetPackage}/select-items', [\App\Http\Controllers\Admin\PackageItemController::class, 'selectItems'])->name('wizard.step1');
        Route::post('/packages/{budgetPackage}/toggle-item', [\App\Http\Controllers\Admin\PackageItemController::class, 'toggleItem'])->name('wizard.toggle-item');
        Route::post('/packages/{budgetPackage}/reorder-items', [\App\Http\Controllers\Admin\PackageItemController::class, 'reorderItems'])->name('wizard.reorder-items');

        // Wizard Step 2: Pilih Penerima
        Route::get('/packages/{budgetPackage}/select-recipients', [\App\Http\Controllers\Admin\PackageItemController::class, 'selectRecipients'])->name('wizard.step2');
        Route::post('/package-items/{packageItem}/save-recipients', [\App\Http\Controllers\Admin\PackageItemController::class, 'saveRecipients'])->name('wizard.save-recipients');

        // Wizard Step 3: Preview
        Route::get('/packages/{budgetPackage}/preview', [\App\Http\Controllers\Admin\PackageItemController::class, 'preview'])->name('wizard.step3');

        // Item operations
        Route::delete('/package-items/{packageItem}', [\App\Http\Controllers\Admin\PackageItemController::class, 'removeItem'])->name('wizard.remove-item');

        // API: Ambil keterangan unik per satker
        Route::get('/satker-keterangan/{satker}', [\App\Http\Controllers\Admin\PackageItemController::class, 'getSatkerKeterangan'])->name('wizard.satker-keterangan');

        // Export & Invoice
        Route::get('/packages/{budgetPackage}/recap', [\App\Http\Controllers\Admin\BudgetExportController::class, 'previewRecap'])->name('recap');
        Route::get('/packages/{budgetPackage}/invoice', [\App\Http\Controllers\Admin\BudgetExportController::class, 'previewInvoice'])->name('invoice');
        Route::get('/packages/{budgetPackage}/export-csv', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportRecapExcel'])->name('export-csv');
        Route::get('/packages/{budgetPackage}/export-pdf', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportRecapPdf'])->name('export-pdf');
        Route::get('/packages/{budgetPackage}/export-detail', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportDetailExcel'])->name('export-detail');
        Route::post('/packages/{budgetPackage}/export-sppm', [\App\Http\Controllers\Admin\BudgetExportController::class, 'exportSppmWord'])->name('export-sppm');
        Route::post('/invoice-settings', [\App\Http\Controllers\Admin\BudgetExportController::class, 'updateSettings'])->name('update-settings');
    });
});

// â”€â”€ Superadmin Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    // Superadmin juga bisa akses satker CRUD dengan prefix superadmin
    Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class, 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class, 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class, 'destroy'])->name('satkers.destroy');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/next-year', [\App\Http\Controllers\SettingsController::class, 'nextYear'])->name('settings.next-year');

    Route::get('/statistik', function () {
        return view('superadmin.statistics');
    }
    )->name('statistics');

    Route::get('/kapor-items', function () {
        return view('superadmin.kapor-items.index');
    }
    )->name('kapor-items.index');
});
