#!/bin/bash
set -e

read_env_value() {
    local key="$1"
    local fallback="$2"
    local current_value="${!key:-}"

    if [ -n "$current_value" ]; then
        printf '%s' "$current_value"
        return 0
    fi

    if [ -f ".env" ]; then
        local line
        line=$(grep -E "^${key}=" .env | tail -n 1 || true)
        if [ -n "$line" ]; then
            local value="${line#*=}"
            value="${value%\"}"
            value="${value#\"}"
            printf '%s' "$value"
            return 0
        fi
    fi

    printf '%s' "$fallback"
}

wait_for_app() {
    echo "==> Menunggu container app siap menerima perintah..."
    for i in $(seq 1 60); do
        if docker compose exec -T app php -v >/dev/null 2>&1; then
            echo "==> Container app siap."
            return 0
        fi
        sleep 2
    done

    echo "==> Gagal: container app tidak siap dalam batas waktu."
    return 1
}

wait_for_database() {
    echo "==> Menunggu database benar-benar siap..."
    for i in $(seq 1 60); do
        if docker compose exec -T app php -r '$host=getenv("DB_HOST") ?: "db"; $db=getenv("DB_DATABASE") ?: "kapor"; $user=getenv("DB_USERNAME") ?: "root"; $pass=getenv("DB_PASSWORD") ?: ""; $port=getenv("DB_PORT") ?: "3306"; try { new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass); exit(0); } catch (Throwable $e) { exit(1); }' >/dev/null 2>&1; then
            echo "==> Database siap."
            return 0
        fi
        sleep 2
    done

    echo "==> Gagal: database tidak siap dalam batas waktu."
    return 1
}

core_seed_needed() {
    docker compose exec -T app php artisan tinker --execute="echo (int) (
        \Illuminate\Support\Facades\DB::table('roles')->count() === 0 ||
        \Illuminate\Support\Facades\DB::table('ranks')->count() === 0 ||
        \Illuminate\Support\Facades\DB::table('satkers')->count() === 0 ||
        \Illuminate\Support\Facades\DB::table('settings')->count() === 0
    );" 2>/dev/null | tr -d '\r\n[:space:]'
}

# Pastikan file .env ada sebagai file, bukan jadi folder gara-gara volume docker-compose
if [ ! -f ".env" ]; then
    if [ -d ".env" ]; then
        echo "==> MENGHAPUS folder .env yang tidak sengaja terbuat oleh Docker..."
        rm -rf .env
    fi
    echo "==> MENGUPDATE: Membuat file .env kosong agar terisi otomatis dari .env.example..."
    touch .env
fi

BOOTSTRAP_EMAIL="$(read_env_value "BOOTSTRAP_SUPERADMIN_EMAIL" "")"
BOOTSTRAP_NAME="$(read_env_value "BOOTSTRAP_SUPERADMIN_NAME" "Bootstrap Super Administrator")"
BOOTSTRAP_ON_UPDATE="$(read_env_value "BOOTSTRAP_SUPERADMIN_ON_UPDATE" "false")"
RUN_SEED_ON_UPDATE="$(read_env_value "RUN_SEED_ON_UPDATE" "false")"
RUN_DEMO_SEED_ON_UPDATE="$(read_env_value "RUN_DEMO_SEED_ON_UPDATE" "false")"
TARGET_BRANCH="$(read_env_value "TARGET_BRANCH" "fitur-import-sdm")"

echo "==> Konfigurasi update:"
echo "    TARGET_BRANCH=${TARGET_BRANCH}"
echo "    RUN_SEED_ON_UPDATE=${RUN_SEED_ON_UPDATE}"
echo "    RUN_DEMO_SEED_ON_UPDATE=${RUN_DEMO_SEED_ON_UPDATE}"
echo "    BOOTSTRAP_SUPERADMIN_ON_UPDATE=${BOOTSTRAP_ON_UPDATE}"

echo "==> Pindah ke branch ${TARGET_BRANCH}..."
git fetch origin "${TARGET_BRANCH}"
git checkout "${TARGET_BRANCH}"

echo "==> Menarik perubahan kode terbaru dari ${TARGET_BRANCH}..."
git pull origin "${TARGET_BRANCH}"

echo "==> Build image baru (jika ada perubahan Dockerfile/package)..."
docker compose build

echo "==> Menerapkan container (recreate jika image berubah)..."
docker compose up -d

wait_for_app
wait_for_database

echo "==> Menjalankan migrasi database..."
docker compose exec -T app php artisan migrate --force

CORE_SEED_NEEDED="$(core_seed_needed || true)"

if [ "$RUN_SEED_ON_UPDATE" = "true" ]; then
    echo "==> Menjalankan seeder inti aplikasi..."
    docker compose exec -T app php artisan db:seed --force
elif [ "$CORE_SEED_NEEDED" = "1" ]; then
    echo "==> Master data inti belum ada. Menjalankan seeder inti otomatis..."
    docker compose exec -T app php artisan db:seed --force
else
    echo "==> Seeder inti dilewati karena master data inti sudah tersedia."
fi

if [ "$RUN_DEMO_SEED_ON_UPDATE" = "true" ]; then
    echo "==> Menjalankan seeder demo user (khusus server uji)..."
    docker compose exec -T app php artisan db:seed --class=DemoUserSeeder --force
fi

echo "==> Menjalankan optimasi Laravel dan cache config..."
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan optimize

if [ "$BOOTSTRAP_ON_UPDATE" = "true" ] && [ -n "$BOOTSTRAP_EMAIL" ]; then
    echo "==> Memastikan akun bootstrap superadmin tersedia..."
    docker compose exec -T app php artisan app:bootstrap-superadmin "$BOOTSTRAP_EMAIL" --name="$BOOTSTRAP_NAME" --generate --only-if-missing
fi

echo "==> Restart web server dan queue worker..."
docker compose restart web queue

echo "==> Update branch ${TARGET_BRANCH} selesai! Aplikasi siap digunakan."
