#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/Applications/XAMPP/bin/php}"
HOST="${TEST_HOST:-127.0.0.1}"
PORT="${TEST_PORT:-8891}"
BASE_URL="${TEST_BASE_URL:-http://${HOST}:${PORT}}"
SERVER_LOG="${TEST_SERVER_LOG:-/tmp/kupiana-test-server.log}"
SERVER_PID=""

cleanup() {
  if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

"$ROOT_DIR/tests/lint.sh"

if [[ -z "${TEST_BASE_URL:-}" ]]; then
  if lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
    echo "Port $PORT is already in use. Set TEST_BASE_URL to test that server, or free the port." >&2
    exit 1
  fi
  "$PHP_BIN" -S "${HOST}:${PORT}" -t "$ROOT_DIR" "$ROOT_DIR/tests/support/router.php" >"$SERVER_LOG" 2>&1 &
  SERVER_PID="$!"
  export KUPIANA_WAIT_URL="$BASE_URL/"
  for _ in {1..40}; do
    if "$PHP_BIN" -r '$url = getenv("KUPIANA_WAIT_URL"); $code = 0; $ch = curl_init($url); curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 1)); curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch); exit($code > 0 ? 0 : 1);' >/dev/null 2>&1; then
      break
    fi
    sleep 0.25
  done
fi

TEST_BASE_URL="$BASE_URL" "$PHP_BIN" "$ROOT_DIR/tests/smoke.php"
