-- =====================================================================
-- 00_bootstrap.sql  (COMP 018: roles and privileges, step 1 of 2)
--
-- Run ONCE as the PostgreSQL superuser, BEFORE "php artisan migrate":
--
--     psql -U postgres -f database/sql/00_bootstrap.sql
--
-- It creates four database roles with different privileges and two
-- databases: "motorshop" (the real one) and "motorshop_test" (used only by
-- the automated tests). The passwords are for classroom use only.
--
--   shop_owner    owns the databases and all tables. Used by Laravel
--                 migrations (DDL: CREATE / ALTER / DROP). Can create DBs
--                 so the restore test can build a scratch database.
--   shop_app      what the running web app may use. Can read/write the
--                 data (SELECT, INSERT, UPDATE, DELETE) but cannot change
--                 the structure.
--   shop_readonly for reports. SELECT only, and it cannot see
--                 users.password.
--   shop_backup   used ONLY by scripts/backup.sh and scripts/restore.sh.
--                 job_orders has FORCE ROW LEVEL SECURITY, which also
--                 blocks the table owner, so a plain pg_dump as shop_owner
--                 fails. This role has BYPASSRLS (it sees every row) and
--                 pg_read_all_data (it can read every table), but it cannot
--                 be used to log in to the web app. Keep its password with
--                 the database administrator only.
--
-- The exact GRANT statements are in 02_privileges.sql, which the last
-- migration runs for you.
-- =====================================================================

-- Roles (CREATE ROLE has no IF NOT EXISTS, so we check first).
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_owner') THEN
        CREATE ROLE shop_owner LOGIN CREATEDB PASSWORD 'owner_pass_123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_app') THEN
        CREATE ROLE shop_app LOGIN PASSWORD 'app_pass_123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_readonly') THEN
        CREATE ROLE shop_readonly LOGIN PASSWORD 'readonly_pass_123';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_backup') THEN
        CREATE ROLE shop_backup LOGIN CREATEDB BYPASSRLS PASSWORD 'backup_pass_123';
    END IF;
END
$$;

-- shop_backup may read every table (PostgreSQL 14 or newer), and it is a member of
-- shop_owner so that a restored database can be handed back to shop_owner.
GRANT pg_read_all_data TO shop_backup;
GRANT shop_owner TO shop_backup;

-- The databases (CREATE DATABASE cannot run inside DO, so \gexec is used).
SELECT 'CREATE DATABASE motorshop OWNER shop_owner ENCODING ''UTF8'''
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'motorshop')
\gexec

SELECT 'CREATE DATABASE motorshop_test OWNER shop_owner ENCODING ''UTF8'''
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'motorshop_test')
\gexec

-- Only the shop roles may connect to the real database.
REVOKE ALL ON DATABASE motorshop FROM PUBLIC;
GRANT CONNECT ON DATABASE motorshop TO shop_owner, shop_app, shop_readonly, shop_backup;

\connect motorshop

-- The restricted roles may look inside the schema; they cannot create objects.
GRANT USAGE ON SCHEMA public TO shop_app, shop_readonly;
