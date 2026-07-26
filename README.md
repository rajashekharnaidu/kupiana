# Kupiana

Fresh CodeIgniter 3 ecommerce scaffold with HMVC-style modules.

## Stack

- CodeIgniter 3.1.13
- HMVC modules under `application/modules`
- MySQL using `mysqli`
- Shared email/password login for admin and customer users
- Role assignment through `roles` and `user_roles`

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

Passwords are stored with MD5 because that was requested. Before production, replace this with PHP `password_hash()` and `password_verify()`.

## Modules

- `admin`: admin dashboard and future back-office modules
- `user`: customer account area
- `catalog`: SEO-focused storefront/category/product entry points

## Ecommerce SEO

The scaffold includes clean routes, canonical links, per-page metadata, Open Graph tags, product slug helpers, category/product meta columns, and starter Product JSON-LD on product pages.
