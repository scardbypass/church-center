# Church Center ✦

**Church Center** adalah proyek SaaS multi-gereja untuk tim Fulltime dan Admin Pusat, dengan rencana otomasi WhatsApp, renungan, jadwal ibadah, pengingat, lisensi, dan pembayaran.

> **Status implementasi: Starter / fase 1 (schema tenant + worker health PM2).** Halaman publik Beranda, Fitur, Lisensi, dan endpoint `/health` sudah dibuat. **Login, dashboard, koneksi WhatsApp, database multi-tenant, pembayaran, serta otomatisasi belum dibuat.** Jangan gunakan starter ini untuk transaksi nyata atau data jemaat produksi.

## Sudah ada sekarang
- Halaman landing responsif bergaya iOS-inspired Liquid Glass, CSS dipisah dari Blade.
- Halaman katalog roadmap fitur dan lisensi (harga belum aktif).
- Endpoint pemeriksaan `GET /health` (JSON).
- Migration fondasi `churches`, `church_memberships`, `church_settings`, `church_contacts`, `church_whatsapp_connections`.
- Node worker `apps/whatsapp-worker/chiwa.js` yang bisa dijalankan PM2, saat ini **health-only**, belum terhubung WhatsApp.
- Panduan domain, TLS, PM2: [docs/domain-and-pm2.md](docs/domain-and-pm2.md).
- Skrip `install.sh` untuk membuat Laravel 12 baru dan menerapkan overlay.
- Dokumentasi instalasi Debian 12 + Nginx + PHP-FPM.

## Roadmap fitur Church Center

| Modul | Rencana | Status |
|---|---|---|
| Admin Pusat | Kelola gereja, Fulltime, harga, lisensi, transaksi, monitoring, tiket, maintenance, kontrol global | Belum |
| Fulltime | Dashboard gereja, tim, izin, konfigurasi semua layanan | Belum |
| Akun | Google OAuth, undangan tim, pembatasan per `church_id` | Schema awal; login belum |
| Kontak | Input manual, CSV/Excel, Google Contacts, consent, deduplikasi | Belum |
| Bot WhatsApp | QR/pairing per gereja, koneksi, `menu`/`start`, balasan otomatis, FAQ | Worker health-only; integrasi WA belum |
| Auto IH | Pagi/siang/malam, target grup, jadwal, preview, ON/OFF masing-masing | Belum |
| Broadcast | Langsung, terjadwal, berulang, media, antrean, log | Belum |
| Pengingat | Ibadah mingguan, acara khusus, H-7/H-3/H-1, ulang tahun | Belum |
| Backup | Backup pengaturan manual/terjadwal dan restore selektif | Belum |
| Lisensi | Satu tier fitur, durasi 1/3/6/12 bulan, harga dari Admin | Belum |
| Pembayaran | Transfer manual dengan bukti dan ACC Admin, QRIS GoPay Merchant setelah verifikasi SDK | Belum |
| Bantuan | Tiket Fulltime ke Admin dengan lampiran dan status | Belum |
| Operasional | Kontrol global, maintenance website/bot, emergency stop, audit | Belum |

**Role login hanya Admin Pusat dan Fulltime.** Jemaat tidak perlu dashboard atau akun. Satu lisensi berlaku per gereja, bukan per Fulltime. Semua data operasional harus dibatasi oleh `church_id`. Switch global OFF harus mengalahkan switch lokal ON.

## Arsitektur

- `overlay/web/` — file Laravel khusus Church Center (routes, Blade, CSS).
- `apps/web/` — Laravel 12 hasil `install.sh`; sengaja tidak disimpan sebagai ribuan file vendor/framework.
- `apps/whatsapp-worker/` — **direncanakan**, belum ada worker.
- `docs/` — panduan instalasi dan keamanan.

**Mengapa overlay?** Repository awal tetap kecil dan mudah dibaca. Skrip mengambil Laravel resmi melalui Composer lalu menyalin file custom; `vendor/`, `node_modules/`, `.env`, sesi WA, dan database tidak boleh masuk Git.

## Instalasi VPS Debian 12 (dari GitHub sampai ON)

Contoh domain: `church.example.com`. Ganti dengan domain milik sendiri.

### 1. Persiapan sistem

Gunakan Debian 12, minimal disarankan 2 vCPU / 2 GB RAM untuk starter web saja. Untuk bot multi-sesi dan antrean produksi perlu sizing terpisah.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git unzip curl ca-certificates \
  php-cli php-fpm php-mbstring php-xml php-curl php-zip \
  php-sqlite3 php-bcmath composer nodejs npm
