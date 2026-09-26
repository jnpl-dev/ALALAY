#!/usr/bin/env bash
set -euo pipefail

echo "=== ALALAY Railway scheduler (every 60s) ==="

while true; do
    php artisan schedule:run --no-interaction &
    sleep 60
done
