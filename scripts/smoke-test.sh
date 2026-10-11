#!/usr/bin/env bash
set -Eeuo pipefail
BASE="${1:-http://127.0.0.1:8088}"
for route in / /features /pricing /health; do
  status="$(curl -sS -o /dev/null -w '%{http_code}' "${BASE%/}$route")"
  printf '%-12s %s\n' "$route" "$status"
  [[ "$status" == 200 ]] || exit 1
done
curl -fsS "${BASE%/}/health" | grep -q '"status":"ok"'
echo "Smoke test passed"
