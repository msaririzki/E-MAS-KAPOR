#!/bin/sh
set -e

echo "==> [Entrypoint] Memulai setup aplikasi..."

# 1. Setup .env jika belum ada atau kosong (0 bytes)
if [ ! -s /var/www/html/.env ]; then
    echo "==> [Entrypoint] Membuat/mengisi .env dari .env.example..."
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

# 3. Sync file statis baru dari image ke volume (selalu dijalankan)
# Menggunakan cp -ru agar hanya file baru/lebih baru yang disalin (tidak menghapus upload user)
echo "==> [Entrypoint] Menyinkronkan file public dari image ke volume..."
cp -ru /public-init/. /var/www/html/public/
chown -R www-data:www-data /var/www/html/public

# 4. Tunggu MariaDB siap menggunakan PHP PDO
if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ] || [ -n "$DB_HOST" ]; then
    echo "==> [Entrypoint] Menunggu database $DB_HOST siap..."
    php -r "
      \$host = getenv('DB_HOST') ?: '127.0.0.1';
      \$db = getenv('DB_DATABASE') ?: 'kapor';
      \$user = getenv('DB_USERNAME') ?: 'root';
      \$pass = getenv('DB_PASSWORD') ?: '';
      \$port = getenv('DB_PORT') ?: '3306';
      
      for (\$i=0; \$i<30; \$i++) {
          try {
              new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
              echo \"==> [Entrypoint] Database siap!\\n\";
              exit(0);
          } catch (PDOException \$e) {
              sleep(2);
          }
      }
      echo \"==> [WARN] Database timeout atau credential salah.\\n\";
      exit(1);
    " || exit 1
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
