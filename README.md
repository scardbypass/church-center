# Church Center ✦

**Church Center** adalah proyek SaaS multi-gereja untuk tim Fulltime dan Admin Pusat, dengan rencana otomasi WhatsApp, renungan, jadwal ibadah, pengingat, lisensi, dan pembayaran.

> **Status: starter pengembangan (belum siap produksi).** Halaman publik, skema awal multi-gereja, kode Google OAuth, dashboard Fulltime dasar, dan health endpoint worker PM2 sudah tersedia di GitHub. **Belum diuji end-to-end di VPS.** Bot WhatsApp belum terhubung; pembayaran dan otomatisasi belum diimplementasikan. Jangan gunakan untuk transaksi atau data jemaat nyata.

## Sudah ada sekarang
- Halaman landing responsif bergaya iOS-inspired Liquid Glass, CSS dipisah dari Blade.
- Halaman katalog roadmap fitur dan lisensi (harga belum aktif).
- Endpoint pemeriksaan `GET /health` (JSON).
- Migration fondasi `churches`, `church_memberships`, `church_settings`, `church_contacts`, `church_whatsapp_connections`.
- Node worker `apps/whatsapp-worker/chiwa.js` yang bisa dijalankan PM2, saat ini **health-only**, belum terhubung WhatsApp.
- Panduan domain, TLS, PM2: [docs/domain-and-pm2.md](docs/domain-and-pm2.md).
- Panduan Google OAuth: [docs/google-login.md](docs/google-login.md).
- Skrip `install.sh` untuk membuat Laravel 12 baru dan menerapkan overlay.
- Dokumentasi instalasi Debian 12 + Nginx + PHP-FPM.

## Roadmap fitur Church Center

| Modul | Rencana | Status |
|---|---|---|
| Admin Pusat | Kelola gereja, Fulltime, harga, lisensi, transaksi, monitoring, tiket, maintenance, kontrol global | Dashboard ringkasan dan tutup tiket tersedia dalam kode; fitur lainnya belum |
| Fulltime | Dashboard gereja, tim, izin, konfigurasi semua layanan | Dashboard dasar dan buat gereja ditulis; belum diuji, modul lainnya belum |
| Akun | Google OAuth, undangan tim, pembatasan per `church_id` | Login Google dasar dibuat, belum diuji; undangan belum |
| Kontak | Input manual, CSV/Excel, Google Contacts, consent, deduplikasi | Belum |
| Bot WhatsApp | QR/pairing per gereja, koneksi, `menu`/`start`, balasan otomatis, FAQ | Worker health-only; integrasi WA belum |
| Auto IH | Pagi/siang/malam, target grup, jadwal, preview, ON/OFF masing-masing | Form draft jadwal tersedia; **pengiriman belum** |
| Broadcast | Langsung, terjadwal, berulang, media, antrean, log | Form draft jadwal tersedia; **pengiriman belum** |
| Pengingat | Ibadah mingguan, acara khusus, H-7/H-3/H-1, ulang tahun | Belum |
| Backup | Backup pengaturan manual/terjadwal dan restore selektif | Belum |
| Lisensi | Satu tier fitur, durasi 1/3/6/12 bulan, harga dari Admin | Struktur database ada; aktivasi/renewal belum |
| Pembayaran | Transfer manual dengan bukti dan ACC Admin, QRIS GoPay Merchant setelah verifikasi SDK | Struktur database ada; proses pembayaran belum |
| Bantuan | Tiket Fulltime ke Admin dengan lampiran dan status | Buat, lihat, tutup tiket dasar tersedia dalam kode; balasan/lampiran belum |
| Operasional | Kontrol global, maintenance website/bot, emergency stop, audit | Belum |

**Rencana role login hanya Admin Pusat dan Fulltime.** Jemaat tidak perlu dashboard atau akun. Satu lisensi berlaku per gereja, bukan per Fulltime. Semua data operasional harus dibatasi oleh `church_id`. Switch global OFF harus mengalahkan switch lokal ON.

## Arsitektur

- `overlay/web/` — file Laravel khusus Church Center (routes, Blade, CSS).
- `apps/web/` — Laravel 12 hasil `install.sh`; sengaja tidak disimpan sebagai ribuan file vendor/framework.
- `apps/whatsapp-worker/` — worker Node.js health-only, dapat dikelola PM2; **belum** mengirim/menerima pesan WhatsApp.
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

Jangan jalankan ulang `install.sh` pada produksi untuk update rutin. Versi terbaru menjaga APP_KEY yang sudah terisi, tetapi tetap gunakan prosedur update di atas. Gunakan prosedur update di atas agar APP_KEY tetap. Backup database dan `.env` sebelum migrasi besar. Jika konfigurasi PHP-FPM menggunakan opcache agresif, reload service diperlukan.

