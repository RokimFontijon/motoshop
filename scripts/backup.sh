#!/usr/bin/env bash
# =====================================================================
# backup.sh  (COMP 018: backup script)
#
# Makes a complete backup of the "motorshop" database with pg_dump.
#
#     bash scripts/backup.sh              # saves into the backups/ folder
#     bash scripts/backup.sh /some/folder # saves into another folder
#
# It reads DB_HOST, DB_PORT and DB_DATABASE from the .env file, the same ones
# Laravel uses. The login is the backup account (BACKUP_DB_USERNAME and
# BACKUP_DB_PASSWORD in .env, created by database/sql/00_bootstrap.sql).
#
# Why a separate account? job_orders has FORCE ROW LEVEL SECURITY, which also
# applies to its owner, so pg_dump as shop_owner stops with "query would be
# affected by row-level security policy". shop_backup has BYPASSRLS, so it
# always dumps every row.
# On Windows, run it in "Git Bash" (it comes with Git) and make sure the
# PostgreSQL "bin" folder is in your PATH so pg_dump can be found.
#
# The backup is a "custom format" archive (-Fc): compressed, and pg_restore
# can restore the whole database or only some tables from it. It contains
# the tables, data, indexes, the view, the function, the trigger, the RLS
# policies and the privileges.
# =====================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

# Load DB_* values from .env (only those lines).
if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1090
    source <(grep -E '^(DB|BACKUP_DB)_[A-Z_]+=' .env | sed 's/\r$//')
    set +a
fi

HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-5432}"
NAME="${DB_DATABASE:-motorshop}"
USER_NAME="${BACKUP_DB_USERNAME:-shop_backup}"
export PGPASSWORD="${BACKUP_DB_PASSWORD:-}"

OUT_DIR="${1:-backups}"
mkdir -p "$OUT_DIR"

STAMP="$(date +%Y%m%d_%H%M%S)"
FILE="$OUT_DIR/${NAME}_${STAMP}.dump"

echo "Backing up database '$NAME' on $HOST:$PORT as '$USER_NAME' ..."
pg_dump --host "$HOST" --port "$PORT" --username "$USER_NAME" \
        --format=custom --no-password --file "$FILE" "$NAME"

# A backup you never checked is only a hope. List the archive to prove it is readable.
OBJECTS="$(pg_restore --list "$FILE" | grep -c -v '^;' || true)"

echo "OK: $FILE"
echo "    size: $(du -h "$FILE" | cut -f1), objects inside: $OBJECTS"
