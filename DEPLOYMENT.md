# Kupiana Deployment Runbook

This runbook prepares Kupiana for a standard Apache + PHP + MySQL production
deployment.

## 1. Server requirements

- PHP 8.0+ with `mysqli`, `curl`, `json`, `mbstring`, `openssl` and `fileinfo`.
- MySQL/MariaDB database and user.
- Apache with `mod_rewrite`; `mod_headers` and `mod_expires` are recommended.
- TLS certificate installed before enabling secure cookies.

## 2. Release files

Deploy the repository root as the web root, or point the virtual host document root
at the repository root. The root `.htaccess` routes requests through `index.php`,
blocks directory indexes and denies direct access to sensitive project files.

Do not expose these directories through a separate public alias:

- `application/`
- `system/`
- `database/`
- `tests/`
- `scripts/`

## 3. Environment

Copy `.env.example` to `.env` on the server and replace the placeholders.

Minimum production values:

- `CI_ENV=production`
- `APP_BASE_URL=https://your-domain.example/`
- `APP_ENCRYPTION_KEY=` a unique random value of at least 32 characters
- database credentials for the production database
- `COOKIE_SECURE=true` when the site is served over HTTPS

Optional provider values:

- ZeptoMail: `MAIL_ENABLED`, `ZEPTOMAIL_API_KEY`, sender address/name
- Razorpay: `RAZORPAY_ENABLED`, key ID, key secret and webhook secret
- Courier webhook: `TRACKING_WEBHOOK_SECRET`
- Reverse proxy: `TRUSTED_PROXY_IPS`

## 4. Writable paths

The web server user must be able to write:

- `application/cache/`
- `application/cache/sessions/`
- `application/logs/`
- `public/uploads/`
- `public/uploads/backups/`
- `public/uploads/invoices/`

The uploads `.htaccess` blocks script execution inside user-controlled uploads.

## 5. Database

Create the production database from the files under `database/`, then create an
initial admin account from the seed data or through your normal operations process.

Take a verified database backup before every production release.

## 6. Preflight

Run the deployment checker from the project root:

```bash
php scripts/preflight.php
```

Treat failures as blockers. Warnings identify values that are acceptable in local
development but should be reviewed before launch.

## 7. Smoke test

Run the automated harness before cutting over:

```bash
tests/run.sh
```

For an already-running staging URL:

```bash
KUPIANA_BASE_URL=https://staging.example.com php tests/smoke.php
```

## 8. Scheduler

Add a daily scheduler entry for login-attempt retention cleanup:

```bash
php /path/to/kupiana/index.php cron prune_login_attempts 90
```

Use the same PHP binary that serves the application. Increase/decrease the retention
window if your audit policy requires it.

## 9. Gateway webhooks

Configure provider dashboards to call:

- Razorpay: `https://your-domain.example/payments/razorpay/webhook`
- Courier/tracking provider: `https://your-domain.example/tracking/webhook`

Set and verify the corresponding webhook secrets before enabling live traffic.

## 10. Launch checklist

- Production `.env` is present and not committed.
- `CI_ENV=production` is active.
- `APP_BASE_URL` uses the final HTTPS domain.
- Secure cookies are enabled after TLS is active.
- ZeptoMail, Razorpay and tracking webhook secrets are configured if those services
  are live.
- Preflight passes.
- Smoke tests pass.
- A fresh database backup exists and restore steps are known.
- Error logs are clean after the first staging/production smoke pass.
