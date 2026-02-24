<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Auth Events Logging ─────────────────────────────────
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class , function ($event) {
            \App\Services\AuditLogger::log('Login', 'Autentikasi', $event->user, null, null, 'success', 'Pengguna masuk ke sistem');
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class , function ($event) {
            if ($event->user) {
                \App\Services\AuditLogger::log('Logout', 'Autentikasi', $event->user, null, null, 'success', 'Pengguna keluar dari sistem');
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class , function ($event) {
            \App\Services\AuditLogger::log('Login Gagal', 'Autentikasi', null, null, null, 'failed', 'Percobaan masuk gagal untuk username: ' . ($event->credentials['username'] ?? 'unknown'));
        });
    }
}
