# Kupiana — Build Status & Resume File

> **Read this file first.** It is the single source of truth for what is built and
> what is next. Any agent resuming this project must read this file, then continue
> from the first phase marked `TODO`. Update this file at the end of every phase.

- **Project:** Kupiana — Enterprise E-Commerce Application
- **Stack:** PHP 8.x, CodeIgniter 3.1.13 (HMVC), MySQL (mysqli), Bootstrap 5, jQuery, AJAX
- **Repo root:** `/Users/techmonster/Documents/UMS/kupiana`
- **Last updated:** 2026-08-02 (Phase 3 complete)

---

## Environment notes

- Config is env-driven via `.env` at repo root (parsed in `index.php`).
- DB: `kupiana`, user `root`, empty password, host `localhost`.
- **PHP: `/Applications/XAMPP/bin/php` (8.2.4). MySQL: `/Applications/XAMPP/bin/mysql`.**
  These are not on `PATH` — always call them by full path.
- **Dev server for smoke tests** (the built-in server needs a router to emulate the
  `.htaccess` rewrite; without one, CI sees an empty URI and every route falls through
  to the default controller):

  ```php
  // /tmp/router.php
  <?php
  $root = $_SERVER['DOCUMENT_ROOT'];
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  if ($path !== '/' && file_exists($root.$path) && !is_dir($root.$path)) { return false; }
  $_SERVER['SCRIPT_NAME'] = '/index.php';
  require $root.'/index.php';
  ```
  ```bash
  /Applications/XAMPP/bin/php -S 127.0.0.1:8899 -t /Users/techmonster/Documents/UMS/kupiana /tmp/router.php
  ```
  Kill it by port (`lsof -ti:8899 | xargs kill -9`) — shell job control does not survive
  between agent tool calls, and a stale server silently serves old behaviour.
- Lint everything with:
  `find application -name "*.php" | while read f; do /Applications/XAMPP/bin/php -l "$f"; done`
- Bootstrap 5 and Font Awesome are loaded from CDN in the layouts. If the target
  deployment is offline, vendor them into `public/assets/vendor/`.

---

## Phase progress

| # | Phase | Status |
|---|-------|--------|
| 1 | Project Architecture | **DONE** |
| 2 | Database Design | **DONE** |
| 3 | Authentication | **DONE** |
| 4 | Admin Panel (50 modules) | TODO |
| 5 | Customer Website | TODO |
| 6 | Inventory | TODO |
| 7 | Orders | TODO |
| 8 | Payments (Razorpay) | TODO |
| 9 | Tracking | TODO |
| 10 | Reports | TODO |
| 11 | Testing | TODO |
| 12 | Optimization | TODO |
| 13 | Deployment | TODO |

---

## Phase 1 — Project Architecture — DONE

Established the shared foundation every later phase builds on. The goal was to make
modules 4–10 mostly declarative: a new admin CRUD module should need a model with a
`$table` + `$fillable` and a thin controller, nothing more.

### Delivered

**Core**
- `application/core/MY_Model.php` — base model. Gives every table:
  CRUD, soft delete (`deleted_at`) + restore + force delete, audit stamping
  (`created_at/by`, `updated_at/by`), `search()`, sorting whitelist, filter map,
  `paginate()`, `count_all()`, bulk ops, and `$fillable` mass-assignment protection.
  Subclasses normally only set `$table`, `$fillable`, `$searchable`, `$sortable`.
- `application/core/MY_Controller.php` — controller hierarchy:
  - `MY_Controller` (base): shared view data, `render()`, SEO meta, flash helpers.
  - `Admin_Controller`: forces admin login, `admin` layout, permission gate via
    `$required_permission`, breadcrumbs, sidebar active state.
  - `Store_Controller`: storefront layout, cart/wishlist counts placeholder.
  - `Api_Controller`: JSON-only, no layout, CSRF-aware, uses `Api_response`.

