# Defense notes (COMP 016 + COMP 018)

Gabay ito para maipaliwanag mo ang bawat bahagi ng project sa sarili mong salita.
Ang sabi sa sheet: **individual ang grade, base sa kakayahan mong ipaliwanag at ipagtanggol ang trabaho mo**.
Kaya basahin ang bawat seksyon, buksan ang file na nakasulat, at subukan ang demo bago ang defense.

---

## A. COMP 016: Web Development

### 1. Request flow ng MVC (route, middleware, controller, model, view)

Halimbawa: binuksan ang listahan ng job orders (`GET /job-orders`).

1. **Route** (`routes/web.php`): hinahanap kung aling controller ang hahawak.
2. **Middleware**: `auth` (dapat naka-login) at `db.session` (sinasabi sa PostgreSQL kung sino ang naka-login).
3. **Controller** (`JobOrderController@index`): kinukuha ang data, may search at filter.
4. **Model** (`JobOrder`): ang Eloquent ang nakikipag-usap sa database.
5. **View** (`resources/views/job_orders/index.blade.php`): ang HTML na ipinapakita.

Sabihin: *"Ang route ang pintuan, ang middleware ang guwardiya, ang controller ang nagdedesisyon, ang model ang kumukuha ng data, at ang view ang nagpapakita."*

### 2. Migrations at Eloquent models

- 12 migration files sa `database/migrations/`, bawat isa ay isang table (o pagbabago).
- Demo: `php artisan migrate:fresh --seed` (binubura lahat, ginagawang muli, nilalagyan ng demo data). Gumagana ito mula sa wala.
- May relationships ang bawat model: halimbawa `Customer hasMany Motorcycle`, `JobOrder belongsTo Motorcycle`, `JobOrder belongsToMany ServiceType`.
- **Tanong:** *Bakit may `down()` ang migration?* Para ma-rollback.

### 3. Validation at mass assignment

- **Validation:** `$request->validate($this->rules())` sa controller (halimbawa `JobOrderController`). Ang petsa ay hindi pwedeng sa hinaharap, ang odometer ay hindi pwedeng negatibo, at hindi pwedeng bumaba ang odometer ng isang motorsiklo kumpara sa dati.
- **Mass assignment:** may `$fillable` ang bawat model. Ang hindi nakalista ay hindi mapupunan ng `create()` o `update()` mula sa request.
- **Tanong:** *Bakit nasa `$fillable` ng User ang `role_id`? Hindi ba delikado?* Hindi, kasi hindi kailanman galing sa request ang `role_id` sa public registration. Ang `AuthController@register` mismo ang naglalagay ng role na `mechanic`.
- Hindi pwedeng i-edit ng non-admin ang `mechanic_id` ng job order (inaalis sa `update()`).
- Ang CRUD ay gumagana sa higit sa dalawang entity: customers, motorcycles, job orders, parts, users.

### 4. Authentication at role checks

- Login, logout, at register: `AuthController`.
- Pagkatapos mag-login, `session()->regenerate()` (proteksyon laban sa session fixation). Sa logout, `invalidate()` at bagong CSRF token.
- Ang password ay hashed na awtomatiko (`'password' => 'hashed'` cast sa `User`).
- **Dalawang role** na may proteksyon sa route:
  - `role:admin` (middleware `EnsureRole`) sa delete ng records, parts management, at users: 403 kapag mechanic.
  - Sa loob ng controller: `JobOrder::canBeEditedBy()` (mechanic: sariling job order lang, at hindi na pagkatapos ma-release).
- Demo: mag-login bilang `mechanic1`, buksan ang `/users` (403), buksan ang job order ng ibang mechanic (403).

### 5. Normalization at keys (tingnan ang `docs/erd.png`)

- Hindi inuulit ang brand at model sa bawat motorsiklo: nasa `motorcycle_models`.
- Ang `service_intervals` ay many-to-many ng model at service type, may dagdag na datos (km at buwan), at **unique** ang pares.
- Ang `job_order_services` ay pivot table na ang **composite primary key** ay ang dalawang foreign key.
- Walang `total` na naka-store sa job order: kinukuwenta ng view na `v_job_order_totals`, kaya hindi pwedeng magkamali ang total.
- **Tanong:** *Bakit may `unit_price` sa `job_order_parts` kahit nasa `parts` na iyon? Hindi ba paglabag sa normalization?* Sagot: sinadyang kopya (snapshot). Kapag nagbago ang presyo ng part sa susunod na buwan, hindi dapat magbago ang bayad sa lumang job order.

---

## B. COMP 018: Database Administration

### 1. Indexes at pagbasa ng EXPLAIN

Patakbuhin: `psql -U shop_owner -d motorshop -f database/sql/03_explain_demo.sql` (nasa `docs/explain_output.txt` ang resulta). Tapos sa ROLLBACK, walang nabago.

| Query | Walang index | May index |
|---|---|---|
| History ng isang motorsiklo (`WHERE motorcycle_id = ? ORDER BY service_date DESC LIMIT 5`) | **Seq Scan** + Sort, 3.480 ms | **Index Scan Backward** gamit ang `idx_job_orders_motorcycle_date`, 0.035 ms |
| Mga part na paubos na | **Seq Scan**, 1.418 ms | **Bitmap Index Scan** gamit ang partial index `idx_parts_low_stock`, 0.075 ms |

