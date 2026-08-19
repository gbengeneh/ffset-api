#!/bin/sh
set -eu
: "${1:?Usage: restore-postgres.sh backup.dump}"
: "${POSTGRES_CONTAINER:?Set POSTGRES_CONTAINER}"
: "${DB_DATABASE:?Set DB_DATABASE}"
: "${DB_USERNAME:?Set DB_USERNAME}"
cat "$1" | docker exec -i "$POSTGRES_CONTAINER" pg_restore -U "$DB_USERNAME" -d "$DB_DATABASE" --clean --if-exists