## Keamanan & batasan

- **Jangan commit:** `.env`, token API, OAuth secret, password, OTP, bukti transfer, kontak jemaat, database, WhatsApp auth state.
- Repository Public berisi kode, **bukan data pengguna**.
- Koneksi WhatsApp melalui library tidak resmi dapat berisiko pemblokiran; evaluasi WhatsApp Business Platform resmi untuk produksi.
- QRIS GoPay Merchant belum diintegrasikan; flow OTP, webhook, dan verifikasi transaksi harus diuji terhadap SDK/API yang benar.
- Login Google memakai sesi OAuth dan membutuhkan email Google terverifikasi. Form pembuatan gereja memakai CSRF dan rate limit, tetapi **belum ada tes isolasi tenant yang lengkap**, audit log, dan otorisasi per modul.

## Alur yang akan dibangun

Admin Pusat mengatur paket dan layanan global → Fulltime login dengan Google → membuat/bergabung gereja → mengaktifkan lisensi → menyambungkan WA → mengimpor kontak berizin → mengaktifkan IH/pengingat/FAQ/broadcast sesuai kebutuhan. Saat lisensi kedaluwarsa, otomatisasi berhenti tetapi data tetap disimpan dan tiket/renewal tetap dapat diakses.

## Pengembangan berikutnya

1. Database multi-tenant, autentikasi Google, role Admin/Fulltime, dan tes isolasi.
2. Dashboard Fulltime dan Admin Pusat yang fungsional.
3. Worker WhatsApp, sesi per gereja, menu dan Auto Reply/FAQ.
4. Scheduler IH, ulang tahun, pengingat acara, dan broadcast.
5. Lisensi, pembayaran terverifikasi, tiket, backup, maintenance, dan audit.

Kontribusi: gunakan modul kecil, service teruji, dan komponen UI reusable. Jangan menambahkan fungsi palsu atau tombol yang tidak melakukan apa-apa.

---

## Login Google: dari Google Cloud sampai berhasil masuk

> Tutorial ini untuk kode OAuth awal yang sudah ada. **Belum ada login password, login Admin Pusat, ataupun import Google Contacts.**

