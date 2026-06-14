#!/bin/sh
# Periodic MariaDB dump with retention. Runs inside the `backup` service.
set -eu

: "${BACKUP_INTERVAL_SECONDS:=86400}"
: "${BACKUP_KEEP:=7}"
: "${DB_HOST:=mariadb}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"

mkdir -p /backups

echo "[backup] interval=${BACKUP_INTERVAL_SECONDS}s keep=${BACKUP_KEEP}"

while true; do
    timestamp="$(date +%Y%m%d-%H%M%S)"
    target="/backups/ticketera-${timestamp}.sql.gz"

    echo "[backup] $(date -Iseconds) dumping ${DB_DATABASE} -> ${target}"
    if mariadb-dump \
            --host="${DB_HOST}" \
            --user="${DB_USERNAME}" \
            --password="${DB_PASSWORD}" \
            --single-transaction --routines --triggers --no-tablespaces \
            "${DB_DATABASE}" | gzip > "${target}"; then
        echo "[backup] ok ${target}"
    else
        echo "[backup] FAILED ${target}" >&2
        rm -f "${target}"
    fi

    # Retention: keep only the newest $BACKUP_KEEP dumps.
    ls -1t /backups/ticketera-*.sql.gz 2>/dev/null \
        | tail -n +"$((BACKUP_KEEP + 1))" \
        | xargs -r rm -f

    sleep "${BACKUP_INTERVAL_SECONDS}"
done