**Libraries**
- `Acl.php` — role/permission checks (`can()`, `any()`, `all()`, `require_permission()`).
  Degrades safely to role-only checks while the `permissions` tables do not yet exist,
  so the app stays runnable before Phase 2.
- `Settings.php` — cached key/value application settings with DB-table fallback.
- `Audit.php` — writes audit-log rows; no-ops until the table exists.
- `Api_response.php` — one JSON envelope for all AJAX/API output
  (`{status, message, data, errors, meta}`).

**Helpers**
- `common_helper.php` — money/INR formatting, date formatting, slugs, order numbers,
  random tokens, safe array access, percentage/discount math, file-size, masking.
- `ui_helper.php` — status badges, breadcrumbs, page headers, empty states,
  pagination renderer, table toolbars, icon buttons.

**Config**
- `application/config/app.php` — brand, currency, pagination sizes, upload paths and
  limits, date formats, order-status and payment-status maps, theme tokens.
- `autoload.php` — loads the new libraries and helpers.

**Views / assets**
- `views/layouts/admin.php` — Bootstrap 5 shell: collapsible sidebar, topbar with
  search + notifications + profile menu, breadcrumbs, toast container, global
  confirm modal, loading overlay.
- `views/layouts/store.php` — storefront shell: announcement bar, header with search /
  wishlist / cart, mega-menu mount point, footer, newsletter, toasts.
- `views/partials/` — `admin_sidebar`, `admin_topbar`, `store_header`, `store_footer`,
  `flash`, `pagination`, `empty_state`.
- `public/assets/css/app.css` — design tokens (light + dark), admin shell, cards,
  tables, forms, buttons, badges, storefront components, animations, responsive rules.
- `public/assets/js/app.js` — `Kupiana` namespace: toasts, AJAX wrapper with CSRF,
  confirm dialogs, loading overlay, form validation, bulk-select tables, live search
  debounce, quantity steppers, lazy images.

### Files created / modified in Phase 1

**Created**
```
PROJECT_STATUS.md
application/config/app.php
application/config/admin_menu.php
application/core/MY_Model.php
application/libraries/Acl.php
application/libraries/Settings.php
application/libraries/Audit.php
application/libraries/Api_response.php
application/helpers/common_helper.php
application/helpers/ui_helper.php
application/views/partials/admin_sidebar.php
application/views/partials/admin_topbar.php
application/views/partials/store_header.php
application/views/partials/store_footer.php
public/assets/js/app.js
public/assets/images/placeholder.svg
public/uploads/{products,categories,brands,banners,users,blog,testimonials,invoices,imports,backups}/
public/uploads/.htaccess          (blocks script execution on uploaded files)
```

**Modified**
```
application/core/MY_Controller.php        rewritten as the 4-class hierarchy
application/config/autoload.php           new libraries + helpers
application/views/layouts/admin.php       Bootstrap 5 admin shell
application/views/layouts/store.php       Bootstrap 5 storefront shell
application/views/auth/login.php          restyled (Phase 3 rebuilds the flow)
application/modules/admin/controllers/Dashboard.php   -> Admin_Controller
application/modules/admin/views/dashboard.php         KPI tiles + charts
application/modules/catalog/controllers/*.php         -> Store_Controller
application/modules/user/controllers/Dashboard.php    -> Store_Controller
application/controllers/{Home,Auth}.php               -> Store_Controller
application/modules/catalog/views/home.php            restyled
application/helpers/seo_helper.php        og:image points at an existing asset
public/assets/css/app.css                 replaced with the design system
.gitignore                                keep upload dirs, ignore uploads
```

### Pre-existing bug fixed: HMVC modules were entirely non-functional

Every module route (`/admin`, `/account`, `/catalog/*`) returned 404 at `HEAD`, before
any Phase 1 work. Confirmed by checking out `HEAD` into a separate worktree and
reproducing there.

**Cause.** `application/config/config.php` defined:

```php
$config['modules_locations'] = array(APPPATH.'modules/' => '../modules/');
```