1. Buka [Google Cloud Console](https://console.cloud.google.com/), buat atau pilih project Church Center.
2. Di **Google Auth Platform**, atur **Branding** (nama aplikasi, email dukungan), **Audience** (External jika dipakai beberapa gereja), dan email kontak. Selama mode **Testing**, tambahkan akun Gmail penguji ke daftar **Test users**.
3. Buka **Clients → Create client → Web application**.
4. Pada **Authorized JavaScript origins** tambahkan `https://DOMAIN_ANDA` bila diperlukan; untuk redirect server-side, bagian yang wajib adalah **Authorized redirect URIs**:
   ```text
   https://DOMAIN_ANDA/auth/google/callback
   ```
   Alamat harus **persis sama**, termasuk protokol HTTPS, domain, dan path; jangan gunakan garis miring tambahan di akhir.
5. Salin **Client ID** dan **Client secret**. Di VPS, edit `/var/www/church-center/apps/web/.env`:

   ```dotenv
   APP_NAME="Church Center"
   APP_URL=https://DOMAIN_ANDA
   APP_ENV=production
   APP_DEBUG=false
   GOOGLE_CLIENT_ID=ISI_CLIENT_ID_DARI_GOOGLE
   GOOGLE_CLIENT_SECRET=ISI_CLIENT_SECRET_DARI_GOOGLE
   GOOGLE_REDIRECT_URI=https://DOMAIN_ANDA/auth/google/callback
   SESSION_SECURE_COOKIE=true
   ```

6. Pastikan domain sudah diarahkan ke VPS dan HTTPS sudah aktif. Jalankan:

   ```bash
   cd /var/www/church-center
   git pull --ff-only
   cp -R overlay/web/. apps/web/
   cd apps/web
   composer require 'laravel/socialite:^5.0' --no-interaction
   php artisan migrate --force
   php artisan optimize:clear
   php artisan route:list --path=auth
   ```

7. Buka `https://DOMAIN_ANDA/login`, klik **Masuk dengan Google**, selesaikan izin akun, lalu periksa `/dashboard`. Buat gereja, refresh halaman, dan pastikan gereja tetap muncul.

**Jika gagal:** `redirect_uri_mismatch` berarti URI di Google tidak identik dengan `.env`; `access_denied` sering berarti akun belum ditambahkan ke Test users; error 503 berarti Client ID/secret belum dikonfigurasi; jika redirect ke HTTP, periksa HTTPS, proxy, `APP_URL`, dan konfigurasi trusted proxies. Jangan pernah mengirim Client secret atau isi `.env` ke chat publik.

Dokumentasi terpisah: [Panduan Login Google](docs/google-login.md).

## Domain dan PM2

Panduan lengkap DNS, Nginx, HTTPS, serta perintah `pm2 start chiwa.js --name church-center-bot`: [Domain & PM2](docs/domain-and-pm2.md).

```bash
cd /var/www/church-center
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup
pm2 status
curl -fsS http://127.0.0.1:31181/health
```

**Status `ok` dari worker hanya menunjukkan proses Node aktif, bukan bot WhatsApp sudah tersambung.** Website Laravel tetap berjalan dengan Nginx + PHP-FPM.

## Audit bug dan status fitur (11 Oktober 2026)

| Pemeriksaan | Hasil |
|---|---|
| Installer menimpa APP_KEY ketika diulang | **Diperbaiki di kode:** hanya generate saat belum ada key |
| Installer mengubah koneksi DB menjadi SQLite | **Diperbaiki di kode:** tidak menimpa koneksi DB yang sudah disetel |
| OAuth menerima email tanpa verifikasi eksplisit | **Diperketat di kode:** wajib email Google terverifikasi |
| Dashboard membaca data gereja lain | Query dashboard dibatasi ke membership user; **belum ada tes integrasi lintas akun** |
| UI landing, fitur, lisensi, login, dashboard | File dan routing tersedia; **belum diuji lewat browser/VPS** |
| Google login & sesi | Implementasi dasar ada; **perlu pengujian Google OAuth nyata** |
| WhatsApp QR/pairing, koneksi per gereja, menu bot, Auto Reply, FAQ | **Belum ada** |
| Auto IH, broadcast, pengingat, ulang tahun | **Belum ada** |
| Google Contacts, impor CSV, persetujuan penerima | **Belum ada** |
| Admin Pusat, lisensi, pembayaran, tiket, backup, laporan, maintenance | **Belum ada** |
| PM2 worker health endpoint | Kode tersedia; **belum diuji berjalan di VPS** |

**Prioritas sebelum produksi:** pengujian migrasi pada MariaDB dan SQLite, autentikasi OAuth nyata, otorisasi per gereja untuk semua endpoint, pengujian dua akun berbeda, job queue idempotent, consent dan rate limit broadcast, validasi webhook pembayaran, logging aman, backup, dan uji pemulihan service. Tidak ada klaim “bebas bug” sebelum pengujian tersebut selesai.

## Modul Admin Pusat dan Tiket Bantuan (kode awal)

Setelah update overlay dan `php artisan migrate --force`, akun Fulltime dapat membuka tiket dari dashboard gereja. Admin Pusat dapat melihat jumlah gereja, pengguna, tiket terbuka, permintaan pembayaran, dan menutup tiket melalui `/admin`. Semua endpoint memerlukan login, tiket gereja diperiksa keanggotaan `church_id`, dan halaman Admin Pusat hanya mengizinkan `platform_role=admin`.

**Penting:** login Google baru tidak otomatis menjadi admin. Untuk menetapkan admin pertama, lakukan hanya setelah memverifikasi identitas pemilik dan menggunakan akses shell VPS tepercaya:

```bash
cd church-center/apps/web
php artisan tinker
```

Lalu di Tinker, ganti email dengan email milik sendiri:

```php
$user = App\Models\User::where('email', 'EMAIL_ADMIN_ANDA')->firstOrFail();
$user->platform_role = 'admin';
$user->save();
```

Jangan berikan role Admin Pusat melalui formulir pendaftaran publik. Audit log, manajemen role, balasan tiket, lampiran, transaksi, serta integrasi bot dan pembayaran masih harus dibuat. Status tabel di atas menggambarkan **kode yang ditulis**, bukan hasil pengujian VPS.

## Otomatisasi: draft aman, belum terkirim

Menu **Draft Otomatisasi** dari dashboard gereja dapat menyimpan jadwal IH pagi/siang/malam, broadcast, dan pengingat ke database terisolasi per gereja. Setiap draft memakai ID idempotensi unik. Status awal selalu `draft`; **tidak ada worker pengiriman** dan tidak ada pesan yang terkirim. Input waktu saat ini memerlukan format `YYYY-MM-DD HH:MM:SS` mengikuti timezone aplikasi; untuk produksi, pengaturan timezone gereja dan konversi UTC harus diselesaikan.

**Belum boleh digunakan untuk pemberitahuan sungguhan.** Modul berikutnya harus menambahkan penghubung WhatsApp yang terautentikasi, penyimpanan sesi terenkripsi, koneksi dan reconnect, validasi lisensi dan consent, queue atomik, pengiriman dengan batas laju, deduplikasi, retry, audit, dan tes integrasi. Tidak boleh mengubah semua draft menjadi aktif sebelum pengujian dan persetujuan pengirim.
