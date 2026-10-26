-- =====================================================================
-- 04_privilege_demo.sql  (COMP 018: prove that the roles really differ)
--
-- Run as the PostgreSQL superuser. Each "ERROR" you see below is EXPECTED:
-- it is the proof that the role is not allowed to do that.
--
--     psql -U postgres -d motorshop -f database/sql/04_privilege_demo.sql
--
-- This script assumes the demo data from "php artisan migrate:fresh --seed"
-- (user 1 = admin, users 2 and 3 = mechanics).
-- =====================================================================

\echo '=== shop_readonly: can read business data ==='
SET ROLE shop_readonly;
SELECT count(*) AS customers FROM customers;

\echo '--- ...but cannot write (expect: permission denied) ---'
INSERT INTO customers (full_name, created_at, updated_at) VALUES ('Should Fail', now(), now());

\echo '--- ...cannot read the password column (expect: permission denied) ---'
SELECT password FROM users LIMIT 1;

\echo '--- ...can read non-sensitive user columns ---'
SELECT id, name, email FROM users ORDER BY id LIMIT 3;
RESET ROLE;

\echo
\echo '=== shop_app: can read and write data but cannot change structure ==='
SET ROLE shop_app;
SELECT count(*) AS parts FROM parts;
\echo '--- DDL is not allowed (expect: must be owner / permission denied) ---'
DROP TABLE customers;
\echo '--- TRUNCATE is not allowed (expect: permission denied) ---'
TRUNCATE TABLE customers CASCADE;
RESET ROLE;

\echo
\echo '=== ROW LEVEL SECURITY on job_orders (role shop_app) ==='
\echo '--- As a MECHANIC (user id 2): can read all, can only change own job orders ---'
SET ROLE shop_app;
SELECT set_config('app.user_id', '2', false), set_config('app.role', 'mechanic', false);
SELECT count(*) AS visible_job_orders FROM job_orders;

BEGIN;
\echo 'Update a job order of mechanic 2 (expect: UPDATE n, n > 0)'
UPDATE job_orders SET complaint = complaint WHERE mechanic_id = 2;
\echo 'Update a job order of someone else (expect: UPDATE 0, the policy hides those rows)'
UPDATE job_orders SET complaint = complaint WHERE mechanic_id IS DISTINCT FROM 2;
\echo 'Delete a job order as mechanic (expect: DELETE 0, only admin may delete)'
DELETE FROM job_orders WHERE mechanic_id = 2;
ROLLBACK;

\echo '--- As an ADMIN: can change any job order ---'
SELECT set_config('app.user_id', '1', false), set_config('app.role', 'admin', false);
BEGIN;
UPDATE job_orders SET complaint = complaint;
ROLLBACK;
RESET ROLE;
