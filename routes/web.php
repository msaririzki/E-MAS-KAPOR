<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SatkerController;
use Illuminate\Support\Facades\Route;

/* |-------------------------------------------------------------------------- | SI-KAPOR Polda NTB — Web Routes |-------------------------------------------------------------------------- */

// ── Public / Auth Routes ───────────────────────────────────────

Route::get('/', fn() => redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class , 'login']);
});

Route::post('/logout', [AuthController::class , 'logout'])->name('logout')->middleware('auth');

// ── Authenticated Routes (Semua Role) ──────────────────────────

Route::middleware(['auth', 'satker.scope'])->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

    Route::get('/profile', function () {
            return view('profile.index');
        }
        )->name('profile');
    });

// ── Personil Routes ────────────────────────────────────────────

Route::middleware(['auth', 'role:personil', 'system.lock'])->prefix('personil')->name('personil.')->group(function () {
    Route::get('/kapor', function () {
            return view('personil.kapor.index');
        }
        )->name('kapor.index');

        Route::post('/kapor', function () {
            return 'Store kapor submission';
        }
        )->name('kapor.store');

        Route::get('/kapor/riwayat', function () {
            return view('personil.kapor.history');
        }
        )->name('kapor.history');
    });

// ── Admin Satker Routes ────────────────────────────────────────

Route::middleware(['auth', 'role:admin_satker', \App\Http\Middleware\SatkerScope::class])->prefix('admin-satker')->name('admin-satker.')->group(function () {
    Route::get('/personil', function () {
            return view('admin-satker.personnel.index');
        }
        )->name('personnel.index');

        Route::get('/personil/{id}', function ($id) {
            return view('admin-satker.personnel.show', compact('id'));
        }
        )->name('personnel.show');

        Route::get('/monitor', function () {
            return view('admin-satker.monitor');
        }
        )->name('monitor');

        Route::get('/export', function () {
            return 'Export Data Satker';
        }
        )->name('export');
    });

// ── Admin Routes ───────────────────────────────────────────────

Route::middleware(['auth', 'role:admin|superadmin'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::get('/users/template', [\App\Http\Controllers\UserController::class , 'downloadTemplate'])->name('users.template');
    Route::post('/users/import', [\App\Http\Controllers\UserController::class , 'import'])->name('users.import');
    Route::get('/users', [\App\Http\Controllers\UserController::class , 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\UserController::class , 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class , 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class , 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [\App\Http\Controllers\Admin\UserStatusController::class , 'toggle'])->name('users.toggle-status');

    // Personnel Management
    Route::get('/personnel/template', [\App\Http\Controllers\Admin\PersonnelController::class , 'downloadTemplate'])->name('personnel.template');
    Route::post('/personnel/import', [\App\Http\Controllers\Admin\PersonnelController::class , 'import'])->name('personnel.import');
    Route::delete('/personnel/bulk-delete', [\App\Http\Controllers\Admin\PersonnelController::class , 'bulkDeleteBySatker'])->name('personnel.bulk-delete');
    Route::get('/personnel/export-rekap', [\App\Http\Controllers\Admin\PersonnelController::class , 'exportRekap'])->name('personnel.export-rekap');
    Route::resource('kapor-items', \App\Http\Controllers\Admin\KaporItemController::class)->except(['create', 'edit', 'show']);

    Route::get('/personnel/print-satker', [\App\Http\Controllers\Admin\PersonnelController::class , 'printSatker'])->name('personnel.print-satker');
    Route::get('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class , 'index'])->name('personnel.index');
    Route::post('/personnel', [\App\Http\Controllers\Admin\PersonnelController::class , 'store'])->name('personnel.store');
    Route::put('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class , 'update'])->name('personnel.update');
    Route::post('/personnel/{personnel}/measurements', [\App\Http\Controllers\Admin\PersonnelController::class , 'storeMeasurements'])->name('personnel.measurements.store');
    Route::delete('/personnel/{personnel}', [\App\Http\Controllers\Admin\PersonnelController::class , 'destroy'])->name('personnel.destroy');

    // Satker CRUD
    Route::get('/satkers', [SatkerController::class , 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class , 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class , 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class , 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class , 'destroy'])->name('satkers.destroy');

    Route::get('/laporan', function () {
            return view('admin.reports.index');
        }
        )->name('reports');

        Route::get('/laporan/export', function () {
            return 'Export Laporan';
        }
        )->name('reports.export');

        // Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class , 'index'])->name('audit-logs.index');
    });

// ── Superadmin Routes ──────────────────────────────────────────

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    // Superadmin juga bisa akses satker CRUD dengan prefix superadmin
    Route::get('/satkers', [SatkerController::class , 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class , 'store'])->name('satkers.store');
    Route::put('/satkers/{satker}', [SatkerController::class , 'update'])->name('satkers.update');
    Route::patch('/satkers/{satker}/personnel', [SatkerController::class , 'updatePersonnelCount'])->name('satkers.update-personnel');
    Route::delete('/satkers/{satker}', [SatkerController::class , 'destroy'])->name('satkers.destroy');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class , 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class , 'update'])->name('settings.update');
    Route::post('/settings/next-year', [\App\Http\Controllers\SettingsController::class , 'nextYear'])->name('settings.next-year');

    Route::get('/statistik', function () {
            return view('superadmin.statistics');
        }
        )->name('statistics');

        Route::get('/kapor-items', function () {
            return view('superadmin.kapor-items.index');
        }
        )->name('kapor-items.index');
    });
