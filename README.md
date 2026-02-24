# E-MAS-KAPOR

Sistem Informasi Manajemen Kapor berbasis Laravel untuk pendataan personel, pengelolaan item kapor, dan rekap ukuran per satuan kerja.

## Ringkasnya

- Framework: Laravel 12 (PHP 8.2+)
- UI: Blade + Vite + Tailwind CSS v4
- Auth: login dengan `nrp_nip` + password
- Role utama: `superadmin`, `admin`, `admin_satker`, `personil`
- RBAC: `spatie/laravel-permission`
- Import/Export: Maatwebsite Excel + DomPDF
- Audit trail: `app/Services/AuditLogger.php`

---

## Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Quick Start Lokal](#quick-start-lokal)
3. [Akun Demo](#akun-demo)
4. [Perintah Harian](#perintah-harian)
5. [Testing (Termasuk Single Test)](#testing-termasuk-single-test)
6. [Peta Repositori](#peta-repositori)
7. [Arsitektur & Alur Data](#arsitektur--alur-data)
8. [Konvensi & Kualitas Kode](#konvensi--kualitas-kode)
9. [Laravel Boost (Opsional)](#laravel-boost-opsional)
10. [Troubleshooting Cepat](#troubleshooting-cepat)

---

## Prasyarat

Pastikan environment lokal memiliki:

- PHP 8.2+
- Composer
- Node.js + npm
- Database: MySQL/MariaDB atau SQLite

---

## Quick Start Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Buka aplikasi di `http://127.0.0.1:8000`.

### Opsi development all-in-one

```bash
composer dev
```

Perintah ini menjalankan server Laravel + queue listener + Vite secara bersamaan.

---

## Akun Demo

Setelah `php artisan migrate --seed`, gunakan akun berikut untuk login:

| Role         | NRP/NIP    | Password   |
| ------------ | ---------- | ---------- |
| Superadmin   | `SA001`    | `password` |
| Admin        | `ADM001`   | `password` |
| Admin Satker | `AS001`    | `password` |
| Personil     | `87654321` | `password` |

Gunakan hanya untuk dev/lokal dan ganti password untuk environment non-lokal.

---

## Perintah Harian

### Build dan assets

```bash
npm run dev
npm run build
```

### Lint / format

```bash
./vendor/bin/pint --test
./vendor/bin/pint
```

Catatan: lint JavaScript (ESLint) belum dikonfigurasi di repo ini.

### Cleanup cache Laravel

```bash
php artisan optimize:clear
```

---

## Testing (Termasuk Single Test)

### Jalankan semua test

```bash
composer test
# atau
php artisan test
```

### Jalankan test paralel

```bash
php artisan test --parallel
```

### Jalankan satu file test

```bash
php artisan test tests/Feature/ExampleTest.php
```

### Jalankan satu class test

```bash
php artisan test --filter=Tests\\Feature\\ExampleTest
```

### Jalankan satu method test

```bash
php artisan test --filter=test_the_application_returns_a_successful_response
```

### Kombinasi file + method

```bash
php artisan test tests/Feature/ExampleTest.php --filter=test_the_application_returns_a_successful_response
```

---

## Peta Repositori

Struktur yang paling sering disentuh:

- `app/Http/Controllers/` -> controller untuk auth, dashboard, admin CRUD, settings.
- `app/Http/Middleware/` -> pembatas role/satker/system lock.
- `app/Http/Requests/` -> Form Request (sudah dipakai sebagian).
- `app/Models/` -> relasi, casts, scopes domain utama.
- `app/Imports/` dan `app/Exports/` -> impor/ekspor Excel + rekap.
- `app/Services/AuditLogger.php` -> helper audit log terpusat.
- `routes/web.php` -> semua route web aplikasi.
- `resources/views/` -> Blade template tiap role/module.
- `database/migrations/` -> definisi skema.
- `database/seeders/` -> role, data referensi, akun demo.
- `tests/` -> unit/feature tests.

---

## Arsitektur & Alur Data

### Modul inti

1. **Master data**: Satker, Rank, Item Kapor, Setting tahun anggaran.
2. **Manajemen user/personel**: sinkronisasi `users` <-> `personnels`.
3. **Pengisian ukuran kapor**: saat ini dominan di `personnels.kapor_sizes` (JSON).
4. **Rekap/laporan**: export Excel/PDF berdasarkan kategori item dan satker.
5. **Audit log**: aksi kritis dicatat ke `audit_logs`.

### Akses berbasis role (ringkas)

- `superadmin`: pengaturan global (tahun anggaran, lock sistem), akses penuh.
- `admin`: kelola data global tanpa otoritas setting tertinggi.
- `admin_satker`: akses terbatas satker sendiri (scoped).
- `personil`: input dan lihat data kapor pribadi.

### Middleware penting

- `satker.scope`: enforce scope satker untuk admin satker.
- `system.lock`: blok pengisian data ketika sistem dikunci.
- `role:*` (Spatie): pembatas akses route berbasis role.

---

## Konvensi & Kualitas Kode

Ringkasan praktis (detail lengkap ada di `AGENTS.md`):

- Gunakan style Laravel + PSR-12, format dengan Pint.
- Tetap gunakan 4 spasi, LF, UTF-8 sesuai `.editorconfig`.
- Utamakan type hints + return types di method baru/yang diubah.
- Gunakan Form Request untuk validasi yang kompleks.
- Bungkus operasi multi-tabel dalam transaksi DB.
- Pertahankan flash message `success/error/warning` untuk UX konsisten.
- Untuk aksi admin/destruktif, teruskan audit logging via `AuditLogger::log(...)`.
- Jangan commit `.env` dan rahasia lainnya.

---

## Troubleshooting Cepat

- Jika perubahan route/view tidak terbaca: jalankan `php artisan optimize:clear`.
- Jika test gagal karena state: cek `.env.testing`/config database testing.
- Jika role/permission terasa tidak sinkron: reseed lokal dan cek cache permission.
- Jika front-end tidak update: restart `npm run dev` atau rebuild dengan `npm run build`.

---
