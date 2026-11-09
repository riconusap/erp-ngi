#!/bin/sh
set -e

# Copy .env from .env.example jika belum ada
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# Pastikan direktori storage dan cache tersedia dan writable
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public/avatars \
         storage/app/public/covers \
         bootstrap/cache

chmod -R 777 storage bootstrap/cache

# Generate APP_KEY jika belum terisi
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating Application Key..."
    php artisan key:generate --force
fi

# Tunggu database siap jika DB_HOST diset
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database ($DB_HOST:${DB_PORT:-5432})..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-5432}"; do
        sleep 1
    done
    echo "Database is connected!"
fi

# Jalankan migrasi database
echo "Running database migrations..."
php artisan migrate --force

# Cek apakah database perlu di-seed
echo "Checking initial seed data..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
try {
    if (\App\Models\User::count() === 0) {
        echo 'Database empty. Running seeders...\n';
        Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        echo 'Database seeding completed successfully.\n';
    } else {
        echo 'Database already seeded. Skipping.\n';
    }
} catch (\Throwable \$e) {
    echo 'Seeding check error: ' . \$e->getMessage() . '\n';
}
" || true

# Pastikan symbolic link storage terpasang
php artisan storage:link --force 2>/dev/null || true

# Generate dokumentasi Swagger L5
php artisan l5-swagger:generate 2>/dev/null || true

echo "Backend ready. Starting server..."
exec "$@"
