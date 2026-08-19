#!/bin/sh
set -eu
: "${POSTGRES_CONTAINER:?Set POSTGRES_CONTAINER}"
: "${DB_DATABASE:?Set DB_DATABASE}"
: "${DB_USERNAME:?Set DB_USERNAME}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
mkdir -p "$BACKUP_DIR"
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
docker exec "$POSTGRES_CONTAINER" pg_dump -U "$DB_USERNAME" -Fc "$DB_DATABASE" > "$BACKUP_DIR/ffset-$STAMP.dump"
find "$BACKUP_DIR" -type f -name 'ffset-*.dump' -mtime "+$RETENTION_DAYS" -delete
echo "Backup created: $BACKUP_DIR/ffset-$STAMP.dump"
