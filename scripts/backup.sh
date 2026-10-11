#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

# Usage: BACKUP_DIR=/private/backups ./scripts/backup.sh
# Run from repository root. Never expose backup output via Nginx.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP="$ROOT/apps/web"
DEST="${BACKUP_DIR:-$ROOT/backups}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$DEST"
chmod 700 "$DEST"
[[ -f "$APP/.env" ]] || { echo 'Laravel .env not found' >&2; exit 1; }
[[ -d "$APP/storage" ]] || { echo 'Laravel storage not found' >&2; exit 1; }

# This backup archives application storage only; database backup must be performed separately
# with a consistent snapshot using database-native tools.
FILE="$DEST/church-center-storage-$STAMP.tar.gz"
tar -czf "$FILE" -C "$APP" storage
sha256sum "$FILE" > "$FILE.sha256"
chmod 600 "$FILE" "$FILE.sha256"
echo "Storage archive created: $FILE"
echo 'IMPORTANT: database and worker session credentials are NOT included.'
echo 'Restore: verify sha256sum -c, stop app, extract to isolated test instance first.'