That is the **wiredesignz "Modular Extensions"** format. The installed package is
**Jens Segers' HMVC** (`application/third_party/HMVC/`), whose Router constructor
expects a plain list and maps `realpath()` over the array *values*.
`realpath('../modules/')` returns `FALSE`, which then became `'/'` — so
`locate()` tested `is_dir('/admin/controllers/')` and every module 404'd.

**Fix.** Both `config.php` and `config/modules.php` now use the list form
`array(APPPATH.'modules/')`. Note `config/modules.php` is *never autoloaded*
(`$autoload['config']` is empty) — `config.php` is the file that actually takes effect.
They are kept in sync so a future `$autoload['config'] = array('modules')` cannot
reintroduce the bug.

### Decisions worth knowing

- **CSRF regeneration.** `csrf_protection` and `csrf_regenerate` are both `TRUE`.
  Because the token rotates on every request, `MY_Controller::json()` attaches the
  fresh hash to `meta.csrf_hash` on *every* JSON response, and `app.js`
  (`Kupiana.refreshCsrf`) writes it back into `KUPIANA.csrfHash` and every form.
  Without this the second AJAX call on a page would 403. **Phase 8 must add the
  Razorpay webhook URI to `csrf_exclude_uris`** — an external POST cannot carry a token.
- **Config sections.** `app.php` is loaded with `$this->config->load('app', TRUE)`,
  so reads need the section: `$this->config->item('currency', 'app')`, never
  `item('currency')`. The `app_config()` helper wraps this.
- **Graceful degradation.** `Acl`, `Settings` and `Audit` probe for their tables and
  no-op until Phase 2 creates them. That is deliberate: the app runs at every commit.
  `Acl::can()` currently falls back to "is this an admin role" — once the permission
  tables exist it switches to real permission checks with no code change.
- **Passwords are still MD5** in `Auth_service` (inherited from the scaffold).
  **Phase 3 must replace this with `password_hash()` / `password_verify()`** and
  migrate existing hashes. Do not build new auth on top of the MD5 path.

### Phase 1 verification — PASSED

Lint: every file under `application/` passes `php -l`.

Executed against the dev server (results observed, not assumed):

| Check | Result |
|---|---|
| `GET /` | 200, storefront renders |
| `GET /login` | 200, Bootstrap form + CSRF field |
| `GET /admin` (guest) | 307 → `/login?redirect=admin` |
| `GET /sitemap.xml` | 200, valid XML |
| `GET /robots.txt` | 200, plain text |
| `GET /nope-404` | 404 |
| `POST /login` as `admin@kupiana.test` | 303 → `/admin` |
| `GET /admin` (admin) | 200 — sidebar, 8 stat tiles, both chart canvases, full module tree |
| `POST /login` as `user@kupiana.test` | 303 → `/account` |
| `GET /admin` (customer) | 403 "restricted to staff accounts" |
| `GET /account` (customer) | 200 |

Note: the CSRF field is named **`kupiana_csrf_token`**, not `csrf_test_name`
(`$config['csrf_token_name']` is customised). Scripted POSTs must use that name.

Still to check by eye in a browser (not scriptable via curl): dark-theme toggle
persistence, off-canvas sidebar below 992px, toast rendering.

### Known gaps carried into later phases

- `application/modules/user/views/dashboard.php` and the catalog `category.php` /
  `product_detail.php` views still use the removed scaffold CSS classes. They render
  but look plain. Phase 5 rebuilds them.
- Bootstrap/Font Awesome/Chart.js load from CDN. Vendor them for offline deployment
  (Phase 13).
- No `Upload`, `Mailer`, `Sms`, `Pdf` or `Excel` library yet — added in the phases
  that first need them (4, 8, 10).

### Conventions established (follow these in later phases)

1. **Every table** carries `id, status, created_at, updated_at, deleted_at, created_by, updated_by`.
2. **Every model** extends `MY_Model`; never write raw CRUD again.
3. **Every admin CRUD controller** extends `Admin_Controller` and sets
   `$required_permission` (e.g. `products.view`).
