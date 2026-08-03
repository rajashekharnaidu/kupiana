# Kupiana

Enterprise ecommerce application built on CodeIgniter 3 with HMVC-style modules.

## Stack

- CodeIgniter 3.1.13
- HMVC modules under `application/modules`
- MySQL using `mysqli`
- Shared email/password login for admin and customer users
- Role and permission assignment through `roles`, `permissions`, `role_permissions`
  and `user_roles`

## Database

Create the database and tables from `database/schema.sql`.

Copy `.env.example` to `.env`, then adjust the values for your machine.

Default local connection values:

- `DB_DATABASE=kupiana`
- `DB_USERNAME=root`
- `DB_PASSWORD=`
- `DB_HOST=localhost`

Seed accounts:

- Admin: `admin@kupiana.test` / `admin123`
- User: `user@kupiana.test` / `user123`

Passwords are stored with PHP `password_hash()` and verified with `password_verify()`.

## Deployment

Production deployment notes live in [DEPLOYMENT.md](DEPLOYMENT.md). The short path:

```bash
cp .env.example .env
php scripts/preflight.php
tests/run.sh
```

Before launch, set `CI_ENV=production`, an HTTPS `APP_BASE_URL`, a unique
`APP_ENCRYPTION_KEY`, production database credentials, secure-cookie settings and any
live ZeptoMail/Razorpay/tracking webhook secrets.

Schedule login-attempt pruning daily:

```bash
php index.php cron prune_login_attempts 90
```

## Testing

Run the Phase 11 test harness:

```bash
tests/run.sh
```

Or via Composer:

```bash
composer test
```

The runner lints the application, starts a local PHP dev server, checks public/admin/customer routes, verifies ACL behavior, exercises report CSV export, checks webhook rejection paths, and performs read-only database integrity assertions.

## Modules

- `admin`: admin dashboard and future back-office modules
- `user`: customer account area
- `catalog`: SEO-focused storefront/category/product entry points

## Ecommerce SEO

The storefront includes clean routes, canonical links, per-page metadata, Open Graph
and Twitter tags, robots/sitemap endpoints, product slug helpers, entity-level SEO
overrides, and JSON-LD for site, product, breadcrumb, listing, page and blog content.
See [SEO.md](SEO.md) for the launch checklist.
