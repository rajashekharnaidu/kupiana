#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/Applications/XAMPP/bin/php}"

if [[ ! -x "$PHP_BIN" ]]; then
  echo "PHP binary not found or not executable: $PHP_BIN" >&2
  exit 1
fi

find "$ROOT_DIR/application" -name '*.php' -print0 | xargs -0 -n1 "$PHP_BIN" -l
find "$ROOT_DIR/tests" -name '*.php' -print0 | xargs -0 -n1 "$PHP_BIN" -l