php -v
composer --version
node -v
```

**Penting:** Laravel 12 memerlukan PHP >= 8.2. Paket Node.js dari Debian mungkin terlalu lama untuk tooling frontend; gunakan Node.js 20+ LTS dari sumber tepercaya jika `npm run build` gagal karena versi. Installer tidak mengubah konfigurasi Nginx atau firewall otomatis.

### 2. Clone & instalasi

```bash
sudo mkdir -p /var/www/church-center
sudo chown -R "$USER":"$USER" /var/www/church-center
git clone https://github.com/scardbypass/church-center.git /var/www/church-center
cd /var/www/church-center
bash install.sh
```

Jika `composer create-project` gagal, periksa ekstensi PHP dan koneksi internet. Skrip instalasi harus dijalankan ulang hanya setelah memeriksa error. Untuk pengembangan lokal, jalankan:

```bash
cd /var/www/church-center/apps/web
php artisan serve --host=127.0.0.1 --port=8088
# tes dari VPS lain terminal:
curl http://127.0.0.1:8088/health
```

Respon yang diharapkan: JSON berisi `"status":"ok"`. Hentikan `artisan serve` setelah tes; **jangan gunakan sebagai service produksi**.

### 3. Nginx + PHP-FPM agar tetap ON

Pastikan socket sesuai hasil `ls /run/php/php*-fpm.sock`. Contoh Debian 12: `php8.2-fpm.sock`.

```bash
sudo chown -R www-data:www-data /var/www/church-center/apps/web/storage \
  /var/www/church-center/apps/web/bootstrap/cache \
  /var/www/church-center/apps/web/database
sudo chmod -R ug+rwX /var/www/church-center/apps/web/storage \
  /var/www/church-center/apps/web/bootstrap/cache \
  /var/www/church-center/apps/web/database
sudo nano /etc/nginx/sites-available/church-center
```

Isi file Nginx:

```nginx
server {
    listen 80;
    server_name church.example.com;
    root /var/www/church-center/apps/web/public;
    index index.php;

    add_header X-Content-Type-Options nosniff always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ /\. { deny all; }
}
```

Aktifkan dan uji:

```bash
sudo ln -s /etc/nginx/sites-available/church-center /etc/nginx/sites-enabled/church-center
sudo nginx -t
sudo systemctl enable --now nginx php8.2-fpm
sudo systemctl reload nginx
curl -I -H 'Host: church.example.com' http://127.0.0.1/
```

Untuk HTTPS, arahkan DNS ke IP VPS lalu pasang sertifikat TLS valid (misalnya Certbot/Let's Encrypt atau Cloudflare Origin Certificate bila melalui Cloudflare). Jangan mempublikasikan login atau kontak jemaat lewat HTTP.

### 4. Verifikasi

```bash
curl -fsS https://church.example.com/health
curl -I https://church.example.com/
curl -I https://church.example.com/features
curl -I https://church.example.com/pricing
```

Halaman akan terlihat jika DNS, Nginx, PHP-FPM, dan TLS sudah benar. **PM2 dapat menjalankan fondasi worker Node** (belum koneksi WhatsApp). Lihat [panduan PM2](docs/domain-and-pm2.md).

### 5. Update dari GitHub

```bash
cd /var/www/church-center
git pull --ff-only
cp -R overlay/web/. apps/web/
cd apps/web
php artisan migrate --force
npm install && npm run build
php artisan optimize:clear
sudo systemctl reload php8.2-fpm
```

Jangan jalankan ulang `install.sh` pada produksi setiap update: skrip awal menghasilkan APP_KEY baru. Gunakan prosedur update di atas agar APP_KEY tetap. Backup database dan `.env` sebelum migrasi besar. Jika konfigurasi PHP-FPM menggunakan opcache agresif, reload service diperlukan.

## Keamanan & batasan

- **Jangan commit:** `.env`, token API, OAuth secret, password, OTP, bukti transfer, kontak jemaat, database, WhatsApp auth state.
- Repository Public berisi kode, **bukan data pengguna**.
- Koneksi WhatsApp melalui library tidak resmi dapat berisiko pemblokiran; evaluasi WhatsApp Business Platform resmi untuk produksi.
- QRIS GoPay Merchant belum diintegrasikan; flow OTP, webhook, dan verifikasi transaksi harus diuji terhadap SDK/API yang benar.
- Setelah fitur login tersedia, wajib ada CSRF protection, otorisasi role, rate limiting, audit log, dan tes isolasi tenant.

## Alur yang akan dibangun

Admin Pusat mengatur paket dan layanan global → Fulltime login dengan Google → membuat/bergabung gereja → mengaktifkan lisensi → menyambungkan WA → mengimpor kontak berizin → mengaktifkan IH/pengingat/FAQ/broadcast sesuai kebutuhan. Saat lisensi kedaluwarsa, otomatisasi berhenti tetapi data tetap disimpan dan tiket/renewal tetap dapat diakses.

## Pengembangan berikutnya

1. Database multi-tenant, autentikasi Google, role Admin/Fulltime, dan tes isolasi.
2. Dashboard Fulltime dan Admin Pusat yang fungsional.
3. Worker WhatsApp, sesi per gereja, menu dan Auto Reply/FAQ.
4. Scheduler IH, ulang tahun, pengingat acara, dan broadcast.
5. Lisensi, pembayaran terverifikasi, tiket, backup, maintenance, dan audit.

Kontribusi: gunakan modul kecil, service teruji, dan komponen UI reusable. Jangan menambahkan fungsi palsu atau tombol yang tidak melakukan apa-apa.
