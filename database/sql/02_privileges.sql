-- =====================================================================
-- 02_privileges.sql  (COMP 018: roles and privileges, step 2 of 2)
--
-- Executed by the last migration, after all tables exist. Run it again by
-- hand whenever you add a new table:
--
--     psql -U shop_owner -d motorshop -f database/sql/02_privileges.sql
--
-- If the roles from 00_bootstrap.sql do not exist (for example in the test
-- database), the blocks do nothing.
-- =====================================================================

DO $$
DECLARE
    t text;
BEGIN
    -- shop_app: what the web application may use at runtime.
    -- It can read and write data but cannot CREATE / ALTER / DROP / TRUNCATE.
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO shop_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO shop_app;
        -- The app does not need Laravel's migration bookkeeping table.
        IF to_regclass('public.migrations') IS NOT NULL THEN
            REVOKE ALL ON migrations FROM shop_app;
        END IF;
    END IF;

    -- shop_readonly: for reports. SELECT only.
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'shop_readonly') THEN
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO shop_readonly;

        -- Hide framework / sensitive tables completely.
        FOREACH t IN ARRAY ARRAY['migrations', 'sessions', 'password_reset_tokens', 'users',
                                 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs']
        LOOP
            IF to_regclass('public.' || t) IS NOT NULL THEN
                EXECUTE format('REVOKE ALL ON %I FROM shop_readonly', t);
            END IF;
        END LOOP;

        -- Column-level privilege: the report role may read who the staff are,
        -- but never the password column.
        GRANT SELECT (id, name, email, role_id, created_at) ON users TO shop_readonly;
    END IF;
END
$$;
