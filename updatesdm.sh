#!/bin/bash
set -e

BOOTSTRAP_EMAIL="${BOOTSTRAP_SUPERADMIN_EMAIL:-}"
BOOTSTRAP_NAME="${BOOTSTRAP_SUPERADMIN_NAME:-Bootstrap Super Administrator}"
BOOTSTRAP_ON_UPDATE="${BOOTSTRAP_SUPERADMIN_ON_UPDATE:-false}"
TARGET_BRANCH="${TARGET_BRANCH:-fitur-import-sdm}"

# Pastikan file .env ada sebagai file, bukan jadi folder gara-gara volume docker-compose
if [ ! -f ".env" ]; then
    if [ -d ".env" ]; then
        echo "==> MENGHAPUS folder .env yang tidak sengaja terbuat oleh Docker..."
        rm -rf .env
    fi
    echo "==> MENGUPDATE: Membuat file .env kosong agar terisi otomatis dari .env.example..."
    touch .env
fi

echo "==> Pindah ke branch ${TARGET_BRANCH}..."
git fetch origin "${TARGET_BRANCH}"
git checkout "${TARGET_BRANCH}"

echo "==> Menarik perubahan kode terbaru dari ${TARGET_BRANCH}..."
git pull origin "${TARGET_BRANCH}"

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

if [ "$BOOTSTRAP_ON_UPDATE" = "true" ] && [ -n "$BOOTSTRAP_EMAIL" ]; then
    echo "==> Memastikan akun bootstrap superadmin tersedia..."
    docker compose exec -T app php artisan app:bootstrap-superadmin "$BOOTSTRAP_EMAIL" --name="$BOOTSTRAP_NAME" --generate --only-if-missing
fi

echo "==> Restart queue worker..."
docker compose restart queue

echo "==> Update branch ${TARGET_BRANCH} selesai! Aplikasi siap digunakan."
