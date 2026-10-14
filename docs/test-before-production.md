# Church Center — test checklist (14 October 2026)

## Deploy code to staging (not production first)
```bash
git pull --ff-only
cp -R overlay/web/. apps/web/
cd apps/web
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan route:list
php artisan church:sync-replies
```

Run the Laravel test suite in a **separate test environment** with a disposable database:
```bash
cd apps/web
php artisan test
```

Worker JavaScript syntax check (Node 18+):
```bash
node --check apps/whatsapp-worker/chiwa.js
```

## Test cases
- Login as Church A owner; edit WhatsApp FAQ (menu/start, jadwal, alamat). Confirm reply only in Church A's private WhatsApp.
- Restart worker and run `php artisan church:sync-replies`; verify FAQ is restored.
- Church B cannot edit Church A settings or see Church A contacts.
- Manual disconnect remains disconnected (no automatic reconnection).
- Create recurring IH only for opted-in contacts; revoke consent, then attempt to re-enable rule.
- Create birthday greeting for consenting contact; run birthday command twice; verify only one job per contact/day.
- Queue broadcast with one consenting and one non-consenting contact; verify only consenting contact is queued.
- Revoke consent before dispatch; verify job is blocked and no send is performed.
- Delete a contact with queued jobs; verify pending jobs blocked and recurring rule disabled.
- Verify dashboard, reports, CSV export, support tickets, manual license approval.
- Restore DB backup into isolated staging database using the staging-only helper; never overwrite production as first test.

## Not yet production-ready
- Official GoPay Merchant QRIS API and signed webhook integration
- Google People API Contacts OAuth and import
- XLSX import
- WhatsApp inbound opt-out and delivery receipts
- Automated encrypted offsite backups and end-to-end disaster recovery
- Comprehensive multi-tenant penetration and load testing

## Deployment caution
The worker keeps reply settings in memory. Re-run `church:sync-replies` after each worker restart or deployment. Sending jobs have at-most-once retry semantics on unknown outcomes: inspect `unknown` manually, do not blindly retry.

No VPS test, real WhatsApp delivery test or provider sandbox test was executed during these repository updates.
