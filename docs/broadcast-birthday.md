# Broadcast dan ulang tahun otomatis

## Fitur
- Broadcast dibuat sebagai job individual per kontak yang memiliki `consent_at`.
- Setiap batch maksimal 1.000 kontak; form dapat menjadwalkan waktu pengiriman.
- Birthday template per gereja memakai `{name}`. Template kosong berarti nonaktif.
- Generator ulang tahun hanya mengantrekan kontak yang memiliki tanggal lahir dan persetujuan.
- `church:dispatch` memeriksa lisensi dan persetujuan ulang sebelum pengiriman, serta memberi jeda 1,5 detik antar pesan. Status `sent` berarti worker menerima request, bukan bukti pesan sampai.
- Pengiriman otomatis ke grup ditolak sampai ada verifikasi persetujuan grup.

## Staging setup
Apply overlay and migrations first. Then set secure `CHURCH_WORKER_URL` and `CHURCH_WORKER_TOKEN`. Run:

```bash
php artisan church:birthdays
php artisan church:generate-recurring
php artisan church:dispatch --limit=20
```

Only after testing, configure one cron instance:

```cron
* * * * * cd /absolute/path/apps/web && /usr/bin/php artisan church:birthdays >> /var/log/church-center-birthdays.log 2>&1
* * * * * cd /absolute/path/apps/web && /usr/bin/php artisan church:generate-recurring >> /var/log/church-center-recurring.log 2>&1
* * * * * cd /absolute/path/apps/web && /usr/bin/php artisan church:dispatch --limit=20 >> /var/log/church-center-dispatch.log 2>&1
```

IMPORTANT: a dispatch run of 20 jobs can take 30+ seconds, and concurrent cron instances could exceed pacing. Add a host-level `flock` or Laravel distributed locking before production. `unknown` jobs require manual investigation; do not retry blindly.

## Verification checklist
1. Contact with no consent is not queued.
2. Revoking consent blocks pending jobs.
3. Two generator runs produce one birthday job per recipient/day.
4. Two broadcast batches create distinct job IDs.
5. Church A cannot access Church B's broadcasts or contacts.
6. Inactive/expired license prevents sending.
7. Disconnected worker yields a failed or unknown job, never a fake delivery receipt.
8. QRIS, Google Contacts, automatic opt-out replies, restore and end-to-end tests remain separate unfinished work.

These workflows have not been executed against a live WhatsApp account.
