#!/usr/bin/env bash
# =====================================================================
# restore.sh  (COMP 018: restore script)
#
# Restores a backup made by backup.sh into a database.
#
#     bash scripts/restore.sh backups/motorshop_20261025_101500.dump
#         -> restores into a NEW database called "motorshop_restored"
#
#     bash scripts/restore.sh backups/FILE.dump motorshop --replace
#         -> DISASTER RECOVERY: drops the database "motorshop" and rebuilds
#            it from the backup. Everything entered after the backup is lost!
#
# Normal use is the first form: restore beside the real database, check it,
# and only then decide what to do. scripts/restore_test.sh does that check
# for you automatically.
# =====================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

if [ $# -lt 1 ] || [ ! -f "$1" ]; then
    echo "Usage: bash scripts/restore.sh <backup.dump> [target_database] [--replace]" >&2
    exit 1
fi

BACKUP="$1"
TARGET="${2:-motorshop_restored}"
REPLACE="${3:-}"

if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1090
    source <(grep -E '^(DB|BACKUP_DB)_[A-Z_]+=' .env | sed 's/\r$//')
    set +a
fi

HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-5432}"
USER_NAME="${BACKUP_DB_USERNAME:-shop_backup}"   # the backup account (it has BYPASSRLS)
OWNER_ROLE="${DB_USERNAME:-shop_owner}"           # who should own the restored tables
export PGPASSWORD="${BACKUP_DB_PASSWORD:-}"

PSQL=(psql --host "$HOST" --port "$PORT" --username "$USER_NAME" --no-password --quiet --tuples-only --no-align)

exists() { "${PSQL[@]}" --dbname postgres -c "select 1 from pg_database where datname = '$1'" | grep -q 1; }

if exists "$TARGET"; then
    if [ "$REPLACE" != "--replace" ]; then
        echo "Database '$TARGET' already exists. Add --replace to drop it and restore over it." >&2
        exit 1
    fi
    echo "Dropping database '$TARGET' ..."
    "${PSQL[@]}" --dbname postgres -c "drop database \"$TARGET\" with (force)"
fi

echo "Creating database '$TARGET' ..."
"${PSQL[@]}" --dbname postgres -c "create database \"$TARGET\" encoding 'UTF8'"

echo "Restoring $BACKUP into '$TARGET' ..."
# --no-owner: the objects become owned by the user who runs the restore (fixed again below).
# The backup account is used because loading rows into job_orders must bypass row level security.
# --exit-on-error: stop at the first problem instead of leaving a half-restored database.
pg_restore --host "$HOST" --port "$PORT" --username "$USER_NAME" --no-password \
           --dbname "$TARGET" --no-owner --exit-on-error "$BACKUP"

# The tables were restored as the backup account. Hand everything back to the real owner,
# so the web application (which logs in as shop_owner or shop_app) can use the database.
# (The database itself is handed over first: the schema "public" belongs to the database owner,
#  and the new table owner needs CREATE rights in it.)
"${PSQL[@]}" --dbname postgres -c "alter database \"$TARGET\" owner to \"$OWNER_ROLE\""
"${PSQL[@]}" --dbname "$TARGET" -c "reassign owned by \"$USER_NAME\" to \"$OWNER_ROLE\""

echo "OK: '$TARGET' restored and owned by '$OWNER_ROLE'."
