# Church Center production readiness gate

**Status: NOT production-ready.** Do not accept customer payments or enable mass messaging based solely on a successful git pull.

## Automated message dispatch (implemented but not verified)

The Laravel command `php artisan church:dispatch --limit=20` claims due jobs in a transaction and sends through the private authenticated worker. The user must explicitly activate a draft and the church must have an active license. Direct-message recipients must be church contacts with `consent_at` set; invalid recipients are blocked. Jobs are marked `unknown` on transport exceptions and never automatically retried, avoiding duplicate sends when delivery outcome is uncertain.

Configure cron **only after** consent, target and WhatsApp tests succeed:

```cron
* * * * * cd /absolute/path/church-center/apps/web && /usr/bin/php artisan church:dispatch --limit=20 >> /var/log/church-center-dispatch.log 2>&1
```

A successful worker response indicates accepted by the transport, **not confirmed delivery**. No recurring IH generation, opt-out flow, delivery receipts, media, robust retry or provider limits yet. The current group-recipient path lacks group membership/consent verification: **do not enable group broadcast in production**. No scheduler cron has been installed by GitHub updates.

## Payments / QRIS

Only **manual license request and admin approval** are implemented. No verified GoPay Merchant QRIS API contract, credentials, callback signature or settlement reconciliation is available. QRIS must remain disabled. Never infer a paid status from a browser redirect. Before production, obtain official provider documentation and sandbox access, implement signed webhook verification, idempotent payment processing, reconciliation and tests. Do not commit keys.

## Backup / restore

`bash scripts/backup.sh` creates a private checksum-verified **storage-only** archive. It does **not** back up MariaDB, Laravel encryption keys, or WhatsApp sessions. Full recovery requires consistent database-native backups, encrypted offsite copies, a secure secret escrow and an isolated restore drill. Never put archives under public web roots.

## Release checks

1. Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan test`, `php artisan route:list` on a staging clone.
2. Run `npm install`, `node --check chiwa.js`, and `npm test` for the worker.
3. Configure matching 32+ byte `WORKER_API_TOKEN` and `CHURCH_WORKER_TOKEN` securely. Keep port 31181 on loopback.
4. Test Google OAuth, church separation, QR scanning, reconnect, message receipt, consent enforcement, license expiration, payment approvals and backup restoration on staging.
5. Review Nginx/TLS, CSRF, session cookies, security headers, queue operations, monitoring, log rotation and backups.
6. Do not claim release complete until these tests pass and missing features above are implemented.

## Daily recurring IH (code added; staging verification required)

The Fulltime dashboard has an **Auto IH Harian** page. Each morning/noon/evening rule is independent and disabled by default. Rules accept opted-in direct contacts only, with local HH:MM and the church timezone. `church:generate-recurring` inserts at most one due message per rule per local date, using a unique idempotency key. `church:dispatch` sends queued messages only for active churches with an active license and valid consent.

Run both commands each minute **only after completing staging tests**:

```cron
* * * * * cd /absolute/path/church-center/apps/web && /usr/bin/php artisan church:generate-recurring >> /var/log/church-center-generate.log 2>&1
* * * * * cd /absolute/path/church-center/apps/web && /usr/bin/php artisan church:dispatch --limit=20 >> /var/log/church-center-dispatch.log 2>&1
```

The commands are not installed in cron automatically. Configure log rotation. For high concurrency, add a distributed lock and provider-level rate limiting before using multiple scheduler instances. Stop the scheduler during database restore.

## MariaDB backup

`bash scripts/backup-database.sh` creates a compressed `mariadb-dump` using the database credentials from the Laravel `.env`, in a private backup directory. The script is intended for local trusted administrators and has **not been executed on the VPS**. Use `BACKUP_DIR=/secure/offsite/staging` to set a non-public destination. Validate the checksum and test restoring into a separate database. Back up Laravel `APP_KEY` securely and worker session files separately; neither is included in this script.

## Still not implemented

Provider-verified QRIS (awaiting official GoPay Merchant API specification and sandbox credentials), authenticated payment webhooks, refund/reconciliation, verified opt-out and group broadcast compliance, contact imports, complete tenant-aware analytics, central admin user controls, secure whole-system one-click restore and end-to-end provider tests. Do not mark these as complete.
