#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR="${BACKUP_PATH:-/var/www/alalay/storage/app/backups}"
DB_DATABASE="${DB_DATABASE:-alalay}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
ENCRYPT_PASS="${BACKUP_ENCRYPT_PASS}"
SUPABASE_URL="${SUPABASE_STORAGE_ENDPOINT%/storage/v1/s3}"
SUPABASE_BACKUP_KEY="${SUPABASE_KEY}"
SUPABASE_BACKUP_SECRET="${SUPABASE_SECRET}"
SUPABASE_BACKUP_BUCKET="${SUPABASE_BACKUP_BUCKET:-alalay-backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"

if [ -z "$ENCRYPT_PASS" ]; then
    echo "ERROR: BACKUP_ENCRYPT_PASS is not set" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

FILENAME="alalay_$(date '+%Y-%m-%d_%H-%M-%S').sql.gz.enc"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

echo "=== ALALAY Backup: $(date) ==="
echo "Dumping database ${DB_DATABASE}..."

MYSQL_OPTS=""
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_OPTS="-p${DB_PASSWORD}"
fi

mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    -u "${DB_USERNAME}" ${MYSQL_OPTS} \
    "${DB_DATABASE}" \
    | gzip \
    | openssl enc -aes-256-cbc -pbkdf2 -pass pass:"${ENCRYPT_PASS}" \
    > "${FILEPATH}"

echo "Local backup saved: ${FILEPATH} ($(du -h "${FILEPATH}" | cut -f1))"

# Offsite upload to Supabase
if [ -n "$SUPABASE_URL" ] && [ -n "$SUPABASE_BACKUP_KEY" ] && [ -n "$SUPABASE_BACKUP_SECRET" ]; then
    echo "Uploading to Supabase Storage (${SUPABASE_BACKUP_BUCKET})..."

    RESOURCE="/${SUPABASE_BACKUP_BUCKET}/db/${FILENAME}"
    S3_ENDPOINT="${SUPABASE_URL}/storage/v1/s3"

    CONTENT_TYPE="application/octet-stream"
    DATE=$(date -R)
    STRING_TO_SIGN="PUT\n\n${CONTENT_TYPE}\n${DATE}\n${RESOURCE}"
    SIGNATURE=$(echo -en "${STRING_TO_SIGN}" | openssl dgst -sha256 -hmac "${SUPABASE_BACKUP_SECRET}" -binary | base64 -w 0)

    curl -s -X PUT \
        "${S3_ENDPOINT}${RESOURCE}" \
        -H "Host: $(echo "$S3_ENDPOINT" | awk -F/ '{print $3}')" \
        -H "Date: ${DATE}" \
        -H "Content-Type: ${CONTENT_TYPE}" \
        -H "Authorization: AWS ${SUPABASE_BACKUP_KEY}:${SIGNATURE}" \
        --data-binary @"${FILEPATH}"

    echo "Offsite upload complete."
fi

# Prune old backups
echo "Pruning backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -name 'alalay_*.sql.gz.enc' -mtime +"${RETENTION_DAYS}" -delete

echo "=== Backup complete: $(date) ==="
