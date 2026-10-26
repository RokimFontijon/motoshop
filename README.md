# Motorcycle Repair Shop: Job Order, Inventory and Service Reminder System

A web-based system for a motorcycle repair shop, built with **Laravel 13** and **PostgreSQL**.
It records customers, motorcycles and job orders, keeps the spare-parts inventory, and estimates
when each motorcycle is next due for service.

Made for **COMP 016 (Web Development)** and **COMP 018 (Database Administration)** midterm projects.

> The due date is a **rule-based estimate** (manufacturer interval + the rider's own average
> kilometers per day). It does not use AI or machine learning, and the odometer is typed in by
> the shop staff at every job order (no CAN bus reading).

---

## 1. What you need

| Software | Version | Notes |
|---|---|---|
| PHP | 8.3 or newer | The `pdo_pgsql` and `pgsql` extensions must be **enabled** (see Troubleshooting) |
| Composer | 2.x | |
| PostgreSQL | 14 or newer | Tested on 16. The `psql` command must work in your terminal |
| Git Bash | any | Windows only. The backup/restore scripts are `bash` scripts |

## 2. Setup (about 5 minutes)

Open a terminal in this folder.

```bash
# 1. Install the PHP packages
composer install

# 2. Create the database roles and the databases (run as the PostgreSQL superuser)
#    IMPORTANT: use psql. The script uses psql commands (\gexec, \connect),
#    so it will NOT run in the pgAdmin query tool.
psql -U postgres -f database/sql/00_bootstrap.sql

# 3. Create your .env and the app key
cp .env.example .env            # Windows cmd: copy .env.example .env
php artisan key:generate

# 4. Build the tables and load the demo data (the COMP 016 "migrate:fresh --seed" check)
php artisan migrate:fresh --seed

# 5. Run it
php artisan serve               # open http://localhost:8000
```

### Demo accounts (password for all: `password`)

| Email | Role | Can do |
|---|---|---|
| `admin@motorshop.test` | admin | Everything: delete records, manage parts and staff accounts |
| `mechanic1@motorshop.test` | mechanic | Add and edit customers, motorcycles and **their own** job orders |
| `mechanic2@motorshop.test` | mechanic | Same as above |

You can also create a new account at `/register`. Public registration always creates a **mechanic**.

## 3. Run the tests

```bash
composer test
```

74 tests (66 feature tests, 8 unit tests). They use the separate database `motorshop_test`
(created by step 2), so your demo data is never touched.

## 4. What the system does

- **Customers and motorcycles:** one customer can own many motorcycles; each motorcycle has a model, plate number and odometer.
- **Job orders:** each visit records the odometer, the services done, the mechanic, the parts used and the labor. The total is calculated by a database view, never stored.
- **Inventory:** stock goes down automatically when a part is added to a job order, and is returned when it is removed (database trigger). The dashboard lists low-stock parts.
- **Service reminders:** the dashboard shows which services are **Overdue**, **Due soon** (within 14 days) or **OK**, and the **Reports** page compares the estimated due date with the date the customer actually came back.
- **Roles:** admins can do everything; mechanics can only change their own job orders (checked in the controller **and** again in PostgreSQL).

### How the next due date is computed

For each service type (for example Change Oil, every 3,000 km or 3 months):

```
mileage due      = odometer at last service + interval_km
time due         = date of last service + interval_months
average km/day   = (last odometer - first odometer) / days between first and last visit
mileage date     = date of latest visit + (km remaining / average km per day)
next due date    = the EARLIER of mileage date and time due
```

If a motorcycle has fewer than two visits, a default of 20 km/day is used and the page says so.
The code is in `app/Services/ServiceDueCalculator.php` (plain PHP, no database), with its own unit tests.

> **Before the final defense:** the service intervals in `database/seeders/data/catalog.php` are
> sample values. Confirm them with the owner's manual of each model and with the shop's mechanic,
> then edit that file and run `php artisan migrate:fresh --seed` again.

## 5. Project map

