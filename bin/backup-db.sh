#!/usr/bin/env bash
# One-off MariaDB backup for the production stack.
# Usage: ./bin/backup-db.sh [output-directory]
set -euo pipefail
cd "$(dirname "$0")/.."

COMPOSE="docker compose -f docker-compose.prod.yml"
OUT_DIR="${1:-./backups}"
mkdir -p "$OUT_DIR"

TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
TARGET="${OUT_DIR}/ticketera-${TIMESTAMP}.sql.gz"

echo "→ Generando backup en ${TARGET}..."
$COMPOSE exec -T mariadb sh -c \
    'mariadb-dump --user="$MARIADB_USER" --password="$MARIADB_PASSWORD" --single-transaction --routines --triggers --no-tablespaces "$MARIADB_DATABASE"' \
    | gzip > "$TARGET"

echo "✓ Backup listo: ${TARGET}"
