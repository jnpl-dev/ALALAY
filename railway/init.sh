#!/usr/bin/env bash
set -euo pipefail

echo "=== ALALAY Railway build (web service) ==="

echo "1. Install PHP dependencies (no dev)..."
composer install --no-dev --optimize-autoloader

echo "2. Install JS dependencies..."
npm ci

echo "3. Build frontend..."
npm run build

echo "=== Build complete (artisan caches + migrations run in preDeployCommand) ==="