4. **All AJAX** returns the `Api_response` envelope. Never echo raw JSON.
5. **All output escaped** with `html_escape()`; all input via `$this->input` and
   validated server-side with `form_validation`, plus client-side in `app.js`.
6. **Money** stored as `DECIMAL(12,2)`; formatted only in views via `money()`.
7. **Assets** under `public/assets/`; page-specific JS in `public/assets/js/pages/`.
8. Soft delete everywhere — never hard-delete business records.

---

## Phase 2 — Database Design — DONE

Replaced the 15-table starter schema with the full production schema, plus seed data.
Applied and verified against the live `kupiana` database.

### Delivered

**`database/schema.sql`** — 72 tables, 74 foreign keys, 253 indexes, all InnoDB /
utf8mb4. Dependency-ordered, idempotent (drops before creating), nine sections:

| Section | Tables |
|---|---|
| 1. Auth & RBAC | users, roles, permissions, role_permissions, user_roles, password_resets, email_verifications, otp_codes, login_attempts, user_sessions |
| 2. Reference | countries, states, currencies, hsn_codes, tax_rates |
| 3. Catalog | categories, brands, products, product_categories, attributes, attribute_values, product_variants, variant_attribute_values, product_images, tags, product_tags, product_reviews, review_images |
| 4. Inventory | warehouses, suppliers, inventory, batches, purchase_orders, purchase_order_items, stock_adjustments, stock_movements |
| 5. Customer | addresses, carts, cart_items, wishlists, wallets, wallet_transactions |
| 6. Promotions | coupons, coupon_restrictions, offers |
| 7. Orders | orders, order_items, order_status_history, payments, payment_logs, refunds, return_requests, return_items, shipments, shipment_tracking, invoices, coupon_usages |
| 8. CMS | banners, pages, blog_categories, blog_posts, testimonials, faqs, contact_messages, newsletter_subscribers, seo_meta |
| 9. System | settings, email_templates, sms_templates, notifications, audit_logs, backups |

**`database/seed.sql`** — 3 users, 5 roles, 83 permissions, 234 role-permission grants,
35 Indian states with GST codes, 5 tax rates, 10 categories, 4 brands, 10 products,
10 inventory rows, 3 coupons, 31 settings, 10 email + 5 SMS templates, 5 CMS pages,
8 FAQs, 3 banners, 3 testimonials.

### Schema decisions (do not "fix" these without reading the reasoning)

1. **`created_by` / `updated_by` are indexed but have no FK.** Constraining them on 72
   tables makes `users` effectively undeletable and forces a strict seed order, for no
   real integrity gain.
2. **`inventory.variant_id` / `batches.variant_id` / `stock_movements.variant_id` are
   `NOT NULL DEFAULT 0`, no FK** (0 = "no variant"). MySQL treats NULLs as *distinct*
   inside a UNIQUE key, so a nullable column would allow duplicate stock rows for the
   same product+warehouse. `uq_inventory_stock (product_id, variant_id, warehouse_id)`
   only actually guarantees one row per combination because the column is NOT NULL.
3. **Domain status vs lifecycle status.** Where a table needs both, the domain column is
   named explicitly and `status` stays the MY_Model lifecycle column:
   `refunds.refund_status`, `return_requests.return_status`, `shipments.shipment_status`,
   `backups.backup_status`, `purchase_orders.receive_status`.
   **`payments` is the one inversion**: `payments.status` is the *gateway* state and
   `payments.status_flag` is the lifecycle column — so `Payment_model` **must** set
   `protected $status_column = 'status_flag';`.
4. **Money is `DECIMAL(12,2)`, percentages `DECIMAL(5,2)`.** Never FLOAT.
5. **Order/return/shipment addresses are JSON snapshots**, not FKs to `addresses`.
   A customer editing their address must not retroactively change a past order.

### Application changes required by the new schema

