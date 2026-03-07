#!/bin/sh
set -e

echo "==> [Entrypoint] Memulai setup aplikasi..."

# 1. Setup .env jika belum ada
if [ ! -f /var/www/html/.env ]; then
    echo "==> [Entrypoint] Membuat .env dari .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# 2. Generate APP_KEY
APP_KEY_VALUE=$(grep -E '^APP_KEY=' /var/www/html/.env | cut -d '=' -f2)
if [ -z "$APP_KEY_VALUE" ]; then
    if [ -w /var/www/html/.env ]; then
        echo "==> [Entrypoint] Generating APP_KEY..."
        php artisan key:generate --force
    else
        echo "==> [WARN] APP_KEY kosong tapi .env read-only. Isi APP_KEY di host terlebih dahulu!"
    fi
fi

# 3. Populate shared public volume (jika masih kosong)
if [ ! -f /var/www/html/public/index.php ]; then
    echo "==> [Entrypoint] Menyalin file public ke volume bersama..."
    cp -r /public-init/. /var/www/html/public/
    chown -R www-data:www-data /var/www/html/public
fi

# 4. Tunggu MariaDB siap
if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    echo "==> [Entrypoint] Menunggu database $DB_HOST siap..."
    while ! mysqladmin ping -h"$DB_HOST" --silent; do
        sleep 2
    done
    echo "==> [Entrypoint] Database siap!"
fi

# 5. Jalankan migrasi database
echo "==> [Entrypoint] Menjalankan migrasi database..."
php artisan migrate --force

# 6. Buat symlink storage jika belum ada
if [ ! -d /var/www/html/public/storage ]; then
    echo "==> [Entrypoint] Membuat storage symlink..."
    php artisan storage:link
fi

# 7. Optimasi Laravel & Fix Permission Akhir
echo "==> [Entrypoint] Optimasi Laravel..."
php artisan optimize

echo "==> [Entrypoint] Memastikan hak akses storage milik www-data..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "==> [Entrypoint] Setup selesai! Menjalankan: $@"
exec "$@"
