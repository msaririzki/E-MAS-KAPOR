# E-MAS-KAPOR

Sistem Informasi Manajemen Kapor berbasis Laravel untuk pendataan personel, pengelolaan item kapor, input ukuran kapor, rekap kebutuhan, dan pelaporan per satker.

## Ringkasan

- Framework: Laravel 12
- Runtime: PHP 8.2+
- Frontend: Blade + Vite + Tailwind CSS v4
- Database: MariaDB / MySQL
- RBAC: `spatie/laravel-permission`
- Import/Export: Laravel Excel + DomPDF
- Audit trail: `app/Services/AuditLogger.php`
- Login:
  - `superadmin`, `admin`, `admin_gudang`, `admin_satker` memakai Gmail
  - `personil` tetap memakai `NRP/NIP`

---

## Daftar Isi

1. [Fitur Utama](#fitur-utama)
2. [Prasyarat](#prasyarat)
3. [Setup Lokal](#setup-lokal)
4. [Akun Demo Lokal](#akun-demo-lokal)
5. [Perintah Harian](#perintah-harian)
6. [Testing](#testing)
7. [Struktur Proyek](#struktur-proyek)
8. [Arsitektur Singkat](#arsitektur-singkat)
9. [Docker Server Uji](#docker-server-uji)
10. [Bootstrap Superadmin Aman](#bootstrap-superadmin-aman)
11. [Catatan Keamanan](#catatan-keamanan)
12. [Troubleshooting](#troubleshooting)

---

## Fitur Utama

- Manajemen satker, pangkat, item kapor, dan data referensi lain
- Manajemen akun admin dan personel
- Import data personel dari file Excel/CSV
- Input ukuran kapor oleh personel
- Rekap kebutuhan dan laporan per satker
- Pengelolaan gudang dan distribusi item
- Audit log untuk aktivitas penting

---

## Prasyarat

Pastikan environment lokal memiliki:

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- MariaDB / MySQL
- Ekstensi PHP yang umum untuk Laravel, terutama:
  - `pdo_mysql`
  - `mysqli`
  - `mbstring`
  - `openssl`
  - `fileinfo`

---

## Setup Lokal

### 1. Buat database

```bash
mysql -u root -p -e "CREATE DATABASE kapor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Siapkan environment

```bash
cp .env.example .env
php artisan key:generate
```

Lalu sesuaikan `.env`:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### 4. Migrasi dan seed

```bash
php artisan migrate --seed
```

### 5. Jalankan aplikasi

Opsi standar:

```bash
php artisan serve
npm run dev
```

Opsi all-in-one:

```bash
composer dev
```

Opsi Windows helper:

```bat
serve.bat
```

Setelah itu buka:

- `http://127.0.0.1:8000`

---

## Akun Demo Lokal

Jika Anda menjalankan `php artisan migrate --seed` pada environment `local` atau `testing`, akun demo akan dibuat otomatis.

### Akun administratif

| Role | Login | Password |
| --- | --- | --- |
| Superadmin | `superadmin.kapor@gmail.com` | `password` |
| Admin | `admin.kapor@gmail.com` | `password` |
| Admin Gudang | `admin.gudang.kapor@gmail.com` | `password` |
| Admin Satker | `admin.satker.mataram@gmail.com` | `password` |

### Akun personel

| Role | Login | Password |
| --- | --- | --- |
| Personil | `87654321` | `password` |

Catatan:

- Akun demo hanya untuk lokal/dev.
- Jangan gunakan password demo di server uji atau produksi.
- Untuk lingkungan non-lokal, buat atau rotasi akun superadmin secara manual menggunakan command bootstrap.

---

## Perintah Harian

### Backend dan frontend

```bash
php artisan serve
npm run dev
npm run build
composer dev
```

### Cache Laravel

```bash
php artisan optimize:clear
php artisan optimize
```

### Format kode

```bash
./vendor/bin/pint --test
./vendor/bin/pint
```

Catatan:

- ESLint belum dikonfigurasi di repo ini.

---

## Testing

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

### Jalankan satu file

```bash
php artisan test tests/Feature/ExampleTest.php
```

### Jalankan satu class

```bash
php artisan test --filter=Tests\\Feature\\ExampleTest
```

### Jalankan satu method

```bash
php artisan test --filter=test_the_application_returns_a_successful_response
```

### Jalankan file + method

```bash
php artisan test tests/Feature/ExampleTest.php --filter=test_the_application_returns_a_successful_response
```

---

## Struktur Proyek

Bagian yang paling sering disentuh:

- `app/Http/Controllers/`  
  Controller auth, dashboard, CRUD admin, gudang, laporan, settings

- `app/Http/Middleware/`  
  Middleware pembatas role, scope satker, dan lock sistem

- `app/Http/Requests/`  
  Form Request untuk validasi input

- `app/Models/`  
  Model Eloquent, relasi, cast, scope

- `app/Services/`  
  Service layer untuk audit, statistik, kalkulasi, sanitasi ukuran, dan domain logic lain

- `app/Imports/` dan `app/Exports/`  
  Import/Export Excel, PDF, dan rekap

- `resources/views/`  
  Blade template untuk setiap modul dan role

- `routes/web.php`  
  Semua route web aplikasi

- `database/migrations/`  
  Skema database

- `database/seeders/`  
  Role, satker, data referensi, akun demo

- `tests/`  
  Feature test dan unit test

---

## Arsitektur Singkat

### Modul inti

1. Master data
   - Satker
   - Rank/Pangkat
   - Item kapor
   - Setting tahun anggaran

2. Manajemen user dan personel
   - Akun admin
   - Sinkronisasi `users` dan `personnels`

3. Pengisian ukuran
   - Personel mengisi ukuran kapor
   - Data utama tersimpan pada `personnels.kapor_sizes`

4. Rekap dan pelaporan
   - Rekap kebutuhan
   - Laporan Excel/PDF
   - Statistik dashboard

5. Audit dan kontrol sistem
   - Audit log
   - Lock sistem
   - Scope satker

### Role utama

- `superadmin`
  - akses penuh
  - pengaturan sistem
  - statistik global

- `admin`
  - kelola data global
  - tidak setinggi superadmin untuk kontrol sistem

- `admin_gudang`
  - fokus pada modul gudang

- `admin_satker`
  - akses terbatas ke satker sendiri

- `personil`
  - login dengan `NRP/NIP`
  - isi dan lihat data ukuran pribadi

---

## Docker Server Uji

Repo ini sudah memiliki `docker-compose.yml` dan `update.sh` untuk server uji berbasis Docker.

### Menjalankan stack Docker

Pastikan `.env` di server sudah berisi minimal:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://contoh-domain.test

DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=kapor
DB_USERNAME=root
DB_PASSWORD=isi-password-yang-kuat

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

Lalu jalankan:

```bash
docker compose build
docker compose up -d
docker compose exec -T app php artisan migrate --force
```

### Update server uji

Skrip:

- [update.sh](./update.sh)

Flow utama:

1. `git pull origin main`
2. `docker compose build`
3. `docker compose up -d`
4. `php artisan optimize:clear`
5. `php artisan optimize`
6. `php artisan migrate --force`
7. restart queue worker

Catatan penting:

- `docker-compose.yml` sekarang mewajibkan `DB_PASSWORD` ada di `.env`
- tidak ada lagi fallback password hardcoded
- bootstrap superadmin lewat `update.sh` tidak aktif secara default

---

## Bootstrap Superadmin Aman

Untuk server uji atau produksi, jangan menyimpan password superadmin tetap di source code atau `.env`.

Gunakan command berikut:

```bash
php artisan app:bootstrap-superadmin namaakun@gmail.com --generate
```

Contoh dalam container Docker:

```bash
docker compose exec -T app php artisan app:bootstrap-superadmin namaakun@gmail.com --generate
```

Perilaku command:

- membuat akun jika belum ada
- atau merotasi password akun yang ada
- memastikan role `superadmin` terpasang
- menampilkan password baru hanya sekali di terminal

Jika Anda hanya ingin membuat akun kalau belum ada dan tidak ingin merotasi password:

```bash
php artisan app:bootstrap-superadmin namaakun@gmail.com --generate --only-if-missing
```

---

## Catatan Keamanan

Hal yang sudah disiapkan di kode:

- password disimpan hashed
- login admin memakai Gmail
- login personel tetap memakai NRP/NIP
- login dilindungi rate limiting
- validasi password admin dibuat lebih kuat
- session encryption dan secure cookie sudah disiapkan di config

Rekomendasi untuk server uji dan produksi:

- gunakan `APP_ENV=production`
- gunakan `APP_DEBUG=false`
- aktifkan HTTPS
- set `SESSION_ENCRYPT=true`
- set `SESSION_SECURE_COOKIE=true`
- jangan commit `.env`
- jangan simpan password default permanen di README, source, atau compose file
- rotasi semua akun admin yang masih memakai password demo

---

## Troubleshooting

### Perubahan route/view tidak terbaca

```bash
php artisan optimize:clear
```

### Role atau permission terasa tidak sinkron

```bash
php artisan optimize:clear
php artisan db:seed --class=RolePermissionSeeder
```

### Assets tidak berubah

```bash
npm run dev
# atau
npm run build
```

### Login admin tidak bisa

Cek hal berikut:

- admin login memakai Gmail, bukan `NRP/NIP`
- akun aktif (`is_active = true`)
- migrasi terbaru sudah dijalankan
- data akun di database sesuai dengan environment yang sedang dipakai

### Docker gagal start

Cek:

- `.env` benar-benar file, bukan folder
- `DB_PASSWORD` terisi
- port container tidak bentrok
- container `db` sehat sebelum migrasi dijalankan

---

## Catatan Kontributor

Untuk aturan kerja agen/koding yang lebih detail, lihat:

- [AGENTS.md](./AGENTS.md)

