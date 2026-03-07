#!/bin/bash
set -e

echo "==> Menarik perubahan kode terbaru..."
git pull origin main

echo "==> Build image baru (jika ada perubahan Dockerfile/package)..."
docker compose build

echo "==> Menerapkan container (recreate jika image berubah)..."
docker compose up -d

echo "==> Menjalankan optimasi Laravel dan cache config..."
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize

echo "==> Menjalankan migrasi database..."
docker compose exec app php artisan migrate --force

echo "==> Restart queue worker..."
docker compose restart queue

echo "==> Update selesai! Aplikasi siap digunakan."
