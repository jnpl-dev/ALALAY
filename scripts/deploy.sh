#!/usr/bin/env bash
set -euo pipefail

echo "=== ALALAY Deployment ==="

echo "1. Composer install (no dev)..."
composer install --no-dev --optimize-autoloader

echo "2. Composer audit..."
composer audit --no-dev
if [ $? -ne 0 ]; then
    echo "Composer audit failed — deployment aborted."
    exit 1
fi

echo "3. npm ci..."
npm ci

echo "4. npm audit..."
npm audit --audit-level=high || echo "npm audit warnings found — review manually"

echo "5. Build frontend..."
npm run build

echo "6. Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "7. Run migrations..."
php artisan migrate --force

echo "=== Deployment complete ==="
