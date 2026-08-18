#!/usr/bin/env bash
set -euo pipefail

echo "=== ALALAY Railway queue worker ==="

exec php artisan queue:work database --sleep=3 --tries=3