The schema renamed/removed columns the Phase 1 code relied on. All fixed and verified:

- **`Auth_service::attempt()`** — was `md5($password)` and `is_active = 1`.
  Now uses `password_verify()` against bcrypt and checks `status = 'active'`
  plus `deleted_at IS NULL`.
- **`Auth_service::verify_password()`** (new) — accepts bcrypt, and accepts a legacy
  32-char MD5 hash *once*, immediately rehashing it to bcrypt via
  `Auth_model::update_password()`. Also rehashes bcrypt if the cost factor rises.
  **Verified live:** a 32-char MD5 hash became a 60-char bcrypt hash after one login.
- **`Auth_service::redirect_path()`** — checked a literal `admin` role, which sent the
  seeded `super_admin` to the customer area. Now delegates to `Acl::is_admin()`.
- **`Auth_model`** — select list updated (`is_active` no longer exists);
  `touch_login()` also records IP and resets the failed-attempt counter;
  `update_password()` added.
- **`user/Dashboard`** — required the role `user`, which the seed renamed to `customer`,
  so `/account` 403'd for everyone. Now just requires login.
- **`catalog/Seo`** — sitemap filtered categories on `is_active = 1`; now
  `status = 'active' AND deleted_at IS NULL`.
- **`schema.sql`** drops the legacy `user_addresses` table explicitly. Without that,
  its dangling FK to the dropped `users` table made `CREATE TABLE users` fail with
  errno 150 (old `users.id` was INT UNSIGNED, new is BIGINT UNSIGNED).

### Phase 2 verification — PASSED

Lint clean. Schema applied to a scratch DB first, then to `kupiana`.

| Check | Result |
|---|---|
| Tables created | 72 |
| Foreign keys | 74 |
| Indexes | 253 |
| Non-InnoDB tables | none |
| Tables missing any of the 6 standard columns | none |
| Seed rows | users 3, roles 5, permissions 83, grants 234, products 10, settings 31 |
| bcrypt login `admin@kupiana.test` | 303 → `/admin` |
| bcrypt login `staff@kupiana.test` | 303 → `/admin` |
| bcrypt login `user@kupiana.test` | 303 → `/account` |
| Wrong password | re-renders login with error, no session |
| Legacy MD5 login → auto-upgrade | 32-char hash became 60-char bcrypt |
| `/admin` as super_admin / manager / customer | 200 / 200 / 403 |
| `/account` as any signed-in user | 200; guest → `/login?redirect=account` |
| **ACL now permission-driven** | super_admin renders 71 sidebar links, manager 46 |
| `/sitemap.xml` | 20 URLs (1 home + 10 categories + 9 published products) |
| Error log after full sweep | empty |

The sidebar link difference is the key result: `Acl` has left its Phase 1 role-only
fallback and is reading `permissions` / `role_permissions` for real.

### Accounts

| Email | Password | Role |
|---|---|---|
| admin@kupiana.test | admin123 | super_admin |
| staff@kupiana.test | admin123 | manager |
| user@kupiana.test | user123 | customer |

### Reload the database

```bash
/Applications/XAMPP/bin/mysql -uroot < database/schema.sql
/Applications/XAMPP/bin/mysql -uroot < database/seed.sql
```

### Carried into later phases

- Seeded product/banner images point at `products/placeholder.svg` and
  `banners/placeholder.svg`, which do not exist under `public/uploads/`. Harmless —
  `upload_url()` falls back to the placeholder — but Phase 4's uploader should replace them.
- `product_variants` and `variant_attribute_values` are seeded empty even though
  products 3 and 8 are typed `variable`. Phase 4's variant builder creates them.
- Phase 3 still owes: rate limiting via `login_attempts`, lockout via
  `users.locked_until`, remember-me via `user_sessions`, reset via `password_resets`,
  OTP via `otp_codes`, verification via `email_verifications`. All tables are ready.

---

## Phase 3 — Authentication — DONE