Paliwanag:
- **Seq Scan** = binabasa ang LAHAT ng row. **Index Scan** = diretso sa tamang row.
- **Composite index** `(motorcycle_id, service_date)`: ang unang column ang pang-filter, ang pangalawa ang pang-sort kaya hindi na kailangan ng Sort.
- **Partial index**: `WHERE stock_qty <= reorder_level`. Ang laman ay mga paubos na part lang, kaya napakaliit.
- Ang `cost=a..b` ay tantiya ng planner; ang `Execution Time` ay totoong oras.
- **Tanong:** *Bakit mo dinrop din ang `idx_job_orders_service_date` sa demo?* Kasi kung hindi, ang planner ay lalakad sa index na iyon at magsasala, kaya hindi lalabas ang problema. Patas na paghahambing ang gusto kong ipakita.
- Ang demo ay 60,000 job orders at 20,000 parts (gawa ng script, tinatanggal sa ROLLBACK). Totoo ang oras, pero sa laptop na ito at sa ganitong laki ng data.

### 2. Constraints, function, at trigger

- **Constraints:** CHECK (`stock_qty >= 0`, `quantity > 0`, tamang `status`), UNIQUE (plate number), at foreign keys na `RESTRICT` (hindi pwedeng burahin ang customer na may motorsiklo).
- **Function at trigger:** `fn_adjust_part_stock()` + `trg_job_order_parts_stock`. Kapag may idinagdag na part line, **bumababa ang stock**; kapag binago o binura, **bumabalik**. May `FOR UPDATE` lock para hindi maubos ng dalawang mechanic ang huling piraso nang sabay. Kapag kulang ang stock, may error (`check_violation`).
- Bakit sa database at hindi sa PHP? Para tama ang stock kahit ibang programa ang magsulat ng data.

### 3. Roles at privileges (apat na role)

| Role | Kaya | Hindi kaya |
|---|---|---|
| `shop_owner` | Lahat ng nasa `motorshop` | |
| `shop_app` | SELECT / INSERT / UPDATE / DELETE | CREATE / DROP / TRUNCATE |
| `shop_readonly` | SELECT | Sumulat; basahin ang `users.password` (column-level) |
| `shop_backup` | Basahin lahat (BYPASSRLS) para sa backup | Hindi para sa web app |

Demo: `psql -U postgres -d motorshop -f database/sql/04_privilege_demo.sql`. Ang bawat `ERROR` doon ay **inaasahan** at siyang patunay (nasa `docs/privilege_demo_output.txt`).

### 4. Row Level Security (RLS)

- Naka-on ang RLS sa `job_orders` (`ENABLE` at `FORCE`), may apat na policy: lahat ay nakakakita, admin lang ang makakapag-delete, at ang mechanic ay makakapagdagdag o makakapagbago lang ng job order na **kanya**.
- Nalalaman ng PostgreSQL kung sino ang naka-login dahil sa middleware na `SetDatabaseSession`, na nagse-set ng `app.user_id` at `app.role` sa bawat request.
- Dalawang patong ng proteksyon: ang Laravel (403) **at** ang database mismo. Kahit magkamali ang PHP code, hindi papayagan ng PostgreSQL.
- **Tanong:** *Bakit may `FORCE`?* Kasi kung wala, hindi sakop ng RLS ang may-ari ng table. Sa `FORCE`, ang superuser lang ang nakakalampas. Kaya may hiwalay na `shop_backup` na may `BYPASSRLS`: kung hindi, hindi makukuha ng `pg_dump` ang lahat ng row.
- **Tanong:** *Paano pinatunayan na gumagana?* Tingnan ang demo: mechanic: `UPDATE 11` sa sarili niya, `UPDATE 0` sa iba, `DELETE 0`; admin: `UPDATE 22` (lahat).

### 5. Backup at restore (ang restore ay napatunayan)

- `bash scripts/backup.sh`: `pg_dump -Fc` (compressed). Pagkatapos, binabasa ang archive para siguruhing hindi sira.
- `bash scripts/restore.sh <file>`: ibabalik sa **bagong** database (`motorshop_restored`). Para sa disaster recovery: `... motorshop --replace`, na binubura at binubuo mula sa backup, kaya mawawala ang lahat ng pagbabago pagkatapos ng backup.
- `bash scripts/restore_test.sh`: gumagawa ng backup, nire-restore sa scratch database, at **ikinukumpara ang bawat table row-by-row** (md5 ng lahat ng row), tinitingnan ang trigger, view, function, RLS policies, indexes, grants, at sinusubukan ulit ang trigger sa restored copy. Ang huling linya ay `RESTORE PROVEN` kapag pasado lahat (22 checks).
- **Tanong:** *Bakit may hiwalay na backup user?* Tingnan ang RLS sa itaas.

### 6. Laravel connection settings

- `.env`: `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Binabasa ito ng `config/database.php` (`env('DB_...')`).
- Para sa migrations: `shop_owner`. Para sa pang-araw-araw: pwedeng `shop_app` (least privilege). Nasubukan ko ang buong app na naka-`shop_app`.

---

## C. Mga mahihinang punto: ihanda ang sagot

- **Walang AI.** Rule-based estimate ito (interval ng manufacturer + karaniwang takbo ng rider). Huwag itong tawaging "smart" o "predictive." Ang "estimated vs actual" na report ang panukat kung gaano ito katumpak.
- **Sample ang service intervals.** Kumpirmahin sa owner's manual at sa mekaniko, tapos baguhin ang `database/seeders/data/catalog.php`.
- **Gawa-gawa ang demo data** (pangalan, plaka, numero). Sabihin ito kung itanong.
- **Bukas ang `/register`** (kailangan ng "registration works"), pero mechanic lang ang nagagawa. Sa totoong shop, patayin ito at admin lang ang gumagawa ng account.
- **Classroom passwords** ang nasa `00_bootstrap.sql`. Palitan sa totoong gamit.
- **Hindi kasama:** appointment scheduling, SMS, at online payment.