```
app/Models/                 Eloquent models ($fillable, relationships)
app/Http/Controllers/       One controller per resource (validation lives here)
app/Http/Middleware/        EnsureRole (admin check), SetDatabaseSession (tells PostgreSQL who is logged in)
app/Services/               ServiceDueCalculator (the rules), ServiceDueReport (connects them to the database)
routes/web.php              All routes, grouped by guest / auth / admin
resources/views/            Blade pages (layout, dashboard, customers, motorcycles, job orders, parts, users, reports)
database/migrations/        12 migrations for this project (schema, constraints)
database/sql/               Plain SQL for COMP 018 (see section 6)
database/seeders/           Roles, catalog (models, intervals, parts) and fictional demo data
scripts/                    backup.sh, restore.sh, restore_test.sh
docs/                       erd.png / erd.mmd, EXPLAIN and privilege demo outputs, DEFENSE_NOTES.md
tests/                      PHPUnit tests
```

ERD: [`docs/erd.png`](docs/erd.png) (source: `docs/erd.mmd`).

## 6. COMP 018: database administration

| Topic | Where | How to show it |
|---|---|---|
| Normalization, keys, relationships | `database/migrations/`, `docs/erd.png` | Open the ERD. Models are in a catalog table, not repeated text; many-to-many through `job_order_services`; composite PK; unique and CHECK constraints |
| Indexes and EXPLAIN | `database/sql/03_explain_demo.sql`, `docs/explain_output.txt` | `psql -U shop_owner -d motorshop -f database/sql/03_explain_demo.sql` |
| Function and trigger | `database/sql/01_db_objects.sql` | `fn_adjust_part_stock()` + trigger `trg_job_order_parts_stock` |
| View | `database/sql/01_db_objects.sql` | `v_job_order_totals` |
| Roles and privileges | `database/sql/00_bootstrap.sql`, `02_privileges.sql`, `04_privilege_demo.sql` | Four roles with different rights; `docs/privilege_demo_output.txt` |
| Row level security | `database/sql/01_db_objects.sql` | Policies on `job_orders`; the demo shows mechanic vs admin |
| Backup | `scripts/backup.sh` | `bash scripts/backup.sh` |
| Restore is proven | `scripts/restore_test.sh` | `bash scripts/restore_test.sh` (ends with `RESTORE PROVEN`) |
| Laravel connection | `.env.example`, `config/database.php` | `DB_*` values; switch to `shop_app` for the least-privilege user |

The four database roles:

| Role | Purpose | Can |
|---|---|---|
| `shop_owner` | Owns the tables, runs migrations | Everything in `motorshop` |
| `shop_app` | The running web app (least privilege) | SELECT / INSERT / UPDATE / DELETE only, no DDL |
| `shop_readonly` | Reports | SELECT only; cannot read `users.password` |
| `shop_backup` | Backup and restore scripts only | Reads every row (bypasses RLS) |

To run the web app as the least-privilege user, set `DB_USERNAME=shop_app` and
`DB_PASSWORD=app_pass_123` in `.env` **after** running the migrations.

The passwords in `00_bootstrap.sql` and `.env.example` are for classroom use only. Change them for real use.

## 7. Troubleshooting

- **`could not find driver`**: open `php.ini` and remove the `;` before `extension=pdo_pgsql` and `extension=pgsql`, then restart the terminal.
- **`psql` is not recognized** (Windows): add PostgreSQL's `bin` folder (for example `C:\Program Files\PostgreSQL\16\bin`) to PATH.
- **`password authentication failed for user "shop_owner"`**: you skipped step 2, or `.env` has a different password than `00_bootstrap.sql`.
- **`permission denied` after switching to `shop_app`**: run `psql -U shop_owner -d motorshop -f database/sql/02_privileges.sql` again (needed whenever a table is added).
- **Tests say the database does not exist**: step 2 creates `motorshop_test`; run it again (it is safe to repeat).
- **Backup script says `pg_dump: command not found`**: PostgreSQL's `bin` folder is not in PATH (see above).
