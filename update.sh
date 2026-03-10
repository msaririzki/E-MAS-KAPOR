#!/bin/bash
set -e

# Pastikan file .env ada sebagai file, bukan jadi folder gara-gara volume docker-compose
if [ ! -f ".env" ]; then
    if [ -d ".env" ]; then
        echo "==> MENGHAPUS folder .env yang tidak sengaja terbuat oleh Docker..."
        rm -rf .env
    fi
    echo "==> MENGUPDATE: Membuat file .env kosong agar terisi otomatis dari .env.example..."
    touch .env
fi

echo "==> Menarik perubahan kode terbaru..."
git pull origin main

echo "==> Build image baru (jika ada perubahan Dockerfile/package)..."
docker compose build

echo "==> Menerapkan container (recreate jika image berubah)..."
docker compose up -d

echo "==> Menunggu container siap..."
sleep 2

echo "==> Menjalankan optimasi Laravel dan cache config..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

echo "==> Menjalankan migrasi database..."
docker compose exec -T app php artisan migrate --force

echo "==> Restart queue worker..."
docker compose restart queue

echo "==> Update selesai! Aplikasi siap digunakan."
