#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
[[ $# -eq 2 ]] || { echo "Usage: restore-database-staging.sh backup.sql.gz staging_database" >&2; exit 2; }
BACKUP="$(realpath -- "$1")"
TARGET="$2"
[[ -f "$BACKUP" && -f "$BACKUP.sha256" ]] || exit 1
[[ "$TARGET" =~ ^church_center_(staging|restoretest)_[a-zA-Z0-9_]+$ ]] || { echo "Staging target only" >&2; exit 2; }
[[ -f "${MYSQL_TEST_DEFAULTS_FILE:-}" ]] || { echo "Set staging-only MYSQL_TEST_DEFAULTS_FILE" >&2; exit 2; }
[[ "${RESTORE_CONFIRM:-}" == "RESTORE_TO_STAGING:$TARGET" ]] || { echo "Set RESTORE_CONFIRM=RESTORE_TO_STAGING:$TARGET" >&2; exit 2; }
(cd "$(dirname "$BACKUP")" && sha256sum -c "$(basename "$BACKUP").sha256")
gzip -t "$BACKUP"
gzip -dc "$BACKUP" | mariadb --defaults-extra-file="$MYSQL_TEST_DEFAULTS_FILE" "$TARGET"
echo "Staging restore complete; verify data before production changes."
