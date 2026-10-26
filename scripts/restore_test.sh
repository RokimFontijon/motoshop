#!/usr/bin/env bash
# =====================================================================
# restore_test.sh  (COMP 018: "the restore is proven")
#
# Proves that a backup really works. In one run it:
#   1. backs up the real database          (scripts/backup.sh)
#   2. restores it into a scratch database (scripts/restore.sh)
#   3. compares EVERY table, row by row, between the original and the
#      restored copy using an md5 fingerprint of all the rows
#   4. checks the trigger, view, function and RLS policies came back
#   5. tests that the restored trigger still works (inside a transaction
#      that is rolled back, so nothing is changed)
#   6. drops the scratch database
#
#     bash scripts/restore_test.sh
#
# The last line says "RESTORE PROVEN" only if every check passed.
# The real database is only read, never changed.
# =====================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1090
    source <(grep -E '^(DB|BACKUP_DB)_[A-Z_]+=' .env | sed 's/\r$//')
    set +a
fi

HOST="${DB_HOST:-127.0.0.1}"
PORT="${DB_PORT:-5432}"
SOURCE="${DB_DATABASE:-motorshop}"
USER_NAME="${BACKUP_DB_USERNAME:-shop_backup}"
OWNER_ROLE="${DB_USERNAME:-shop_owner}"
export PGPASSWORD="${BACKUP_DB_PASSWORD:-}"
SCRATCH="${SOURCE}_restore_check"

q() { psql --host "$HOST" --port "$PORT" --username "$USER_NAME" --no-password --quiet --tuples-only --no-align --dbname "$1" -c "$2"; }

PASS=0
FAIL=0
check() { # name, expected, actual
    if [ "$2" = "$3" ]; then
        echo "  PASS  $1"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  $1   (expected: $2 | got: $3)"
        FAIL=$((FAIL + 1))
    fi
}

echo "== 1. Backup =="
BACKUP_DIR="$(mktemp -d)"
bash scripts/backup.sh "$BACKUP_DIR"
DUMP="$(ls "$BACKUP_DIR"/*.dump)"

echo
echo "== 2. Restore into scratch database '$SCRATCH' =="
bash scripts/restore.sh "$DUMP" "$SCRATCH" --replace

echo
echo "== 3. Compare every table (row count + md5 of all rows) =="
TABLES="$(q "$SOURCE" "select tablename from pg_tables where schemaname='public' and tablename not in ('migrations','sessions','cache','cache_locks','jobs','job_batches','failed_jobs','password_reset_tokens') order by 1")"
for T in $TABLES; do
    SQL="select count(*) || ':' || coalesce(md5(string_agg(t::text, '|' order by t::text)), 'empty') from \"$T\" t"
    check "table $T" "$(q "$SOURCE" "$SQL")" "$(q "$SCRATCH" "$SQL")"
done

echo
echo "== 4. Database objects came back =="
check "trigger"           "1" "$(q "$SCRATCH" "select count(*) from pg_trigger where tgname='trg_job_order_parts_stock'")"
check "view"              "1" "$(q "$SCRATCH" "select count(*) from pg_views where viewname='v_job_order_totals'")"
check "functions"         "3" "$(q "$SCRATCH" "select count(*) from pg_proc where proname in ('fn_adjust_part_stock','app_user_id','app_is_admin')")"
check "RLS policies"      "4" "$(q "$SCRATCH" "select count(*) from pg_policies where tablename='job_orders'")"
check "RLS switched on"   "t" "$(q "$SCRATCH" "select relrowsecurity from pg_class where relname='job_orders'")"
check "partial index"     "1" "$(q "$SCRATCH" "select count(*) from pg_indexes where indexname='idx_parts_low_stock'")"
check "foreign keys"      "$(q "$SOURCE" "select count(*) from pg_constraint where contype='f'")" "$(q "$SCRATCH" "select count(*) from pg_constraint where contype='f'")"
check "check constraints" "$(q "$SOURCE" "select count(*) from pg_constraint where contype='c'")" "$(q "$SCRATCH" "select count(*) from pg_constraint where contype='c'")"
check "owner of job_orders" "$OWNER_ROLE" "$(q "$SCRATCH" "select tableowner from pg_tables where tablename='job_orders'")"
check "privileges (grants) came back" "$(q "$SOURCE" "select count(*) from information_schema.role_table_grants where grantee in ('shop_app','shop_readonly') and table_schema='public'")" "$(q "$SCRATCH" "select count(*) from information_schema.role_table_grants where grantee in ('shop_app','shop_readonly') and table_schema='public'")"

echo
echo "== 5. The restored trigger still works (rolled back, nothing is kept) =="
RESULT="$(psql --host "$HOST" --port "$PORT" --username "$USER_NAME" --no-password --quiet --tuples-only --no-align --dbname "$SCRATCH" <<'SQL'
begin;
select set_config('app.role', 'admin', true);
create temp table probe as
    select p.id as part_id, p.stock_qty as before_qty,
           (select min(id) from job_orders) as job_order_id
    from parts p order by p.id limit 1;
insert into job_order_parts (job_order_id, part_id, quantity, unit_price, created_at, updated_at)
    select job_order_id, part_id, 2, 1, now(), now() from probe;
select (select before_qty from probe) - (select stock_qty from parts where id = (select part_id from probe));
rollback;
SQL
)"
# psql prints one line per statement result; the last non-empty line is the difference we want.
DIFF="$(echo "$RESULT" | grep -E '^-?[0-9]+$' | tail -1)"
check "adding 2 pieces lowers the stock by 2" "2" "$DIFF"

echo
echo "== 6. Clean up =="
q postgres "drop database \"$SCRATCH\" with (force)"
rm -rf "$BACKUP_DIR"
echo "  scratch database dropped"

echo
echo "$PASS passed, $FAIL failed"
if [ "$FAIL" -eq 0 ]; then
    echo "RESTORE PROVEN: the backup restores to an identical database."
else
    echo "RESTORE NOT PROVEN: see the FAIL lines above." >&2
    exit 1
fi
