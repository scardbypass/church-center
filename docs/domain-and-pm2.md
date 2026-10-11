# Domain, Nginx, PHP-FPM, PM2

> The PM2 worker currently provides a health endpoint only. It does not yet connect to WhatsApp, receive messages, or send broadcasts.

## DNS domain

Create a DNS A record for your chosen domain/subdomain pointing to the VPS public IPv4. Example: `church.example.com -> YOUR_VPS_IP`. If using Cloudflare, start with DNS only until HTTPS works. Do not put your actual IP, API keys, or credentials into GitHub.

Check propagation:

```bash
dig +short church.example.com
```

If your VPS already hosts other websites on ports 80/443, **do not start another Nginx instance**. Add a new `server_name` virtual host to the existing Nginx. A domain name selects the site while the same Nginx shares ports 80 and 443.

## Website (persistent)

Install Laravel via the root README, configure Nginx `root /var/www/church-center/apps/web/public`, set `APP_URL=https://church.example.com` in `apps/web/.env`, and use PHP-FPM. Set `APP_ENV=production` and `APP_DEBUG=false`. Keep the APP_KEY unchanged on upgrades.

For HTTPS on a public DNS-resolved domain, use a valid certificate. Example on Debian 12:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d church.example.com
sudo nginx -t && sudo systemctl reload nginx
```

Certbot requires the domain to resolve to this server and HTTP validation to be reachable. If using a Cloudflare proxy or origin certificate, follow the applicable Cloudflare TLS setup instead.

Verify:

```bash
curl -fsS https://church.example.com/health
sudo systemctl status nginx php8.2-fpm
```

## Node worker under PM2

Use Node.js 20+ and install PM2:

```bash
sudo npm install -g pm2
cd /var/www/church-center
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup
```

**Run the additional sudo command printed by `pm2 startup` as the same non-root deployment user.** Do not run PM2 as multiple users unintentionally.

Equivalent manual command:

```bash
cd /var/www/church-center/apps/whatsapp-worker
pm2 start chiwa.js --name church-center-bot
pm2 save
```

Check worker:

```bash
pm2 status
pm2 logs church-center-bot --lines 60
curl -fsS http://127.0.0.1:31181/health
```

The worker binds only to `127.0.0.1`; it should **not** be publicly exposed by Nginx or firewall. A successful health check means the Node process is running, **not** that WhatsApp is connected.

Restart and persistence:

```bash
pm2 restart church-center-bot
pm2 save
```

## Laravel scheduler and queue (future)

When automation jobs are implemented, run `php artisan schedule:run` each minute via cron or Laravel's supported scheduler service and a long-running `php artisan queue:work` via systemd/Supervisor. These are **not configured yet**. Do not enable cron for unfinished features.

## Troubleshooting

- 502 Bad Gateway: verify PHP-FPM socket version and service.
- 404: Nginx document root must end with `apps/web/public`, not repository root.
- 500: inspect `apps/web/storage/logs/laravel.log`; check PHP extensions, APP_KEY and permissions.
- CSS missing: `apps/web/public/church-center.css` must exist; rerun overlay copy.
- PM2 process errored: check Node version, `pm2 logs`, and whether port 31181 is already used.
- TLS failure: verify DNS, firewall, proxy mode, and certificate.
- Website works but bot doesn't: the current worker is a **health-only foundation** and cannot send WhatsApp messages yet.

## Updates

```bash
cd /var/www/church-center
git pull --ff-only
cp -R overlay/web/. apps/web/
cd apps/web && php artisan migrate --force && npm install && npm run build
cd ../..
pm2 restart church-center-bot
pm2 save
```

Back up configuration and database first. Do not regenerate the Laravel APP_KEY.
