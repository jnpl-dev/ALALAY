#!/usr/bin/env bash
set -euo pipefail

echo "=== ALALAY Railway build (web service) ==="

echo "1. Install PHP dependencies (no dev)..."
composer install --no-dev --optimize-autoloader

echo "2. Build frontend (JS deps already installed by Railpack)..."
npm run build

echo "=== Build complete (artisan caches + migrations run in preDeployCommand) ==="
