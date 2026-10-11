#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENVFILE="$ROOT/apps/web/.env"
DEST="${BACKUP_DIR:-$ROOT/backups}"
[[ -f "$ENVFILE" ]] || { echo 'Missing Laravel .env' >&2; exit 1; }
command -v mariadb-dump >/dev/null || { echo 'Install mariadb-client' >&2; exit 1; }
command -v python3 >/dev/null || exit 1
mkdir -p "$DEST"
chmod 700 "$DEST"
# Parse only literal dotenv assignments; never evaluate .env as shell commands.
CONFIG="$(python3 - "$ENVFILE" <<'PY'
import json,sys
v={}
for line in open(sys.argv[1]):
    line=line.strip()
    if not line or line.startswith('#') or '=' not in line: continue
    k,x=line.split('=',1)
    v[k.strip()]=x.strip().strip('"').strip("'")
print(json.dumps({k:v.get(k,'') for k in ('DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD')}))
PY
)"
readarray -t SETTINGS < <(python3 -c 'import json,sys; v=json.loads(sys.argv[1]); print("\n".join(v.get(k,"") for k in ("DB_CONNECTION","DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD")))' "$CONFIG")
[[ "${SETTINGS[0]}" == mysql || "${SETTINGS[0]}" == mariadb ]] || { echo 'Only MySQL/MariaDB supported' >&2; exit 1; }
[[ -n "${SETTINGS[3]}" && -n "${SETTINGS[4]}" ]] || { echo 'DB name/user required' >&2; exit 1; }
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "${SETTINGS[1]:-127.0.0.1}" "${SETTINGS[2]:-3306}" "${SETTINGS[4]}" "${SETTINGS[5]}" > "$TMP"
chmod 600 "$TMP"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUT="$DEST/church-center-db-$STAMP.sql.gz"
mariadb-dump --defaults-extra-file="$TMP" --single-transaction --quick --routines --triggers --events -- "${SETTINGS[3]}" | gzip -c > "$OUT"
gzip -t "$OUT"
sha256sum "$OUT" > "$OUT.sha256"
chmod 600 "$OUT" "$OUT.sha256"
echo "Created $OUT"
echo 'Restore to an isolated test database first. Session files and encryption keys are separate.'
