-- =====================================================================
-- 01_db_objects.sql  (COMP 018: function, trigger, view, index, RLS)
--
-- Executed by the last migration (2026_10_24_000012_create_database_objects).
-- You can also read it and run it by hand during the defense:
--
--     psql -U shop_owner -d motorshop -f database/sql/01_db_objects.sql
--
-- Every statement is safe to run again (CREATE OR REPLACE / IF EXISTS).
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. TRIGGER + FUNCTION: automatic stock update
--
-- When a part line is added to a job order, the stock goes down. When the
-- line is changed or removed (or the whole job order is deleted), the
-- stock is given back. The web app never has to do this by itself, so
-- the stock stays correct no matter which program writes the data.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_adjust_part_stock() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Lock the part row so two mechanics cannot take the last item at once.
        IF (SELECT stock_qty FROM parts WHERE id = NEW.part_id FOR UPDATE) < NEW.quantity THEN
            RAISE EXCEPTION 'Not enough stock for part id % (requested %)', NEW.part_id, NEW.quantity
                USING ERRCODE = 'check_violation';
        END IF;
        UPDATE parts SET stock_qty = stock_qty - NEW.quantity WHERE id = NEW.part_id;
        RETURN NEW;

    ELSIF TG_OP = 'UPDATE' THEN
        -- Give back the old quantity, then take the new one.
        UPDATE parts SET stock_qty = stock_qty + OLD.quantity WHERE id = OLD.part_id;
        UPDATE parts SET stock_qty = stock_qty - NEW.quantity WHERE id = NEW.part_id;
        RETURN NEW;

    ELSE
        -- DELETE: give the quantity back to stock.
        UPDATE parts SET stock_qty = stock_qty + OLD.quantity WHERE id = OLD.part_id;
        RETURN OLD;
    END IF;
END;
$$;

DROP TRIGGER IF EXISTS trg_job_order_parts_stock ON job_order_parts;
CREATE TRIGGER trg_job_order_parts_stock
    AFTER INSERT OR UPDATE OR DELETE ON job_order_parts
    FOR EACH ROW EXECUTE FUNCTION fn_adjust_part_stock();


-- ---------------------------------------------------------------------
-- 2. VIEW: job order totals (calculated, never stored)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_job_order_totals AS
SELECT  jo.id                                                          AS job_order_id,
        jo.labor_cost                                                  AS labor_cost,
        COALESCE(SUM(jp.quantity * jp.unit_price), 0)::numeric(12,2)   AS parts_total,
        (jo.labor_cost + COALESCE(SUM(jp.quantity * jp.unit_price), 0))::numeric(12,2) AS grand_total
FROM    job_orders jo
LEFT JOIN job_order_parts jp ON jp.job_order_id = jo.id
GROUP BY jo.id, jo.labor_cost;


-- ---------------------------------------------------------------------
-- 3. PARTIAL INDEX: only the rows that are low on stock
--
-- The "low stock" list is read on every dashboard load, but only a few
-- parts are low. An index that contains only those rows is tiny and fast.
-- ---------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_parts_low_stock
    ON parts (name)
    WHERE stock_qty <= reorder_level;


-- ---------------------------------------------------------------------
-- 4. ROW LEVEL SECURITY on job_orders
--
-- The Laravel app tells PostgreSQL who is logged in by setting two session
-- variables on every request (see app/Http/Middleware/SetDatabaseSession.php):
--     app.user_id   the logged-in user's id
--     app.role      'admin' or 'mechanic'
-- The policies below read them. Rules:
--     everybody can SEE every job order
--     an admin can add / change / delete any job order
--     a mechanic can only add or change job orders assigned to themself
--
-- FORCE makes the rules apply to the table owner too (shop_owner, the
-- default Laravel user), so the rules are active even in the simplest
-- setup. Only a PostgreSQL superuser (for example "postgres") bypasses RLS.
-- When no user is logged in (for example in a seeder), set the role first:
--     SELECT set_config('app.role', 'admin', false);
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION app_user_id() RETURNS bigint
LANGUAGE sql STABLE
AS $$ SELECT NULLIF(current_setting('app.user_id', true), '')::bigint $$;

CREATE OR REPLACE FUNCTION app_is_admin() RETURNS boolean
LANGUAGE sql STABLE
AS $$ SELECT COALESCE(current_setting('app.role', true), '') = 'admin' $$;

ALTER TABLE job_orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE job_orders FORCE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS job_orders_select ON job_orders;
DROP POLICY IF EXISTS job_orders_insert ON job_orders;
DROP POLICY IF EXISTS job_orders_update ON job_orders;
DROP POLICY IF EXISTS job_orders_delete ON job_orders;

CREATE POLICY job_orders_select ON job_orders FOR SELECT
    USING (true);

CREATE POLICY job_orders_insert ON job_orders FOR INSERT
    WITH CHECK (app_is_admin() OR mechanic_id = app_user_id());

CREATE POLICY job_orders_update ON job_orders FOR UPDATE
    USING      (app_is_admin() OR mechanic_id = app_user_id())
    WITH CHECK (app_is_admin() OR mechanic_id = app_user_id());

CREATE POLICY job_orders_delete ON job_orders FOR DELETE
    USING (app_is_admin());
