-- =====================================================================
-- 03_explain_demo.sql  (COMP 018: indexes and reading EXPLAIN output)
--
-- Shows, with real numbers, what an index does. It runs inside one
-- transaction and ends with ROLLBACK, so your real data is NOT changed.
--
--     psql -U shop_owner -d motorshop -f database/sql/03_explain_demo.sql
--
-- HOW TO READ EXPLAIN (ANALYZE):
--   Seq Scan          reads EVERY row of the table (slow on big tables).
--   Index Scan        jumps straight to the matching rows using an index.
--   Bitmap Index Scan like an index scan, but collects many matches first.
--   cost=a..b         the planner's estimate (a = first row, b = all rows).
--   rows=             how many rows the planner expects / actually returned.
--   Execution Time    real milliseconds. Compare before vs after the index.
-- =====================================================================

BEGIN;

-- Row level security is active on job_orders, so say we act as an admin.
SELECT set_config('app.role', 'admin', false) AS acting_as;

-- Step 0: build a realistic amount of data.
--   3,000 motorcycles and 60,000 job orders (about 20 per motorcycle),
--   and 20,000 spare parts, of which only a few are low on stock.
INSERT INTO motorcycles (customer_id, motorcycle_model_id, plate_number, current_odometer, created_at, updated_at)
SELECT (SELECT min(id) FROM customers), (SELECT min(id) FROM motorcycle_models), 'DEMO-' || g, 0, now(), now()
FROM generate_series(1, 3000) AS g;

INSERT INTO job_orders (job_number, motorcycle_id, service_date, odometer_at_service, status, labor_cost, created_at, updated_at)
SELECT  'DEMO-' || g,
        m.id,
        DATE '2023-01-01' + (g % 1000),
        1000 + g,
        'completed',
        150,
        now(), now()
FROM generate_series(1, 60000) AS g
JOIN (SELECT id, row_number() OVER (ORDER BY id) AS rn
      FROM motorcycles WHERE plate_number LIKE 'DEMO-%') m
  ON m.rn = (g % 3000) + 1;

INSERT INTO parts (part_code, name, unit_price, stock_qty, reorder_level, created_at, updated_at)
SELECT 'DEMO-' || g, 'Demo part ' || g, 100, CASE WHEN g % 500 = 0 THEN 1 ELSE 50 END, 5, now(), now()
FROM generate_series(1, 20000) AS g;

ANALYZE motorcycles;
ANALYZE job_orders;
ANALYZE parts;

-- The motorcycle we will look up (one of the demo motorcycles).
SELECT min(id) AS demo_mc FROM motorcycles WHERE plate_number LIKE 'DEMO-%' \gset

\echo
\echo '=== QUERY 1: service history of ONE motorcycle (used on the motorcycle page) ==='

\echo '--- 1a. WITHOUT any helpful index: expect Seq Scan on job_orders ---'
\echo '    (idx_job_orders_service_date is dropped too, otherwise the planner'
\echo '     could walk that index and filter, which hides the problem)'
DROP INDEX idx_job_orders_motorcycle_date;
DROP INDEX idx_job_orders_service_date;
EXPLAIN (ANALYZE, COSTS, TIMING OFF)
SELECT * FROM job_orders WHERE motorcycle_id = :demo_mc ORDER BY service_date DESC LIMIT 5;

\echo '--- 1b. WITH the composite index (motorcycle_id, service_date): expect Index Scan ---'
CREATE INDEX idx_job_orders_motorcycle_date ON job_orders (motorcycle_id, service_date);
ANALYZE job_orders;
EXPLAIN (ANALYZE, COSTS, TIMING OFF)
SELECT * FROM job_orders WHERE motorcycle_id = :demo_mc ORDER BY service_date DESC LIMIT 5;

\echo
\echo '=== QUERY 2: low stock parts (used on the dashboard) ==='

\echo '--- 2a. WITHOUT the partial index: expect Seq Scan on parts ---'
DROP INDEX idx_parts_low_stock;
EXPLAIN (ANALYZE, COSTS, TIMING OFF)
SELECT name, stock_qty FROM parts WHERE stock_qty <= reorder_level ORDER BY name;

\echo '--- 2b. WITH the partial index (only low stock rows are in it): expect Bitmap/Index Scan ---'
CREATE INDEX idx_parts_low_stock ON parts (name) WHERE stock_qty <= reorder_level;
ANALYZE parts;
EXPLAIN (ANALYZE, COSTS, TIMING OFF)
SELECT name, stock_qty FROM parts WHERE stock_qty <= reorder_level ORDER BY name;

-- Undo everything: the demo rows and the index changes disappear.
ROLLBACK;