Full auth stack on the Phase 2 tables, with transactional email through
**ZeptoMail (Zoho)**.

### Delivered

**`libraries/Mailer.php`** — ZeptoMail HTTP transport (`POST https://api.zeptomail.in/v1.1/email`,
`authorization: Zoho-enczapikey <key>`). Bodies render from the `email_templates`
table with `{{placeholder}}` substitution, wrapped in `views/email/layout.php`
(table-based, inline styles). Placeholder values are HTML-escaped unless the key ends
in `_html`, so template copy cannot inject markup.

**`libraries/Auth_service.php`** — rewritten as a real service. Every method returns
`array('success','code','message','user')` so controllers render one message without
knowing which layer refused.

**Models** (each maps to one Phase 2 table): `User_model`, `Password_reset_model`,
`Email_verification_model`, `Otp_model`, `Login_attempt_model`, `User_session_model`.
`Auth_model` deleted — `User_model` replaces it.

**`controllers/Auth.php`** + views `auth/{login,register,forgot_password,reset_password,otp}`
sharing `auth/_open.php` / `auth/_close.php`.

**Routes:** `/login`, `/login/otp`, `/logout`, `/register`, `/verify-email`,
`/resend-verification`, `/forgot-password`, `/reset-password`.

### Security decisions

- **Tokens are never stored in plaintext.** Password-reset, email-verification and
  remember-me tokens are stored as SHA-256 digests; OTPs as bcrypt. A database leak
  cannot be replayed.
- **Remember-me cookie is `<id>:<secret>`.** Lookup is by primary key, then
  `hash_equals()` — not vulnerable to timing analysis. The secret **rotates on every
  use**, and presenting a valid id with a wrong secret **revokes the whole session**
  (treated as a stolen cookie).
- **Two throttle layers.** Per account (`users.failed_login_attempts` + `locked_until`,
  5 tries → 15 min lock) and per IP (`login_attempts`, 15 failures → blocked), because
  the account counter alone does nothing against spraying many usernames from one host.
  The IP allowance is 3× the account one so a shared office IP is not blocked by one
  careless colleague.
- **No user enumeration.** Forgot-password and OTP-request always report the same
  generic message; failed logins for unknown addresses are still recorded so the IP
  throttle applies.
- **Password change revokes every remembered device** (`revoke_all`).
- **The API key is read from `.env`, never committed.** (The reference BSC project
  hardcodes it in `constants.php` — deliberately not copied.)
- **Open-redirect guard** on `?redirect=` uses an allow-list pattern, not a deny-list.

### Bugs found and fixed while testing

1. **Timezone skew (pre-existing, cross-cutting).** `$config['timezone'] = 'Asia/Kolkata'`
   was declared but never applied, so PHP ran on `Europe/Berlin` (php.ini) while MySQL
   `NOW()` followed the system clock — a **3.5-hour gap** between rows written by PHP
   `date()` and by SQL `NOW()`. That silently corrupts token expiry windows, throttle
   counters and any future report date range. Fixed by applying
   `date_default_timezone_set(APP_TIMEZONE)` in `config/constants.php`, which CI loads
   before any controller; `app.php` now reads the same constant. **Verified: PHP and
   MySQL now agree to the second.**
2. **Open-redirect guard was ordered wrong.** The protocol-relative check ran *after*
   `ltrim($target,'/')`, so `//evil.test` slipped past it. It happened to stay on-host,
   but CI's `redirect()` treats a leading `//` as absolute, so it was one refactor away
   from a real off-site redirect. Replaced with an allow-list regex validated on the
   raw value.
3. **Every validation message rendered twice** — `_open.php` printed a
   `validation_errors()` summary while each field also printed `field_error()`. Dropped
   the summary; inline errors point at the offending input.
4. **`log_threshold` was 1 (errors only)**, which made the Mailer's
   "no credentials → log the message" fallback useless: the verification and reset
   links went nowhere. Now `3` (INFO) in development, `1` in production.

