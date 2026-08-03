# Kupiana test suite

Phase 11 adds a lightweight, local-first test harness for this CodeIgniter 3 app.
It does not require Composer or PHPUnit.

## Run everything

```bash
tests/run.sh
```

The runner:

- lints every PHP file under `application/` and `tests/`;
- starts the PHP built-in server with `tests/support/router.php`;
- runs `tests/smoke.php` against `http://127.0.0.1:8891`;
- stops the server when finished.

## Run against an existing server

```bash
TEST_BASE_URL=http://127.0.0.1:8899 tests/run.sh
```

## Useful overrides

```bash
PHP_BIN=/Applications/XAMPP/bin/php TEST_PORT=8892 tests/run.sh
```

The smoke suite is intentionally mostly read-only. It verifies public routes, admin
routes, customer routes, ACL, report exports, webhook rejection paths and database
integrity assumptions without creating checkout, payment, shipment or return records.
