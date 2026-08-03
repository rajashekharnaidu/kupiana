# Kupiana — Build Status & Resume File

> **Read this file first.** It is the single source of truth for what is built and
> what is next. Any agent resuming this project must read this file, then continue
> from the first phase marked `TODO`. Update this file at the end of every phase.

- **Project:** Kupiana — Enterprise E-Commerce Application
- **Stack:** PHP 8.x, CodeIgniter 3.1.13 (HMVC), MySQL (mysqli), Bootstrap 5, jQuery, AJAX
- **Repo root:** `/Users/techmonster/Documents/UMS/kupiana`
- **Last updated:** 2026-08-03 (terracotta theme #cc4e3a)

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
| 4 | Admin Panel (50 modules) | **DONE** |
| 5 | Customer Website | **DONE** |
| 6 | Inventory | **DONE** |
| 7 | Orders | **DONE** |
| 8 | Payments (Razorpay) | **DONE** |
| 9 | Tracking | **DONE** |
| 10 | Reports | **DONE** |
| 11 | Testing | **DONE** |
| 12 | Optimization | **DONE** |
| 13 | Deployment | **DONE** |

---

## Phase 4 — Admin Panel — DONE

### Delivered so far

- Generic, permission-gated admin CRUD foundation for catalog, sales, inventory,
  people, CMS, SEO, notifications and system resources.
- Shared listing and form screens with search, sorting, pagination, status filters,
  bulk actions, validation, soft delete and CSV export.
- Dynamic form widgets for relations, enums, boolean flags, long text, dates and
  image upload fields.
- Live admin dashboard KPIs and AJAX chart data.
- Inventory submenu routes for stock movements, adjustments and low-stock views.
- Order detail page with items, totals, payments, shipments, invoices, refunds and
  status timeline.
- Transactional order status updates with order status history and audit logging.
- Dedicated stock-in, stock-out and stock-adjustment forms that validate requests,
  update inventory balances, record stock movements and roll product stock totals.
- Grouped settings editor for general, shipping, payment, tax, inventory, catalog,
  SEO, mail and security settings.
- Read-only report pages for sales, revenue, GST, customers, inventory, products,
  suppliers and estimated profit.

### Verification

- Full PHP lint passed for every file under `application/`.
- Authenticated admin smoke tests passed for dashboard, generic resources, product
  management, order operations, inventory transactions, grouped settings, reports,
  backups and audit logs.
- Customer access to admin remains forbidden.
- No database schema changes were made in Phase 4.

---

## Phase 5 — Customer Website — DONE

### Delivered so far

- Storefront catalog model for reusable product, category, brand, banner, offer,
  testimonial, cart and wishlist reads.
- Live homepage with categories, featured products, trending products and trust
  messaging.
- Shop/search/category/brand/deals/offers browsing with filters, sorting and
  pagination.
- Product detail page with gallery, variants, pricing, stock status, structured
  product JSON-LD, add-to-cart and wishlist actions.
- Database-backed cart for guests and signed-in users, with add/update/remove and
  subtotal/shipping summary.
- Wishlist page with signed-in database storage and guest session fallback.
- Customer account dashboard, order list/detail and address book save/delete.
- Header mega-menu from live category data and live search suggestions API.
- Customer profile editing, password change and remembered-device revocation.
- Contact page with CSRF-protected persistence into `contact_messages`.
- Guest order tracking lookup by order number plus email/phone, including shipment
  and timeline display when order data exists.
- Content/utility pages for contact, track order, blog and checkout handoff.

### Verification

- Full PHP lint passed for every file under `application/`.
- Public storefront smoke tests passed for `/`, `/shop`, `/search`, `/deals`,
  `/offers`, `/brands`, `/brand/aurex`, `/category/electronics`, a product detail
  page, `/cart`, `/wishlist`, `/contact`, `/track-order`, `/blog`, `/checkout`
  and `/api/search/suggest`.
- Customer login smoke test passed with `user@kupiana.test`; account routes passed
  for dashboard, profile, security, orders and addresses.
- CSRF-protected contact POST was verified and created a test message row.
- No database schema changes were made in Phase 5.

### Deferred intentionally to later phases

- Checkout address/payment/order creation remains a handoff placeholder until
  Phases 7 and 8.
- Full payment, fulfilment and delivery tracking automation remain in Phases 8 and 9.

---

## Phase 6 — Inventory — DONE

### Delivered

- Dedicated `Inventory_model` as the single stock-writing gateway for direct stock
  movements, adjustments and purchase receipts.
- Custom admin stock overview at `/admin/inventory` with KPIs for on-hand units,
  available stock, low-stock rows and stock valuation.
- Searchable/filterable warehouse stock balances with product, SKU, variant,
  warehouse, reserved quantity, available quantity, reorder level and stock state.
- Low-stock view using each row's `reorder_level` instead of a hardcoded threshold.
- CSV export for current stock balance filters.
- Direct stock-in, stock-out and stock-adjustment workflows backed by signed
  `stock_movements` ledger rows.
- Atomic adjustment headers: `stock_adjustments` rows are created inside the same
  transaction as the stock ledger entry.
- Purchase entry workflow at `/admin/purchases/create` with supplier, receiving
  warehouse, purchase lines, totals and tax/discount math.
- Purchase detail/receive workflow at `/admin/purchases/view/{id}` and
  `/admin/purchases/receive/{id}`.
- Purchase receipt now creates batch rows when a batch number is supplied, updates
  item received quantities, updates `purchase_orders.receive_status`, appends
  purchase-type stock movements and rolls stock totals into products/variants.
- Purchase list action now links to the receive/detail screen.

### Verification

- Full PHP lint passed for every file under `application/`.
- Authenticated admin route smoke passed for stock overview, low stock, stock in,
  stock out, adjustments, purchase list/create/detail, suppliers and warehouses.
- Inventory CSV export returned 200 with a downloadable response.
- CSRF-protected stock-in POST created a stock movement and updated inventory/product
  rollups.
- Excessive stock-out POST was rejected without taking stock below zero.
- CSRF-protected purchase create POST created a purchase order and item line.
- CSRF-protected purchase receive POST marked the purchase received, created a batch,
  added a purchase stock movement and updated inventory/product rollups.
- CSRF-protected adjustment POST created a linked adjustment header and signed stock
  movement atomically.
- No database schema changes were made in Phase 6.

### Bugs found and fixed

- Purchase-specific routes were shadowed by the generic admin CRUD route; moved the
  purchase routes above generic catch-alls.
- `random_token()` was referenced in new inventory code, but the project helper is
  `generate_token()`; corrected before final verification.

### Carried forward

- Order checkout and fulfilment will consume these inventory APIs in Phase 7.
- Payment-aware purchase accounting remains out of scope until payment/reporting
  phases.

---

## Phase 7 — Orders — DONE

### Delivered

- Dedicated `Order_model` as the order-writing gateway for checkout, stock
  reservation, fulfilment, cancellation, shipment creation and status history.
- Real COD checkout at `/checkout` with customer/contact/address capture,
  CSRF-protected order placement and order success page.
- Cart-to-order conversion creates `orders`, `order_items` and initial
  `order_status_history` rows, clears cart items and snapshots billing/shipping
  addresses as JSON.
- Order totals now include item subtotal, default GST/tax calculation, CGST/SGST
  or IGST split by state code and free/flat shipping.
- Stock is reserved when the order is placed, released on cancellation and converted
  into signed `sale` stock movements when the order is packed/shipped/fulfilled.
- Admin order status updates now delegate lifecycle side effects to `Order_model`.
- Packing/fulfilment creates a shipment row and marks order items fulfilled.
- Invoice generation remains available from admin order detail and customer-safe
  invoice viewing is available from account order detail.
- Customer order detail now shows item fulfilment, timeline comments, shipments,
  invoices and self-cancel while the order is still cancellable.
- Guest/customer track-order lookup works against real Phase 7 orders.

### Verification

- Full PHP lint passed for every file under `application/`.
- Customer cart → checkout → COD order placement passed with CSRF.
- Created order was verified in DB with order item, totals, address snapshots,
  pending status and initial timeline.
- Stock reservation was verified: inventory quantity stayed unchanged and
  `reserved_quantity` increased at order placement.
- Admin status update to `packed` was verified: item fulfilled, inventory quantity
  decremented, reservation cleared, sale stock movement created and shipment row
  created.
- Invoice generation created an invoice row and both admin/customer invoice views
  returned 200.
- Customer cancellation was verified on a separate pending order: status changed to
  cancelled, history was appended, reservation was released and no sale movement
  was created.
- Route smoke passed for checkout success, account orders/detail/invoice,
  track-order, admin orders/detail/invoice and shipments.
- No database schema changes were made in Phase 7.

### Carried forward

- Razorpay payment creation/capture/refund flows remain Phase 8.
- Courier tracking webhooks and return logistics were completed in Phase 9.

---

## Phase 8 — Payments (Razorpay) — DONE

### Delivered

- Dedicated `Payment_model` for payment lifecycle writes, gateway lookup, capture,
  failure handling and immutable payment log entries.
- Razorpay gateway library with settings/env-backed credentials, order creation,
  Checkout signature verification and webhook signature verification.
- Customer Razorpay flow at `/payments/razorpay/pay/{order_id}` with pending-payment
  reuse, gateway-order attachment and success/failure redirects.
- Local offline Razorpay simulator for development when Razorpay is disabled or keys
  are missing, keeping the checkout path testable without live credentials.
- Razorpay Checkout callback endpoint that marks verified payments captured, updates
  parent order payment totals/status and logs the event.
- CSRF-excluded Razorpay webhook endpoint for signed `payment.captured` and
  `payment.failed` events, with invalid-signature rejection.
- Checkout now supports COD and Razorpay; Razorpay orders redirect to the payment
  page after order placement.
- Customer order success and account order detail screens expose `Pay Now` only for
  Razorpay payments still pending or failed.
- Admin payment detail screen with payment metadata, related order link, gateway
  identifiers, captured response payload, event logs and refund form.
- Admin manual capture/refund actions for operational support, including refund rows,
  order refunded totals and payment/order status updates.
- Generic admin payment listing now links each payment row to the dedicated payment
  detail screen.
- `payments.status` remains reserved for gateway status; admin generic lifecycle
  operations use `payments.status_flag` instead.

### Verification

- Full PHP lint passed for every file under `application/`.
- Customer cart → checkout with Razorpay → local offline simulator capture passed.
- Captured Razorpay test order was verified through customer success and account
  order detail pages.
- Payment capture updated the order as paid and created `order.create` plus
  `payment.captured` log entries.
- Admin payment list, payment detail and payment logs routes returned 200.
- Admin refund POST created a completed refund record, moved the payment/order into
  partially-refunded state and appended `refund.completed`.
- Invalid Razorpay webhook signature returned 400 and logged receipt without marking
  the payment captured.
- Regression smoke verified partially-refunded orders cannot re-enter payment and no
  longer show `Pay Now` on success/account order screens.
- No database schema changes were made in Phase 8.

### Carried forward

- Live Razorpay settlement/refund API calls should be enabled once production keys
  and webhook secret are configured.
- Courier shipment automation, tracking webhooks and return logistics remain Phase 9.

---

## Phase 9 — Tracking — DONE

### Delivered

- Dedicated `Tracking_model` for shipment dashboards, courier assignment, tracking
  events, unified customer timelines and return logistics.
- Shipment and return status maps in app config, plus badge rendering support for
  `shipment` and `return` status families.
- Admin delivery tracking dashboard at `/admin/tracking` with shipment KPIs,
  searchable/filterable shipment list and direct detail links.
- Admin shipment detail screen for courier name/code, tracking number, tracking URL,
  package weight, shipping cost, estimated delivery and manual tracking events.
- Tracking events can now advance shipment state and keep order lifecycle dates/status
  synchronized for shipped, out-for-delivery, delivered and returned milestones.
- Existing admin order detail shipment widget now links to the dedicated tracking
  screen and can append status-aware tracking events.
- Public courier webhook endpoint at `/tracking/webhook`, CSRF-excluded, with optional
  HMAC verification through `tracking_webhook_secret` or `TRACKING_WEBHOOK_SECRET`.
- Public track-order page now renders richer shipment badges and timestamped delivery
  events alongside order history.
- Customer account order detail now uses a unified order/shipment timeline, richer
  shipment cards and delivered-order return/exchange CTA.
- Customer returns area at `/account/returns` plus delivered-order return/exchange
  request form at `/account/returns/request/{order_id}`.
- Admin return detail/review screen at `/admin/returns/view/{id}` with status updates,
  rejection notes and optional returned-item restocking.
- Generic admin resource list now exposes direct tracking/return detail actions and
  renders shipment/return statuses with the correct badge maps.

### Verification

- Full PHP lint passed for every file under `application/`.
- Admin tracking dashboard loaded and showed the existing packed shipment.
- Admin shipment detail loaded, courier assignment saved and tracking number/URL were
  persisted.
- Admin tracking event marked the shipment delivered and synchronized the parent order
  to delivered.
- Public track-order lookup displayed the delivered tracking event.
- Tracking webhook accepted a JSON courier update and appended a second delivery
  event.
- Customer account order detail showed the unified timeline and return/exchange CTA
  after delivery.
- Customer return request form created a return request for a delivered order.
- Customer returns list displayed the new return request.
- Admin returns list/detail loaded and the return status advanced to approved.
- Latest Phase 9 log scan showed no fresh errors.
- No database schema changes were made in Phase 9.

### Verification data created

- Shipment `SHP-20260802-38FE13` was assigned to `Kupiana Express` with tracking
  number `KXP-PHASE9-001` and marked delivered for smoke testing.
- Order `ORD-20260802-F4A564` was moved to delivered by the tracking flow.
- Return request `RET-20260803-8E47A3` was created and approved for smoke testing.

### Carried forward

- Shipment and return analytics were added to reports in Phase 10.
- Production courier integrations can replace the local webhook adapter once a real
  courier provider and secret are configured.

---

## Phase 10 — Reports — DONE

### Delivered

- Dedicated `Report_model` for read-only aggregate reporting across sales, revenue,
  GST, payments, shipments, returns, customers, inventory, products, suppliers,
  coupons and estimated profit/loss.
- Shared report dashboard KPIs for selected date ranges: orders, revenue, delivered
  shipments and return volume.
- Date range filters on every report with safe defaults to the latest 30-day window.
- CSV export route for every report at `/admin/reports/export/{type}`.
- Expanded reports menu entries for payments, shipments, returns and coupons.
- Rebuilt report view with report-type tabs, reusable KPI cards, dynamic extra
  columns and totals.
- GST report now includes CGST, SGST and IGST split totals.
- Inventory report now separates available, on-hand and reserved stock.
- Products report now ranks top products by ordered quantity and sales value.
- Profit/loss report now estimates gross profit from gross revenue, refunds,
  product cost and shipment cost.
- Shipment and return analytics now consume the Phase 9 tracking/return data.

### Verification

- Full PHP lint passed for every file under `application/`.
- Authenticated admin smoke passed for all report pages:
  sales, revenue, GST, payments, shipments, returns, customers, inventory,
  products, suppliers, coupons and profit/loss.
- Date-range filters were exercised with `2026-08-01` through `2026-08-03`.
- Sales CSV export returned 200 with `text/csv; charset=utf-8`.
- Report data range was verified against existing orders, shipments and returns.
- Latest Phase 10 log scan showed no fresh error-level entries.
- No database schema changes were made in Phase 10.

### Carried forward

- Repeatable smoke/lint coverage was added in Phase 11.
- Future production analytics can add chart widgets and scheduled report delivery.

---

## Phase 11 — Testing — DONE

### Delivered

- Project-native test harness under `tests/` that does not require Composer vendor
  dependencies or PHPUnit.
- `tests/lint.sh` to lint every PHP file under `application/` and `tests/`.
- `tests/run.sh` to run lint, start the PHP built-in server with an HMVC-compatible
  router, execute the smoke suite and stop the server automatically.
- `tests/support/router.php` mirrors the documented dev-server rewrite behavior.
- `tests/smoke.php` provides repeatable route, auth, ACL, report export, webhook
  rejection and read-only database integrity assertions.
- `tests/README.md` documents how to run the suite locally and against an existing
  server.
- Composer shortcuts added: `composer test` and `composer lint`.
- README updated to reflect the current bcrypt/password_hash authentication and the
  Phase 11 test workflow.

### Automated coverage

- Public storefront routes: home, shop, search, deals, offers, brands, cart, wishlist,
  contact, track-order, blog, robots.txt and sitemap.xml.
- Checkout empty-cart behavior: page renders or safely redirects instead of 500ing.
- Admin access control: guests redirect to login; customers receive 403.
- Admin route smoke: dashboard, products, orders, inventory, tracking, returns,
  payments and all report pages.
- Report export: sales CSV export returns a CSV response.
- Webhook rejection paths: invalid Razorpay signature and invalid tracking JSON return
  400.
- Customer account routes: dashboard, orders, returns, profile, security and
  addresses.
- Read-only database integrity: database connection, expected table count,
  soft-delete column presence and seeded active users.

### Verification

- `tests/run.sh` passed twice after implementation.
- Final run: **52 passed, 0 failed**.
- Full PHP lint passed for every file under `application/` and `tests/`.
- Latest Phase 11 log scan showed no fresh error-level entries.
- The test runner stopped its local PHP server automatically; no listener remained on
  port 8891.
- No database schema changes were made in Phase 11.

### Carried forward

- Phase 12 used the repeatable test harness as the safety net for optimization work.
- A future disposable test database can enable mutating end-to-end checkout,
  fulfilment, payment, tracking and return-flow assertions.

---

## Phase 12 — Optimization — DONE

### Delivered

- Added `App_cache`, a small application cache facade that uses CodeIgniter's file
  cache when available and falls back to per-request memory when file caching is not
  writable or supported.
- Autoloaded the cache facade so controllers/models can share the same lightweight
  optimization path.
- Cached frequently reused storefront reference data: featured products, categories,
  mega menu, brands, testimonials and CMS pages.
- Rebuilt the storefront mega menu loader to remove the parent/child category N+1
  query pattern.
- Replaced cart and wishlist header-count lookups with direct aggregate/count
  queries instead of loading full product rows.
- Added cached report aggregation for admin report pages and CSV exports with short
  TTLs suitable for operational dashboards.
- Added short-lived admin dashboard KPI and chart caching to reduce repeated
  aggregate work during normal page refresh/AJAX polling.
- Added Apache asset cache headers for static files under `public/assets/`.

### Verification

- Full PHP lint passed for every file under `application/` and `tests/`.
- `tests/run.sh` passed after optimization.
- Final run: **52 passed, 0 failed**.
- Latest Phase 12 log scan showed no fresh error-level entries.
- The test runner stopped its local PHP server automatically; no listener remained on
  port 8891.
- No database schema changes were made in Phase 12.

### Carried forward

- Phase 13 can focus on deployment hardening: production environment values, web
  server rewrite/cache configuration, writable-directory checks, backup/restore
  routine, scheduler/cron notes and launch verification.
- Current caches are deliberately short-TTL and self-expiring; explicit cache
  invalidation hooks can be added later if admin catalog edits need instant
  storefront freshness.

---

## Phase 13 — Deployment — DONE

### Delivered

- Added a production deployment runbook covering server requirements, release files,
  environment setup, writable directories, database launch notes, preflight checks,
  smoke testing, scheduler setup, gateway webhooks and launch checklist.
- Added `.env.example` with production-ready placeholders for app, database, cookie,
  session, ZeptoMail, Razorpay and tracking webhook settings.
- Updated `index.php` so `CI_ENV` can be supplied by the server process or `.env`
  before CodeIgniter defines the application environment.
- Made deployment-sensitive config env-driven: `APP_BASE_URL`,
  `APP_ENCRYPTION_KEY`, `SESSION_SAVE_PATH`, cookie domain/secure/samesite and
  trusted proxy IPs.
- Disabled CodeIgniter database query saving in production to reduce memory use and
  avoid retaining debug query strings.
- Hardened the root Apache `.htaccess` with directory-index blocking, sensitive-file
  denial and baseline security headers while preserving CodeIgniter rewrites.
- Added `scripts/preflight.php` to verify PHP/runtime requirements, protected files,
  writable paths, database connectivity and production readiness warnings.
- Added Composer shortcuts for deployment preflight and the production maintenance
  cron task.
- Added a CLI-only `Cron` controller and wired the carried-forward
  `Login_attempt_model::prune()` cleanup into a daily scheduler command.
- Updated README with the deployment quick path and scheduler note.

### Verification

- Targeted PHP lint passed for the new/changed deployment files.
- Full `tests/run.sh` passed after deployment hardening.
- Final run: **52 passed, 0 failed**.
- Local preflight passed with **0 failures** and expected local-development warnings.
- Production-mode preflight passed with supplied production environment values:
  **0 failures, 0 warnings**.
- CLI maintenance command ran successfully and removed no rows with the conservative
  verification retention window.
- Latest Phase 13 log scan showed no fresh error-level entries.
- The test runner stopped its local PHP server automatically; no listener remained on
  port 8891.
- No database schema changes were made in Phase 13.

### Carried forward

- A real production launch still requires host-specific values: final domain, TLS,
  production database credentials, mail/gateway secrets, webhook dashboard setup and
  a verified backup/restore process.
- If the deployment target is offline or CDN-restricted, vendor Bootstrap 5 and Font
  Awesome into `public/assets/vendor/` before launch.

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
- Bootstrap/Font Awesome/Chart.js load from CDN. Phase 13 documents vendoring them
  for offline or CDN-restricted deployments.
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
- `login_attempts` pruning is now available through the Phase 13 CLI scheduler
  command documented in `DEPLOYMENT.md`.
- Registration does not yet verify the phone number.

---

## Current resume point — COMPLETE

Phase 13 is complete. All listed build phases are marked **DONE**. Future work should
be treated as launch customization, production credential setup or post-launch
enhancement rather than a remaining numbered phase.

### Post-phase SEO hardening — DONE

- Storefront layout now renders normalized title, description, keywords when
  present, canonical, prev/next pagination links, hreflang, Open Graph and Twitter
  card metadata.
- Site-wide Organization and WebSite JSON-LD is emitted automatically.
- Product pages now render richer Product/Offer/AggregateRating/BreadcrumbList
  schema, canonical URLs, product Open Graph images and admin-managed `seo_meta`
  overrides.
- Shop, category, brand and deals pages now render clean canonicals, ItemList schema
  and breadcrumb schema; filtered/search variants are kept `noindex,follow`.
- CMS pages, contact and blog posts now render page/contact/article schema, with a
  new SEO-friendly blog detail route.
- `robots.txt` now keeps admin, account, cart, checkout, payment, API and filtered
  search URLs out of crawl focus while advertising the sitemap.
- `sitemap.xml` now includes storefront discovery URLs, categories, brands,
  products with image metadata, CMS pages and blog posts.
- Admin/API responses emit `X-Robots-Tag` noindex headers where appropriate.
- `SEO.md` documents the launch SEO checklist for domain, content, assets,
  Search Console, analytics and sitemap submission.
- The smoke harness now verifies key SEO behavior; latest run passed **60 passed,
  0 failed**.
- No database schema changes were made.

### Organic spices & oils storefront conversion — DONE

- Homepage hero, section labels, trust strip and route metadata now position
  Kupiana as an organic spices, whole masalas and cold-pressed oils store.
- Live catalog content now uses pantry-specific categories, brands, products,
  product images, banners, CMS pages, testimonials, blog categories, attributes,
  tags, HSN descriptions and SEO settings.
- Seed data now mirrors the organic spices/oils catalog so future resets do not
  restore the previous general-commerce product mix.
- Added SVG product, category, brand and hero/banner assets for the organic pantry
  catalog.
- Smoke harness now searches for turmeric instead of the previous sample product.
- Latest verification passed **60 passed, 0 failed**.
- No database schema changes were made.

Remember: `payments.status` is the gateway state, while `payments.status_flag` is the
MY_Model lifecycle column. Any dedicated `Payment_model` must set
`$status_column = 'status_flag'`.

---

## Storefront imagery — DONE (2026-08-03)

All flat SVG artwork was replaced with real photography, watermarked with the
Kupiana logo. 25 images: 10 products, 10 categories, 3 banners, hero, og:image.

### The pipeline (this is the deliverable, not the images)

| File | Purpose |
|---|---|
| `tools/fetch_sources.php` | Re-downloads the source photographs (~20 MB, not in git) |
| `tools/image_lib.php` | GD routines: crop, cover-fit, white-key, watermark, save |
| `tools/build_images.php` | Crop map + build. `--sheet` writes a QA contact sheet |
| `tools/source/CREDITS.md` | Origin and licence of every source frame |

```bash
/Applications/XAMPP/bin/php tools/fetch_sources.php
/Applications/XAMPP/bin/php tools/build_images.php --sheet   # -> /tmp/kupiana_images.jpg
```

Output overwrites the exact filenames the database already references, so
swapping in real product photography needs **no SQL** — point a recipe's `src`
at a new file and re-run. Verified by deleting `tools/source/` entirely and
rebuilding all 25 images from scratch.

### Key decision: one flatlay, many products

Eight of ten product images are cropped from a **single 6000×4000 flatlay**.
Sourcing ten separate stock photos was tried first and produced exactly the
"odd" look being complained about — mismatched white balance, backgrounds and
lighting across the grid. One frame means one lighting setup, so the catalogue
reads as art-directed rather than assembled. `tools/build_images.php` holds the
crop map in *source* pixel coordinates.

### Watermark

The supplied logo is an opaque PNG on white, so it cannot be composited
directly. `img_key_white()` keys the background to transparency with a feathered
edge, and the mark sits on a soft translucent plate — without it the dark-brown
logo vanishes on dark photography and the green leaves disappear against herbs.

### Sourcing: what was actually tried

Four sources were tested before settling. Recorded so nobody repeats it:

| Source | Result |
|---|---|
| Unsplash direct ids | Best quality and licence, but **no keyless search** — ids must be recalled and verified by eye. ~85% resolve, ~20% on-subject. |
| Unsplash / loremflickr search endpoints | Dead (503/500). |
| Openverse API | Real topical search, but CC0/PD coverage is thin and often off-subject. Yielded one usable frame. |
| Wikimedia Commons categories | Densely populated but **documentary**: cumin *fields*, oil boiling in pots, a competitor's branded jar. Unusable for retail. |

### Known gaps

- **Groundnut oil and sesame oil share one bottle photograph** (wide vs. tight
  crop). Both are clear golden pressed oils so it reads acceptably, but it is
  the same bottle. These two are the first to replace with a real shoot.
- These are stock photos of the correct *ingredients*, not of Kupiana's actual
  products. Fine for launch; replace before print or paid ads.
- Brand logos stay as SVG (`public/uploads/brands/*.svg`) — they are marks, not
  photographs, and vector is correct for them.
- `pantry-staples` category has an image but no products mapped to it.
- Related products render 0 on product pages: `related` matches the product's
  primary category, which for this catalogue is a leaf holding one product.
  Not a regression — worth revisiting when the catalogue grows.

### `.gitignore`

`public/uploads/**` is ignored as runtime upload space, but the seeded
catalogue imagery is now **un-ignored** — `database/seed.sql` references those
exact filenames, so a fresh clone without them 404s every product tile.
`tools/source/*.jpg` stays ignored and is re-fetchable.

### Two pre-existing bugs found and fixed

Both were hiding the new imagery, so they were fixed rather than just reported.

1. **`Store_model::products()` paginated at one product per page.** Callers
   build `$params` straight from the query string, so `per_page` is *present
   but NULL* when the visitor supplies nothing. `array_get()` only falls back on
   a **missing** key, not a null value, so the default of 12 never applied:
   `(int) NULL` → `0` → `max(1, 0)` → **1**. `/shop` showed "Showing 1–1 of 10"
   across 10 pages. Now coalesces on emptiness rather than absence.
2. **Category pages listed no products at all.** `product_query()` filtered on
   `products.category_id`, the single *primary* category. Every product's
   primary category here is a leaf ("Turmeric & Ginger"), so browsing a parent
   ("Organic Spices") matched nothing even though `product_categories` mapped
   it correctly. Now matches through the pivot via a subquery, which avoids the
   row duplication a JOIN would introduce.

Also fixed: `seo_helper` advertised the grey `placeholder.svg` as the default
`og:image` — social shares showed a placeholder. Now `og-default.jpg`.
`catalog/views/home.php` hard-coded a deleted SVG hero.

### Verification — PASSED

Lint clean across `application/` and `tools/`; no ERROR log entries.

| Check | Result |
|---|---|
| Images built | 25 (6.0 MB total) |
| Rebuild from empty `tools/source/` | All 8 sources fetched, all 25 rebuilt |
| DB paths resolving to a file on disk | 23/23 (10 products, 10 categories, 3 banners) |
| Fresh `schema.sql` + `seed.sql` load | Clean; 10/10/3 distinct images |
| Every image URL on `/`, `/shop`, `/category/*`, product page | all HTTP 200 |
| `/shop` product cards | 1 → **10** after pagination fix |
| Category cards vs. pivot counts | 4/3/3/3/1/2/1/0 — exact match |
| `?per_page=4` override | still honoured ("Showing 1–4 of 10") |
| Served content types / dimensions | `image/jpeg`, 1000×1000 / 800×600 / 2000×760 |
| SVGs still served from uploads | none |

---

## Theme — terracotta (2026-08-03)

Primary is **`#cc4e3a`**, the chilli red from the logo. The rest of the palette
was retuned around it rather than left on the stock Bootstrap blue-grey scale.

### Palette

| Token | Value | Notes |
|---|---|---|
| `--k-primary` | `#cc4e3a` | Brand. Washes, borders, gradients, focus rings |
| `--k-primary-ink` | `#c24733` | Anything with text on it — see accessibility below |
| `--k-primary-hover` / `-dark` | `#ae3f2d` / `#933425` | Hover / active |
| `--k-success` | `#4b8b3b` | Leaf green from the logo sprig, not emerald |
| `--k-info` | `#0f7d8c` | Teal — complement of a warm red |
| `--k-warning` | `#d59120` | Turmeric amber |
| `--k-danger` | `#a4133c` | Cool crimson — **deliberately not a deeper red** |
| `--k-dark` / sidebar | `#2a1a12` | Warm near-black from the logo brown |
| neutrals | `#faf6f2` `#f6efe8` `#e9dfd6` `#2c1e16` `#7b6a5f` | Warm greys |

Two decisions worth not undoing:

- **Danger is crimson, not a deeper red.** A darker red sits at the same hue as
  primary (8°) and becomes indistinguishable inside a 12%-tint badge — exactly
  where a destructive action must never be ambiguous. Crimson (343°) reads
  clearly apart.
- **Neutrals are warm.** Cool slate next to terracotta reads dirty; these greys
  carry a little red so surfaces sit under the brand colour.

Dark theme lifts primary to `#e4674f` and uses warm near-blacks — the
light-mode tones go muddy on a dark backdrop, and cold slate makes the
terracotta look orange.

### Accessibility

White text on `#cc4e3a` measures **4.45:1** — just under the WCAG AA threshold
of 4.5 for normal text, which button labels and links are. `--k-primary-ink`
(`#c24733`, 4.95:1) is 2% darker, visually indistinguishable, and carries every
text-bearing surface: buttons, links, active pagination, avatars, the newsletter
band. `#cc4e3a` itself remains the brand colour everywhere text is not involved.

To use the exact brand hex everywhere instead, set `--k-primary-ink: #cc4e3a`
and accept 4.45 — one line.

**All 27 foreground/background pairs pass AA in both themes**, verified by
computing relative luminance (including flattening the `rgba()` badge tints over
their surface, since a tint is not a solid colour). `btn-success` also had to
drop to `#437d35`; `badge-soft-warning` and `-secondary` inks were nudged from
AA-large to full AA.

### Files touched

- `public/assets/css/app.css` — tokens, dark theme, soft badges, sidebar,
  overlays, dashboard gradient.
- **Bootstrap override block** (new). Bootstrap 5.3 compiles `.btn-*` and
  `.btn-outline-*` to literal hex through Sass, so setting `--bs-primary` alone
  leaves ~100 buttons blue. Each variant is re-declared via its own
  `--bs-btn-*` API so hover/active/focus/disabled all follow. Same for alerts,
  `.form-check-input:checked`, `.progress`, `.dropdown-item.active`,
  `.nav-pills`, `.list-group`, `.accordion`.
- `application/modules/admin/views/dashboard.php` — Chart.js fallbacks, revenue
  gradient, and the order-status doughnut (`pending / processing / shipped /
  delivered / cancelled` now map to the new semantic colours).
- `application/views/email/layout.php` — header, links **and neutrals**. Email
  clients cannot read CSS custom properties, so these are literal hex and would
  otherwise have stayed cold grey against a terracotta header.

Not touched: `application/views/errors/html/*` are CodeIgniter's stock error
pages, still on framework defaults. Low traffic, but restyle them if a visitor
could ever see one.

### Verification

CSS braces balanced, zero old-palette hex remaining anywhere in `application/`
or `public/assets/`, PHP lint clean, `/`, `/shop`, `/category/*`, product,
`/login`, `/register` all HTTP 200, no ERROR log entries.

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