### Phase 3 verification — PASSED (26 assertions, 0 failures)

Lint clean; no ERROR-level log entries after the full sweep.

| Area | Checks |
|---|---|
| Registration | redirect, row created, bcrypt hash, `customer` role, wallet created, verification token issued, duplicate email rejected once |
| Email verification | link consumed, `email_verified_at` set, token single-use on replay, welcome email queued |
| Password reset | link issued, form loads, reset completes, token marked `used`, new password works, **old password rejected**, spent token bounces to forgot-password |
| OTP | code issued, wrong code increments `attempts`, correct code signs in, row marked `verified` |
| Lockout | locked after exactly 5 failures, correct password refused while locked, works after unlock, counter resets on success |
| Remember-me | auto-login with **no session cookie**, token rotates on use, forged cookie (valid id + wrong secret) revokes the session, reset revokes all sessions |
| Open redirect | `https://evil.test`, `//evil.test`, `../../etc`, `javascript:` all fall back to `/account`; `account/orders` and `account?tab=profile` pass |
| Regression | all three seeded accounts sign in; `/admin` = 200/200/403 for super_admin/manager/customer |

**ZeptoMail transport verified against a local capture server** (not just the
log fallback): correct `POST`, exact headers (`accept`, `authorization: Zoho-enczapikey`,
`cache-control`, `content-type`), payload shape matching the reference
(`from.address/name`, `to[0].email_address.address/name`, `subject`, `htmlbody`),
subject rendered from the DB template, reset link embedded, **0 placeholders left
unreplaced**. Failure paths also exercised: HTTP 401 and connection-refused both log
the detail and show the user the generic message — no 500, no gateway detail leaked.

### Configuration

`.env` (key intentionally blank — fill in to send for real):
```
ZEPTOMAIL_API_KEY=
ZEPTOMAIL_API_URL=https://api.zeptomail.in/v1.1/email
MAIL_FROM_ADDRESS=noreply@kupiana.test
MAIL_FROM_NAME=Kupiana
MAIL_ENABLED=false
```
With `MAIL_ENABLED=false` or an empty key, Mailer **logs the full message to
`application/logs/` instead of sending** — that is how the verification and reset
links were obtained during testing, and how a developer works without credentials.
`Admin > Settings` (`zeptomail_api_key`, `mail_from_address`, `mail_from_name`)
overrides `.env` at runtime.

New settings rows: `require_email_verification` (0), `allow_otp_login` (1).
Turn the first on to force verification before sign-in.

### Carried into later phases

- **SMS is not wired.** `sms_templates` is seeded and `Otp_model` accepts
  `channel = 'sms'`, but there is no SMS gateway — OTP is email-only. Phase 8/9 should
  add an `Sms` library alongside `Mailer`.
- Account-security screens (change password, active devices) are **service-ready but
  have no UI**: `Auth_service::change_password()` and
  `User_session_model::active_for_user()` exist, unused until Phase 5 builds the
  customer dashboard.
- `login_attempts` grows unbounded; `Login_attempt_model::prune()` exists but nothing
  calls it. Wire it to a scheduled task in Phase 13.
- Registration does not yet verify the phone number.

---

## Phase 4 — Admin Panel — TODO (next)

Build the 50 back-office modules on `Admin_Controller` + `MY_Model`. Start with the
generic CRUD scaffold (list + filters + bulk actions + form + validation), then apply
it per module. Remember: `Payment_model` must set `$status_column = 'status_flag'`.

---

## Resume instructions for a new agent

1. Read this file top to bottom.
2. Read `application/core/MY_Model.php` and `application/core/MY_Controller.php` —
   they define the patterns everything else follows.
3. Find the first phase marked `TODO` and build it completely before moving on.
4. Before starting a phase: state the architecture, list the files, then implement.
5. After finishing a phase: flip its row to **DONE**, add a "Delivered" section like
   Phase 1's, and update "Last updated".
6. The app must remain runnable after every phase.
