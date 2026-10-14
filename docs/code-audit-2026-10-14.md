# Church Center code audit — 2026-10-14

## Corrected in this batch
- Birthday query now uses Laravel date query builder instead of MySQL-only DATE_FORMAT; SQLite test databases can run it.
- Recurring rules cannot be re-enabled after consent revocation or license expiration.
- Manual automation drafts and activation require a consented church contact; group JIDs are not accepted.
- Contacts can be exported as a church-scoped CSV with spreadsheet-formula protection.
- Additional regression tests cover manual automation consent and group-target rejection.

## Outstanding production blockers (do not mark as complete)
1. Worker inbound opt-out: STOP/Berhenti is not wired to Laravel consent revocation. Staff can revoke manually. A signed worker-to-web callback, idempotency, and durable event queue are needed.
2. Google Contacts: requires Google People API OAuth scopes, token encryption/rotation, explicit import preview and consent policy. Not implemented.
3. XLSX import: CSV works; XLSX parsing, file constraints, and preview not implemented.
4. GoPay Merchant QRIS: official gateway API credentials, sandbox integration, signature verification and reconciliation are not implemented. Manual approval only.
5. WhatsApp auto-reply: fixed menu/start message exists; per-church configurable FAQ not implemented.
6. Birthday and recurring schedulers: cron is documented but not configured on VPS. Test timezone boundaries and leap-day policy.
7. Dispatch: statuses indicate API acceptance, not delivery receipts; rate limiting is conservative but lacks a robust provider policy and circuit breaker. A durable distributed lock is needed in production.
8. Restore: staging DB-only restore helper exists, not an automated production disaster recovery procedure. Test restore and include media/sessions securely.
9. Backup: database script exists, but retention, offsite encryption, schedule and recovery drills remain.
10. Admin settings and reports: partial. Revenue totals are requested amounts by status, not audited settlement.
11. Multi-tenant penetration tests, integration tests and actual worker/provider tests have not been executed.

## Verification before deploying
```bash
git pull --ff-only
cp -R overlay/web/. apps/web/
cd apps/web
php artisan migrate --force
php artisan optimize:clear
php artisan route:list
php artisan test
```

No live environment tests were executed as part of this repository edit.
