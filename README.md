# E-MAS-KAPOR (Sistem Informasi Manajemen Kapor)

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)

E-MAS-KAPOR adalah sebuah sistem informasi manajemen berbasis web untuk pengelolaan administrasi, rekapitulasi, dan pendataan barang / perlengkapan (Kapor) secara digital. Aplikasi ini dikembangkan untuk memberikan kemudahan bagi instansi/organisasi dalam melakukan tracking pengajuan, manajemen user (Personil & Admin Satker), serta mengoptimalkan operasional secara terpusat.

Arsitektur aplikasi ini dibangun di atas [Laravel](https://laravel.com), sebuah framework PHP yang elegan, untuk memastikan keamanan, skalabilitas, dan kemudahan _maintenance_.

## Fitur Utama

- **Manajemen Pengguna Terpusat (RBAC)**: Role Based Access Control untuk Superadmin, Admin, Admin Satker, dan Personil.
- **Pendataan Kapor**: Pengelolaan data spesifikasi ukuran Kapor untuk tiap personil.
- **Audit Logging**: Perekaman aktivitas sistem secara *real-time* untuk keamanan.
- **Import/Export Data Format Excel/CSV**: Kemudahan pendaftaran user secara massal melalui *spreadsheet*.
- **Desain Modern**: Antarmuka yang ramah pengguna menggunakan perpaduan antara framework front-end minimalis dan CSS.

---

## Prasyarat Lingkungan

Sebelum menjalankan proyek ini, pastikan sistem Anda memiliki lingkungan berikut:
- **PHP** versi 8.2 atau lebih baru
- **Composer** (untuk dependensi backend)
- **Node.js** & **npm** (untuk proses build aset frontend)
- **Database Server** (MySQL / PostgreSQL / SQLite)

---

## 🚀 Cara Instalasi & Menjalankan Aplikasi

Ikuti panduan ini untuk melakukan pengaturan E-MAS-KAPOR di lingkungan lokal (Local Development).

### 1. Kloning Repository

```bash
git clone https://github.com/msaririzki/E-MAS-KAPOR.git
cd E-MAS-KAPOR
```

### 2. Konfigurasi Lingkungan (.env)

Salin fail konfigurasi *environment* bawaan dan sesuaikan dengan database Anda.

```bash
# Windows
copy .env.example .env

# Linux / Mac
cp .env.example .env
```

Buka fail `.env` dan atur koneksi basis data Anda, contoh (jika menggunakan MySQL):
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_kapor
DB_USERNAME=root
DB_PASSWORD=
```
*(Catatan: Aplikasi ini dapat dijalankan menggunakan SQLite secara default untuk testing).*

### 3. Instalasi Dependensi

Jalankan perintah berikut untuk mengunduh modul PHP dan Node.

```bash
# Install PHP Dependencies
composer install

# Install Javascript / CSS Dependencies
npm install
```

### 4. Build Aset Frontend

*Compile* seluruh aset *development* (Vite/Tailwind/CSS) ke spesifikasi produksi.

```bash
npm run build
```

*(Untuk development secara aktif berjalan, gunakan `npm run dev` di terminal terpisah).*

### 5. Generate Application Key dan Migrasi Database

```bash
# Generate encryption key
php artisan key:generate

# Migrasi dan Seeding referensi awal (Termasuk akun Demo)
php artisan migrate --seed
```

### 6. Jalankan Server Development

```bash
php artisan serve
```

Aplikasi sekarang dapat diakses secara lokal pada **`http://localhost:8000`**.

---

## Kredensial Akun Bawaan (Demo)

Bila Anda menjalankan _Seeding_ diatas (`--seed`), Anda bisa login menggunakan akun demo berikut:

| Akun | NRP/NIP | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `SA001` | `password` | Kendali penuh aplikasi |
| **Admin** | `ADM001` | `password` | Admin tingkat atas |
| **Admin Satker** | `AS001` | `password` | Pengelola unit Polresta Mataram |
| **User / Personil** | `87654321` | `password` | Akun personil (Polri) |

*(Silakan segera ubah kata sandi ini untuk lingkungan produksi).*

---

## Perizinan dan Lisensi

Dikembangkan dengan framework [Laravel](https://laravel.com) yang berlisensi MIT. Pastikan Anda mengatur kredensial Anda dan `.env` sebelum aplikasi dipublikasikan sepenuhnya.
